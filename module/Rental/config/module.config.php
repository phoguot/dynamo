<?php

declare(strict_types=1);

namespace Rental;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'platform_event_handlers' => [
        'dispatch.handover.completed' => [
            Listener\DispatchEventHandler::class,
        ],
        'dispatch.return.completed' => [
            Listener\DispatchEventHandler::class,
        ],
        'dispatch.swap.completed' => [
            Listener\DispatchEventHandler::class,
        ],
    ],

    'router' => [
        'routes' => [
            'rental-orders' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/rental-orders[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\RentalOrderController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\RentalOrderController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
