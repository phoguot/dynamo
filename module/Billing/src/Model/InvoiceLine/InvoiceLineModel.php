<?php

declare(strict_types=1);

namespace Billing\Model\InvoiceLine;

use Application\Model\AppModel;

class InvoiceLineModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $invoiceId = null;
    protected ?string $lineType = null;
    protected ?int $generatorId = null;
    protected ?string $description = null;
    protected ?float $quantity = null;
    protected ?string $unit = null;
    protected int $unitPrice = 0;
    protected int $lineAmount = 0;
    protected ?int $isVatable = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getInvoiceId(): ?int { return $this->invoiceId; }
    public function setInvoiceId(?int $invoiceId): self { $this->invoiceId = $invoiceId; return $this; }
    public function getLineType(): ?string { return $this->lineType; }
    public function setLineType(?string $lineType): self { $this->lineType = $lineType; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getQuantity(): ?float { return $this->quantity; }
    public function setQuantity(?float $quantity): self { $this->quantity = $quantity; return $this; }
    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(?string $unit): self { $this->unit = $unit; return $this; }
    public function getUnitPrice(): int { return $this->unitPrice; }
    public function setUnitPrice(int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getLineAmount(): int { return $this->lineAmount; }
    public function setLineAmount(int $lineAmount): self { $this->lineAmount = $lineAmount; return $this; }
    public function getIsVatable(): ?int { return $this->isVatable; }
    public function setIsVatable(?int $isVatable): self { $this->isVatable = $isVatable; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getTypeLabel(): string { return InvoiceLineConst::typeLabel($this->lineType); }
    public function getUnitLabel(): string { return InvoiceLineConst::unitLabel($this->unit); }
    public function isVatable(): bool { return (bool)$this->isVatable; }
}
