<?php

declare(strict_types=1);

namespace Maintenance\Model\PartUsed;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class PartUsedMapper extends AppMapper
{
    public const string TABLE_NAME = 'mnt_parts_used';

    /** @return PartUsedModel[] */
    public function fetchByJob(int $jobId): array
    {
        if ($jobId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PartUsedMapper::TABLE_NAME]);
        $select->where(['p.jobId = ?' => $jobId]);
        $select->order(['p.id ASC']);

        $result = [];
        foreach ($dbSql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[] = (new PartUsedModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    public function getPartUsed(int $id): ?PartUsedModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PartUsedMapper::TABLE_NAME]);
        $select->where(['p.id = ?' => $id]);
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new PartUsedModel())->exchangeArray((array)$row) : null;
    }

    public function savePartUsed(PartUsedModel $item): PartUsedModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'jobId'      => $item->getJobId(),
            'partCode'   => $item->getPartCode(),
            'partName'   => $item->getPartName(),
            'quantity'   => $item->getQuantity(),
            'unit'       => $item->getUnit(),
            'unitPrice'  => $item->getUnitPrice(),
            'lineAmount' => $item->getLineAmount(),
            'supplier'   => $item->getSupplier(),
            'updatedAt'  => $now,
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(PartUsedMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(PartUsedMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function deletePartUsed(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(PartUsedMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();
    }

    public function clearByJob(int $jobId): bool
    {
        if ($jobId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(PartUsedMapper::TABLE_NAME);
        $delete->where(['jobId = ?' => $jobId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public function sumLineAmountByJob(int $jobId): int
    {
        if ($jobId <= 0) {
            return 0;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PartUsedMapper::TABLE_NAME]);
        $select->columns(['total' => new \Laminas\Db\Sql\Expression('COALESCE(SUM(lineAmount), 0)')]);
        $select->where(['p.jobId = ?' => $jobId]);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return (int)($row['total'] ?? 0);
    }
}
