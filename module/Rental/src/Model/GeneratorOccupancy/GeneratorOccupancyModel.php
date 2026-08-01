<?php

declare(strict_types=1);

namespace Rental\Model\GeneratorOccupancy;

use Application\Model\AppModel;

class GeneratorOccupancyModel extends AppModel
{
    protected ?int $generatorId = null;
    protected ?string $occupiedDate = null;
    protected ?int $rentalOrderId = null;
    protected ?string $holdType = null;
    protected ?string $sourceType = null;
    protected ?int $sourceId = null;
    protected ?string $expiresAt = null;
    protected ?string $createdAt = null;
    protected ?int $createdBy = null;

    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getOccupiedDate(): ?string { return $this->occupiedDate; }
    public function setOccupiedDate(?string $occupiedDate): self { $this->occupiedDate = $occupiedDate; return $this; }
    public function getRentalOrderId(): ?int { return $this->rentalOrderId; }
    public function setRentalOrderId(?int $rentalOrderId): self { $this->rentalOrderId = $rentalOrderId; return $this; }
    public function getHoldType(): ?string { return $this->holdType; }
    public function setHoldType(?string $holdType): self { $this->holdType = $holdType; return $this; }
    public function getSourceType(): ?string { return $this->sourceType; }
    public function setSourceType(?string $sourceType): self { $this->sourceType = $sourceType; return $this; }
    public function getSourceId(): ?int { return $this->sourceId; }
    public function setSourceId(?int $sourceId): self { $this->sourceId = $sourceId; return $this; }
    public function getExpiresAt(): ?string { return $this->expiresAt; }
    public function setExpiresAt(?string $expiresAt): self { $this->expiresAt = $expiresAt; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getHoldLabel(): string
    {
        return GeneratorOccupancyConst::holdLabel($this->holdType);
    }
}

