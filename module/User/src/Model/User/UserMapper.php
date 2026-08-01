<?php

declare(strict_types=1);

namespace User\Model\User;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

/**
 * Truy vấn bảng usr_users. Chỉ M01 user được đọc/ghi bảng này.
 *
 * Module khác cần thông tin người dùng thì gọi `User\Service\UserService`, không JOIN sang
 * đây — xem .claude/rules/module-boundaries.md.
 */
class UserMapper extends AppMapper
{
    public const string TABLE_NAME = 'usr_users';

    /**
     * Danh sách người dùng có phân trang.
     *
     * @param array{page?:int, pageSize?:int, sort?:string, dir?:string} $paging
     */
    public function searchUsers(UserModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);

        $this->applySort(
            $select,
            UserConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            UserConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new UserModel());
    }

    private function buildSearchSelect(UserModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['u' => UserMapper::TABLE_NAME]);

        if ($criteria->getRole() !== null) {
            $select->where(['u.role = ?' => $criteria->getRole()]);
        }

        if ($criteria->getStatus() !== null) {
            $select->where(['u.status = ?' => $criteria->getStatus()]);
        }

        // Tìm theo tiền tố để còn dùng được index — .claude/rules/database.md mục Hiệu năng.
        $keyword = trim((string)$criteria->getKeyword());
        if ($keyword !== '') {
            $prefix = $this->escapeLike($keyword) . '%';
            $select->where->nest()
                ->like('u.username', $prefix)
                ->or->like('u.fullName', $prefix)
                ->or->like('u.email', $prefix)
                ->unnest();
        }

        return $select;
    }

    public function getUser(int $id): ?UserModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['u.id = ?' => $id]);
    }

    /** Dùng cho luồng đăng nhập và để chặn trùng tên đăng nhập. */
    public function getUserByUsername(string $username, ?int $exceptId = null): ?UserModel
    {
        $username = strtolower(trim($username));
        if ($username === '') {
            return null;
        }

        return $this->fetchOne(['u.username = ?' => $username], $exceptId);
    }

    /** Email là UNIQUE ở tầng DB — hàm này chỉ để báo lỗi đẹp trước khi ghi. */
    public function getUserByEmail(string $email, ?int $exceptId = null): ?UserModel
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->fetchOne(['u.email = ?' => $email], $exceptId);
    }

    /**
     * Đếm số tài khoản quản trị còn hoạt động.
     * Dùng để chặn việc khóa/hạ vai trò người quản trị cuối cùng.
     */
    public function countActiveAdmins(?int $exceptId = null): int
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['u' => UserMapper::TABLE_NAME]);
        $select->columns(['total' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $select->where(['u.role = ?' => UserConst::ROLE_ADMIN]);
        $select->where(['u.status = ?' => UserConst::STATUS_HOAT_DONG]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('u.id', $exceptId);
        }

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return (int)($row['total'] ?? 0);
    }

    /**
     * Thêm mới hoặc cập nhật. Trả về model kèm id sau khi lưu.
     *
     * KHÔNG ghi `passwordHash` ở đây — mật khẩu đi đường riêng qua updatePassword(),
     * để không bao giờ có chuyện lưu hồ sơ mà vô tình ghi đè hash bằng null.
     */
    public function saveUser(UserModel $item): UserModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'username'   => $item->getUsername(),
            'fullName'   => $item->getFullName(),
            'email'      => $item->getEmail(),
            'phone'      => $item->getPhone(),
            'role'       => $item->getRole(),
            'status'     => $item->getStatus(),
            'note'       => $item->getNote(),
            'updatedAt'  => $now,
            'updatedBy'  => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['passwordHash']      = $item->getPasswordHash();
            $data['failedLoginCount']  = 0;
            $data['createdAt']         = $now;
            $data['createdBy']         = $item->getCreatedBy();

            $insert = $dbSql->insert(UserMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(UserMapper::TABLE_NAME)->set($data)->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    /**
     * Cập nhật MỘT VÀI cột. Tên cột do CODE truyền vào, không bao giờ lấy từ client.
     *
     * @param array<string, mixed> $data cột DB => giá trị
     */
    public function updateAttrsUser(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(UserMapper::TABLE_NAME)->set($data)->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    /** Đổi mật khẩu; đồng thời gỡ khóa tạm vì đặt lại mật khẩu là một lần xác thực hợp lệ. */
    public function updatePassword(int $id, string $passwordHash, ?int $actorId = null): bool
    {
        return $this->updateAttrsUser($id, [
            'passwordHash'      => $passwordHash,
            'failedLoginCount'  => 0,
            'lockedUntil'       => null,
        ], $actorId);
    }

    /**
     * Tên hiển thị của nhiều người trong một truy vấn — dùng để gắn tên người thực hiện vào
     * danh sách nhật ký mà không JOIN sang bảng khác (repo không dùng JOIN).
     *
     * @param int[] $ids
     * @return array<int, string> id => fullName
     */
    public function displayNamesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['u' => UserMapper::TABLE_NAME]);
        $select->columns(['id', 'fullName']);
        $select->where->in('u.id', $ids);

        $result = $dbSql->prepareStatementForSqlObject($select)->execute();

        $map = [];
        foreach ($result as $row) {
            $map[(int)$row['id']] = (string)$row['fullName'];
        }

        return $map;
    }

    /** @param array<string, mixed> $where */
    private function fetchOne(array $where, ?int $exceptId = null): ?UserModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['u' => UserMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('u.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new UserModel())->exchangeArray((array)$row) : null;
    }

    /** Thoát ký tự đại diện của LIKE để người dùng gõ `%` không quét cả bảng. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
