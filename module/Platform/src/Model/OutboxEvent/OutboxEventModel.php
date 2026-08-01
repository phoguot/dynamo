<?php

declare(strict_types=1);

namespace Platform\Model\OutboxEvent;

use Application\Model\AppFormat;
use Application\Model\AppModel;
use Platform\Model\PlatformConst;

class OutboxEventModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $eventName = null;
    protected ?int $aggregateId = null;
    protected ?string $payloadJson = null;
    protected ?string $status = null;
    protected ?int $attempts = null;
    protected ?string $lastError = null;
    protected ?string $publishedAt = null;
    protected ?string $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getEventName(): ?string { return $this->eventName; }
    public function setEventName(?string $eventName): self { $this->eventName = $eventName; return $this; }
    public function getAggregateId(): ?int { return $this->aggregateId; }
    public function setAggregateId(?int $aggregateId): self { $this->aggregateId = $aggregateId; return $this; }
    public function getPayloadJson(): ?string { return $this->payloadJson; }
    public function setPayloadJson(?string $payloadJson): self { $this->payloadJson = $payloadJson; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getAttempts(): ?int { return $this->attempts; }
    public function setAttempts(?int $attempts): self { $this->attempts = $attempts; return $this; }
    public function getLastError(): ?string { return $this->lastError; }
    public function setLastError(?string $lastError): self { $this->lastError = $lastError; return $this; }
    public function getPublishedAt(): ?string { return $this->publishedAt; }
    public function setPublishedAt(?string $publishedAt): self { $this->publishedAt = $publishedAt; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getPayload(): array
    {
        return PlatformConst::decodeJson($this->payloadJson);
    }

    public function getRespOutboxEvent(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'eventName'   => AppFormat::castStringOrNull($this->eventName),
            'aggregateId' => AppFormat::castIntOrNull($this->aggregateId),
            'payload'     => $this->getPayload(),
            'status'      => AppFormat::castStringOrNull($this->status),
            'attempts'    => AppFormat::castIntOrNull($this->attempts),
            'lastError'   => AppFormat::castStringOrNull($this->lastError),
            'publishedAt' => AppFormat::castStringOrNull($this->publishedAt),
            'createdAt'   => AppFormat::castStringOrNull($this->createdAt),
        ];
    }
}
