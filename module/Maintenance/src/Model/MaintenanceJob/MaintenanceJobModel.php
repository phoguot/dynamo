<?php

declare(strict_types=1);

namespace Maintenance\Model\MaintenanceJob;

use Application\Model\AppModel;

class MaintenanceJobModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $jobNo = null;
    protected ?int $generatorId = null;
    protected ?int $scheduleId = null;
    protected ?string $jobType = null;
    protected ?string $priority = null;
    protected ?string $status = null;
    protected ?string $triggerReason = null;
    protected ?float $triggerHourMeter = null;
    protected ?string $idempotencyKey = null;
    protected ?string $scheduledDate = null;
    protected ?string $startedAt = null;
    protected ?string $completedAt = null;
    protected ?int $assigneeId = null;
    protected int $laborCost = 0;
    protected int $partsCost = 0;
    protected int $totalCost = 0;
    protected ?string $findings = null;
    protected ?string $cancelledAt = null;
    protected ?string $cancelReason = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getJobNo(): ?string { return $this->jobNo; }
    public function setJobNo(?string $jobNo): self { $this->jobNo = $jobNo !== null ? strtoupper(trim($jobNo)) : null; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getScheduleId(): ?int { return $this->scheduleId; }
    public function setScheduleId(?int $scheduleId): self { $this->scheduleId = $scheduleId; return $this; }
    public function getJobType(): ?string { return $this->jobType; }
    public function setJobType(?string $jobType): self { $this->jobType = $jobType; return $this; }
    public function getPriority(): ?string { return $this->priority; }
    public function setPriority(?string $priority): self { $this->priority = $priority; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getTriggerReason(): ?string { return $this->triggerReason; }
    public function setTriggerReason(?string $triggerReason): self { $this->triggerReason = $triggerReason; return $this; }
    public function getTriggerHourMeter(): ?float { return $this->triggerHourMeter; }
    public function setTriggerHourMeter(?float $triggerHourMeter): self { $this->triggerHourMeter = $triggerHourMeter; return $this; }
    public function getIdempotencyKey(): ?string { return $this->idempotencyKey; }
    public function setIdempotencyKey(?string $idempotencyKey): self { $this->idempotencyKey = $idempotencyKey; return $this; }
    public function getScheduledDate(): ?string { return $this->scheduledDate; }
    public function setScheduledDate(?string $scheduledDate): self { $this->scheduledDate = $scheduledDate; return $this; }
    public function getStartedAt(): ?string { return $this->startedAt; }
    public function setStartedAt(?string $startedAt): self { $this->startedAt = $startedAt; return $this; }
    public function getCompletedAt(): ?string { return $this->completedAt; }
    public function setCompletedAt(?string $completedAt): self { $this->completedAt = $completedAt; return $this; }
    public function getAssigneeId(): ?int { return $this->assigneeId; }
    public function setAssigneeId(?int $assigneeId): self { $this->assigneeId = $assigneeId; return $this; }
    public function getLaborCost(): int { return $this->laborCost; }
    public function setLaborCost(int $laborCost): self { $this->laborCost = $laborCost; return $this; }
    public function getPartsCost(): int { return $this->partsCost; }
    public function setPartsCost(int $partsCost): self { $this->partsCost = $partsCost; return $this; }
    public function getTotalCost(): int { return $this->totalCost; }
    public function setTotalCost(int $totalCost): self { $this->totalCost = $totalCost; return $this; }
    public function getFindings(): ?string { return $this->findings; }
    public function setFindings(?string $findings): self { $this->findings = $findings; return $this; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function setCancelledAt(?string $cancelledAt): self { $this->cancelledAt = $cancelledAt; return $this; }
    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function setCancelReason(?string $cancelReason): self { $this->cancelReason = $cancelReason; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getTypeLabel(): string { return MaintenanceJobConst::typeLabel($this->jobType); }
    public function getStatusLabel(): string { return MaintenanceJobConst::statusLabel($this->status); }
    public function getPriorityLabel(): string { return MaintenanceJobConst::priorityLabel($this->priority); }
}
