<?php

declare(strict_types=1);

namespace Maintenance\Model\Schedule;

use Application\Model\AppModel;

class ScheduleModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $generatorId = null;
    protected ?string $scheduleType = null;
    protected ?float $intervalHours = null;
    protected ?int $intervalDays = null;
    protected ?float $lastServiceHour = null;
    protected ?string $lastServiceDate = null;
    protected ?float $nextDueHour = null;
    protected ?string $nextDueDate = null;
    protected ?int $isActive = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getGeneratorId(): ?int { return $this->generatorId; }
    public function setGeneratorId(?int $generatorId): self { $this->generatorId = $generatorId; return $this; }
    public function getScheduleType(): ?string { return $this->scheduleType; }
    public function setScheduleType(?string $scheduleType): self { $this->scheduleType = $scheduleType; return $this; }
    public function getIntervalHours(): ?float { return $this->intervalHours; }
    public function setIntervalHours(?float $intervalHours): self { $this->intervalHours = $intervalHours; return $this; }
    public function getIntervalDays(): ?int { return $this->intervalDays; }
    public function setIntervalDays(?int $intervalDays): self { $this->intervalDays = $intervalDays; return $this; }
    public function getLastServiceHour(): ?float { return $this->lastServiceHour; }
    public function setLastServiceHour(?float $lastServiceHour): self { $this->lastServiceHour = $lastServiceHour; return $this; }
    public function getLastServiceDate(): ?string { return $this->lastServiceDate; }
    public function setLastServiceDate(?string $lastServiceDate): self { $this->lastServiceDate = $lastServiceDate; return $this; }
    public function getNextDueHour(): ?float { return $this->nextDueHour; }
    public function setNextDueHour(?float $nextDueHour): self { $this->nextDueHour = $nextDueHour; return $this; }
    public function getNextDueDate(): ?string { return $this->nextDueDate; }
    public function setNextDueDate(?string $nextDueDate): self { $this->nextDueDate = $nextDueDate; return $this; }
    public function getIsActive(): ?int { return $this->isActive; }
    public function setIsActive(?int $isActive): self { $this->isActive = $isActive; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getTypeLabel(): string { return ScheduleConst::typeLabel($this->scheduleType); }
    public function isActive(): bool { return (bool)$this->isActive; }
}
