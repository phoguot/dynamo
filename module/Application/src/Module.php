<?php

declare(strict_types=1);

namespace Application;

use Application\Factory\AppInvokableFactory;
use Application\Filter\SafeHtmlFilter;
use Application\Listener\ErrorListener;
use Application\Listener\LayoutListener;
use Application\Service\AuthContextService;
use Application\Service\DbAdapterFactory;
use Application\Service\DbSqlFactory;
use Laminas\EventManager\EventInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

class Module
{
    public const string VERSION = '1.0';

    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig(): array
    {
        return [
            'factories'  => [
                DbAdapterFactory::class => DbAdapterFactory::class,
                DbSqlFactory::class     => DbSqlFactory::class,
            ],
            'invokables' => [
                AuthContextService::class => AuthContextService::class,
            ],
            'aliases'    => [
                'dbAdapter' => DbAdapterFactory::class,
                'dbSql'     => DbSqlFactory::class,
            ],
        ];
    }

    /**
     * Filter tự viết phải khai ở đây thì mới gọi được bằng tên class trong InputFilter.
     */
    public function getFilterConfig(): array
    {
        return [
            'invokables' => [
                SafeHtmlFilter::class => SafeHtmlFilter::class,
            ],
        ];
    }

    public function onBootstrap(EventInterface $event): void
    {
        $application  = $event->getApplication();
        $eventManager = $application->getEventManager();

        $this->initSession($event);

        (new LayoutListener())->attach($eventManager);
        (new ErrorListener($application->getServiceManager()))->attach($eventManager);
    }

    /**
     * Phiên đăng nhập dùng session cookie (HttpOnly/Secure/SameSite=Lax) — ADR-0002 + rules/security.
     */
    private function initSession(EventInterface $event): void
    {
        $config = $event->getApplication()->getServiceManager()->get('config');

        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($config['session_config'] ?? []);

        $sessionManager = new SessionManager($sessionConfig);
        $sessionManager->start();

        Container::setDefaultManager($sessionManager);
    }
}
