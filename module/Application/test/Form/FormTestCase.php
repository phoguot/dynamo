<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Service\AuthContextService;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

/**
 * Nền cho test tầng Form: dựng container tối thiểu và một phiên trong bộ nhớ.
 *
 * Mỗi test tự dựng phiên mới ⇒ không phụ thuộc thứ tự chạy (.claude/rules/testing.md).
 */
abstract class FormTestCase extends TestCase
{
    protected ServiceManager $container;

    protected AuthContextService $auth;

    protected function setUp(): void
    {
        parent::setUp();

        // Phiên lưu trong mảng — không đụng $_SESSION thật, chạy được ở CLI.
        Container::setDefaultManager(new SessionManager(null, new ArrayStorage()));

        $this->container = new ServiceManager([
            'invokables' => [AuthContextService::class => AuthContextService::class],
        ]);
        $this->auth = $this->container->get(AuthContextService::class);
    }
}
