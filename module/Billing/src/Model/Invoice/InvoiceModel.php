<?php

declare(strict_types=1);

namespace Billing\Model\Invoice;

use Application\Model\AppModel;

class InvoiceModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $invoiceNo = null;
    protected ?int $customerId = null;
    protected ?int $contractId = null;
    protected ?int $rentalOrderId = null;
    protected ?string $periodFrom = null;
    protected ?string $periodTo = null;
    protected ?string $issueDate = null;
    protected ?string $dueDate = null;
    protected ?string $status = null;
    protected int $rentAmount = 0;
    protected int $surchargeAmount = 0;
    protected int $discountAmount = 0;
    protected int $vatRate = 0;
    protected int $vatAmount = 0;
    protected int $totalAmount = 0;
    protected int $paidAmount = 0;
    protected int $remainAmount = 0;
    protected ?string $voidedAt = null;
    protected ?int $voidedBy = null;
    protected ?string $voidReason = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getInvoiceNo(): ?string { return $this->invoiceNo; }
    public function setInvoiceNo(?string $invoiceNo): self { $this->invoiceNo = $invoiceNo !== null ? strtoupper(trim($invoiceNo)) : null; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getContractId(): ?int { return $this->contractId; }
    public function setContractId(?int $contractId): self { $this->contractId = $contractId; return $this; }
    public function getRentalOrderId(): ?int { return $this->rentalOrderId; }
    public function setRentalOrderId(?int $rentalOrderId): self { $this->rentalOrderId = $rentalOrderId; return $this; }
    public function getPeriodFrom(): ?string { return $this->periodFrom; }
    public function setPeriodFrom(?string $periodFrom): self { $this->periodFrom = $periodFrom; return $this; }
    public function getPeriodTo(): ?string { return $this->periodTo; }
    public function setPeriodTo(?string $periodTo): self { $this->periodTo = $periodTo; return $this; }
    public function getIssueDate(): ?string { return $this->issueDate; }
    public function setIssueDate(?string $issueDate): self { $this->issueDate = $issueDate; return $this; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function setDueDate(?string $dueDate): self { $this->dueDate = $dueDate; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getRentAmount(): int { return $this->rentAmount; }
    public function setRentAmount(int $rentAmount): self { $this->rentAmount = $rentAmount; return $this; }
    public function getSurchargeAmount(): int { return $this->surchargeAmount; }
    public function setSurchargeAmount(int $surchargeAmount): self { $this->surchargeAmount = $surchargeAmount; return $this; }
    public function getDiscountAmount(): int { return $this->discountAmount; }
    public function setDiscountAmount(int $discountAmount): self { $this->discountAmount = $discountAmount; return $this; }
    public function getVatRate(): int { return $this->vatRate; }
    public function setVatRate(int $vatRate): self { $this->vatRate = $vatRate; return $this; }
    public function getVatAmount(): int { return $this->vatAmount; }
    public function setVatAmount(int $vatAmount): self { $this->vatAmount = $vatAmount; return $this; }
    public function getTotalAmount(): int { return $this->totalAmount; }
    public function setTotalAmount(int $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }
    public function getPaidAmount(): int { return $this->paidAmount; }
    public function setPaidAmount(int $paidAmount): self { $this->paidAmount = $paidAmount; return $this; }
    public function getRemainAmount(): int { return $this->remainAmount; }
    public function setRemainAmount(int $remainAmount): self { $this->remainAmount = $remainAmount; return $this; }
    public function getVoidedAt(): ?string { return $this->voidedAt; }
    public function setVoidedAt(?string $voidedAt): self { $this->voidedAt = $voidedAt; return $this; }
    public function getVoidedBy(): ?int { return $this->voidedBy; }
    public function setVoidedBy(?int $voidedBy): self { $this->voidedBy = $voidedBy; return $this; }
    public function getVoidReason(): ?string { return $this->voidReason; }
    public function setVoidReason(?string $voidReason): self { $this->voidReason = $voidReason; return $this; }
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

    public function getStatusLabel(): string { return InvoiceConst::statusLabel($this->status); }
}
