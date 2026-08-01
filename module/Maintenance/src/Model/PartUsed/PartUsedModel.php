<?php

declare(strict_types=1);

namespace Maintenance\Model\PartUsed;

use Application\Model\AppModel;

class PartUsedModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $jobId = null;
    protected ?string $partCode = null;
    protected ?string $partName = null;
    protected ?float $quantity = null;
    protected ?string $unit = null;
    protected int $unitPrice = 0;
    protected int $lineAmount = 0;
    protected ?string $supplier = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getJobId(): ?int { return $this->jobId; }
    public function setJobId(?int $jobId): self { $this->jobId = $jobId; return $this; }
    public function getPartCode(): ?string { return $this->partCode; }
    public function setPartCode(?string $partCode): self { $this->partCode = $partCode !== null ? strtoupper(trim($partCode)) : null; return $this; }
    public function getPartName(): ?string { return $this->partName; }
    public function setPartName(?string $partName): self { $this->partName = $partName; return $this; }
    public function getQuantity(): ?float { return $this->quantity; }
    public function setQuantity(?float $quantity): self { $this->quantity = $quantity; return $this; }
    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(?string $unit): self { $this->unit = $unit; return $this; }
    public function getUnitPrice(): int { return $this->unitPrice; }
    public function setUnitPrice(int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getLineAmount(): int { return $this->lineAmount; }
    public function setLineAmount(int $lineAmount): self { $this->lineAmount = $lineAmount; return $this; }
    public function getSupplier(): ?string { return $this->supplier; }
    public function setSupplier(?string $supplier): self { $this->supplier = $supplier; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getUnitLabel(): string { return PartUsedConst::unitLabel($this->unit); }
}
