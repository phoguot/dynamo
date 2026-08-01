<?php

declare(strict_types=1);

namespace Reporting\Model\RevenueMonthly;

use Application\Model\AppMapper;
use Application\Paginator\Paginator;
use InvalidArgumentException;
use Reporting\Model\ReportingConst;

class RevenueMonthlyMapper extends AppMapper
{
    public const string TABLE_NAME = 'rpt_revenue_monthly';

    public function searchRevenue(RevenueMonthlyModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['r' => RevenueMonthlyMapper::TABLE_NAME]);
        if ($criteria->getPeriodYear() !== null) {
            $select->where(['r.periodYear = ?' => $criteria->getPeriodYear()]);
        }
        if ($criteria->getPeriodMonth() !== null) {
            $select->where(['r.periodMonth = ?' => $criteria->getPeriodMonth()]);
        }
        if ($criteria->getCustomerId() !== null) {
            $select->where(['r.customerId = ?' => $criteria->getCustomerId()]);
        }

        $this->applySort($select, ReportingConst::REVENUE_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, ReportingConst::REVENUE_SORT_DEFAULT);
        $select->order('r.periodMonth DESC');

        return $this->preparePaginator($select, $paging, new RevenueMonthlyModel());
    }

    public function getLatestCompanyRow(): ?RevenueMonthlyModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['r' => RevenueMonthlyMapper::TABLE_NAME]);
        $select->where->isNull('r.customerId');
        $select->order(['r.periodYear DESC', 'r.periodMonth DESC', 'r.id DESC']);
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new RevenueMonthlyModel())->exchangeArray((array)$row) : null;
    }

    public function saveRevenueMonth(RevenueMonthlyModel $item): ?RevenueMonthlyModel
    {
        $year = $item->getPeriodYear();
        $month = $item->getPeriodMonth();
        if ($year === null || $month === null) {
            throw new InvalidArgumentException('Revenue period is required.');
        }

        if ($this->isEmptyRevenueMonth($item)) {
            $this->deleteRevenueMonth($year, $month, $item->getCustomerId());
            return null;
        }

        $dbSql = $this->getDbSql();
        $existing = $this->getRevenueMonth($year, $month, $item->getCustomerId());
        $data = [
            'periodYear'        => $year,
            'periodMonth'       => $month,
            'customerId'        => $item->getCustomerId(),
            'invoicedAmount'    => $item->getInvoicedAmount(),
            'collectedAmount'   => $item->getCollectedAmount(),
            'outstandingAmount' => $item->getOutstandingAmount(),
            'overdueAmount'     => $item->getOverdueAmount(),
            'orderCount'        => $item->getOrderCount(),
            'computedAt'        => $item->getComputedAt(),
        ];

        if ($existing === null) {
            $insert = $dbSql->insert(RevenueMonthlyMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }

        $update = $dbSql->update(RevenueMonthlyMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $existing->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item->setId($existing->getId());
    }

    public function getRevenueMonth(int $year, int $month, ?int $customerId): ?RevenueMonthlyModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['r' => RevenueMonthlyMapper::TABLE_NAME]);
        $select->where(['r.periodYear = ?' => $year, 'r.periodMonth = ?' => $month]);
        if ($customerId === null) {
            $select->where->isNull('r.customerId');
        } else {
            $select->where(['r.customerId = ?' => $customerId]);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new RevenueMonthlyModel())->exchangeArray((array)$row) : null;
    }

    public function deleteRevenueMonth(int $year, int $month, ?int $customerId): bool
    {
        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(RevenueMonthlyMapper::TABLE_NAME);
        $delete->where(['periodYear = ?' => $year, 'periodMonth = ?' => $month]);
        if ($customerId === null) {
            $delete->where->isNull('customerId');
        } else {
            $delete->where(['customerId = ?' => $customerId]);
        }
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function isEmptyRevenueMonth(RevenueMonthlyModel $item): bool
    {
        return $item->getInvoicedAmount() === 0
            && $item->getCollectedAmount() === 0
            && $item->getOutstandingAmount() === 0
            && $item->getOverdueAmount() === 0
            && $item->getOrderCount() === 0;
    }
}
