<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Db\Sql\Sql;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Đối tượng dựng câu lệnh SQL (alias 'dbSql'). Mọi Mapper dựng select/insert/update qua đây
 * để luôn ra prepared statement — xem .claude/rules/security.md.
 */
class DbSqlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Sql
    {
        return new Sql($container->get('dbAdapter'));
    }
}
