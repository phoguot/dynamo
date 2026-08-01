<?php

declare(strict_types=1);

namespace Crm\Model\Site;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class SiteModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $customerId = null;
    protected ?string $code = null;
    protected ?string $name = null;
    protected ?string $address = null;
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?string $contactName = null;
    protected ?string $contactPhone = null;
    protected ?string $installConditions = null;
    protected ?string $accessNote = null;
    protected ?string $status = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $latitude): self { $this->latitude = $latitude; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $longitude): self { $this->longitude = $longitude; return $this; }
    public function getContactName(): ?string { return $this->contactName; }
    public function setContactName(?string $contactName): self { $this->contactName = $contactName; return $this; }
    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function setContactPhone(?string $contactPhone): self { $this->contactPhone = $contactPhone; return $this; }
    public function getInstallConditions(): ?string { return $this->installConditions; }
    public function setInstallConditions(?string $installConditions): self { $this->installConditions = $installConditions; return $this; }
    public function getAccessNote(): ?string { return $this->accessNote; }
    public function setAccessNote(?string $accessNote): self { $this->accessNote = $accessNote; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getStatusLabel(): string
    {
        return SiteConst::statusLabel($this->status);
    }

    public function getMaskedContactPhone(): ?string
    {
        $value = trim((string)$this->contactPhone);
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
    public function getRespSite(): array
    {
        return [
            'id'           => AppFormat::castIntOrNull($this->id),
            'customerId'   => AppFormat::castIntOrNull($this->customerId),
            'code'         => AppFormat::castStringOrNull($this->code),
            'name'         => AppFormat::castStringOrNull($this->name),
            'address'      => AppFormat::castStringOrNull($this->address),
            'contactName'  => AppFormat::castStringOrNull($this->contactName),
            'contactPhone' => AppFormat::castStringOrNull($this->getMaskedContactPhone()),
            'status'       => ['id' => $this->status, 'name' => $this->getStatusLabel()],
        ];
    }
}
