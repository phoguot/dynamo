<?php

declare(strict_types=1);

namespace Sales\Model\QuoteLine;

use Application\Model\AppModel;
use Sales\Model\PriceListItem\PriceListItemConst;

class QuoteLineModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $quoteId = null;
    protected ?int $generatorId = null;
    protected ?int $capacityKva = null;
    protected ?int $quantity = null;
    protected ?string $rentFrom = null;
    protected ?string $rentTo = null;
    protected ?string $durationTier = null;
    protected ?float $durationQty = null;
    protected ?int $unitPrice = null;
    protected ?int $oddDays = null;
    protected ?int $oddDayRate = null;
    protected ?int $lineAmount = null;
    protected ?string $suggestReason = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getQuoteId(): ?int { return $this->quoteId; }
    public function setQuoteId(?int $quoteId): self { $this->quoteId = $quoteId; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getCapacityKva(): ?int { return $this->capacityKva; }
    public function setCapacityKva(?int $capacityKva): self { $this->capacityKva = $capacityKva; return $this; }
    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(?int $quantity): self { $this->quantity = $quantity; return $this; }
    public function getRentFrom(): ?string { return $this->rentFrom; }
    public function setRentFrom(?string $rentFrom): self { $this->rentFrom = $rentFrom; return $this; }
    public function getRentTo(): ?string { return $this->rentTo; }
    public function setRentTo(?string $rentTo): self { $this->rentTo = $rentTo; return $this; }
    public function getDurationTier(): ?string { return $this->durationTier; }
    public function setDurationTier(?string $durationTier): self { $this->durationTier = $durationTier; return $this; }
    public function getDurationQty(): ?float { return $this->durationQty; }
    public function setDurationQty(?float $durationQty): self { $this->durationQty = $durationQty; return $this; }
    public function getUnitPrice(): ?int { return $this->unitPrice; }
    public function setUnitPrice(?int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getOddDays(): ?int { return $this->oddDays; }
    public function setOddDays(?int $oddDays): self { $this->oddDays = $oddDays; return $this; }
    public function getOddDayRate(): ?int { return $this->oddDayRate; }
    public function setOddDayRate(?int $oddDayRate): self { $this->oddDayRate = $oddDayRate; return $this; }
    public function getLineAmount(): ?int { return $this->lineAmount; }
    public function setLineAmount(?int $lineAmount): self { $this->lineAmount = $lineAmount; return $this; }
    public function getSuggestReason(): ?string { return $this->suggestReason; }
    public function setSuggestReason(?string $suggestReason): self { $this->suggestReason = $suggestReason; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getDurationLabel(): string
    {
        return PriceListItemConst::durationLabel($this->durationTier);
    }
}

