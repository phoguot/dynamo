<?php

declare(strict_types=1);

namespace Billing\Model\CreditLimit;

use Application\Model\AppModel;

class CreditLimitModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $customerId = null;
    protected int $creditLimit = 0;
    protected int $currentDebt = 0;
    protected int $overdueAmount = 0;
    protected ?int $isBlocked = null;
    protected ?string $lastCheckedAt = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getCreditLimit(): int { return $this->creditLimit; }
    public function setCreditLimit(int $creditLimit): self { $this->creditLimit = $creditLimit; return $this; }
    public function getCurrentDebt(): int { return $this->currentDebt; }
    public function setCurrentDebt(int $currentDebt): self { $this->currentDebt = $currentDebt; return $this; }
    public function getOverdueAmount(): int { return $this->overdueAmount; }
    public function setOverdueAmount(int $overdueAmount): self { $this->overdueAmount = $overdueAmount; return $this; }
    public function getIsBlocked(): ?int { return $this->isBlocked; }
    public function setIsBlocked(?int $isBlocked): self { $this->isBlocked = $isBlocked; return $this; }
    public function getLastCheckedAt(): ?string { return $this->lastCheckedAt; }
    public function setLastCheckedAt(?string $lastCheckedAt): self { $this->lastCheckedAt = $lastCheckedAt; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function isBlocked(): bool { return (bool)$this->isBlocked; }
}
