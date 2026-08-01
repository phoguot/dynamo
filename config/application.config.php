<?php

declare(strict_types=1);

// Đăng ký module Laminas. Thêm module nghiệp vụ (Crm, Sales, ...) vào đây khi tạo.
//
// THỨ TỰ CÓ Ý NGHĨA: `User` (M01) phải nằm trước các module nghiệp vụ vì nó đăng ký
// `PermissionCheckerInterface` mà guard của BaseController hỏi tới ở mọi request.
return [
    'modules' => [
        'Laminas\Router',
        'Laminas\Session',
        'Laminas\Mvc\Plugin\FlashMessenger',
        'Application',
        'User',
        'Platform',
        'Fleet',
        'Crm',
        'Sales',
        'Rental',
        'Dispatch',
        'Maintenance',
        'Billing',
        'Reporting',
    ],

    'module_listener_options' => [
        'config_glob_paths'    => [
            realpath(__DIR__) . '/autoload/{,*.}{global,local}.php',
        ],
        'module_paths' => [
            './module',
        ],
    ],
];
