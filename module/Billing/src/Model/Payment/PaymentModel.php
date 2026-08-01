<?php

declare(strict_types=1);

namespace Billing\Model\Payment;

use Application\Model\AppModel;

class PaymentModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $paymentNo = null;
    protected ?int $invoiceId = null;
    protected ?int $customerId = null;
    protected int $amount = 0;
    protected ?string $paymentDate = null;
    protected ?string $method = null;
    protected ?string $referenceNo = null;
    protected ?int $attachmentId = null;
    protected ?string $status = null;
    protected ?string $cancelledAt = null;
    protected ?int $cancelledBy = null;
    protected ?string $cancelReason = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getPaymentNo(): ?string { return $this->paymentNo; }
    public function setPaymentNo(?string $paymentNo): self { $this->paymentNo = $paymentNo !== null ? strtoupper(trim($paymentNo)) : null; return $this; }
    public function getInvoiceId(): ?int { return $this->invoiceId; }
    public function setInvoiceId(?int $invoiceId): self { $this->invoiceId = $invoiceId; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getAmount(): int { return $this->amount; }
    public function setAmount(int $amount): self { $this->amount = $amount; return $this; }
    public function getPaymentDate(): ?string { return $this->paymentDate; }
    public function setPaymentDate(?string $paymentDate): self { $this->paymentDate = $paymentDate; return $this; }
    public function getMethod(): ?string { return $this->method; }
    public function setMethod(?string $method): self { $this->method = $method; return $this; }
    public function getReferenceNo(): ?string { return $this->referenceNo; }
    public function setReferenceNo(?string $referenceNo): self { $this->referenceNo = $referenceNo; return $this; }
    public function getAttachmentId(): ?int { return $this->attachmentId; }
    public function setAttachmentId(?int $attachmentId): self { $this->attachmentId = $attachmentId; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function setCancelledAt(?string $cancelledAt): self { $this->cancelledAt = $cancelledAt; return $this; }
    public function getCancelledBy(): ?int { return $this->cancelledBy; }
    public function setCancelledBy(?int $cancelledBy): self { $this->cancelledBy = $cancelledBy; return $this; }
    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function setCancelReason(?string $cancelReason): self { $this->cancelReason = $cancelReason; return $this; }
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

    public function getMethodLabel(): string { return PaymentConst::methodLabel($this->method); }
    public function getStatusLabel(): string { return PaymentConst::statusLabel($this->status); }
}
