<?php

declare(strict_types=1);

namespace Sales\Model\PriceListItem;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class PriceListItemModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $priceListId = null;
    protected ?int $capacityFrom = null;
    protected ?int $capacityTo = null;
    protected ?string $durationTier = null;
    protected ?int $minDays = null;
    protected ?int $unitPrice = null;
    protected ?int $dailyRate = null;
    protected ?int $deliveryFee = null;
    protected ?int $installFee = null;
    protected ?int $depositAmount = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getPriceListId(): ?int { return $this->priceListId; }
    public function setPriceListId(?int $priceListId): self { $this->priceListId = $priceListId; return $this; }
    public function getCapacityFrom(): ?int { return $this->capacityFrom; }
    public function setCapacityFrom(?int $capacityFrom): self { $this->capacityFrom = $capacityFrom; return $this; }
    public function getCapacityTo(): ?int { return $this->capacityTo; }
    public function setCapacityTo(?int $capacityTo): self { $this->capacityTo = $capacityTo; return $this; }
    public function getDurationTier(): ?string { return $this->durationTier; }
    public function setDurationTier(?string $durationTier): self { $this->durationTier = $durationTier; return $this; }
    public function getMinDays(): ?int { return $this->minDays; }
    public function setMinDays(?int $minDays): self { $this->minDays = $minDays; return $this; }
    public function getUnitPrice(): ?int { return $this->unitPrice; }
    public function setUnitPrice(?int $unitPrice): self { $this->unitPrice = $unitPrice; return $this; }
    public function getDailyRate(): ?int { return $this->dailyRate; }
    public function setDailyRate(?int $dailyRate): self { $this->dailyRate = $dailyRate; return $this; }
    public function getDeliveryFee(): ?int { return $this->deliveryFee; }
    public function setDeliveryFee(?int $deliveryFee): self { $this->deliveryFee = $deliveryFee; return $this; }
    public function getInstallFee(): ?int { return $this->installFee; }
    public function setInstallFee(?int $installFee): self { $this->installFee = $installFee; return $this; }
    public function getDepositAmount(): ?int { return $this->depositAmount; }
    public function setDepositAmount(?int $depositAmount): self { $this->depositAmount = $depositAmount; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getDurationLabel(): string
    {
        return PriceListItemConst::durationLabel($this->durationTier);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRespPriceListItem(): array
    {
        return [
            'id'            => AppFormat::castIntOrNull($this->id),
            'priceListId'   => AppFormat::castIntOrNull($this->priceListId),
            'capacityFrom'  => AppFormat::castIntOrNull($this->capacityFrom),
            'capacityTo'    => AppFormat::castIntOrNull($this->capacityTo),
            'durationTier'  => ['id' => $this->durationTier, 'name' => $this->getDurationLabel()],
            'minDays'       => AppFormat::castIntOrNull($this->minDays),
            'unitPrice'     => AppFormat::castIntOrNull($this->unitPrice),
            'dailyRate'     => AppFormat::castIntOrNull($this->dailyRate),
            'deliveryFee'   => AppFormat::castIntOrNull($this->deliveryFee),
            'installFee'    => AppFormat::castIntOrNull($this->installFee),
            'depositAmount' => AppFormat::castIntOrNull($this->depositAmount),
        ];
    }
}
