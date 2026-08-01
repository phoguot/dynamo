<?php

declare(strict_types=1);

namespace Rental\Model\RentalOrder;

use Application\Model\AppModel;

class RentalOrderModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $orderNo = null;
    protected ?int $contractId = null;
    protected ?int $customerId = null;
    protected ?int $siteId = null;
    protected ?int $generatorId = null;
    protected ?string $startDate = null;
    protected ?string $expectedEndDate = null;
    protected ?string $actualEndDate = null;
    protected ?string $status = null;
    protected ?float $startHourMeter = null;
    protected ?float $endHourMeter = null;
    protected ?string $handoverAt = null;
    protected ?string $recoveredAt = null;
    protected ?int $unitPrice = null;
    protected ?string $durationTier = null;
    protected ?bool $withOperator = null;
    protected ?int $extendedTimes = null;
    protected ?string $settledAt = null;
    protected ?string $cancelledAt = null;
    protected ?int $cancelledBy = null;
    protected ?string $cancelReason = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getOrderNo(): ?string { return $this->orderNo; }
    public function setOrderNo(?string $orderNo): self { $this->orderNo = $orderNo; return $this; }
    public function getContractId(): ?int { return $this->contractId; }
    public function setContractId(?int $contractId): self { $this->contractId = $contractId; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getStartDate(): ?string { return $this->startDate; }
    public function setStartDate(?string $startDate): self { $this->startDate = $startDate; return $this; }
    public function getExpectedEndDate(): ?string { return $this->expectedEndDate; }
    public function setExpectedEndDate(?string $expectedEndDate): self { $this->expectedEndDate = $expectedEndDate; return $this; }
    public function getActualEndDate(): ?string { return $this->actualEndDate; }
    public function setActualEndDate(?string $actualEndDate): self { $this->actualEndDate = $actualEndDate; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getStartHourMeter(): ?float { return $this->startHourMeter; }
    public function setStartHourMeter(?float $startHourMeter): self { $this->startHourMeter = $startHourMeter; return $this; }
    public function getEndHourMeter(): ?float { return $this->endHourMeter; }
    public function setEndHourMeter(?float $endHourMeter): self { $this->endHourMeter = $endHourMeter; return $this; }
    public function getHandoverAt(): ?string { return $this->handoverAt; }
    public function setHandoverAt(?string $handoverAt): self { $this->handoverAt = $handoverAt; return $this; }
    public function getRecoveredAt(): ?string { return $this->recoveredAt; }
    public function setRecoveredAt(?string $recoveredAt): self { $this->recoveredAt = $recoveredAt; return $this; }
    public function getUnitPrice(): ?int { return $this->unitPrice; }
    public function setUnitPrice(?int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getDurationTier(): ?string { return $this->durationTier; }
    public function setDurationTier(?string $durationTier): self { $this->durationTier = $durationTier; return $this; }
    public function getWithOperator(): ?bool { return $this->withOperator; }
    public function setWithOperator(?bool $withOperator): self { $this->withOperator = $withOperator; return $this; }
    public function getExtendedTimes(): ?int { return $this->extendedTimes; }
    public function setExtendedTimes(?int $extendedTimes): self { $this->extendedTimes = $extendedTimes; return $this; }
    public function getSettledAt(): ?string { return $this->settledAt; }
    public function setSettledAt(?string $settledAt): self { $this->settledAt = $settledAt; return $this; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function setCancelledAt(?string $cancelledAt): self { $this->cancelledAt = $cancelledAt; return $this; }
    public function getCancelledBy(): ?int { return $this->cancelledBy; }
    public function setCancelledBy(?int $cancelledBy): self { $this->cancelledBy = $cancelledBy; return $this; }
    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function setCancelReason(?string $cancelReason): self { $this->cancelReason = $cancelReason; return $this; }
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

    public function getStatusLabel(): string
    {
        return RentalOrderConst::statusLabel($this->status);
    }
}

