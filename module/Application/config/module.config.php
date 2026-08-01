<?php

declare(strict_types=1);

namespace Application;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Literal;

return [
    'router'       => [
        'routes' => [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers'  => [
        'factories' => [
            Controller\IndexController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'doctype'                  => 'HTML5',
        'display_not_found_reason' => false,
        'display_exceptions'       => false,
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map'             => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'error/403'     => __DIR__ . '/../view/error/403.phtml',
            'error/404'     => __DIR__ . '/../view/error/404.phtml',
            'error/index'   => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack'      => [
            __DIR__ . '/../view',
        ],
    ],
];
