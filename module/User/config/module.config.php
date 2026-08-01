<?php

declare(strict_types=1);

namespace User;

use Application\Factory\AppInvokableFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router'       => [
        'routes' => [
            // `/login` là đường dẫn mà BaseController::guard() chuyển hướng tới khi chưa
            // đăng nhập — đổi đường dẫn ở đây phải đổi cả bên đó.
            'login'       => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/login',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'login',
                    ],
                ],
            ],

            'logout'      => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action'     => 'logout',
                    ],
                ],
            ],

            'profile'     => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/profile',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action'     => 'profile',
                    ],
                ],
            ],

            // Trang HTML: /users, /users/detail/12, /users/edit/12
            'users'       => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/users[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\UserController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

            // Nhật ký kiểm toán toàn hệ thống, chỉ đọc: /audit-logs
            'audit-logs'  => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/audit-logs[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults'    => [
                        'controller' => Controller\AuditLogController::class,
                        'action'     => 'index',
                    ],
                ],
            ],

            // Ma trận phân quyền của một người: /permissions/index/12
            'permissions' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => '/permissions[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults'    => [
                        'controller' => Controller\PermissionController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers'  => [
        'factories' => [
            Controller\AuthController::class       => AppInvokableFactory::class,
            Controller\UserController::class       => AppInvokableFactory::class,
            Controller\AuditLogController::class   => AppInvokableFactory::class,
            Controller\PermissionController::class => AppInvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
