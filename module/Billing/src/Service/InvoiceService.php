<?php

declare(strict_types=1);

namespace Billing\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Billing\Form\Invoice\InvoiceSaveForm;
use Billing\Form\Invoice\InvoiceSearchForm;
use Billing\Form\Invoice\InvoiceStatusForm;
use Billing\Form\InvoiceLine\InvoiceLineSaveForm;
use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\Invoice\InvoiceMapper;
use Billing\Model\Invoice\InvoiceModel;
use Billing\Model\InvoiceLine\InvoiceLineMapper;
use Billing\Model\InvoiceLine\InvoiceLineModel;
use Billing\Model\Payment\PaymentMapper;
use Crm\Service\CustomerService;
use Fleet\Service\GeneratorService;
use Rental\Service\RentalOrderService;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotModel;
use Reporting\Model\RevenueMonthly\RevenueMonthlyModel;
use Reporting\Service\ReportingService;
use Sales\Service\ContractService;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class InvoiceService extends AppServiceFactory
{
    private function mapper(): InvoiceMapper { return $this->getContainerEntry(InvoiceMapper::class); }
    private function lineMapper(): InvoiceLineMapper { return $this->getContainerEntry(InvoiceLineMapper::class); }
    private function paymentMapper(): PaymentMapper { return $this->getContainerEntry(PaymentMapper::class); }
    private function auditLog(): AuditLogService { return $this->getContainerEntry(AuditLogService::class); }

    public function newSearchForm(array $query = []): InvoiceSearchForm
    {
        $form = new InvoiceSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newSaveForm(?InvoiceModel $existing = null, array $values = []): InvoiceSaveForm
    {
        $form = new InvoiceSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }
        return $form;
    }

    public function newStatusForm(): InvoiceStatusForm { return new InvoiceStatusForm($this->getContainer()); }

    public function newLineForm(InvoiceModel $invoice): InvoiceLineSaveForm
    {
        $form = new InvoiceLineSaveForm($this->getContainer());
        $form->setData([]);
        $form->fill(['invoiceId' => $invoice->getId(), 'isVatable' => 1]);
        return $form;
    }

    public function searchInvoices(array $payload = []): Paginator
    {
        $form = new InvoiceSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $this->selfHealOverdueInvoices();
        $data = $form->getData();
        $criteria = new InvoiceModel();
        $criteria->addOption('keyword', $this->nullIfBlank($data['keyword'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $criteria->setContractId($this->positiveIntOrNull($data['contractId'] ?? null));
        $criteria->setRentalOrderId($this->positiveIntOrNull($data['rentalOrderId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($data['status'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? InvoiceConst::SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';
        return $this->mapper()->searchInvoices($criteria, $paging);
    }

    public function getInvoice(int $id): InvoiceModel
    {
        $item = $this->mapper()->getInvoice($id);
        if ($item === null) {
            throw new NotFoundException();
        }
        $this->selfHealInvoiceIfOverdue($item);

        return $item;
    }

    /** @return InvoiceLineModel[] */
    public function linesOf(InvoiceModel $invoice): array
    {
        return $this->lineMapper()->fetchByInvoice((int)$invoice->getId());
    }

    public function nextStatuses(InvoiceModel $invoice): array
    {
        $result = [];
        foreach (InvoiceConst::STATUS_TRANSITIONS[(string)$invoice->getStatus()] ?? [] as $status) {
            $result[$status] = InvoiceConst::statusLabel($status);
        }
        return $result;
    }

    public function saveInvoice(array $payload = []): InvoiceModel
    {
        $form = new InvoiceSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $id = $this->positiveIntOrNull($data['id'] ?? null);
        $existing = $id !== null ? $this->getInvoice($id) : null;
        if ($existing !== null && $existing->getStatus() !== InvoiceConst::STATUS_DRAFT) {
            throw new ValidationException(['status' => 'Chỉ được sửa hóa đơn nháp.']);
        }
        $invoiceNo = (string)($data['invoiceNo'] ?? '');
        if ($this->mapper()->getInvoiceByNo($invoiceNo, $id) !== null) {
            throw new ValidationException(['invoiceNo' => 'Số hóa đơn này đã được dùng.']);
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

        $before = $existing !== null ? $this->invoiceAuditValues($existing) : null;

        $item = $existing ?? new InvoiceModel();
        $item->setInvoiceNo($invoiceNo);
        $item->setCustomerId($customerId);
        $item->setContractId($contractId);
        $item->setRentalOrderId($rentalOrderId);
        $item->setPeriodFrom((string)$data['periodFrom']);
        $item->setPeriodTo((string)$data['periodTo']);
        $item->setIssueDate($this->nullIfBlank($data['issueDate'] ?? null));
        $item->setDueDate($this->nullIfBlank($data['dueDate'] ?? null));
        $item->setStatus($existing?->getStatus() ?? InvoiceConst::STATUS_DRAFT);
        $item->setRentAmount($existing?->getRentAmount() ?? 0);
        $item->setSurchargeAmount($existing?->getSurchargeAmount() ?? 0);
        $item->setDiscountAmount(max(0, (int)($data['discountAmount'] ?? 0)));
        $item->setVatRate(max(0, (int)($data['vatRate'] ?? 0)));
        $item->setVatAmount($existing?->getVatAmount() ?? 0);
        $item->setTotalAmount($existing?->getTotalAmount() ?? 0);
        $item->setPaidAmount($existing?->getPaidAmount() ?? 0);
        $item->setRemainAmount(max(0, $item->getTotalAmount() - $item->getPaidAmount()));
        $item->setNote($this->nullIfBlank($data['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }
        $saved = $this->mapper()->saveInvoice($item);
        $this->refreshInvoiceAmounts($saved);
        $updated = $this->getInvoice((int)$saved->getId());

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            InvoiceMapper::TABLE_NAME,
            $updated->getId(),
            $before,
            $this->invoiceAuditValues($updated)
        );

        return $updated;
    }

    public function changeStatus(array $payload = []): InvoiceModel
    {
        $form = new InvoiceStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $invoice = $this->getInvoice((int)($data['id'] ?? 0));
        $from = (string)$invoice->getStatus();
        $to = (string)($data['status'] ?? '');
        if ($from === $to) {
            return $invoice;
        }
        if (!InvoiceConst::canTransit($from, $to)) {
            throw new ValidationException(['status' => sprintf('Không thể chuyển hóa đơn từ "%s" sang "%s".', InvoiceConst::statusLabel($from), InvoiceConst::statusLabel($to))]);
        }
        if ($to === InvoiceConst::STATUS_CANCELLED) {
            $reason = $this->nullIfBlank($data['voidReason'] ?? null);
            if ($reason === null) {
                throw new ValidationException(['voidReason' => 'Cần nhập lý do hủy hóa đơn.']);
            }
            $before = $this->invoiceAuditValues($invoice) + [
                'voidReason' => $reason,
            ];
            $paymentKeys = $this->paymentMapper()->fetchRecordedPaymentReportingKeysByInvoice((int)$invoice->getId());
            $this->mapper()->transactional(function () use ($invoice, $paymentKeys): void {
                $invoiceId = (int)$invoice->getId();
                $this->paymentMapper()->deleteByInvoice($invoiceId);
                $this->lineMapper()->clearByInvoice($invoiceId);
                $this->mapper()->deleteInvoice($invoiceId);
                $this->syncFinancialReportingForInvoice($invoice);
                $this->syncFinancialReportingKeys($paymentKeys);
            });
            $invoice->setStatus($to)->setVoidReason($this->nullIfBlank($data['voidReason'] ?? null));

            $this->auditLog()->write(
                AuditLogModel::ACTION_DELETE,
                InvoiceMapper::TABLE_NAME,
                $invoice->getId(),
                $before,
                null
            );

            return $invoice;
        }

        $attrs = ['status' => $to];
        if ($to === InvoiceConst::STATUS_ISSUED && $invoice->getIssueDate() === null) {
            $attrs['issueDate'] = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }

        $updated = $this->mapper()->transactional(function () use ($invoice, $attrs): InvoiceModel {
            $this->mapper()->updateAttrsInvoice((int)$invoice->getId(), $attrs, $this->currentUserId());
            $updated = $this->getInvoice((int)$invoice->getId());
            $this->syncFinancialReportingForInvoice($updated);

            return $updated;
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            InvoiceMapper::TABLE_NAME,
            $updated->getId(),
            ['status' => $from],
            ['status' => $to, 'voidReason' => $this->nullIfBlank($data['voidReason'] ?? null)]
        );

        return $updated;
    }

    public function saveLine(array $payload = []): InvoiceLineModel
    {
        $form = new InvoiceLineSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $invoice = $this->getInvoice((int)($data['invoiceId'] ?? 0));
        $this->assertInvoiceDraft($invoice);
        $generatorId = $this->positiveIntOrNull($data['generatorId'] ?? null);
        if ($generatorId !== null) {
            $this->getContainerEntry(GeneratorService::class)->getGenerator($generatorId);
        }
        $quantity = (float)$data['quantity'];
        $unitPrice = max(0, (int)($data['unitPrice'] ?? 0));
        $item = new InvoiceLineModel();
        $item->setInvoiceId((int)$invoice->getId());
        $item->setLineType((string)$data['lineType']);
        $item->setGeneratorId($generatorId);
        $item->setDescription((string)$data['description']);
        $item->setQuantity($quantity);
        $item->setUnit($this->nullIfBlank($data['unit'] ?? null));
        $item->setUnitPrice($unitPrice);
        $item->setLineAmount((int)round($quantity * $unitPrice));
        $item->setIsVatable((int)($data['isVatable'] ?? 0) === 0 ? 0 : 1);
        $saved = $this->lineMapper()->saveInvoiceLine($item);
        $this->refreshInvoiceAmounts($invoice);
        $this->auditLog()->write(
            AuditLogModel::ACTION_CREATE,
            InvoiceLineMapper::TABLE_NAME,
            $saved->getId(),
            null,
            $this->invoiceLineAuditValues($saved)
        );
        return $saved;
    }

    public function deleteLine(int $lineId): void
    {
        $line = $this->lineMapper()->getInvoiceLine($lineId);
        if ($line === null) {
            throw new NotFoundException();
        }
        $invoice = $this->getInvoice((int)$line->getInvoiceId());
        $this->assertInvoiceDraft($invoice);
        $before = $this->invoiceLineAuditValues($line);
        $this->lineMapper()->deleteInvoiceLine($lineId);
        $this->refreshInvoiceAmounts($invoice);
        $this->auditLog()->write(
            AuditLogModel::ACTION_DELETE,
            InvoiceLineMapper::TABLE_NAME,
            $lineId,
            $before,
            null
        );
    }

    public function refreshInvoicePayments(int $invoiceId): InvoiceModel
    {
        $invoice = $this->getInvoice($invoiceId);
        $paid = $this->paymentMapper()->sumRecordedByInvoice($invoiceId);
        if ($paid > $invoice->getTotalAmount()) {
            throw new ValidationException(['amount' => 'Tổng đã trả vượt tổng hóa đơn.']);
        }
        $remain = $invoice->getTotalAmount() - $paid;
        $status = $invoice->getStatus();
        if ($status !== InvoiceConst::STATUS_CANCELLED) {
            if ($paid === 0 && in_array($status, [InvoiceConst::STATUS_PARTIALLY_PAID, InvoiceConst::STATUS_PAID], true)) {
                $status = InvoiceConst::STATUS_ISSUED;
            } elseif ($remain === 0 && $invoice->getTotalAmount() > 0) {
                $status = InvoiceConst::STATUS_PAID;
            } elseif ($paid > 0) {
                $status = InvoiceConst::STATUS_PARTIALLY_PAID;
            }
        }
        $this->mapper()->updateAttrsInvoice($invoiceId, ['paidAmount' => $paid, 'remainAmount' => $remain, 'status' => $status], $this->currentUserId());
        $updated = $this->getInvoice($invoiceId);
        $this->syncFinancialReportingForInvoice($updated);

        return $updated;
    }

    public function refreshFinancialReportingForDate(int $customerId, ?string $date): void
    {
        $key = $this->reportingPeriodFromDate($customerId, $date);
        if ($key === null) {
            return;
        }

        $this->refreshFinancialReporting($key['customerId'], $key['periodYear'], $key['periodMonth']);
    }

    public function refreshFinancialReporting(int $customerId, int $year, int $month): void
    {
        if ($customerId <= 0 || $year <= 0 || $month < 1 || $month > 12) {
            return;
        }

        $computedAt = DateModel::getUtcNow();
        $reporting = $this->getContainerEntry(ReportingService::class);
        if ($reporting instanceof ReportingService) {
            foreach ([$customerId, null] as $rowCustomerId) {
                $invoiceSummary = $this->mapper()->summarizeRevenueForMonth($year, $month, $rowCustomerId);
                $collectedAmount = $this->paymentMapper()->sumRecordedByMonth($year, $month, $rowCustomerId);
                $reporting->syncRevenueMonth((new RevenueMonthlyModel())
                    ->setPeriodYear($year)
                    ->setPeriodMonth($month)
                    ->setCustomerId($rowCustomerId)
                    ->setInvoicedAmount($invoiceSummary['invoicedAmount'])
                    ->setCollectedAmount($collectedAmount)
                    ->setOutstandingAmount($invoiceSummary['outstandingAmount'])
                    ->setOverdueAmount($invoiceSummary['overdueAmount'])
                    ->setOrderCount($invoiceSummary['orderCount'])
                    ->setComputedAt($computedAt));
            }

            $this->syncReceivablesSnapshot($customerId, $reporting, $computedAt);
        }

        $this->refreshCreditLimitFromBilling($customerId);
    }

    private function refreshInvoiceAmounts(InvoiceModel $invoice): void
    {
        $totals = $this->lineMapper()->totalsByInvoice((int)$invoice->getId());
        $vatRate = $invoice->getVatRate();
        $vatAmount = (int)round($totals['vatable'] * $vatRate / 100);
        $total = max(0, $totals['total'] - $invoice->getDiscountAmount() + $vatAmount);
        $paid = $invoice->getPaidAmount();
        if ($paid > $total) {
            throw new ValidationException(['totalAmount' => 'Tổng hóa đơn nhỏ hơn số đã thanh toán.']);
        }
        $this->mapper()->updateAttrsInvoice((int)$invoice->getId(), [
            'rentAmount' => $totals['rent'],
            'surchargeAmount' => $totals['surcharge'],
            'vatAmount' => $vatAmount,
            'totalAmount' => $total,
            'remainAmount' => $total - $paid,
        ], $this->currentUserId());
    }

    private function assertInvoiceDraft(InvoiceModel $invoice): void
    {
        if ($invoice->getStatus() !== InvoiceConst::STATUS_DRAFT) {
            throw new ValidationException(['status' => 'Chỉ được sửa dòng của hóa đơn nháp.']);
        }
    }

    private function formValues(InvoiceModel $item): array
    {
        return ['id' => $item->getId(), 'invoiceNo' => $item->getInvoiceNo(), 'customerId' => $item->getCustomerId(), 'contractId' => $item->getContractId(), 'rentalOrderId' => $item->getRentalOrderId(), 'periodFrom' => $item->getPeriodFrom(), 'periodTo' => $item->getPeriodTo(), 'issueDate' => $item->getIssueDate(), 'dueDate' => $item->getDueDate(), 'rentAmount' => $item->getRentAmount(), 'surchargeAmount' => $item->getSurchargeAmount(), 'discountAmount' => $item->getDiscountAmount(), 'vatRate' => $item->getVatRate(), 'vatAmount' => $item->getVatAmount(), 'totalAmount' => $item->getTotalAmount(), 'note' => $item->getNote()];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceAuditValues(InvoiceModel $item): array
    {
        return $this->formValues($item) + [
            'status'       => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
            'paidAmount'   => $item->getPaidAmount(),
            'remainAmount' => $item->getRemainAmount(),
            'voidReason'   => $item->getVoidReason(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceLineAuditValues(InvoiceLineModel $item): array
    {
        return [
            'id'          => $item->getId(),
            'invoiceId'   => $item->getInvoiceId(),
            'lineType'    => ['id' => $item->getLineType(), 'name' => $item->getTypeLabel()],
            'generatorId' => $item->getGeneratorId(),
            'description' => $item->getDescription(),
            'quantity'    => $item->getQuantity(),
            'unit'        => ['id' => $item->getUnit(), 'name' => $item->getUnitLabel()],
            'unitPrice'   => $item->getUnitPrice(),
            'lineAmount'  => $item->getLineAmount(),
            'isVatable'   => $item->isVatable(),
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

    private function syncFinancialReportingForInvoice(InvoiceModel $invoice): void
    {
        $this->refreshFinancialReportingForDate(
            (int)$invoice->getCustomerId(),
            $invoice->getIssueDate() ?? $invoice->getPeriodFrom()
        );
    }

    /**
     * @param list<array{customerId:int, periodYear:int, periodMonth:int}> $keys
     */
    private function syncFinancialReportingKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->refreshFinancialReporting($key['customerId'], $key['periodYear'], $key['periodMonth']);
        }
    }

    private function syncReceivablesSnapshot(int $customerId, ReportingService $reporting, string $computedAt): void
    {
        $snapshotDate = DateModel::getCurrentDate();
        $summary = $this->mapper()->summarizeReceivablesForCustomer($customerId, $snapshotDate);
        $reporting->syncReceivablesSnapshot((new ReceivablesSnapshotModel())
            ->setSnapshotDate($snapshotDate)
            ->setCustomerId($customerId)
            ->setBucket0To30($summary['bucket0To30'])
            ->setBucket31To60($summary['bucket31To60'])
            ->setBucket61To90($summary['bucket61To90'])
            ->setBucketOver90($summary['bucketOver90'])
            ->setTotalDebt($summary['totalDebt'])
            ->setDsoDays($summary['dsoDays'])
            ->setComputedAt($computedAt));
    }

    private function refreshCreditLimitFromBilling(int $customerId): void
    {
        $service = $this->getContainerEntry(CreditLimitService::class);
        if ($service instanceof CreditLimitService) {
            $service->refreshCreditStatusFromBilling($customerId, $this->currentUserId());
        }
    }

    private function selfHealOverdueInvoices(): void
    {
        $today = DateModel::getCurrentDate();
        $keys = $this->mapper()->fetchOverdueReportingKeys($today);
        if ($keys === []) {
            return;
        }

        $this->mapper()->transactional(function () use ($today, $keys): void {
            $this->mapper()->markOverdueInvoices($today, $this->currentUserId());
            $this->syncFinancialReportingKeys($keys);
        });
    }

    private function selfHealInvoiceIfOverdue(InvoiceModel $invoice): void
    {
        if (!$this->shouldBeOverdue($invoice)) {
            return;
        }

        $this->mapper()->updateAttrsInvoice((int)$invoice->getId(), ['status' => InvoiceConst::STATUS_OVERDUE], $this->currentUserId());
        $invoice->setStatus(InvoiceConst::STATUS_OVERDUE);
        $this->syncFinancialReportingForInvoice($invoice);
    }

    private function shouldBeOverdue(InvoiceModel $invoice): bool
    {
        $dueDate = $invoice->getDueDate();

        return in_array($invoice->getStatus(), [InvoiceConst::STATUS_ISSUED, InvoiceConst::STATUS_PARTIALLY_PAID], true)
            && $dueDate !== null
            && $dueDate < DateModel::getCurrentDate()
            && $invoice->getRemainAmount() > 0;
    }

    /**
     * @return array{customerId:int, periodYear:int, periodMonth:int}|null
     */
    private function reportingPeriodFromDate(int $customerId, ?string $date): ?array
    {
        if ($customerId <= 0 || $date === null || !preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $date, $m)) {
            return null;
        }

        return [
            'customerId' => $customerId,
            'periodYear' => (int)$m[1],
            'periodMonth' => (int)$m[2],
        ];
    }
}
