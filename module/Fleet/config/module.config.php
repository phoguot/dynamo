<?php

declare(strict_types=1);

namespace Fleet;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'platform_event_handlers' => [
        'dispatch.job.started' => [
            Listener\DispatchEventHandler::class,
        ],
        'dispatch.handover.completed' => [
            Listener\DispatchEventHandler::class,
        ],
        'dispatch.return.completed' => [
            Listener\DispatchEventHandler::class,
        ],
        'dispatch.swap.completed' => [
            Listener\DispatchEventHandler::class,
        ],
        'maintenance.job.status_changed' => [
            Listener\MaintenanceEventHandler::class,
        ],
    ],

    'router'       => [
        'routes' => [
            // Trang HTML: /generators, /generators/detail/12, /generators/edit/12
            'generators'     => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/generators[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\GeneratorController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

        ],
    ],

    'controllers'  => [
        'factories' => [
            Controller\GeneratorController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
