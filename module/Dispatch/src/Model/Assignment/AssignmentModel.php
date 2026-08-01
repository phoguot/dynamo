<?php

declare(strict_types=1);

namespace Dispatch\Model\Assignment;

use Application\Model\AppModel;

class AssignmentModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $jobId = null;
    protected ?int $userId = null;
    protected ?string $roleInJob = null;
    protected ?bool $isLead = null;
    protected ?string $acceptedAt = null;
    protected ?string $createdAt = null;
    protected ?int $createdBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getJobId(): ?int { return $this->jobId; }
    public function setJobId(?int $jobId): self { $this->jobId = $jobId; return $this; }
    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }
    public function getRoleInJob(): ?string { return $this->roleInJob; }
    public function setRoleInJob(?string $roleInJob): self { $this->roleInJob = $roleInJob; return $this; }
    public function getIsLead(): ?bool { return $this->isLead; }
    public function setIsLead(?bool $isLead): self { $this->isLead = $isLead; return $this; }
    public function getAcceptedAt(): ?string { return $this->acceptedAt; }
    public function setAcceptedAt(?string $acceptedAt): self { $this->acceptedAt = $acceptedAt; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }

    public function getRoleLabel(): string
    {
        return AssignmentConst::roleLabel($this->roleInJob);
    }
}
