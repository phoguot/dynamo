<?php

declare(strict_types=1);

namespace Crm\Model\Customer;

use Application\Model\AppFormat;
use Application\Model\AppModel;

class CustomerModel extends AppModel
{
    protected ?int $id = null;
    protected ?string $code = null;
    protected ?string $name = null;
    protected ?string $customerType = null;
    protected ?string $taxCode = null;
    protected ?string $idNumber = null;
    protected ?string $address = null;
    protected ?string $phone = null;
    protected ?string $email = null;
    protected ?string $bankAccount = null;
    protected ?int $salesOwnerId = null;
    protected ?bool $creditWarning = null;
    protected ?string $status = null;
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getCustomerType(): ?string { return $this->customerType; }
    public function setCustomerType(?string $customerType): self { $this->customerType = $customerType; return $this; }
    public function getTaxCode(): ?string { return $this->taxCode; }
    public function setTaxCode(?string $taxCode): self { $this->taxCode = $taxCode; return $this; }
    public function getIdNumber(): ?string { return $this->idNumber; }
    public function setIdNumber(?string $idNumber): self { $this->idNumber = $idNumber; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): self { $this->address = $address; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function getBankAccount(): ?string { return $this->bankAccount; }
    public function setBankAccount(?string $bankAccount): self { $this->bankAccount = $bankAccount; return $this; }
    public function getSalesOwnerId(): ?int { return $this->salesOwnerId; }
    public function setSalesOwnerId(?int $salesOwnerId): self { $this->salesOwnerId = $salesOwnerId; return $this; }
    public function getCreditWarning(): ?bool { return $this->creditWarning; }
    public function setCreditWarning(?bool $creditWarning): self { $this->creditWarning = $creditWarning; return $this; }
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

    public function getTypeLabel(): string
    {
        return CustomerConst::typeLabel($this->customerType);
    }

    public function getStatusLabel(): string
    {
        return CustomerConst::statusLabel($this->status);
    }

    public function getMaskedPhone(): ?string
    {
        return self::maskMiddle($this->phone, 3, 2);
    }

    public function getMaskedTaxCode(): ?string
    {
        return self::maskMiddle($this->taxCode, 3, 2);
    }

    public function getMaskedIdNumber(): ?string
    {
        return self::maskMiddle($this->idNumber, 3, 2);
    }

    public function getMaskedBankAccount(): ?string
    {
        return self::maskMiddle($this->bankAccount, 4, 3);
    }

    public function getRespCustomer(): array
    {
        return [
            'id'             => AppFormat::castIntOrNull($this->id),
            'code'           => AppFormat::castStringOrNull($this->code),
            'name'           => AppFormat::castStringOrNull($this->name),
            'customerType'   => ['id' => $this->customerType, 'name' => $this->getTypeLabel()],
            'status'         => ['id' => $this->status, 'name' => $this->getStatusLabel()],
            'phone'          => AppFormat::castStringOrNull($this->getMaskedPhone()),
            'email'          => AppFormat::castStringOrNull($this->email),
            'creditWarning'  => (bool)$this->creditWarning,
        ];
    }

    private static function maskMiddle(?string $value, int $left, int $right): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (strlen($value) <= $left + $right) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, $left) . str_repeat('*', 4) . substr($value, -$right);
    }
}
