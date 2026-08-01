<?php

declare(strict_types=1);

namespace Sales\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Crm\Service\CustomerService;
use Crm\Service\SiteService;
use Sales\Form\Contract\ContractSaveForm;
use Sales\Form\Contract\ContractSearchForm;
use Sales\Form\Contract\ContractStatusForm;
use Sales\Model\Contract\ContractConst;
use Sales\Model\Contract\ContractMapper;
use Sales\Model\Contract\ContractModel;
use Sales\Model\Quote\QuoteConst;
use Sales\Model\Quote\QuoteMapper;
use Sales\Model\Quote\QuoteModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class ContractService extends AppServiceFactory
{
    private function mapper(): ContractMapper
    {
        return $this->getContainerEntry(ContractMapper::class);
    }

    private function quoteMapper(): QuoteMapper
    {
        return $this->getContainerEntry(QuoteMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    private function customerService(): CustomerService
    {
        return $this->getContainerEntry(CustomerService::class);
    }

    private function siteService(): SiteService
    {
        return $this->getContainerEntry(SiteService::class);
    }

    public function newSaveForm(?ContractModel $existing = null, array $values = []): ContractSaveForm
    {
        $form = new ContractSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): ContractSearchForm
    {
        $form = new ContractSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): ContractStatusForm
    {
        return new ContractStatusForm($this->getContainer());
    }

    public function searchContracts(array $payload = []): Paginator
    {
        $form = new ContractSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new ContractModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($formData['customerId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? ContractConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'desc';

        return $this->mapper()->searchContracts($criteria, $paging);
    }

    public function getContract(int $id): ContractModel
    {
        $item = $this->mapper()->getContract($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function nextStatuses(ContractModel $contract): array
    {
        $allowed = ContractConst::STATUS_TRANSITIONS[(string)$contract->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = ContractConst::statusLabel($status);
        }

        return $result;
    }

    public function saveContract(array $payload = []): ContractModel
    {
        $form = new ContractSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getContract($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if ($existing->getStatus() !== ContractConst::STATUS_DRAFT) {
                throw new ValidationException(['status' => 'Chỉ được sửa hợp đồng đang nháp.']);
            }
        }

        $contractNo = (string)($formData['contractNo'] ?? '');
        if ($mapper->getContractByNo($contractNo, $id) !== null) {
            throw new ValidationException(['contractNo' => 'Số hợp đồng này đã được dùng.']);
        }

        $customerId = (int)($formData['customerId'] ?? 0);
        try {
            $this->customerService()->getCustomer($customerId);
        } catch (NotFoundException) {
            throw new ValidationException(['customerId' => 'Khách hàng không tồn tại.']);
        }

        $siteId = $this->positiveIntOrNull($formData['siteId'] ?? null);
        if ($siteId !== null) {
            try {
                $this->siteService()->getSite($siteId);
            } catch (NotFoundException) {
            throw new ValidationException(['siteId' => 'Công trình không tồn tại.']);
            }
        }

        $quoteId = $this->positiveIntOrNull($formData['quoteId'] ?? null);
        if ($quoteId !== null && $this->quoteMapper()->getQuote($quoteId) === null) {
            throw new ValidationException(['quoteId' => 'Báo giá nguồn không tồn tại.']);
        }

        $before = $existing?->getRespContract();

        $item = $existing ?? new ContractModel();
        $item->setContractNo($contractNo);
        $item->setQuoteId($quoteId);
        $item->setCustomerId($customerId);
        $item->setSiteId($siteId);
        $item->setSignedDate($this->nullIfBlank($formData['signedDate'] ?? null));
        $item->setEffectiveFrom((string)($formData['effectiveFrom'] ?? ''));
        $item->setEffectiveTo((string)($formData['effectiveTo'] ?? ''));
        $item->setStatus($existing?->getStatus() ?? ContractConst::STATUS_DRAFT);
        $item->setTotalAmount((int)($formData['totalAmount'] ?? 0));
        $item->setDepositAmount((int)($formData['depositAmount'] ?? 0));
        $item->setPaymentTermDays((int)($formData['paymentTermDays'] ?? 0));
        $item->setBillingCycle((string)($formData['billingCycle'] ?? ContractConst::BILLING_MONTH));
        $item->setCreditOverrideReason($this->nullIfBlank($formData['creditOverrideReason'] ?? null));
        $item->setTerms($this->nullIfBlank($formData['terms'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveContract($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            ContractMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespContract()
        );

        return $saved;
    }

    public function createFromQuote(int $quoteId, string $contractNo): ContractModel
    {
        $quote = $this->quoteMapper()->getQuote($quoteId);
        if (!$quote instanceof QuoteModel) {
            throw new NotFoundException();
        }
        if ($quote->getStatus() !== QuoteConst::STATUS_APPROVED) {
            throw new ValidationException(['quoteId' => 'Chỉ được tạo hợp đồng từ báo giá đã duyệt.']);
        }
        if ($this->mapper()->getContractByNo($contractNo) !== null) {
            throw new ValidationException(['contractNo' => 'Số hợp đồng này đã được dùng.']);
        }

        $item = new ContractModel();
        $item->setContractNo(strtoupper(trim($contractNo)));
        $item->setQuoteId($quoteId);
        $item->setCustomerId($quote->getCustomerId());
        $item->setSiteId($quote->getSiteId());
        $item->setEffectiveFrom($quote->getRentFrom());
        $item->setEffectiveTo($quote->getRentTo());
        $item->setStatus(ContractConst::STATUS_DRAFT);
        $item->setTotalAmount($quote->getTotalAmount());
        $item->setDepositAmount($quote->getDepositAmount());
        $item->setPaymentTermDays(0);
        $item->setBillingCycle(ContractConst::BILLING_MONTH);
        $item->setTerms($quote->getTerms());
        $item->setCreatedBy($this->currentUserId());
        $item->setUpdatedBy($this->currentUserId());

        $saved = $this->mapper()->saveContract($item);

        $this->auditLog()->write(
            AuditLogModel::ACTION_CREATE,
            ContractMapper::TABLE_NAME,
            $saved->getId(),
            null,
            $saved->getRespContract()
        );

        return $saved;
    }

    public function changeStatus(array $payload = []): ContractModel
    {
        $form = new ContractStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $toStatus = (string)($formData['status'] ?? '');
        $contract = $this->getContract($id);
        $fromStatus = (string)$contract->getStatus();

        if ($fromStatus === $toStatus) {
            return $contract;
        }
        if (!ContractConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển hợp đồng từ "%s" sang "%s".',
                ContractConst::statusLabel($fromStatus),
                ContractConst::statusLabel($toStatus)
            )]);
        }
        if ($toStatus === ContractConst::STATUS_CANCELLED && $this->nullIfBlank($formData['cancelReason'] ?? null) === null) {
            throw new ValidationException(['cancelReason' => 'Bạn cần nhập lý do hủy hợp đồng.']);
        }

        if ($toStatus === ContractConst::STATUS_CANCELLED) {
            $reason = $this->nullIfBlank($formData['cancelReason'] ?? null);
            $before = $contract->getRespContract() + ['cancelReason' => $reason];
            $this->mapper()->deleteContract($id);
            $contract->setStatus($toStatus)->setCancelReason($reason);

            $this->auditLog()->write(
                AuditLogModel::ACTION_DELETE,
                ContractMapper::TABLE_NAME,
                $id,
                $before,
                null
            );

            return $contract;
        }

        $attrs = ['status' => $toStatus];

        $this->mapper()->updateAttrsContract($id, $attrs, $this->currentUserId());
        $updated = $this->getContract($id);

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            ContractMapper::TABLE_NAME,
            $id,
            ['status' => $fromStatus],
            [
                'status'       => $toStatus,
                'cancelReason' => $this->nullIfBlank($formData['cancelReason'] ?? null),
            ]
        );

        return $updated;
    }

    private function formValues(ContractModel $item): array
    {
        return [
            'id'                   => $item->getId(),
            'contractNo'           => $item->getContractNo(),
            'quoteId'              => $item->getQuoteId(),
            'customerId'           => $item->getCustomerId(),
            'siteId'               => $item->getSiteId(),
            'signedDate'           => $item->getSignedDate(),
            'effectiveFrom'        => $item->getEffectiveFrom(),
            'effectiveTo'          => $item->getEffectiveTo(),
            'totalAmount'          => $item->getTotalAmount(),
            'depositAmount'        => $item->getDepositAmount(),
            'paymentTermDays'      => $item->getPaymentTermDays(),
            'billingCycle'         => $item->getBillingCycle(),
            'creditOverrideReason' => $item->getCreditOverrideReason(),
            'terms'                => $item->getTerms(),
        ];
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int)$value;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = $this->intOrNull($value);
        return $value !== null && $value > 0 ? $value : null;
    }
}
