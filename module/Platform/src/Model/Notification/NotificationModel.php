<?php

declare(strict_types=1);

namespace Platform\Model\Notification;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class NotificationModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?string $channel = null;
    protected ?string $title = null;
    protected ?string $body = null;
    protected ?string $linkUrl = null;
    protected ?string $objectType = null;
    protected ?int $objectId = null;
    protected ?string $readAt = null;
    protected ?string $createdAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }
    public function getChannel(): ?string { return $this->channel; }
    public function setChannel(?string $channel): self { $this->channel = $channel; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }
    public function getBody(): ?string { return $this->body; }
    public function setBody(?string $body): self { $this->body = $body; return $this; }
    public function getLinkUrl(): ?string { return $this->linkUrl; }
    public function setLinkUrl(?string $linkUrl): self { $this->linkUrl = $linkUrl; return $this; }
    public function getObjectType(): ?string { return $this->objectType; }
    public function setObjectType(?string $objectType): self { $this->objectType = $objectType; return $this; }
    public function getObjectId(): ?int { return $this->objectId; }
    public function setObjectId(?int $objectId): self { $this->objectId = $objectId; return $this; }
    public function getReadAt(): ?string { return $this->readAt; }
    public function setReadAt(?string $readAt): self { $this->readAt = $readAt; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function isRead(): bool
    {
        return $this->readAt !== null && $this->readAt !== '';
    }

    public function getRespNotification(): array
    {
        return [
            'id'         => AppFormat::castIntOrNull($this->id),
            'userId'     => AppFormat::castIntOrNull($this->userId),
            'channel'    => AppFormat::castStringOrNull($this->channel),
            'title'      => AppFormat::castStringOrNull($this->title),
            'body'       => AppFormat::castStringOrNull($this->body),
            'linkUrl'    => AppFormat::castStringOrNull($this->linkUrl),
            'objectType' => AppFormat::castStringOrNull($this->objectType),
            'objectId'   => AppFormat::castIntOrNull($this->objectId),
            'isRead'     => $this->isRead(),
            'readAt'     => AppFormat::castStringOrNull($this->readAt),
            'createdAt'  => AppFormat::castStringOrNull($this->createdAt),
        ];
    }
}
