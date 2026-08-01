<?php

declare(strict_types=1);

namespace Dispatch\Model\DispatchJob;

use Application\Model\AppModel;

class DispatchJobModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $jobNo = null;
    protected ?string $jobType = null;
    protected ?int $rentalOrderId = null;
    protected ?int $generatorId = null;
    protected ?int $newGeneratorId = null;
    protected ?int $siteId = null;
    protected ?int $vehicleId = null;
    protected ?string $scheduledAt = null;
    protected ?string $departedAt = null;
    protected ?string $arrivedAt = null;
    protected ?string $completedAt = null;
    protected ?string $status = null;
    protected ?string $failReason = null;
    protected ?string $feeBearer = null;
    protected ?string $priority = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getJobNo(): ?string { return $this->jobNo; }
    public function setJobNo(?string $jobNo): self { $this->jobNo = $jobNo !== null ? strtoupper(trim($jobNo)) : null; return $this; }
    public function getJobType(): ?string { return $this->jobType; }
    public function setJobType(?string $jobType): self { $this->jobType = $jobType; return $this; }
    public function getRentalOrderId(): ?int { return $this->rentalOrderId; }
    public function setRentalOrderId(?int $rentalOrderId): self { $this->rentalOrderId = $rentalOrderId; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getNewGeneratorId(): ?int { return $this->newGeneratorId; }
    public function setNewGeneratorId(?int $newGeneratorId): self { $this->newGeneratorId = $newGeneratorId; return $this; }
    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }
    public function getVehicleId(): ?int { return $this->vehicleId; }
    public function setVehicleId(?int $vehicleId): self { $this->vehicleId = $vehicleId; return $this; }
    public function getScheduledAt(): ?string { return $this->scheduledAt; }
    public function setScheduledAt(?string $scheduledAt): self { $this->scheduledAt = $scheduledAt; return $this; }
    public function getDepartedAt(): ?string { return $this->departedAt; }
    public function setDepartedAt(?string $departedAt): self { $this->departedAt = $departedAt; return $this; }
    public function getArrivedAt(): ?string { return $this->arrivedAt; }
    public function setArrivedAt(?string $arrivedAt): self { $this->arrivedAt = $arrivedAt; return $this; }
    public function getCompletedAt(): ?string { return $this->completedAt; }
    public function setCompletedAt(?string $completedAt): self { $this->completedAt = $completedAt; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getFailReason(): ?string { return $this->failReason; }
    public function setFailReason(?string $failReason): self { $this->failReason = $failReason; return $this; }
    public function getFeeBearer(): ?string { return $this->feeBearer; }
    public function setFeeBearer(?string $feeBearer): self { $this->feeBearer = $feeBearer; return $this; }
    public function getPriority(): ?string { return $this->priority; }
    public function setPriority(?string $priority): self { $this->priority = $priority; return $this; }
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

    public function getTypeLabel(): string { return DispatchJobConst::typeLabel($this->jobType); }
    public function getStatusLabel(): string { return DispatchJobConst::statusLabel($this->status); }
    public function getPriorityLabel(): string { return DispatchJobConst::priorityLabel($this->priority); }
    public function getFeeBearerLabel(): string { return DispatchJobConst::feeBearerLabel($this->feeBearer); }
}
