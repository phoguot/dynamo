<?php

declare(strict_types=1);

namespace Reporting\Model\RevenueMonthly;

use Application\Model\AppModel;

class RevenueMonthlyModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $periodYear = null;
    protected ?int $periodMonth = null;
    protected ?int $customerId = null;
    protected int $invoicedAmount = 0;
    protected int $collectedAmount = 0;
    protected int $outstandingAmount = 0;
    protected int $overdueAmount = 0;
    protected int $orderCount = 0;
    protected ?string $computedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getPeriodYear(): ?int { return $this->periodYear; }
    public function setPeriodYear(?int $periodYear): self { $this->periodYear = $periodYear; return $this; }
    public function getPeriodMonth(): ?int { return $this->periodMonth; }
    public function setPeriodMonth(?int $periodMonth): self { $this->periodMonth = $periodMonth; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getInvoicedAmount(): int { return $this->invoicedAmount; }
    public function setInvoicedAmount(int $invoicedAmount): self { $this->invoicedAmount = $invoicedAmount; return $this; }
    public function getCollectedAmount(): int { return $this->collectedAmount; }
    public function setCollectedAmount(int $collectedAmount): self { $this->collectedAmount = $collectedAmount; return $this; }
    public function getOutstandingAmount(): int { return $this->outstandingAmount; }
    public function setOutstandingAmount(int $outstandingAmount): self { $this->outstandingAmount = $outstandingAmount; return $this; }
    public function getOverdueAmount(): int { return $this->overdueAmount; }
    public function setOverdueAmount(int $overdueAmount): self { $this->overdueAmount = $overdueAmount; return $this; }
    public function getOrderCount(): int { return $this->orderCount; }
    public function setOrderCount(int $orderCount): self { $this->orderCount = $orderCount; return $this; }
    public function getComputedAt(): ?string { return $this->computedAt; }
    public function setComputedAt(?string $computedAt): self { $this->computedAt = $computedAt; return $this; }

    public function getCollectionRate(): float
    {
        if ($this->invoicedAmount <= 0) {
            return 0.0;
        }

        return round($this->collectedAmount * 100 / $this->invoicedAmount, 2);
    }
}
