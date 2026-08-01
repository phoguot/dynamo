<?php

declare(strict_types=1);

namespace Dispatch\Model\Vehicle;

use Application\Model\AppModel;

class VehicleModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $code = null;
    protected ?string $plateNumber = null;
    protected ?string $vehicleType = null;
    protected ?int $capacityKg = null;
    protected ?int $driverId = null;
    protected ?string $status = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code !== null ? strtoupper(trim($code)) : null; return $this; }
    public function getPlateNumber(): ?string { return $this->plateNumber; }
    public function setPlateNumber(?string $plateNumber): self { $this->plateNumber = $plateNumber !== null ? strtoupper(trim($plateNumber)) : null; return $this; }
    public function getVehicleType(): ?string { return $this->vehicleType; }
    public function setVehicleType(?string $vehicleType): self { $this->vehicleType = $vehicleType; return $this; }
    public function getCapacityKg(): ?int { return $this->capacityKg; }
    public function setCapacityKg(?int $capacityKg): self { $this->capacityKg = $capacityKg; return $this; }
    public function getDriverId(): ?int { return $this->driverId; }
    public function setDriverId(?int $driverId): self { $this->driverId = $driverId; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
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

    public function getTypeLabel(): string
    {
        return VehicleConst::typeLabel($this->vehicleType);
    }

    public function getStatusLabel(): string
    {
        return VehicleConst::statusLabel($this->status);
    }
}
