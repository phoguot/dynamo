<?php

declare(strict_types=1);

namespace Crm;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Segment;

return [
    'platform_event_handlers' => [
        'billing.credit.exceeded' => [
            Listener\BillingCreditEventHandler::class,
        ],
        'billing.credit.cleared' => [
            Listener\BillingCreditEventHandler::class,
        ],
    ],

    'router'       => [
        'routes' => [
            'customers' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/customers[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\CustomerController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'customer-sites' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/customer-sites[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\SiteController::class,
                        'action'     => 'create',
                    ],
                ],
            ],
            'customer-contacts' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/customer-contacts[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\ContactController::class,
                        'action'     => 'create',
                    ],
                ],
            ],
        ],
    ],

    'controllers'  => [
        'factories' => [
            Controller\CustomerController::class => AppInvokableFactory::class,
            Controller\SiteController::class     => AppInvokableFactory::class,
            Controller\ContactController::class  => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
