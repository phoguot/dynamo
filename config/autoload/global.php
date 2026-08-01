<?php

declare(strict_types=1);

use Laminas\Db\Adapter\Adapter;
use Laminas\Session\Storage\SessionArrayStorage;

// Cấu hình dùng chung cho mọi môi trường. Giá trị bí mật (mật khẩu DB, ...) đặt ở
// config/autoload/local.php (không commit — copy từ local.php.dist).
return [
    'db' => [
        'driver'  => 'Pdo_Mysql',
        'charset' => 'utf8mb4',
    ],

    // Adapter dựng ở Application\Service\DbAdapterFactory (alias 'dbAdapter').
    // Alias thêm Adapter::class để code type-hint Laminas\Db\Adapter\Adapter vẫn lấy đúng
    // một instance duy nhất, không mở thêm kết nối.
    'service_manager' => [
        'aliases' => [
            Adapter::class => 'dbAdapter',
        ],
    ],

    'session_config' => [
        'cookie_httponly' => true,
        'cookie_secure'   => true,
        'cookie_samesite' => 'Lax',
        // Văn phòng: 8 giờ không hoạt động. App hiện trường dùng thời hạn riêng — xem ADR-0004.
        'gc_maxlifetime'  => 8 * 3600,
    ],

    'session_storage' => [
        'type' => SessionArrayStorage::class,
    ],

    'session_manager' => [
        'validators' => [
            \Laminas\Session\Validator\RemoteAddr::class,
            \Laminas\Session\Validator\HttpUserAgent::class,
        ],
    ],
];
