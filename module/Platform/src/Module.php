<?php

declare(strict_types=1);

namespace Platform;

use Application\Factory\AppInvokableFactory;
use Laminas\EventManager\EventInterface;
use Platform\Listener\OutboxDispatchListener;
use Platform\Model\Attachment\AttachmentMapper;
use Platform\Model\Attachment\AttachmentModel;
use Platform\Model\ErrorLog\ErrorLogMapper;
use Platform\Model\ErrorLog\ErrorLogModel;
use Platform\Model\Notification\NotificationMapper;
use Platform\Model\Notification\NotificationModel;
use Platform\Model\OutboxEvent\OutboxEventMapper;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Platform\Model\Setting\SettingMapper;
use Platform\Model\Setting\SettingModel;
use Platform\Service\AttachmentService;
use Platform\Service\ErrorLogService;
use Platform\Service\NotificationService;
use Platform\Service\OutboxDispatcherService;
use Platform\Service\OutboxEventService;
use Platform\Service\SettingService;

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
                AttachmentModel::class  => AttachmentModel::class,
                ErrorLogModel::class    => ErrorLogModel::class,
                NotificationModel::class => NotificationModel::class,
                OutboxEventModel::class => OutboxEventModel::class,
                SettingModel::class     => SettingModel::class,
            ],
            'factories'  => [
                AttachmentMapper::class   => AppInvokableFactory::class,
                AttachmentService::class  => AppInvokableFactory::class,
                ErrorLogMapper::class     => AppInvokableFactory::class,
                ErrorLogService::class    => AppInvokableFactory::class,
                NotificationMapper::class => AppInvokableFactory::class,
                NotificationService::class => AppInvokableFactory::class,
                OutboxDispatcherService::class => AppInvokableFactory::class,
                OutboxEventMapper::class  => AppInvokableFactory::class,
                OutboxEventService::class => AppInvokableFactory::class,
                SettingMapper::class      => AppInvokableFactory::class,
                SettingService::class     => AppInvokableFactory::class,
            ],
        ];
    }

    /**
     * Không có cron, không có worker nền (ADR-0005) — outbox được phát ngay
     * trong request đã ghi nó, sau khi commit và sau khi response đã gửi.
     */
    public function onBootstrap(EventInterface $event): void
    {
        $application = $event->getApplication();

        (new OutboxDispatchListener($application->getServiceManager()))
            ->attach($application->getEventManager());
    }
}
