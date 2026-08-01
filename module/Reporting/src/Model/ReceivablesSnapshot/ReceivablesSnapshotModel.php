<?php

declare(strict_types=1);

namespace Reporting\Model\ReceivablesSnapshot;

use Application\Model\AppModel;

class ReceivablesSnapshotModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $snapshotDate = null;
    protected ?int $customerId = null;
    protected int $bucket0To30 = 0;
    protected int $bucket31To60 = 0;
    protected int $bucket61To90 = 0;
    protected int $bucketOver90 = 0;
    protected int $totalDebt = 0;
    protected float $dsoDays = 0.0;
    protected ?string $computedAt = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getSnapshotDate(): ?string { return $this->snapshotDate; }
    public function setSnapshotDate(?string $snapshotDate): self { $this->snapshotDate = $snapshotDate; return $this; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): self { $this->customerId = $customerId; return $this; }
    public function getBucket0To30(): int { return $this->bucket0To30; }
    public function setBucket0To30(int $bucket0To30): self { $this->bucket0To30 = $bucket0To30; return $this; }
    public function getBucket31To60(): int { return $this->bucket31To60; }
    public function setBucket31To60(int $bucket31To60): self { $this->bucket31To60 = $bucket31To60; return $this; }
    public function getBucket61To90(): int { return $this->bucket61To90; }
    public function setBucket61To90(int $bucket61To90): self { $this->bucket61To90 = $bucket61To90; return $this; }
    public function getBucketOver90(): int { return $this->bucketOver90; }
    public function setBucketOver90(int $bucketOver90): self { $this->bucketOver90 = $bucketOver90; return $this; }
    public function getTotalDebt(): int { return $this->totalDebt; }
    public function setTotalDebt(int $totalDebt): self { $this->totalDebt = $totalDebt; return $this; }
    public function getDsoDays(): float { return $this->dsoDays; }
    public function setDsoDays(float $dsoDays): self { $this->dsoDays = $dsoDays; return $this; }
    public function getComputedAt(): ?string { return $this->computedAt; }
    public function setComputedAt(?string $computedAt): self { $this->computedAt = $computedAt; return $this; }
}
