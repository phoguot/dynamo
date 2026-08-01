<?php

declare(strict_types=1);

namespace Crm\Model\Contact;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class ContactModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $customerId = null;
    protected ?int $siteId = null;
    protected ?string $fullName = null;
    protected ?string $position = null;
    protected ?string $phone = null;
    protected ?string $email = null;
    protected ?bool $isPrimary = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }
    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $fullName): self { $this->fullName = $fullName; return $this; }
    public function getPosition(): ?string { return $this->position; }
    public function setPosition(?string $position): self { $this->position = $position; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function getIsPrimary(): ?bool { return $this->isPrimary; }
    public function setIsPrimary(?bool $isPrimary): self { $this->isPrimary = $isPrimary; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getMaskedPhone(): ?string
    {
        $value = trim((string)$this->phone);
        if ($value === '') {
            return null;
        }
        if (strlen($value) <= 5) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 3) . '****' . substr($value, -2);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRespContact(): array
    {
        return [
            'id'         => AppFormat::castIntOrNull($this->id),
            'customerId' => AppFormat::castIntOrNull($this->customerId),
            'siteId'     => AppFormat::castIntOrNull($this->siteId),
            'fullName'   => AppFormat::castStringOrNull($this->fullName),
            'position'   => AppFormat::castStringOrNull($this->position),
            'phone'      => AppFormat::castStringOrNull($this->getMaskedPhone()),
            'email'      => AppFormat::castStringOrNull($this->email),
            'isPrimary'  => (bool)$this->isPrimary,
        ];
    }
}
