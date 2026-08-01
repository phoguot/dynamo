<?php

declare(strict_types=1);

namespace Sales\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Crm\Service\CustomerService;
use Crm\Service\SiteService;
use Sales\Form\Quote\QuoteSaveForm;
use Sales\Form\Quote\QuoteSearchForm;
use Sales\Form\Quote\QuoteStatusForm;
use Sales\Model\PriceListItem\PriceListItemConst;
use Sales\Model\Quote\QuoteConst;
use Sales\Model\Quote\QuoteMapper;
use Sales\Model\Quote\QuoteModel;
use Sales\Model\QuoteLine\QuoteLineMapper;
use Sales\Model\QuoteLine\QuoteLineModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class QuoteService extends AppServiceFactory
{
    private function mapper(): QuoteMapper
    {
        return $this->getContainerEntry(QuoteMapper::class);
    }

    private function lineMapper(): QuoteLineMapper
    {
        return $this->getContainerEntry(QuoteLineMapper::class);
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

    public function newSaveForm(?QuoteModel $existing = null, array $values = []): QuoteSaveForm
    {
        $form = new QuoteSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): QuoteSearchForm
    {
        $form = new QuoteSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): QuoteStatusForm
    {
        return new QuoteStatusForm($this->getContainer());
    }

    public function searchQuotes(array $payload = []): Paginator
    {
        $form = new QuoteSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new QuoteModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($formData['customerId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? QuoteConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'desc';

        return $this->mapper()->searchQuotes($criteria, $paging);
    }

    public function getQuote(int $id): QuoteModel
    {
        $item = $this->mapper()->getQuote($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return QuoteLineModel[] */
    public function linesOf(QuoteModel $quote): array
    {
        return $this->lineMapper()->fetchByQuote((int)$quote->getId());
    }

    public function nextStatuses(QuoteModel $quote): array
    {
        $allowed = QuoteConst::STATUS_TRANSITIONS[(string)$quote->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = QuoteConst::statusLabel($status);
        }

        return $result;
    }

    public function saveQuote(array $payload = []): QuoteModel
    {
        $form = new QuoteSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getQuote($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if (!in_array($existing->getStatus(), [QuoteConst::STATUS_DRAFT, QuoteConst::STATUS_REJECTED], true)) {
                throw new ValidationException(['status' => 'Chỉ được sửa báo giá đang nháp hoặc bị từ chối.']);
            }
        }

        $quoteNo = (string)($formData['quoteNo'] ?? '');
        if ($mapper->getQuoteByNo($quoteNo, $id) !== null) {
            throw new ValidationException(['quoteNo' => 'Số báo giá này đã được dùng.']);
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

        $lines = $this->buildLines($formData);
        $beforeLineCount = null;
        $before = null;
        if ($existing !== null) {
            $beforeLineCount = count($this->lineMapper()->fetchByQuote((int)$existing->getId()));
            $before = $this->quoteAuditValues($existing, $beforeLineCount);
        }

        $rentAmount = $lines === []
            ? (int)($formData['rentAmount'] ?? 0)
            : array_sum(array_map(fn (QuoteLineModel $line): int => (int)$line->getLineAmount(), $lines));

        $deliveryFee = (int)($formData['deliveryFee'] ?? 0);
        $installFee = (int)($formData['installFee'] ?? 0);
        $otherFee = (int)($formData['otherFee'] ?? 0);
        $discountAmount = (int)($formData['discountAmount'] ?? 0);
        $vatAmount = (int)($formData['vatAmount'] ?? 0);
        $totalAmount = $rentAmount + $deliveryFee + $installFee + $otherFee - $discountAmount + $vatAmount;
        if ($totalAmount < 0) {
            throw new ValidationException(['discountAmount' => 'Tổng tiền báo giá không được âm.']);
        }

        return $mapper->transactional(function () use ($existing, $formData, $quoteNo, $customerId, $siteId, $rentAmount, $totalAmount, $lines, $before, $beforeLineCount): QuoteModel {
            $item = $existing ?? new QuoteModel();
            $item->setQuoteNo($quoteNo);
            $item->setCustomerId($customerId);
            $item->setSiteId($siteId);
            $item->setPriceListId($this->positiveIntOrNull($formData['priceListId'] ?? null));
            $item->setRentFrom((string)($formData['rentFrom'] ?? ''));
            $item->setRentTo((string)($formData['rentTo'] ?? ''));
            $item->setStatus($existing?->getStatus() ?? QuoteConst::STATUS_DRAFT);
            $item->setValidUntil($this->nullIfBlank($formData['validUntil'] ?? null));
            $item->setRentAmount($rentAmount);
            $item->setDeliveryFee((int)($formData['deliveryFee'] ?? 0));
            $item->setInstallFee((int)($formData['installFee'] ?? 0));
            $item->setOtherFee((int)($formData['otherFee'] ?? 0));
            $item->setDiscountAmount((int)($formData['discountAmount'] ?? 0));
            $item->setVatRate((int)($formData['vatRate'] ?? 0));
            $item->setVatAmount((int)($formData['vatAmount'] ?? 0));
            $item->setTotalAmount($totalAmount);
            $item->setDepositAmount((int)($formData['depositAmount'] ?? 0));
            $item->setTerms($this->nullIfBlank($formData['terms'] ?? null));
            $item->setRejectReason(null);
            $item->setUpdatedBy($this->currentUserId());
            if ($existing === null) {
                $item->setCreatedBy($this->currentUserId());
            }

            $saved = $this->mapper()->saveQuote($item);
            if ($lines !== []) {
                $this->lineMapper()->replaceLines((int)$saved->getId(), $lines);
            }

            $afterLineCount = $lines !== [] ? count($lines) : ($beforeLineCount ?? 0);
            $this->auditLog()->write(
                $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
                QuoteMapper::TABLE_NAME,
                $saved->getId(),
                $before,
                $this->quoteAuditValues($saved, $afterLineCount)
            );

            return $saved;
        });
    }

    public function changeStatus(array $payload = []): QuoteModel
    {
        $form = new QuoteStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $toStatus = (string)($formData['status'] ?? '');
        $quote = $this->getQuote($id);
        $fromStatus = (string)$quote->getStatus();

        if ($fromStatus === $toStatus) {
            return $quote;
        }
        if (!QuoteConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển báo giá từ "%s" sang "%s".',
                QuoteConst::statusLabel($fromStatus),
                QuoteConst::statusLabel($toStatus)
            )]);
        }
        if ($toStatus === QuoteConst::STATUS_REJECTED && $this->nullIfBlank($formData['rejectReason'] ?? null) === null) {
            throw new ValidationException(['rejectReason' => 'Bạn cần nhập lý do từ chối báo giá.']);
        }

        $attrs = ['status' => $toStatus];
        if ($toStatus === QuoteConst::STATUS_PENDING) {
            $attrs['submittedAt'] = DateModel::getUtcNow();
            $attrs['rejectReason'] = null;
        }
        if ($toStatus === QuoteConst::STATUS_APPROVED) {
            $attrs['approvedAt'] = DateModel::getUtcNow();
            $attrs['approvedBy'] = $this->currentUserId();
            $attrs['rejectReason'] = null;
        }
        if ($toStatus === QuoteConst::STATUS_REJECTED) {
            $attrs['rejectReason'] = $this->nullIfBlank($formData['rejectReason'] ?? null);
        }

        $this->mapper()->updateAttrsQuote($id, $attrs, $this->currentUserId());
        $updated = $this->getQuote($id);

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            QuoteMapper::TABLE_NAME,
            $id,
            ['status' => $fromStatus],
            ['status' => $toStatus, 'rejectReason' => $this->nullIfBlank($formData['rejectReason'] ?? null)]
        );

        return $updated;
    }

    private function buildLines(array $formData): array
    {
        $lines = [];
        foreach (($formData['lines'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $capacityKva = (int)($row['capacityKva'] ?? 0);
            $quantity = max(1, (int)($row['quantity'] ?? 1));
            $durationTier = (string)($row['durationTier'] ?? PriceListItemConst::TIER_MONTH);
            $durationQty = (float)($row['durationQty'] ?? 0);
            $unitPrice = (int)($row['unitPrice'] ?? 0);
            $oddDays = (int)($row['oddDays'] ?? 0);
            $oddDayRate = (int)($row['oddDayRate'] ?? 0);
            if ($capacityKva <= 0 || $durationQty <= 0 || $unitPrice < 0 || $oddDays < 0 || $oddDayRate < 0) {
                throw new ValidationException(['lines' => 'Dòng báo giá có công suất, thời hạn hoặc đơn giá không hợp lệ.']);
            }
            if (!PriceListItemConst::isValidDurationTier($durationTier)) {
                throw new ValidationException(['lines' => 'Bậc thời hạn thuê của dòng báo giá không hợp lệ.']);
            }

            $lineAmount = (int)round($quantity * (($durationQty * $unitPrice) + ($oddDays * $oddDayRate)));
            $line = new QuoteLineModel();
            $line->setGeneratorId($this->positiveIntOrNull($row['generatorId'] ?? null));
            $line->setCapacityKva($capacityKva);
            $line->setQuantity($quantity);
            $line->setRentFrom((string)($row['rentFrom'] ?? $formData['rentFrom']));
            $line->setRentTo((string)($row['rentTo'] ?? $formData['rentTo']));
            $line->setDurationTier($durationTier);
            $line->setDurationQty($durationQty);
            $line->setUnitPrice($unitPrice);
            $line->setOddDays($oddDays);
            $line->setOddDayRate($oddDayRate);
            $line->setLineAmount($lineAmount);
            $line->setSuggestReason($this->nullIfBlank($row['suggestReason'] ?? null));
            $line->setNote($this->nullIfBlank($row['note'] ?? null));
            $lines[] = $line;
        }

        return $lines;
    }

    private function formValues(QuoteModel $item): array
    {
        return [
            'id'             => $item->getId(),
            'quoteNo'        => $item->getQuoteNo(),
            'customerId'     => $item->getCustomerId(),
            'siteId'         => $item->getSiteId(),
            'priceListId'    => $item->getPriceListId(),
            'rentFrom'       => $item->getRentFrom(),
            'rentTo'         => $item->getRentTo(),
            'validUntil'     => $item->getValidUntil(),
            'rentAmount'     => $item->getRentAmount(),
            'deliveryFee'    => $item->getDeliveryFee(),
            'installFee'     => $item->getInstallFee(),
            'otherFee'       => $item->getOtherFee(),
            'discountAmount' => $item->getDiscountAmount(),
            'vatRate'        => $item->getVatRate(),
            'vatAmount'      => $item->getVatAmount(),
            'depositAmount'  => $item->getDepositAmount(),
            'terms'          => $item->getTerms(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteAuditValues(QuoteModel $item, int $lineCount): array
    {
        return $item->getRespQuote() + [
            'lineCount'      => $lineCount,
            'rentAmount'     => $item->getRentAmount(),
            'deliveryFee'    => $item->getDeliveryFee(),
            'installFee'     => $item->getInstallFee(),
            'otherFee'       => $item->getOtherFee(),
            'discountAmount' => $item->getDiscountAmount(),
            'vatAmount'      => $item->getVatAmount(),
            'depositAmount'  => $item->getDepositAmount(),
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
