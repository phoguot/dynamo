<?php

declare(strict_types=1);

namespace Rental;

use Application\Factory\AppInvokableFactory;
use Rental\Listener\DispatchEventHandler;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyMapper;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyModel;
use Rental\Model\RentalOrder\RentalOrderMapper;
use Rental\Model\RentalOrder\RentalOrderModel;
use Rental\Service\RentalOrderService;

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
                RentalOrderModel::class        => RentalOrderModel::class,
                GeneratorOccupancyModel::class => GeneratorOccupancyModel::class,
            ],
            'factories' => [
                DispatchEventHandler::class    => AppInvokableFactory::class,
                RentalOrderMapper::class        => AppInvokableFactory::class,
                GeneratorOccupancyMapper::class => AppInvokableFactory::class,
                RentalOrderService::class       => AppInvokableFactory::class,
            ],
        ];
    }
}
