<?php

declare(strict_types=1);

namespace Fleet;

use Application\Factory\AppInvokableFactory;
use Fleet\Listener\DispatchEventHandler;
use Fleet\Listener\MaintenanceEventHandler;
use Fleet\Model\Generator\GeneratorMapper;
use Fleet\Model\Generator\GeneratorModel;
use Fleet\Service\GeneratorService;

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
                GeneratorModel::class => GeneratorModel::class,
            ],
            'factories'  => [
                DispatchEventHandler::class => AppInvokableFactory::class,
                MaintenanceEventHandler::class => AppInvokableFactory::class,
                GeneratorMapper::class      => AppInvokableFactory::class,
                GeneratorService::class     => AppInvokableFactory::class,
            ],
        ];
    }
}
