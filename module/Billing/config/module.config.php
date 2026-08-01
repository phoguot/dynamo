<?php

declare(strict_types=1);

namespace Billing;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'credit-limits' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/credit-limits[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CreditLimitController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'invoices' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/invoices[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\InvoiceController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'payments' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payments[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\PaymentController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'deposits' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/deposits[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\DepositController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\CreditLimitController::class => AppInvokableFactory::class,
            Controller\InvoiceController::class     => AppInvokableFactory::class,
            Controller\PaymentController::class     => AppInvokableFactory::class,
            Controller\DepositController::class     => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
