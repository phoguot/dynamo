<?php

declare(strict_types=1);

namespace Platform\Model\Attachment;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class AttachmentModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $ownerType = null;
    protected ?int $ownerId = null;
    protected ?string $kind = null;
    protected ?string $originalName = null;
    protected ?string $storagePath = null;
    protected ?string $mimeType = null;
    protected ?int $sizeBytes = null;
    protected ?string $checksum = null;
    protected ?int $version = null;
    protected ?string $createdAt = null;
    protected ?int $createdBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getOwnerType(): ?string { return $this->ownerType; }
    public function setOwnerType(?string $ownerType): self { $this->ownerType = $ownerType; return $this; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function setOwnerId(?int $ownerId): self { $this->ownerId = $ownerId; return $this; }
    public function getKind(): ?string { return $this->kind; }
    public function setKind(?string $kind): self { $this->kind = $kind; return $this; }
    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(?string $originalName): self { $this->originalName = $originalName; return $this; }
    public function getStoragePath(): ?string { return $this->storagePath; }
    public function setStoragePath(?string $storagePath): self { $this->storagePath = $storagePath; return $this; }
    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): self { $this->mimeType = $mimeType; return $this; }
    public function getSizeBytes(): ?int { return $this->sizeBytes; }
    public function setSizeBytes(?int $sizeBytes): self { $this->sizeBytes = $sizeBytes; return $this; }
    public function getChecksum(): ?string { return $this->checksum; }
    public function setChecksum(?string $checksum): self { $this->checksum = $checksum; return $this; }
    public function getVersion(): ?int { return $this->version; }
    public function setVersion(?int $version): self { $this->version = $version; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getRespAttachment(): array
    {
        return [
            'id'           => AppFormat::castIntOrNull($this->id),
            'ownerType'    => AppFormat::castStringOrNull($this->ownerType),
            'ownerId'      => AppFormat::castIntOrNull($this->ownerId),
            'kind'         => AppFormat::castStringOrNull($this->kind),
            'originalName' => AppFormat::castStringOrNull($this->originalName),
            'mimeType'     => AppFormat::castStringOrNull($this->mimeType),
            'sizeBytes'    => AppFormat::castIntOrNull($this->sizeBytes),
            'checksum'     => AppFormat::castStringOrNull($this->checksum),
            'version'      => AppFormat::castIntOrNull($this->version),
            'createdAt'    => AppFormat::castStringOrNull($this->createdAt),
        ];
    }
}
