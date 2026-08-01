<?php

declare(strict_types=1);

namespace User;

use Application\Factory\AppInvokableFactory;
use Application\Service\PermissionCheckerInterface;
use User\Model\AuditLog\AuditLogMapper;
use User\Model\AuditLog\AuditLogModel;
use User\Model\Permission\PermissionMapper;
use User\Model\Permission\PermissionModel;
use User\Model\User\UserMapper;
use User\Model\User\UserModel;
use User\Service\AuditLogService;
use User\Service\AuthService;
use User\Service\PermissionService;
use User\Service\UserService;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig(): array
    {
        return [
            'invokables' => [
                UserModel::class       => UserModel::class,
                PermissionModel::class => PermissionModel::class,
                AuditLogModel::class   => AuditLogModel::class,
            ],
            'factories'  => [
                UserMapper::class        => AppInvokableFactory::class,
                PermissionMapper::class  => AppInvokableFactory::class,
                AuditLogMapper::class    => AppInvokableFactory::class,
                UserService::class       => AppInvokableFactory::class,
                AuthService::class       => AppInvokableFactory::class,
                AuditLogService::class   => AppInvokableFactory::class,
                PermissionService::class => AppInvokableFactory::class,
            ],
            'aliases'    => [
                // ĐÂY là chỗ M01 tự cắm mình vào guard của tầng nền: BaseController hỏi
                // container theo tên interface, không biết class nào đứng sau.
                // Gỡ dòng này = tầng nền không còn nguồn quyết định quyền action.
                PermissionCheckerInterface::class => PermissionService::class,
            ],
        ];
    }
}
