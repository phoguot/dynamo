<?php

declare(strict_types=1);

namespace Sales;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'price-lists' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/price-lists[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\PriceListController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'quotes' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/quotes[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\QuoteController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'price-list-items' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/price-list-items[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\PriceListItemController::class,
                        'action'     => 'create',
                    ],
                ],
            ],
            'contracts' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/contracts[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ContractController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\PriceListController::class => AppInvokableFactory::class,
            Controller\PriceListItemController::class => AppInvokableFactory::class,
            Controller\QuoteController::class     => AppInvokableFactory::class,
            Controller\ContractController::class  => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
