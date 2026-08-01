<?php

declare(strict_types=1);

namespace Maintenance;

use Application\Factory\AppInvokableFactory;
use Maintenance\Model\MaintenanceJob\MaintenanceJobMapper;
use Maintenance\Model\MaintenanceJob\MaintenanceJobModel;
use Maintenance\Model\PartUsed\PartUsedMapper;
use Maintenance\Model\PartUsed\PartUsedModel;
use Maintenance\Model\Schedule\ScheduleMapper;
use Maintenance\Model\Schedule\ScheduleModel;
use Maintenance\Service\MaintenanceJobService;
use Maintenance\Service\ScheduleService;

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
                ScheduleModel::class       => ScheduleModel::class,
                MaintenanceJobModel::class => MaintenanceJobModel::class,
                PartUsedModel::class       => PartUsedModel::class,
            ],
            'factories' => [
                ScheduleMapper::class       => AppInvokableFactory::class,
                MaintenanceJobMapper::class => AppInvokableFactory::class,
                PartUsedMapper::class       => AppInvokableFactory::class,
                ScheduleService::class      => AppInvokableFactory::class,
                MaintenanceJobService::class => AppInvokableFactory::class,
            ],
        ];
    }
}
