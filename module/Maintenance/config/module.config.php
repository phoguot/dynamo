<?php

declare(strict_types=1);

namespace Maintenance;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'maintenance-schedules' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/maintenance-schedules[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ScheduleController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'maintenance-jobs' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/maintenance-jobs[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\MaintenanceJobController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\ScheduleController::class       => AppInvokableFactory::class,
            Controller\MaintenanceJobController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
