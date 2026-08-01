<?php

declare(strict_types=1);

namespace Sales\Model\PriceList;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class PriceListModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $code = null;
    protected ?string $name = null;
    protected ?string $validFrom = null;
    protected ?string $validTo = null;
    protected ?bool $isActive = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getValidFrom(): ?string { return $this->validFrom; }
    public function setValidFrom(?string $validFrom): self { $this->validFrom = $validFrom; return $this; }
    public function getValidTo(): ?string { return $this->validTo; }
    public function setValidTo(?string $validTo): self { $this->validTo = $validTo; return $this; }
    public function getIsActive(): ?bool { return $this->isActive; }
    public function setIsActive(?bool $isActive): self { $this->isActive = $isActive; return $this; }
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

    public function getActiveLabel(): string
    {
        return $this->isActive ? 'Đang dùng' : 'Ngưng dùng';
    }

    public function getRespPriceList(): array
    {
        return [
            'id'        => AppFormat::castIntOrNull($this->id),
            'code'      => AppFormat::castStringOrNull($this->code),
            'name'      => AppFormat::castStringOrNull($this->name),
            'validFrom' => AppFormat::castStringOrNull($this->validFrom),
            'validTo'   => AppFormat::castStringOrNull($this->validTo),
            'isActive'  => (bool)$this->isActive,
        ];
    }
}

