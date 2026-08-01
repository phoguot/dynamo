<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Factory\AppServiceFactory;
use Platform\Event\OutboxEventHandlerInterface;
use Platform\Model\OutboxEvent\OutboxEventModel;
use RuntimeException;
use Throwable;

class OutboxDispatcherService extends AppServiceFactory
{
    /**
     * @return array{fetched:int, published:int, failed:int}
     */
    public function dispatch(int $limit = 50): array
    {
        $outboxService = $this->getContainerEntry(OutboxEventService::class);
        if (!$outboxService instanceof OutboxEventService) {
            throw new RuntimeException('OutboxEventService chua duoc dang ky trong container.');
        }

        $stats = ['fetched' => 0, 'published' => 0, 'failed' => 0];
        foreach ($outboxService->fetchPendingEvents($limit) as $event) {
            $stats['fetched']++;
            try {
                $handlers = $this->resolveHandlers($event);
                if ($handlers === []) {
                    throw new RuntimeException(sprintf(
                        'Khong co handler dang ky cho event "%s".',
                        (string)$event->getEventName()
                    ));
                }

                foreach ($handlers as $handler) {
                    $handler->handle($event);
                }
                $outboxService->markPublished((int)$event->getId());
                $stats['published']++;
            } catch (Throwable $exception) {
                $outboxService->markFailed((int)$event->getId(), $exception->getMessage());
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @return list<OutboxEventHandlerInterface>
     */
    private function resolveHandlers(OutboxEventModel $event): array
    {
        $handlers = $this->configuredHandlers()[(string)$event->getEventName()] ?? [];
        $handlers = is_array($handlers) ? $handlers : [$handlers];

        $resolved = [];
        foreach ($handlers as $handler) {
            if ($handler instanceof OutboxEventHandlerInterface) {
                $resolved[] = $handler;
                continue;
            }

            if (is_string($handler) && $handler !== '') {
                $service = $this->getContainerEntry($handler);
                if ($service instanceof OutboxEventHandlerInterface) {
                    $resolved[] = $service;
                }
            }
        }

        return $resolved;
    }

    /**
     * @return array<string, string|list<string|OutboxEventHandlerInterface>|OutboxEventHandlerInterface>
     */
    private function configuredHandlers(): array
    {
        $config = $this->getContainerEntry('config');
        $handlers = is_array($config) ? ($config['platform_event_handlers'] ?? []) : [];

        return is_array($handlers) ? $handlers : [];
    }
}
