<?php

declare(strict_types=1);

namespace User\Model\Permission;

use Application\Model\AppMapper;
use Application\Model\DateModel;

/**
 * Truy vấn bảng usr_user_permissions — ngoại lệ quyền theo từng người dùng.
 *
 * Bảng này bị đọc ở MỌI request đã đăng nhập (guard của BaseController), nên hai điều
 * quan trọng hơn bình thường:
 *   - luôn lấy trọn quyền của một người bằng MỘT câu truy vấn (fetchByUser), rồi cache
 *     trong PermissionService theo vòng đời request;
 *   - có index `(userId, resource)` để câu đó luôn dùng index.
 */
class PermissionMapper extends AppMapper
{
    public const string TABLE_NAME = 'usr_user_permissions';

    /**
     * Toàn bộ ngoại lệ quyền của một người dùng.
     *
     * @return PermissionModel[]
     */
    public function fetchByUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PermissionMapper::TABLE_NAME]);
        $select->where(['p.userId = ?' => $userId]);
        $select->order('p.resource ASC');
        // Trần an toàn: một người dùng không thể có nhiều hơn ngần này dòng ngoại lệ.
        $select->limit(PermissionMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (new PermissionModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    /**
     * Thay TOÀN BỘ ngoại lệ quyền của một người bằng danh sách mới, trong một transaction.
     *
     * Dùng "xóa hết rồi ghi lại" thay vì so sánh từng dòng: màn hình phân quyền luôn gửi
     * lên trạng thái đầy đủ, và cách này không để lại dòng mồ côi khi một resource bị gỡ
     * khỏi danh mục. Bảng nhỏ (vài chục dòng mỗi người) nên chi phí không đáng kể.
     *
     * @param PermissionModel[] $permissions
     */
    public function replaceForUser(int $userId, array $permissions, ?int $actorId = null): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->transactional(function () use ($userId, $permissions, $actorId): void {
            $dbSql = $this->getDbSql();

            $delete = $dbSql->delete(PermissionMapper::TABLE_NAME)->where(['userId = ?' => $userId]);
            $dbSql->prepareStatementForSqlObject($delete)->execute();

            $now = DateModel::getUtcNow();
            foreach ($permissions as $permission) {
                $insert = $dbSql->insert(PermissionMapper::TABLE_NAME);
                $insert->values([
                    'userId'     => $userId,
                    'resource'   => $permission->getResource(),
                    'privilege'  => $permission->getPrivilege(),
                    'effect'     => $permission->getEffect(),
                    'createdAt'  => $now,
                    'createdBy'  => $actorId,
                ]);
                $dbSql->prepareStatementForSqlObject($insert)->execute();
            }
        });
    }

    /**
     * Xóa ngoại lệ quyền của một người.
     *
     * Đây là dữ liệu DẪN XUẤT của quyết định phân quyền, không phải bản ghi nghiệp vụ —
     * xóa cứng ở đây đúng quy chuẩn hard delete qua service/mapper sở hữu (.claude/rules/database.md),
     * và vết tích còn nguyên trong `usr_audit_logs`.
     */
    public function deleteByUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(PermissionMapper::TABLE_NAME)->where(['userId = ?' => $userId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();
    }
}
