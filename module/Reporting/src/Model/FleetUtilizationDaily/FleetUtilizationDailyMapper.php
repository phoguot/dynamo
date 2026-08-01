<?php

declare(strict_types=1);

namespace Reporting\Model\FleetUtilizationDaily;

use Application\Model\AppMapper;
use Application\Paginator\Paginator;
use Reporting\Model\ReportingConst;

class FleetUtilizationDailyMapper extends AppMapper
{
    public const string TABLE_NAME = 'rpt_fleet_utilization_daily';

    public function searchFleetUtilization(FleetUtilizationDailyModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['u' => FleetUtilizationDailyMapper::TABLE_NAME]);
        if ($criteria->getReportDate() !== null) {
            $select->where(['u.reportDate = ?' => $criteria->getReportDate()]);
        }
        if ($criteria->getWarehouseCode() !== null) {
            $select->where(['u.warehouseCode = ?' => $criteria->getWarehouseCode()]);
        }

        $this->applySort($select, ReportingConst::FLEET_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, ReportingConst::FLEET_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new FleetUtilizationDailyModel());
    }

    public function getLatestCompanyRow(): ?FleetUtilizationDailyModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['u' => FleetUtilizationDailyMapper::TABLE_NAME]);
        $select->where->isNull('u.warehouseCode');
        $select->order(['u.reportDate DESC', 'u.id DESC']);
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new FleetUtilizationDailyModel())->exchangeArray((array)$row) : null;
    }

    public function saveDaily(FleetUtilizationDailyModel $item): FleetUtilizationDailyModel
    {
        $reportDate = (string)$item->getReportDate();
        $warehouseCode = $item->getWarehouseCode();
        $existing = $this->getDaily($reportDate, $warehouseCode);
        $data = [
            'reportDate'       => $reportDate,
            'warehouseCode'    => $warehouseCode,
            'totalGenerators'  => $item->getTotalGenerators(),
            'activeGenerators' => $item->getActiveGenerators(),
            'rentedCount'      => $item->getRentedCount(),
            'availableCount'   => $item->getAvailableCount(),
            'heldCount'        => $item->getHeldCount(),
            'transitCount'     => $item->getTransitCount(),
            'maintenanceCount' => $item->getMaintenanceCount(),
            'repairCount'      => $item->getRepairCount(),
            'retiredCount'     => $item->getRetiredCount(),
            'utilizationRate'  => $item->getUtilizationRate(),
            'computedAt'       => $item->getComputedAt(),
        ];

        $dbSql = $this->getDbSql();
        if ($existing === null) {
            $insert = $dbSql->insert(FleetUtilizationDailyMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(FleetUtilizationDailyMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $existing->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item->setId($existing->getId());
    }

    public function getDaily(string $reportDate, ?string $warehouseCode): ?FleetUtilizationDailyModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['u' => FleetUtilizationDailyMapper::TABLE_NAME]);
        $select->where(['u.reportDate = ?' => $reportDate]);
        if ($warehouseCode === null) {
            $select->where->isNull('u.warehouseCode');
        } else {
            $select->where(['u.warehouseCode = ?' => $warehouseCode]);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new FleetUtilizationDailyModel())->exchangeArray((array)$row) : null;
    }
}
