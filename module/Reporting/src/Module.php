<?php

declare(strict_types=1);

namespace Reporting;

use Application\Factory\AppInvokableFactory;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyMapper;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotMapper;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotModel;
use Reporting\Model\RevenueMonthly\RevenueMonthlyMapper;
use Reporting\Model\RevenueMonthly\RevenueMonthlyModel;
use Reporting\Service\ReportingService;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig(): array
    {
        return [
            'invokables' => [
                FleetUtilizationDailyModel::class => FleetUtilizationDailyModel::class,
                ReceivablesSnapshotModel::class   => ReceivablesSnapshotModel::class,
                RevenueMonthlyModel::class        => RevenueMonthlyModel::class,
            ],
            'factories'  => [
                FleetUtilizationDailyMapper::class => AppInvokableFactory::class,
                ReceivablesSnapshotMapper::class   => AppInvokableFactory::class,
                RevenueMonthlyMapper::class        => AppInvokableFactory::class,
                ReportingService::class            => AppInvokableFactory::class,
            ],
        ];
    }
}
