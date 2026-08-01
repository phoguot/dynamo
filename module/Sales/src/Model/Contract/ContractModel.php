<?php

declare(strict_types=1);

namespace Sales\Model\Contract;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class ContractModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $contractNo = null;
    protected ?int $quoteId = null;
    protected ?int $customerId = null;
    protected ?int $siteId = null;
    protected ?string $signedDate = null;
    protected ?string $effectiveFrom = null;
    protected ?string $effectiveTo = null;
    protected ?string $status = null;
    protected ?int $totalAmount = null;
    protected ?int $depositAmount = null;
    protected ?int $paymentTermDays = null;
    protected ?string $billingCycle = null;
    protected ?int $creditOverrideBy = null;
    protected ?string $creditOverrideReason = null;
    protected ?string $terms = null;
    protected ?string $cancelledAt = null;
    protected ?int $cancelledBy = null;
    protected ?string $cancelReason = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getContractNo(): ?string { return $this->contractNo; }
    public function setContractNo(?string $contractNo): self { $this->contractNo = $contractNo; return $this; }
    public function getQuoteId(): ?int { return $this->quoteId; }
    public function setQuoteId(?int $quoteId): self { $this->quoteId = $quoteId; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getSiteId(): ?int { return $this->siteId; }
    public function setSiteId(?int $siteId): self { $this->siteId = $siteId; return $this; }
    public function getSignedDate(): ?string { return $this->signedDate; }
    public function setSignedDate(?string $signedDate): self { $this->signedDate = $signedDate; return $this; }
    public function getEffectiveFrom(): ?string { return $this->effectiveFrom; }
    public function setEffectiveFrom(?string $effectiveFrom): self { $this->effectiveFrom = $effectiveFrom; return $this; }
    public function getEffectiveTo(): ?string { return $this->effectiveTo; }
    public function setEffectiveTo(?string $effectiveTo): self { $this->effectiveTo = $effectiveTo; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getTotalAmount(): ?int { return $this->totalAmount; }
    public function setTotalAmount(?int $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }
    public function getDepositAmount(): ?int { return $this->depositAmount; }
    public function setDepositAmount(?int $depositAmount): self { $this->depositAmount = $depositAmount; return $this; }
    public function getPaymentTermDays(): ?int { return $this->paymentTermDays; }
    public function setPaymentTermDays(?int $paymentTermDays): self { $this->paymentTermDays = $paymentTermDays; return $this; }
    public function getBillingCycle(): ?string { return $this->billingCycle; }
    public function setBillingCycle(?string $billingCycle): self { $this->billingCycle = $billingCycle; return $this; }
    public function getCreditOverrideBy(): ?int { return $this->creditOverrideBy; }
    public function setCreditOverrideBy(?int $creditOverrideBy): self { $this->creditOverrideBy = $creditOverrideBy; return $this; }
    public function getCreditOverrideReason(): ?string { return $this->creditOverrideReason; }
    public function setCreditOverrideReason(?string $creditOverrideReason): self { $this->creditOverrideReason = $creditOverrideReason; return $this; }
    public function getTerms(): ?string { return $this->terms; }
    public function setTerms(?string $terms): self { $this->terms = $terms; return $this; }
    public function getCancelledAt(): ?string { return $this->cancelledAt; }
    public function setCancelledAt(?string $cancelledAt): self { $this->cancelledAt = $cancelledAt; return $this; }
    public function getCancelledBy(): ?int { return $this->cancelledBy; }
    public function setCancelledBy(?int $cancelledBy): self { $this->cancelledBy = $cancelledBy; return $this; }
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

    public function getStatusLabel(): string
    {
        return ContractConst::statusLabel($this->status);
    }

    public function getBillingCycleLabel(): string
    {
        return ContractConst::billingLabel($this->billingCycle);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRespContract(): array
    {
        return [
            'id'            => AppFormat::castIntOrNull($this->id),
            'contractNo'    => AppFormat::castStringOrNull($this->contractNo),
            'quoteId'       => AppFormat::castIntOrNull($this->quoteId),
            'customerId'    => AppFormat::castIntOrNull($this->customerId),
            'siteId'        => AppFormat::castIntOrNull($this->siteId),
            'status'        => ['id' => $this->status, 'name' => $this->getStatusLabel()],
            'effectiveFrom' => AppFormat::castStringOrNull($this->effectiveFrom),
            'effectiveTo'   => AppFormat::castStringOrNull($this->effectiveTo),
            'totalAmount'   => AppFormat::castIntOrNull($this->totalAmount),
            'depositAmount' => AppFormat::castIntOrNull($this->depositAmount),
            'billingCycle'  => ['id' => $this->billingCycle, 'name' => $this->getBillingCycleLabel()],
        ];
    }
}
