<?php

declare(strict_types=1);

namespace Billing\Model\Deposit;

use Application\Model\AppModel;

class DepositModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $depositNo = null;
    protected ?int $customerId = null;
    protected ?int $contractId = null;
    protected ?int $rentalOrderId = null;
    protected int $amount = 0;
    protected ?string $receivedDate = null;
    protected int $deductedAmount = 0;
    protected ?string $deductReason = null;
    protected int $refundedAmount = 0;
    protected ?string $refundedDate = null;
    protected ?string $status = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getDepositNo(): ?string { return $this->depositNo; }
    public function setDepositNo(?string $depositNo): self { $this->depositNo = $depositNo !== null ? strtoupper(trim($depositNo)) : null; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getContractId(): ?int { return $this->contractId; }
    public function setContractId(?int $contractId): self { $this->contractId = $contractId; return $this; }
    public function getRentalOrderId(): ?int { return $this->rentalOrderId; }
    public function setRentalOrderId(?int $rentalOrderId): self { $this->rentalOrderId = $rentalOrderId; return $this; }
    public function getAmount(): int { return $this->amount; }
    public function setAmount(int $amount): self { $this->amount = $amount; return $this; }
    public function getReceivedDate(): ?string { return $this->receivedDate; }
    public function setReceivedDate(?string $receivedDate): self { $this->receivedDate = $receivedDate; return $this; }
    public function getDeductedAmount(): int { return $this->deductedAmount; }
    public function setDeductedAmount(int $deductedAmount): self { $this->deductedAmount = $deductedAmount; return $this; }
    public function getDeductReason(): ?string { return $this->deductReason; }
    public function setDeductReason(?string $deductReason): self { $this->deductReason = $deductReason; return $this; }
    public function getRefundedAmount(): int { return $this->refundedAmount; }
    public function setRefundedAmount(int $refundedAmount): self { $this->refundedAmount = $refundedAmount; return $this; }
    public function getRefundedDate(): ?string { return $this->refundedDate; }
    public function setRefundedDate(?string $refundedDate): self { $this->refundedDate = $refundedDate; return $this; }
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

    public function getStatusLabel(): string { return DepositConst::statusLabel($this->status); }
}
