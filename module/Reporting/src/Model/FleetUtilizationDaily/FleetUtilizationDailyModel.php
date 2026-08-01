<?php

declare(strict_types=1);

namespace Reporting\Model\FleetUtilizationDaily;

use Application\Model\AppModel;

class FleetUtilizationDailyModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $reportDate = null;
    protected ?string $warehouseCode = null;
    protected int $totalGenerators = 0;
    protected int $activeGenerators = 0;
    protected int $rentedCount = 0;
    protected int $availableCount = 0;
    protected int $heldCount = 0;
    protected int $transitCount = 0;
    protected int $maintenanceCount = 0;
    protected int $repairCount = 0;
    protected int $retiredCount = 0;
    protected float $utilizationRate = 0.0;
    protected ?string $computedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getReportDate(): ?string { return $this->reportDate; }
    public function setReportDate(?string $reportDate): self { $this->reportDate = $reportDate; return $this; }
    public function getWarehouseCode(): ?string { return $this->warehouseCode; }
    public function setWarehouseCode(?string $warehouseCode): self { $this->warehouseCode = $warehouseCode !== null ? strtoupper(trim($warehouseCode)) : null; return $this; }
    public function getTotalGenerators(): int { return $this->totalGenerators; }
    public function setTotalGenerators(int $totalGenerators): self { $this->totalGenerators = $totalGenerators; return $this; }
    public function getActiveGenerators(): int { return $this->activeGenerators; }
    public function setActiveGenerators(int $activeGenerators): self { $this->activeGenerators = $activeGenerators; return $this; }
    public function getRentedCount(): int { return $this->rentedCount; }
    public function setRentedCount(int $rentedCount): self { $this->rentedCount = $rentedCount; return $this; }
    public function getAvailableCount(): int { return $this->availableCount; }
    public function setAvailableCount(int $availableCount): self { $this->availableCount = $availableCount; return $this; }
    public function getHeldCount(): int { return $this->heldCount; }
    public function setHeldCount(int $heldCount): self { $this->heldCount = $heldCount; return $this; }
    public function getTransitCount(): int { return $this->transitCount; }
    public function setTransitCount(int $transitCount): self { $this->transitCount = $transitCount; return $this; }
    public function getMaintenanceCount(): int { return $this->maintenanceCount; }
    public function setMaintenanceCount(int $maintenanceCount): self { $this->maintenanceCount = $maintenanceCount; return $this; }
    public function getRepairCount(): int { return $this->repairCount; }
    public function setRepairCount(int $repairCount): self { $this->repairCount = $repairCount; return $this; }
    public function getRetiredCount(): int { return $this->retiredCount; }
    public function setRetiredCount(int $retiredCount): self { $this->retiredCount = $retiredCount; return $this; }
    public function getUtilizationRate(): float { return $this->utilizationRate; }
    public function setUtilizationRate(float $utilizationRate): self { $this->utilizationRate = $utilizationRate; return $this; }
    public function getComputedAt(): ?string { return $this->computedAt; }
    public function setComputedAt(?string $computedAt): self { $this->computedAt = $computedAt; return $this; }

    public function getStatusCountTotal(): int
    {
        return $this->availableCount
            + $this->heldCount
            + $this->transitCount
            + $this->rentedCount
            + $this->maintenanceCount
            + $this->repairCount
            + $this->retiredCount;
    }
}
