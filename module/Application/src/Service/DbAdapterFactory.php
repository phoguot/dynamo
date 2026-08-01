<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Adapter MySQL duy nhất của hệ thống (alias 'dbAdapter').
 * Cấu hình kết nối lấy từ config/autoload/local.php — không đặt mật khẩu trong code.
 */
class DbAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Adapter
    {
        $config = $container->get('config');
        $adapter = new Adapter($config['db']);

        if (!empty($config['db']['profilerEnabled'])) {
            $adapter->setProfiler(new Profiler());
        }

        return $adapter;
    }
}
