<?php

declare(strict_types=1);

namespace Reporting\Model\ReceivablesSnapshot;

use Application\Model\AppMapper;
use Application\Paginator\Paginator;
use Reporting\Model\ReportingConst;

class ReceivablesSnapshotMapper extends AppMapper
{
    public const string TABLE_NAME = 'rpt_receivables_snapshot';

    public function searchReceivables(ReceivablesSnapshotModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['s' => ReceivablesSnapshotMapper::TABLE_NAME]);
        if ($criteria->getSnapshotDate() !== null) {
            $select->where(['s.snapshotDate = ?' => $criteria->getSnapshotDate()]);
        }
        if ($criteria->getCustomerId() !== null) {
            $select->where(['s.customerId = ?' => $criteria->getCustomerId()]);
        }

        $this->applySort($select, ReportingConst::RECEIVABLES_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, ReportingConst::RECEIVABLES_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new ReceivablesSnapshotModel());
    }

    public function getLatestSnapshotDate(): ?string
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => ReceivablesSnapshotMapper::TABLE_NAME]);
        $select->columns(['snapshotDate']);
        $select->order('s.snapshotDate DESC');
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (string)$row['snapshotDate'] : null;
    }

    /** @return array{snapshotDate:?string, bucket0To30:int, bucket31To60:int, bucket61To90:int, bucketOver90:int, totalDebt:int, dsoDays:float, computedAt:?string} */
    public function summarizeLatestSnapshot(): array
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => ReceivablesSnapshotMapper::TABLE_NAME]);
        $select->order(['s.customerId ASC', 's.snapshotDate DESC', 's.id DESC']);
        $select->limit(self::MAX_RECORD_FETCH_ALL);
        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();

        $seenCustomers = [];
        $summary = ['snapshotDate' => null, 'bucket0To30' => 0, 'bucket31To60' => 0, 'bucket61To90' => 0, 'bucketOver90' => 0, 'totalDebt' => 0, 'dsoDays' => 0.0, 'computedAt' => null];
        $weightedDso = 0.0;

        foreach ($rows as $row) {
            $row = (array)$row;
            $customerId = (int)($row['customerId'] ?? 0);
            if ($customerId <= 0 || isset($seenCustomers[$customerId])) {
                continue;
            }
            $seenCustomers[$customerId] = true;

            $debt = (int)($row['totalDebt'] ?? 0);
            $summary['bucket0To30'] += (int)($row['bucket0To30'] ?? 0);
            $summary['bucket31To60'] += (int)($row['bucket31To60'] ?? 0);
            $summary['bucket61To90'] += (int)($row['bucket61To90'] ?? 0);
            $summary['bucketOver90'] += (int)($row['bucketOver90'] ?? 0);
            $summary['totalDebt'] += $debt;
            $weightedDso += (float)($row['dsoDays'] ?? 0) * $debt;

            $snapshotDate = isset($row['snapshotDate']) ? (string)$row['snapshotDate'] : null;
            if ($snapshotDate !== null && ($summary['snapshotDate'] === null || $snapshotDate > $summary['snapshotDate'])) {
                $summary['snapshotDate'] = $snapshotDate;
            }
            $computedAt = isset($row['computedAt']) ? (string)$row['computedAt'] : null;
            if ($computedAt !== null && ($summary['computedAt'] === null || $computedAt > $summary['computedAt'])) {
                $summary['computedAt'] = $computedAt;
            }
        }

        if ($seenCustomers === []) {
            return ['snapshotDate' => null, 'bucket0To30' => 0, 'bucket31To60' => 0, 'bucket61To90' => 0, 'bucketOver90' => 0, 'totalDebt' => 0, 'dsoDays' => 0.0, 'computedAt' => null];
        }

        $summary['dsoDays'] = $summary['totalDebt'] > 0 ? round($weightedDso / $summary['totalDebt'], 2) : 0.0;
        return $summary;
    }

    public function saveSnapshot(ReceivablesSnapshotModel $item): ReceivablesSnapshotModel
    {
        $dbSql = $this->getDbSql();
        $existing = $this->getSnapshot((string)$item->getSnapshotDate(), (int)$item->getCustomerId());
        $data = [
            'snapshotDate' => $item->getSnapshotDate(),
            'customerId' => $item->getCustomerId(),
            'bucket0To30' => $item->getBucket0To30(),
            'bucket31To60' => $item->getBucket31To60(),
            'bucket61To90' => $item->getBucket61To90(),
            'bucketOver90' => $item->getBucketOver90(),
            'totalDebt' => $item->getTotalDebt(),
            'dsoDays' => $item->getDsoDays(),
            'computedAt' => $item->getComputedAt(),
        ];

        if ($existing === null) {
            $insert = $dbSql->insert(ReceivablesSnapshotMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }

        $update = $dbSql->update(ReceivablesSnapshotMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $existing->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item->setId($existing->getId());
    }

    public function getSnapshot(string $snapshotDate, int $customerId): ?ReceivablesSnapshotModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => ReceivablesSnapshotMapper::TABLE_NAME]);
        $select->where(['s.snapshotDate = ?' => $snapshotDate, 's.customerId = ?' => $customerId]);
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new ReceivablesSnapshotModel())->exchangeArray((array)$row) : null;
    }
}
