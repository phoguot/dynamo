<?php

declare(strict_types=1);

namespace Billing\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Billing\Form\Deposit\DepositSaveForm;
use Billing\Form\Deposit\DepositSearchForm;
use Billing\Model\Deposit\DepositConst;
use Billing\Model\Deposit\DepositMapper;
use Billing\Model\Deposit\DepositModel;
use Crm\Service\CustomerService;
use Rental\Service\RentalOrderService;
use Sales\Service\ContractService;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class DepositService extends AppServiceFactory
{
    private function mapper(): DepositMapper { return $this->getContainerEntry(DepositMapper::class); }
    private function auditLog(): AuditLogService { return $this->getContainerEntry(AuditLogService::class); }

    public function newSearchForm(array $query = []): DepositSearchForm
    {
        $form = new DepositSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newSaveForm(?DepositModel $existing = null, array $values = []): DepositSaveForm
    {
        $form = new DepositSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }
        return $form;
    }

    public function searchDeposits(array $payload = []): Paginator
    {
        $form = new DepositSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $data = $form->getData();
        $criteria = new DepositModel();
        $criteria->addOption('keyword', $this->nullIfBlank($data['keyword'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $criteria->setContractId($this->positiveIntOrNull($data['contractId'] ?? null));
        $criteria->setRentalOrderId($this->positiveIntOrNull($data['rentalOrderId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($data['status'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? DepositConst::SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';

        return $this->mapper()->searchDeposits($criteria, $paging);
    }

    public function getDeposit(int $id): DepositModel
    {
        $item = $this->mapper()->getDeposit($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function saveDeposit(array $payload = []): DepositModel
    {
        $form = new DepositSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $data = $form->getData();
        $id = $this->positiveIntOrNull($data['id'] ?? null);
        $existing = $id !== null ? $this->getDeposit($id) : null;
        $depositNo = (string)($data['depositNo'] ?? '');
        if ($this->mapper()->getDepositByNo($depositNo, $id) !== null) {
            throw new ValidationException(['depositNo' => 'Số phiếu cọc này đã được dùng.']);
        }

        $customerId = (int)($data['customerId'] ?? 0);
        $this->getContainerEntry(CustomerService::class)->getCustomer($customerId);
        $contractId = $this->positiveIntOrNull($data['contractId'] ?? null);
        if ($contractId !== null) {
            $contract = $this->getContainerEntry(ContractService::class)->getContract($contractId);
            if ((int)$contract->getCustomerId() !== $customerId) {
                throw new ValidationException(['contractId' => 'Hợp đồng phải thuộc cùng khách hàng.']);
            }
        }
        $rentalOrderId = $this->positiveIntOrNull($data['rentalOrderId'] ?? null);
        if ($rentalOrderId !== null) {
            $order = $this->getContainerEntry(RentalOrderService::class)->getRentalOrder($rentalOrderId);
            if ((int)$order->getCustomerId() !== $customerId) {
                throw new ValidationException(['rentalOrderId' => 'Đơn thuê phải thuộc cùng khách hàng.']);
            }
        }

        $deductedAmount = max(0, (int)($data['deductedAmount'] ?? 0));
        $deductReason = $this->nullIfBlank($data['deductReason'] ?? null);
        if ($deductedAmount > 0 && $deductReason === null) {
            throw new ValidationException(['deductReason' => 'Cần nhập lý do bù trừ/trừ cọc.']);
        }

        $before = $existing !== null ? $this->depositAuditValues($existing) : null;

        $item = $existing ?? new DepositModel();
        $item->setDepositNo($depositNo);
        $item->setCustomerId($customerId);
        $item->setContractId($contractId);
        $item->setRentalOrderId($rentalOrderId);
        $item->setAmount((int)($data['amount'] ?? 0));
        $item->setReceivedDate((string)($data['receivedDate'] ?? ''));
        $item->setDeductedAmount($deductedAmount);
        $item->setDeductReason($deductReason);
        $item->setRefundedAmount(max(0, (int)($data['refundedAmount'] ?? 0)));
        $item->setRefundedDate($this->nullIfBlank($data['refundedDate'] ?? null));
        $item->setStatus($this->deriveStatus($item));
        $item->setNote($this->nullIfBlank($data['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $this->mapper()->saveDeposit($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            DepositMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->depositAuditValues($saved)
        );

        return $saved;
    }

    private function deriveStatus(DepositModel $item): string
    {
        $deducted = $item->getDeductedAmount();
        $refunded = $item->getRefundedAmount();
        $amount = $item->getAmount();

        if ($deducted <= 0 && $refunded <= 0) {
            return DepositConst::STATUS_HOLDING;
        }
        if ($deducted > 0) {
            return DepositConst::STATUS_OFFSET;
        }
        if ($refunded >= $amount) {
            return DepositConst::STATUS_REFUNDED;
        }

        return DepositConst::STATUS_PARTIALLY_REFUNDED;
    }

    private function formValues(DepositModel $item): array
    {
        return [
            'id'             => $item->getId(),
            'depositNo'      => $item->getDepositNo(),
            'customerId'     => $item->getCustomerId(),
            'contractId'     => $item->getContractId(),
            'rentalOrderId'  => $item->getRentalOrderId(),
            'amount'         => $item->getAmount(),
            'receivedDate'   => $item->getReceivedDate(),
            'deductedAmount' => $item->getDeductedAmount(),
            'deductReason'   => $item->getDeductReason(),
            'refundedAmount' => $item->getRefundedAmount(),
            'refundedDate'   => $item->getRefundedDate(),
            'note'           => $item->getNote(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function depositAuditValues(DepositModel $item): array
    {
        return $this->formValues($item) + [
            'status' => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
        ];
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function intOrNull(mixed $value): ?int { return ($value === null || $value === '') ? null : (int)$value; }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = $this->intOrNull($value);
        return $value !== null && $value > 0 ? $value : null;
    }
}
