<?php

declare(strict_types=1);

namespace Billing\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Billing\Form\Payment\PaymentCancelForm;
use Billing\Form\Payment\PaymentSaveForm;
use Billing\Form\Payment\PaymentSearchForm;
use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\Invoice\InvoiceMapper;
use Billing\Model\Payment\PaymentConst;
use Billing\Model\Payment\PaymentMapper;
use Billing\Model\Payment\PaymentModel;
use Crm\Service\CustomerService;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class PaymentService extends AppServiceFactory
{
    private function mapper(): PaymentMapper { return $this->getContainerEntry(PaymentMapper::class); }
    private function invoiceMapper(): InvoiceMapper { return $this->getContainerEntry(InvoiceMapper::class); }
    private function auditLog(): AuditLogService { return $this->getContainerEntry(AuditLogService::class); }

    public function newSearchForm(array $query = []): PaymentSearchForm
    {
        $form = new PaymentSearchForm($this->getContainer());
        $form->setData($query);
        return $form;
    }

    public function newSaveForm(?PaymentModel $existing = null, array $values = []): PaymentSaveForm
    {
        $form = new PaymentSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }
        return $form;
    }

    public function newCancelForm(): PaymentCancelForm { return new PaymentCancelForm($this->getContainer()); }

    public function searchPayments(array $payload = []): Paginator
    {
        $form = new PaymentSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $criteria = new PaymentModel();
        $criteria->addOption('keyword', $this->nullIfBlank($data['keyword'] ?? null));
        $criteria->setInvoiceId($this->positiveIntOrNull($data['invoiceId'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($data['customerId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($data['status'] ?? null));
        $paging = PaginatorUtil::fromFormData($data);
        $paging['sort'] = $data['sort'] ?? PaymentConst::SORT_DEFAULT;
        $paging['dir'] = $data['dir'] ?? 'desc';
        return $this->mapper()->searchPayments($criteria, $paging);
    }

    public function getPayment(int $id): PaymentModel
    {
        $item = $this->mapper()->getPayment($id);
        if ($item === null) {
            throw new NotFoundException();
        }
        return $item;
    }

    public function savePayment(array $payload = []): PaymentModel
    {
        $form = new PaymentSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $id = $this->positiveIntOrNull($data['id'] ?? null);
        $existing = $id !== null ? $this->getPayment($id) : null;
        if ($existing !== null) {
            throw new ValidationException(['status' => 'Phiếu thu đã ghi nhận không sửa trực tiếp; hãy hủy rồi lập phiếu mới.']);
        }
        $paymentNo = (string)($data['paymentNo'] ?? '');
        if ($this->mapper()->getPaymentByNo($paymentNo, $id) !== null) {
            throw new ValidationException(['paymentNo' => 'Số phiếu thu này đã được dùng.']);
        }
        $customerId = (int)($data['customerId'] ?? 0);
        $this->getContainerEntry(CustomerService::class)->getCustomer($customerId);
        $invoiceId = $this->positiveIntOrNull($data['invoiceId'] ?? null);
        if ($invoiceId !== null) {
            $invoice = $this->invoiceMapper()->getInvoice($invoiceId);
            if ($invoice === null) {
                throw new NotFoundException();
            }
            if ((int)$invoice->getCustomerId() !== $customerId) {
                throw new ValidationException(['invoiceId' => 'Hóa đơn phải thuộc cùng khách hàng.']);
            }
            if (!in_array($invoice->getStatus(), [
                InvoiceConst::STATUS_ISSUED,
                InvoiceConst::STATUS_PARTIALLY_PAID,
                InvoiceConst::STATUS_OVERDUE,
            ], true)) {
                throw new ValidationException(['invoiceId' => 'Chỉ được thu tiền cho hóa đơn đã phát hành, đang thu một phần hoặc quá hạn.']);
            }
            $paidExceptThis = $this->mapper()->sumRecordedByInvoice($invoiceId, $id);
            if ($paidExceptThis + (int)$data['amount'] > $invoice->getTotalAmount()) {
                throw new ValidationException(['amount' => 'Tổng đã trả không được vượt tổng hóa đơn.']);
            }
        }

        $saved = $this->mapper()->transactional(function () use ($existing, $data, $paymentNo, $invoiceId, $customerId): PaymentModel {
            $item = $existing ?? new PaymentModel();
            $item->setPaymentNo($paymentNo);
            $item->setInvoiceId($invoiceId);
            $item->setCustomerId($customerId);
            $item->setAmount((int)$data['amount']);
            $item->setPaymentDate((string)$data['paymentDate']);
            $item->setMethod((string)$data['method']);
            $item->setReferenceNo($this->nullIfBlank($data['referenceNo'] ?? null));
            $item->setAttachmentId($this->positiveIntOrNull($data['attachmentId'] ?? null));
            $item->setStatus($existing?->getStatus() ?? PaymentConst::STATUS_RECORDED);
            $item->setNote($this->nullIfBlank($data['note'] ?? null));
            $item->setUpdatedBy($this->currentUserId());
            if ($existing === null) {
                $item->setCreatedBy($this->currentUserId());
            }
            $saved = $this->mapper()->savePayment($item);
            if ($invoiceId !== null) {
                $this->getContainerEntry(InvoiceService::class)->refreshInvoicePayments($invoiceId);
            }
            $invoiceService = $this->getContainerEntry(InvoiceService::class);
            if ($invoiceService instanceof InvoiceService) {
                $invoiceService->refreshFinancialReportingForDate($customerId, (string)$data['paymentDate']);
            }
            return $saved;
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_CREATE,
            PaymentMapper::TABLE_NAME,
            $saved->getId(),
            null,
            $this->paymentAuditValues($saved)
        );

        return $saved;
    }

    public function cancelPayment(array $payload = []): PaymentModel
    {
        $form = new PaymentCancelForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }
        $data = $form->getData();
        $payment = $this->getPayment((int)($data['id'] ?? 0));
        if ($payment->getStatus() === PaymentConst::STATUS_CANCELLED) {
            return $payment;
        }
        $before = $this->paymentAuditValues($payment) + [
            'cancelReason' => $this->nullIfBlank($data['cancelReason'] ?? null),
        ];
        $this->mapper()->transactional(function () use ($payment): void {
            $invoiceId = $payment->getInvoiceId();
            $payment->setStatus(PaymentConst::STATUS_CANCELLED);
            $this->mapper()->deletePayment((int)$payment->getId());
            if ($invoiceId !== null) {
                $this->getContainerEntry(InvoiceService::class)->refreshInvoicePayments((int)$invoiceId);
            }
            $invoiceService = $this->getContainerEntry(InvoiceService::class);
            if ($invoiceService instanceof InvoiceService) {
                $invoiceService->refreshFinancialReportingForDate((int)$payment->getCustomerId(), $payment->getPaymentDate());
            }
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_DELETE,
            PaymentMapper::TABLE_NAME,
            $payment->getId(),
            $before,
            null
        );

        return $payment;
    }

    private function formValues(PaymentModel $item): array
    {
        return ['id' => $item->getId(), 'paymentNo' => $item->getPaymentNo(), 'invoiceId' => $item->getInvoiceId(), 'customerId' => $item->getCustomerId(), 'amount' => $item->getAmount(), 'paymentDate' => $item->getPaymentDate(), 'method' => $item->getMethod(), 'referenceNo' => $item->getReferenceNo(), 'attachmentId' => $item->getAttachmentId(), 'note' => $item->getNote()];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAuditValues(PaymentModel $item): array
    {
        return array_replace($this->formValues($item), [
            'method'       => ['id' => $item->getMethod(), 'name' => $item->getMethodLabel()],
            'status'       => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
            'cancelReason' => $item->getCancelReason(),
        ]);
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
