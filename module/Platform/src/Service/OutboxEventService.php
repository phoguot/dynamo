<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Platform\Model\OutboxEvent\OutboxEventMapper;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Platform\Model\PlatformConst;

class OutboxEventService extends AppServiceFactory
{
    private function mapper(): OutboxEventMapper
    {
        return $this->getContainerEntry(OutboxEventMapper::class);
    }

    public function searchEvents(array $criteria = []): Paginator
    {
        $item = (new OutboxEventModel())
            ->setEventName($this->nullIfBlank($criteria['eventName'] ?? null))
            ->setAggregateId($this->positiveIntOrNull($criteria['aggregateId'] ?? null))
            ->setStatus($this->nullIfBlank($criteria['status'] ?? null));
        $paging = PaginatorUtil::fromFormData($criteria);
        $paging['sort'] = $criteria['sort'] ?? PlatformConst::OUTBOX_SORT_DEFAULT;
        $paging['dir'] = $criteria['dir'] ?? 'desc';

        return $this->mapper()->searchEvents($item, $paging);
    }

    /**
     * @return list<OutboxEventModel>
     */
    public function fetchPendingEvents(int $limit = 50): array
    {
        return $this->mapper()->fetchPendingEvents($limit);
    }

    public function recordEvent(string $eventName, ?int $aggregateId, array $payload): OutboxEventModel
    {
        $eventName = trim($eventName);
        $errors = [];
        if ($eventName === '' || strlen($eventName) > 120) {
            $errors['eventName'] = 'Tên event không hợp lệ.';
        }
        if ($aggregateId !== null && $aggregateId <= 0) {
            $errors['aggregateId'] = 'Aggregate id không hợp lệ.';
        }
        $payloadJson = PlatformConst::normalizeJson($payload);
        if ($payloadJson === '' || $payloadJson === 'null') {
            $errors['payloadJson'] = 'Payload event phải là JSON hợp lệ.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $item = (new OutboxEventModel())
            ->setEventName($eventName)
            ->setAggregateId($aggregateId)
            ->setPayloadJson($payloadJson)
            ->setStatus(PlatformConst::OUTBOX_STATUS_PENDING)
            ->setAttempts(0);

        return $this->mapper()->insertEvent($item);
    }

    public function markPublished(int $id): bool
    {
        return $this->mapper()->markPublished($id);
    }

    public function markFailed(int $id, string $lastError): bool
    {
        return $this->mapper()->markFailed($id, $lastError);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
