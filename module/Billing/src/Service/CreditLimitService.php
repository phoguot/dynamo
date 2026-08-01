<?php

declare(strict_types=1);

namespace Billing\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Billing\Form\CreditLimit\CreditLimitSaveForm;
use Billing\Form\CreditLimit\CreditLimitSearchForm;
use Billing\Model\CreditLimit\CreditLimitConst;
use Billing\Model\CreditLimit\CreditLimitMapper;
use Billing\Model\CreditLimit\CreditLimitModel;
use Billing\Model\Invoice\InvoiceMapper;
use Crm\Service\CustomerService;
use Platform\Service\OutboxEventService;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class CreditLimitService extends AppServiceFactory
{
    private function mapper(): CreditLimitMapper { return $this->getContainerEntry(CreditLimitMapper::class); }
    private function invoiceMapper(): InvoiceMapper { return $this->getContainerEntry(InvoiceMapper::class); }
    private function auditLog(): AuditLogService { return $this->getContainerEntry(AuditLogService::class); }

    public function newSearchForm(array $query = []): CreditLimitSearchForm
    {
        $form = new CreditLimitSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newSaveForm(?CreditLimitModel $existing = null, array $values = []): CreditLimitSaveForm
    {
        $form = new CreditLimitSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill(['id' => $existing->getId(), 'customerId' => $existing->getCustomerId(), 'creditLimit' => $existing->getCreditLimit(), 'currentDebt' => $existing->getCurrentDebt(), 'overdueAmount' => $existing->getOverdueAmount(), 'isBlocked' => $existing->getIsBlocked()]);
        }
        return $form;
    }

    public function searchCreditLimits(array $payload = []): Paginator
    {
        $form = new CreditLimitSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $criteria = new CreditLimitModel();
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $criteria->setIsBlocked($this->boolOrNull($data['isBlocked'] ?? null));
        return $this->mapper()->searchCreditLimits($criteria, PaginatorUtil::fromFormData($data));
    }

    public function getCreditLimit(int $id): CreditLimitModel
    {
        $item = $this->mapper()->getCreditLimit($id);
        if ($item === null) {
            throw new NotFoundException();
        }
        return $item;
    }

    public function getCreditStatus(int $customerId): CreditLimitModel
    {
        $item = $this->mapper()->getCreditLimitByCustomer($customerId);
        if ($item !== null) {
            return $item;
        }
        $empty = new CreditLimitModel();
        $empty->setCustomerId($customerId);
        return $empty;
    }

    public function saveCreditLimit(array $payload = []): CreditLimitModel
    {
        $form = new CreditLimitSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $id = $this->positiveIntOrNull($data['id'] ?? null);
        $existing = $id !== null ? $this->getCreditLimit($id) : null;
        $customerId = (int)($data['customerId'] ?? 0);
        $this->getContainerEntry(CustomerService::class)->getCustomer($customerId);
        if ($this->mapper()->getCreditLimitByCustomer($customerId, $id) !== null) {
            throw new ValidationException(['customerId' => 'Khách hàng này đã có dòng hạn mức.']);
        }
        $before = $existing !== null ? $this->creditLimitAuditValues($existing) : null;
        $wasBlocked = $existing?->isBlocked() ?? false;

        $item = $existing ?? new CreditLimitModel();
        $item->setCustomerId($customerId);
        $item->setCreditLimit((int)($data['creditLimit'] ?? 0));
        $item->setCurrentDebt((int)($data['currentDebt'] ?? 0));
        $item->setOverdueAmount((int)($data['overdueAmount'] ?? 0));
        $item->setIsBlocked((int)($data['isBlocked'] ?? 0) === 0 ? 0 : 1);
        $actorId = $this->currentUserId();
        $item->setLastCheckedAt(DateModel::getUtcNow());
        $item->setUpdatedBy($actorId);
        if ($existing === null) {
            $item->setCreatedBy($actorId);
        }

        $saved = $this->mapper()->transactional(function () use ($existing, $item, $before, $wasBlocked, $actorId): CreditLimitModel {
            $saved = $this->mapper()->saveCreditLimit($item);

            $this->auditLog()->write(
                $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
                CreditLimitMapper::TABLE_NAME,
                $saved->getId(),
                $before,
                $this->creditLimitAuditValues($saved)
            );

            $this->recordCreditWarningEventIfNeeded($wasBlocked, $saved, $actorId);

            return $saved;
        });

        return $saved;
    }

    public function refreshCreditStatusFromBilling(int $customerId, ?int $actorId = null): ?CreditLimitModel
    {
        $item = $this->mapper()->getCreditLimitByCustomer($customerId);
        if ($item === null) {
            return null;
        }

        $actorId ??= $this->currentUserId();
        $wasBlocked = $item->isBlocked();
        $before = $this->creditLimitAuditValues($item);
        $summary = $this->invoiceMapper()->summarizeCreditDebtForCustomer($customerId, DateModel::getCurrentDate());
        $item->setCurrentDebt($summary['currentDebt']);
        $item->setOverdueAmount($summary['overdueAmount']);
        $item->setIsBlocked($this->isBlockedByDebt($item) ? 1 : 0);
        $item->setLastCheckedAt(DateModel::getUtcNow());
        $item->setUpdatedBy($actorId);

        $saved = $this->mapper()->saveCreditLimit($item);
        $after = $this->creditLimitAuditValues($saved);
        if ($before !== $after) {
            $this->auditLog()->write(
                AuditLogModel::ACTION_UPDATE,
                CreditLimitMapper::TABLE_NAME,
                $saved->getId(),
                $before,
                $after,
                $actorId
            );
        }

        $this->recordCreditWarningEventIfNeeded($wasBlocked, $saved, $actorId);

        return $saved;
    }

    private function recordCreditWarningEventIfNeeded(bool $wasBlocked, CreditLimitModel $after, ?int $actorId): void
    {
        $isBlocked = $after->isBlocked();
        if ($wasBlocked === $isBlocked) {
            return;
        }

        $eventName = $isBlocked
            ? CreditLimitConst::EVENT_CREDIT_EXCEEDED
            : CreditLimitConst::EVENT_CREDIT_CLEARED;

        $outbox = $this->getContainerEntry(OutboxEventService::class);
        if (!$outbox instanceof OutboxEventService) {
            return;
        }

        $outbox->recordEvent($eventName, (int)$after->getId(), [
            'creditLimitId' => (int)$after->getId(),
            'customerId'    => (int)$after->getCustomerId(),
            'creditLimit'   => $after->getCreditLimit(),
            'currentDebt'   => $after->getCurrentDebt(),
            'overdueAmount' => $after->getOverdueAmount(),
            'isBlocked'     => $isBlocked,
            'checkedAt'     => $after->getLastCheckedAt(),
            'actorId'       => $actorId,
        ]);
    }

    private function isBlockedByDebt(CreditLimitModel $item): bool
    {
        return $item->getCurrentDebt() > $item->getCreditLimit();
    }

    /**
     * @return array<string, mixed>
     */
    private function creditLimitAuditValues(CreditLimitModel $item): array
    {
        return [
            'id'            => $item->getId(),
            'customerId'    => $item->getCustomerId(),
            'creditLimit'   => $item->getCreditLimit(),
            'currentDebt'   => $item->getCurrentDebt(),
            'overdueAmount' => $item->getOverdueAmount(),
            'isBlocked'     => $item->isBlocked(),
        ];
    }

    private function intOrNull(mixed $value): ?int { return ($value === null || $value === '') ? null : (int)$value; }
    private function positiveIntOrNull(mixed $value): ?int { $value = $this->intOrNull($value); return $value !== null && $value > 0 ? $value : null; }
    private function boolOrNull(mixed $value): ?int { return ($value === null || $value === '') ? null : ((int)$value === 0 ? 0 : 1); }
}
