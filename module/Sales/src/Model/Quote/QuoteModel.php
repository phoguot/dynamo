<?php

declare(strict_types=1);

namespace Sales\Model\Quote;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class QuoteModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $quoteNo = null;
    protected ?int $customerId = null;
    protected ?int $siteId = null;
    protected ?int $priceListId = null;
    protected ?string $rentFrom = null;
    protected ?string $rentTo = null;
    protected ?string $status = null;
    protected ?string $validUntil = null;
    protected ?int $rentAmount = null;
    protected ?int $deliveryFee = null;
    protected ?int $installFee = null;
    protected ?int $otherFee = null;
    protected ?int $discountAmount = null;
    protected ?int $vatRate = null;
    protected ?int $vatAmount = null;
    protected ?int $totalAmount = null;
    protected ?int $depositAmount = null;
    protected ?string $submittedAt = null;
    protected ?int $approvedBy = null;
    protected ?string $approvedAt = null;
    protected ?string $rejectReason = null;
    protected ?string $terms = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getQuoteNo(): ?string { return $this->quoteNo; }
    public function setQuoteNo(?string $quoteNo): self { $this->quoteNo = $quoteNo; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }
    public function getPriceListId(): ?int { return $this->priceListId; }
    public function setPriceListId(?int $priceListId): self { $this->priceListId = $priceListId; return $this; }
    public function getRentFrom(): ?string { return $this->rentFrom; }
    public function setRentFrom(?string $rentFrom): self { $this->rentFrom = $rentFrom; return $this; }
    public function getRentTo(): ?string { return $this->rentTo; }
    public function setRentTo(?string $rentTo): self { $this->rentTo = $rentTo; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getValidUntil(): ?string { return $this->validUntil; }
    public function setValidUntil(?string $validUntil): self { $this->validUntil = $validUntil; return $this; }
    public function getRentAmount(): ?int { return $this->rentAmount; }
    public function setRentAmount(?int $rentAmount): self { $this->rentAmount = $rentAmount; return $this; }
    public function getDeliveryFee(): ?int { return $this->deliveryFee; }
    public function setDeliveryFee(?int $deliveryFee): self { $this->deliveryFee = $deliveryFee; return $this; }
    public function getInstallFee(): ?int { return $this->installFee; }
    public function setInstallFee(?int $installFee): self { $this->installFee = $installFee; return $this; }
    public function getOtherFee(): ?int { return $this->otherFee; }
    public function setOtherFee(?int $otherFee): self { $this->otherFee = $otherFee; return $this; }
    public function getDiscountAmount(): ?int { return $this->discountAmount; }
    public function setDiscountAmount(?int $discountAmount): self { $this->discountAmount = $discountAmount; return $this; }
    public function getVatRate(): ?int { return $this->vatRate; }
    public function setVatRate(?int $vatRate): self { $this->vatRate = $vatRate; return $this; }
    public function getVatAmount(): ?int { return $this->vatAmount; }
    public function setVatAmount(?int $vatAmount): self { $this->vatAmount = $vatAmount; return $this; }
    public function getTotalAmount(): ?int { return $this->totalAmount; }
    public function setTotalAmount(?int $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }
    public function getDepositAmount(): ?int { return $this->depositAmount; }
    public function setDepositAmount(?int $depositAmount): self { $this->depositAmount = $depositAmount; return $this; }
    public function getSubmittedAt(): ?string { return $this->submittedAt; }
    public function setSubmittedAt(?string $submittedAt): self { $this->submittedAt = $submittedAt; return $this; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function setApprovedBy(?int $approvedBy): self { $this->approvedBy = $approvedBy; return $this; }
    public function getApprovedAt(): ?string { return $this->approvedAt; }
    public function setApprovedAt(?string $approvedAt): self { $this->approvedAt = $approvedAt; return $this; }
    public function getRejectReason(): ?string { return $this->rejectReason; }
    public function setRejectReason(?string $rejectReason): self { $this->rejectReason = $rejectReason; return $this; }
    public function getTerms(): ?string { return $this->terms; }
    public function setTerms(?string $terms): self { $this->terms = $terms; return $this; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function setUpdatedBy(?int $updatedBy): self { $this->updatedBy = $updatedBy; return $this; }

    public function getStatusLabel(): string
    {
        return QuoteConst::statusLabel($this->status);
    }

    public function getRespQuote(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'quoteNo'     => AppFormat::castStringOrNull($this->quoteNo),
            'customerId'  => AppFormat::castIntOrNull($this->customerId),
            'status'      => ['id' => $this->status, 'name' => $this->getStatusLabel()],
            'totalAmount' => AppFormat::castIntOrNull($this->totalAmount),
        ];
    }
}

