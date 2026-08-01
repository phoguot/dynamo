<?php

declare(strict_types=1);

namespace Reporting\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Reporting\Form\Report\FleetUtilizationSearchForm;
use Reporting\Form\Report\ReceivablesSearchForm;
use Reporting\Form\Report\RevenueSearchForm;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyMapper;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotMapper;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotModel;
use Reporting\Model\RevenueMonthly\RevenueMonthlyMapper;
use Reporting\Model\RevenueMonthly\RevenueMonthlyModel;
use Reporting\Model\ReportingConst;

class ReportingService extends AppServiceFactory
{
    private function fleetMapper(): FleetUtilizationDailyMapper { return $this->getContainerEntry(FleetUtilizationDailyMapper::class); }
    private function revenueMapper(): RevenueMonthlyMapper { return $this->getContainerEntry(RevenueMonthlyMapper::class); }
    private function receivablesMapper(): ReceivablesSnapshotMapper { return $this->getContainerEntry(ReceivablesSnapshotMapper::class); }

    public function newFleetForm(array $query = []): FleetUtilizationSearchForm
    {
        $form = new FleetUtilizationSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newRevenueForm(array $query = []): RevenueSearchForm
    {
        $form = new RevenueSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newReceivablesForm(array $query = []): ReceivablesSearchForm
    {
        $form = new ReceivablesSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    /** @return array{fleet:?FleetUtilizationDailyModel, revenue:?RevenueMonthlyModel, receivables:array<string,mixed>, computedAt:?string} */
    public function dashboardSummary(): array
    {
        $fleet = $this->fleetMapper()->getLatestCompanyRow();
        $revenue = $this->revenueMapper()->getLatestCompanyRow();
        $receivables = $this->receivablesMapper()->summarizeLatestSnapshot();
        $computedAtValues = array_filter([
            $fleet?->getComputedAt(),
            $revenue?->getComputedAt(),
            $receivables['computedAt'] ?? null,
        ]);
        $computedAt = $computedAtValues !== [] ? max($computedAtValues) : null;

        return [
            'fleet' => $fleet,
            'revenue' => $revenue,
            'receivables' => $receivables,
            'computedAt' => $computedAt,
        ];
    }

    public function searchFleetUtilization(array $payload = []): Paginator
    {
        $form = new FleetUtilizationSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $criteria = new FleetUtilizationDailyModel();
        $criteria->setReportDate($this->nullIfBlank($data['reportDate'] ?? null));
        $criteria->setWarehouseCode($this->nullIfBlank($data['warehouseCode'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? ReportingConst::FLEET_SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';

        return $this->fleetMapper()->searchFleetUtilization($criteria, $paging);
    }

    public function searchRevenue(array $payload = []): Paginator
    {
        $form = new RevenueSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $criteria = new RevenueMonthlyModel();
        $criteria->setPeriodYear($this->positiveIntOrNull($data['periodYear'] ?? null));
        $criteria->setPeriodMonth($this->positiveIntOrNull($data['periodMonth'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? ReportingConst::REVENUE_SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';

        return $this->revenueMapper()->searchRevenue($criteria, $paging);
    }

    public function searchReceivables(array $payload = []): Paginator
    {
        $form = new ReceivablesSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $criteria = new ReceivablesSnapshotModel();
        $criteria->setSnapshotDate($this->nullIfBlank($data['snapshotDate'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? ReportingConst::RECEIVABLES_SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';

        return $this->receivablesMapper()->searchReceivables($criteria, $paging);
    }

    public function syncRevenueMonth(RevenueMonthlyModel $item): ?RevenueMonthlyModel
    {
        if ($item->getComputedAt() === null) {
            $item->setComputedAt(DateModel::getUtcNow());
        }

        return $this->revenueMapper()->saveRevenueMonth($item);
    }

    public function syncReceivablesSnapshot(ReceivablesSnapshotModel $item): ReceivablesSnapshotModel
    {
        if ($item->getComputedAt() === null) {
            $item->setComputedAt(DateModel::getUtcNow());
        }

        return $this->receivablesMapper()->saveSnapshot($item);
    }

    public function syncFleetUtilizationDaily(FleetUtilizationDailyModel $item): FleetUtilizationDailyModel
    {
        if ($item->getComputedAt() === null) {
            $item->setComputedAt(DateModel::getUtcNow());
        }

        $item->setUtilizationRate(ReportingConst::utilizationRate(
            $item->getRentedCount(),
            $item->getActiveGenerators()
        ));

        return $this->fleetMapper()->saveDaily($item);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function intOrNull(mixed $value): ?int { return ($value === null || $value === '') ? null : (int)$value; }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = $this->intOrNull($value);
        return $value !== null && $value > 0 ? $value : null;
    }
}
