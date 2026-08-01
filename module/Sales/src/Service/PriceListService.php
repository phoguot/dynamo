<?php

declare(strict_types=1);

namespace Sales\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Sales\Form\PriceList\PriceListSaveForm;
use Sales\Form\PriceList\PriceListSearchForm;
use Sales\Form\PriceList\PriceListItemSaveForm;
use Sales\Model\PriceList\PriceListConst;
use Sales\Model\PriceList\PriceListMapper;
use Sales\Model\PriceList\PriceListModel;
use Sales\Model\PriceListItem\PriceListItemConst;
use Sales\Model\PriceListItem\PriceListItemMapper;
use Sales\Model\PriceListItem\PriceListItemModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class PriceListService extends AppServiceFactory
{
    private function mapper(): PriceListMapper
    {
        return $this->getContainerEntry(PriceListMapper::class);
    }

    private function itemMapper(): PriceListItemMapper
    {
        return $this->getContainerEntry(PriceListItemMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?PriceListModel $existing = null, array $values = []): PriceListSaveForm
    {
        $form = new PriceListSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): PriceListSearchForm
    {
        $form = new PriceListSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newItemSaveForm(?PriceListItemModel $existing = null, ?PriceListModel $priceList = null, array $values = []): PriceListItemSaveForm
    {
        $form = new PriceListItemSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->itemFormValues($existing));
        } elseif ($priceList !== null) {
            $form->fill(['priceListId' => $priceList->getId()]);
        }

        return $form;
    }

    public function searchPriceLists(array $payload = []): Paginator
    {
        $form = new PriceListSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new PriceListModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setIsActive($this->boolOrNull($form->getSubmittedValue('isActive')));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? PriceListConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'desc';

        return $this->mapper()->searchPriceLists($criteria, $paging);
    }

    public function getPriceList(int $id): PriceListModel
    {
        $item = $this->mapper()->getPriceList($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return PriceListItemModel[] */
    public function itemsOf(PriceListModel $priceList): array
    {
        return $this->itemMapper()->fetchByPriceList((int)$priceList->getId());
    }

    public function savePriceList(array $payload = []): PriceListModel
    {
        $form = new PriceListSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getPriceList($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $code = (string)($formData['code'] ?? '');
        if ($mapper->getPriceListByCode($code, $id) !== null) {
            throw new ValidationException(['code' => 'Mã bảng giá này đã được dùng.']);
        }

        $before = $existing?->getRespPriceList();

        $item = $existing ?? new PriceListModel();
        $item->setCode($code);
        $item->setName((string)($formData['name'] ?? ''));
        $item->setValidFrom((string)($formData['validFrom'] ?? ''));
        $item->setValidTo($this->nullIfBlank($formData['validTo'] ?? null));
        $item->setIsActive($this->boolOrNull($formData['isActive'] ?? null) ?? true);
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->savePriceList($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            PriceListMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespPriceList()
        );

        return $saved;
    }

    public function toggleActive(int $id): PriceListModel
    {
        $item = $this->getPriceList($id);
        $next = !($item->getIsActive() ?? false);
        $before = $item->getRespPriceList();
        $this->mapper()->updateAttrsPriceList($id, ['isActive' => (int)$next], $this->currentUserId());
        $item->setIsActive($next);

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            PriceListMapper::TABLE_NAME,
            $id,
            $before,
            $item->getRespPriceList()
        );

        return $item;
    }

    public function getPriceListItem(int $id): PriceListItemModel
    {
        $item = $this->itemMapper()->getPriceListItem($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function savePriceListItem(array $payload = []): PriceListItemModel
    {
        $form = new PriceListItemSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $existing = null;
        if ($id !== null) {
            $existing = $this->itemMapper()->getPriceListItem($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $priceListId = (int)($formData['priceListId'] ?? 0);
        $this->getPriceList($priceListId);

        $before = $existing?->getRespPriceListItem();

        $item = $existing ?? new PriceListItemModel();
        $item->setPriceListId($priceListId);
        $item->setCapacityFrom((int)($formData['capacityFrom'] ?? 0));
        $item->setCapacityTo((int)($formData['capacityTo'] ?? 0));
        $item->setDurationTier((string)($formData['durationTier'] ?? PriceListItemConst::TIER_MONTH));
        $item->setMinDays((int)($formData['minDays'] ?? 1));
        $item->setUnitPrice((int)($formData['unitPrice'] ?? 0));
        $item->setDailyRate($this->intOrNull($formData['dailyRate'] ?? null));
        $item->setDeliveryFee((int)($formData['deliveryFee'] ?? 0));
        $item->setInstallFee((int)($formData['installFee'] ?? 0));
        $item->setDepositAmount((int)($formData['depositAmount'] ?? 0));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        if ($this->itemMapper()->getDuplicate($item, $id) !== null) {
            throw new ValidationException(['capacityFrom' => 'Dòng giá này đã tồn tại trong bảng giá.']);
        }

        $saved = $this->itemMapper()->savePriceListItem($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            PriceListItemMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespPriceListItem()
        );

        return $saved;
    }

    public function resolvePrice(int $priceListId, int $capacityKva, string $durationTier, int $rentDays): PriceListItemModel
    {
        $priceList = $this->getPriceList($priceListId);
        if (!$priceList->getIsActive()) {
            throw new ValidationException(['priceListId' => 'Bảng giá đã ngưng dùng.']);
        }

        if (!PriceListItemConst::isValidDurationTier($durationTier)) {
            throw new ValidationException(['durationTier' => 'Bậc thời hạn thuê không hợp lệ.']);
        }

        $item = $this->itemMapper()->resolvePrice($priceListId, $capacityKva, $durationTier, $rentDays);
        if ($item === null) {
            throw new ValidationException(['capacityKva' => 'Không tìm thấy dòng giá phù hợp.']);
        }

        return $item;
    }

    private function formValues(PriceListModel $item): array
    {
        return [
            'id'        => $item->getId(),
            'code'      => $item->getCode(),
            'name'      => $item->getName(),
            'validFrom' => $item->getValidFrom(),
            'validTo'   => $item->getValidTo(),
            'isActive'  => $item->getIsActive() ? 1 : 0,
            'note'      => $item->getNote(),
        ];
    }

    private function itemFormValues(PriceListItemModel $item): array
    {
        return [
            'id'             => $item->getId(),
            'priceListId'    => $item->getPriceListId(),
            'capacityFrom'   => $item->getCapacityFrom(),
            'capacityTo'     => $item->getCapacityTo(),
            'durationTier'   => $item->getDurationTier(),
            'minDays'        => $item->getMinDays(),
            'unitPrice'      => $item->getUnitPrice(),
            'dailyRate'      => $item->getDailyRate(),
            'deliveryFee'    => $item->getDeliveryFee(),
            'installFee'     => $item->getInstallFee(),
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

    private function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool)(int)$value;
    }
}
