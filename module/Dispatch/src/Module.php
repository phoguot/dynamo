<?php

declare(strict_types=1);

namespace Dispatch;

use Application\Factory\AppInvokableFactory;
use Dispatch\Model\Assignment\AssignmentMapper;
use Dispatch\Model\Assignment\AssignmentModel;
use Dispatch\Model\DispatchJob\DispatchJobMapper;
use Dispatch\Model\DispatchJob\DispatchJobModel;
use Dispatch\Model\Vehicle\VehicleMapper;
use Dispatch\Model\Vehicle\VehicleModel;
use Dispatch\Service\DispatchJobService;
use Dispatch\Service\VehicleService;

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
                VehicleModel::class     => VehicleModel::class,
                DispatchJobModel::class => DispatchJobModel::class,
                AssignmentModel::class  => AssignmentModel::class,
            ],
            'factories' => [
                VehicleMapper::class     => AppInvokableFactory::class,
                DispatchJobMapper::class => AppInvokableFactory::class,
                AssignmentMapper::class  => AppInvokableFactory::class,
                VehicleService::class    => AppInvokableFactory::class,
                DispatchJobService::class => AppInvokableFactory::class,
            ],
        ];
    }
}
