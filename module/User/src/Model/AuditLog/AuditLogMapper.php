<?php

declare(strict_types=1);

namespace User\Model\AuditLog;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;

/**
 * Ghi và đọc bảng usr_audit_logs.
 *
 * CỐ Ý không có hàm update/delete: nhật ký là append-only (.claude/rules/security.md).
 * Ai cần "sửa" một dòng sai thì ghi thêm một dòng đính chính, không sửa dòng cũ.
 */
class AuditLogMapper extends AppMapper
{
    public const string TABLE_NAME = 'usr_audit_logs';

    public function insertLog(AuditLogModel $item): AuditLogModel
    {
        $dbSql = $this->getDbSql();

        $insert = $dbSql->insert(AuditLogMapper::TABLE_NAME);
        $insert->values([
            'userId'      => $item->getUserId(),
            'action'      => $item->getAction(),
            'objectType'  => $item->getObjectType(),
            'objectId'    => $item->getObjectId(),
            'beforeJson'  => $item->getBeforeJson(),
            'afterJson'   => $item->getAfterJson(),
            'ip'          => $item->getIp(),
            'userAgent'   => $item->getUserAgent(),
            'createdAt'   => DateModel::getUtcNow(),
        ]);

        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }

    /**
     * Nhật ký của một đối tượng, mới nhất trước — dùng cho tab "Lịch sử" ở trang chi tiết.
     *
     * @param array{page?:int, pageSize?:int} $paging
     */
    public function searchLogsOfObject(string $objectType, int $objectId, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['a' => AuditLogMapper::TABLE_NAME]);
        $select->where(['a.objectType = ?' => $objectType]);
        $select->where(['a.objectId = ?' => $objectId]);
        $select->order('a.id DESC');

        return $this->preparePaginator($select, $paging, new AuditLogModel());
    }

    /**
     * Màn hình đọc nhật ký toàn hệ thống, có lọc và phân trang. Chỉ ĐỌC — bảng vẫn append-only.
     *
     * `objectType` lọc theo tiền tố để còn dùng được index; `createdFrom`/`createdTo` là ngày
     * (Y-m-d) hiểu theo UTC đúng như cột `createdAt` được lưu.
     *
     * @param array{page?:int, pageSize?:int, sort?:string, dir?:string} $paging
     */
    public function searchLogs(
        AuditLogModel $criteria,
        array $paging = [],
        ?string $createdFrom = null,
        ?string $createdTo = null
    ): Paginator {
        $select = $this->getDbSql()->select(['a' => AuditLogMapper::TABLE_NAME]);

        if ($criteria->getAction() !== null) {
            $select->where(['a.action = ?' => $criteria->getAction()]);
        }
        if ($criteria->getUserId() !== null) {
            $select->where(['a.userId = ?' => $criteria->getUserId()]);
        }
        if ($criteria->getObjectId() !== null) {
            $select->where(['a.objectId = ?' => $criteria->getObjectId()]);
        }

        $objectType = trim((string)$criteria->getObjectType());
        if ($objectType !== '') {
            $select->where->like('a.objectType', $this->escapeLike($objectType) . '%');
        }

        if ($createdFrom !== null) {
            $select->where->greaterThanOrEqualTo('a.createdAt', $createdFrom . ' 00:00:00.000');
        }
        if ($createdTo !== null) {
            $select->where->lessThanOrEqualTo('a.createdAt', $createdTo . ' 23:59:59.999');
        }

        $this->applySort(
            $select,
            AuditLogModel::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            AuditLogModel::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new AuditLogModel());
    }

    /** Thoát ký tự đại diện của LIKE để người dùng gõ `%`/`_` không quét cả bảng. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
