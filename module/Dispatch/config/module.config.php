<?php

declare(strict_types=1);

namespace Dispatch;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'vehicles' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/vehicles[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\VehicleController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'dispatch-jobs' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/dispatch-jobs[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\DispatchJobController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\VehicleController::class     => AppInvokableFactory::class,
            Controller\DispatchJobController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
