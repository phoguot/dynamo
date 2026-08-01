<?php

declare(strict_types=1);

namespace Application\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory chung: khởi tạo class rồi tiêm container vào.
 * Dùng cho mọi Controller, Service, Mapper của dự án.
 */
final class AppInvokableFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $object = $options === null ? new $requestedName() : new $requestedName($options);
        $object->setContainer($container);
        return $object;
    }
}
