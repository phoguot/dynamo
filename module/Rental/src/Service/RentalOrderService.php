<?php

declare(strict_types=1);

namespace Rental\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Crm\Service\CustomerService;
use Crm\Service\SiteService;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Fleet\Service\GeneratorService;
use Rental\Form\RentalOrder\RentalOrderExtendForm;
use Rental\Form\RentalOrder\RentalOrderSaveForm;
use Rental\Form\RentalOrder\RentalOrderSearchForm;
use Rental\Form\RentalOrder\RentalOrderStatusForm;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyConst;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyMapper;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyModel;
use Rental\Model\RentalOrder\RentalOrderConst;
use Rental\Model\RentalOrder\RentalOrderMapper;
use Rental\Model\RentalOrder\RentalOrderModel;
use Sales\Model\PriceListItem\PriceListItemConst;
use Sales\Service\ContractService;
use Throwable;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class RentalOrderService extends AppServiceFactory
{
    private function mapper(): RentalOrderMapper
    {
        return $this->getContainerEntry(RentalOrderMapper::class);
    }

    private function occupancyMapper(): GeneratorOccupancyMapper
    {
        return $this->getContainerEntry(GeneratorOccupancyMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?RentalOrderModel $existing = null, array $values = []): RentalOrderSaveForm
    {
        $form = new RentalOrderSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): RentalOrderSearchForm
    {
        $form = new RentalOrderSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): RentalOrderStatusForm
    {
        return new RentalOrderStatusForm($this->getContainer());
    }

    public function newExtendForm(RentalOrderModel $order): RentalOrderExtendForm
    {
        $form = new RentalOrderExtendForm($this->getContainer());
        $form->setData([]);
        $form->fill([
            'id' => $order->getId(),
            'expectedEndDate' => $order->getExpectedEndDate(),
        ]);

        return $form;
    }

    public function searchRentalOrders(array $payload = []): Paginator
    {
        $form = new RentalOrderSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new RentalOrderModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setCustomerId($this->positiveIntOrNull($formData['customerId'] ?? null));
        $criteria->setGeneratorId($this->positiveIntOrNull($formData['generatorId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? RentalOrderConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'desc';

        return $this->mapper()->searchRentalOrders($criteria, $paging);
    }

    public function getRentalOrder(int $id): RentalOrderModel
    {
        $item = $this->mapper()->getRentalOrder($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return GeneratorOccupancyModel[] */
    public function occupancyOf(RentalOrderModel $order): array
    {
        return $this->occupancyMapper()->fetchByOrder((int)$order->getId());
    }

    public function nextStatuses(RentalOrderModel $order): array
    {
        $allowed = RentalOrderConst::STATUS_TRANSITIONS[(string)$order->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = RentalOrderConst::statusLabel($status);
        }

        return $result;
    }

    public function canExtend(RentalOrderModel $order): bool
    {
        return in_array($order->getStatus(), [
            RentalOrderConst::STATUS_WAITING_DELIVERY,
            RentalOrderConst::STATUS_RENTING,
            RentalOrderConst::STATUS_WAITING_RECOVERY,
        ], true);
    }

    public function checkAvailability(int $generatorId, string $startDate, string $endDate, ?int $exceptOrderId = null): bool
    {
        return !$this->occupancyMapper()->hasOverlap($generatorId, $startDate, $endDate, $exceptOrderId);
    }

    public function saveRentalOrder(array $payload = []): RentalOrderModel
    {
        $form = new RentalOrderSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->positiveIntOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getRentalOrder($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if (!in_array($existing->getStatus(), [RentalOrderConst::STATUS_NEW, RentalOrderConst::STATUS_WAITING_DELIVERY], true)) {
                throw new ValidationException(['status' => 'Chỉ được sửa đơn thuê mới tạo hoặc đang chờ giao.']);
            }
        }

        $orderNo = (string)($formData['orderNo'] ?? '');
        if ($mapper->getRentalOrderByNo($orderNo, $id) !== null) {
            throw new ValidationException(['orderNo' => 'Số đơn thuê này đã được dùng.']);
        }

        $customerId = (int)($formData['customerId'] ?? 0);
        $this->getContainerEntry(CustomerService::class)->getCustomer($customerId);

        $siteId = $this->positiveIntOrNull($formData['siteId'] ?? null);
        if ($siteId !== null) {
            $this->getContainerEntry(SiteService::class)->getSite($siteId);
        }

        $contractId = $this->positiveIntOrNull($formData['contractId'] ?? null);
        if ($contractId !== null) {
            $this->getContainerEntry(ContractService::class)->getContract($contractId);
        }

        $generatorId = (int)($formData['generatorId'] ?? 0);
        $generatorService = $this->getContainerEntry(GeneratorService::class);
        $generatorService->getGenerator($generatorId);
        if (!$generatorService->isAvailableForRent($generatorId)) {
            throw new ValidationException(['generatorId' => 'Máy không ở trạng thái sẵn sàng nhận đơn thuê mới.']);
        }

        $startDate = (string)($formData['startDate'] ?? '');
        $endDate = (string)($formData['expectedEndDate'] ?? '');
        $exceptOrderId = $existing?->getId();
        $conflict = $this->occupancyMapper()->findConflict($generatorId, $startDate, $endDate, $exceptOrderId);
        if ($conflict !== null) {
            throw new ValidationException(['startDate' => $this->overlapMessage($conflict)]);
        }

        $before = $existing !== null ? $this->rentalOrderAuditValues($existing) : null;
        $saved = $mapper->transactional(function () use ($existing, $formData, $orderNo, $contractId, $customerId, $siteId, $generatorId, $startDate, $endDate): RentalOrderModel {
            $item = $existing ?? new RentalOrderModel();
            $item->setOrderNo($orderNo);
            $item->setContractId($contractId);
            $item->setCustomerId($customerId);
            $item->setSiteId($siteId);
            $item->setGeneratorId($generatorId);
            $item->setStartDate($startDate);
            $item->setExpectedEndDate($endDate);
            $item->setStatus($existing?->getStatus() ?? RentalOrderConst::STATUS_NEW);
            $item->setUnitPrice((int)($formData['unitPrice'] ?? 0));
            $item->setDurationTier((string)($formData['durationTier'] ?? PriceListItemConst::TIER_MONTH));
            $item->setWithOperator($this->boolOrNull($formData['withOperator'] ?? null) ?? false);
            $item->setExtendedTimes($existing?->getExtendedTimes() ?? 0);
            $item->setNote($this->nullIfBlank($formData['note'] ?? null));
            $item->setUpdatedBy($this->currentUserId());
            if ($existing === null) {
                $item->setCreatedBy($this->currentUserId());
            }

            $saved = $this->mapper()->saveRentalOrder($item);
            $this->occupancyMapper()->clearByOrder((int)$saved->getId());
            $this->insertOccupancyOrFail($saved, $startDate, $endDate);

            return $saved;
        });

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            RentalOrderMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->rentalOrderAuditValues($saved)
        );

        return $saved;
    }

    public function extendRentalOrder(array $payload = []): RentalOrderModel
    {
        $form = new RentalOrderExtendForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $order = $this->getRentalOrder((int)($formData['id'] ?? 0));
        $newEndDate = (string)($formData['expectedEndDate'] ?? '');

        if (!$this->canExtend($order)) {
            throw new ValidationException(['status' => 'Chỉ được gia hạn đơn thuê chưa kết thúc.']);
        }
        if (strcmp($newEndDate, (string)$order->getExpectedEndDate()) <= 0) {
            throw new ValidationException(['expectedEndDate' => 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.']);
        }

        $conflict = $this->occupancyMapper()->findConflict(
            (int)$order->getGeneratorId(),
            (string)$order->getExpectedEndDate(),
            $newEndDate,
            (int)$order->getId()
        );
        if ($conflict !== null) {
            throw new ValidationException(['expectedEndDate' => $this->overlapMessage($conflict)]);
        }

        $before = $this->rentalOrderAuditValues($order);
        $updated = $this->mapper()->transactional(function () use ($order, $newEndDate): RentalOrderModel {
            $this->insertOccupancyOrFail($order, (string)$order->getExpectedEndDate(), $newEndDate);
            $this->mapper()->updateAttrsRentalOrder((int)$order->getId(), [
                'expectedEndDate' => $newEndDate,
                'extendedTimes' => (int)$order->getExtendedTimes() + 1,
            ], $this->currentUserId());

            return $this->getRentalOrder((int)$order->getId());
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_UPDATE,
            RentalOrderMapper::TABLE_NAME,
            $updated->getId(),
            $before,
            $this->rentalOrderAuditValues($updated)
        );

        return $updated;
    }

    public function changeStatus(array $payload = []): RentalOrderModel
    {
        $form = new RentalOrderStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $order = $this->getRentalOrder((int)($formData['id'] ?? 0));
        $fromStatus = (string)$order->getStatus();
        $toStatus = (string)($formData['status'] ?? '');

        if ($fromStatus === $toStatus) {
            return $order;
        }
        if (!RentalOrderConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển đơn thuê từ "%s" sang "%s".',
                RentalOrderConst::statusLabel($fromStatus),
                RentalOrderConst::statusLabel($toStatus)
            )]);
        }

        $attrs = ['status' => $toStatus];
        $clearOccupancyFromDate = null;
        if ($toStatus === RentalOrderConst::STATUS_RENTING) {
            $startHour = $this->floatOrNull($formData['startHourMeter'] ?? null);
            if ($startHour === null) {
                throw new ValidationException(['startHourMeter' => 'Cần nhập chỉ số giờ máy đầu để bàn giao.']);
            }
            $attrs['startHourMeter'] = $startHour;
            $attrs['handoverAt'] = DateModel::getUtcNow();
        }
        if ($toStatus === RentalOrderConst::STATUS_RECOVERED) {
            $endHour = $this->floatOrNull($formData['endHourMeter'] ?? null);
            if ($endHour === null) {
                throw new ValidationException(['endHourMeter' => 'Cần nhập chỉ số giờ máy cuối để thu hồi.']);
            }
            if ($order->getStartHourMeter() !== null && $endHour < (float)$order->getStartHourMeter()) {
                throw new ValidationException(['endHourMeter' => 'Chỉ số giờ máy cuối không được nhỏ hơn chỉ số đầu.']);
            }
            $actualEndDate = $this->nullIfBlank($formData['actualEndDate'] ?? null) ?? (new DateTimeImmutable('today'))->format('Y-m-d');
            $attrs['endHourMeter'] = $endHour;
            $attrs['actualEndDate'] = $actualEndDate;
            $attrs['recoveredAt'] = DateModel::getUtcNow();
            $clearOccupancyFromDate = $actualEndDate;
        }
        if ($toStatus === RentalOrderConst::STATUS_SETTLED) {
            $attrs['settledAt'] = DateModel::getUtcNow();
        }
        if ($toStatus === RentalOrderConst::STATUS_CANCELLED) {
            $reason = $this->nullIfBlank($formData['cancelReason'] ?? null);
            if ($reason === null) {
                throw new ValidationException(['cancelReason' => 'Bạn cần nhập lý do hủy đơn thuê.']);
            }

            $before = $this->rentalOrderAuditValues($order) + ['cancelReason' => $reason];
            $this->mapper()->transactional(function () use ($order): void {
                $this->occupancyMapper()->clearByOrder((int)$order->getId());
                $this->mapper()->deleteRentalOrder((int)$order->getId());
            });
            $order->setStatus($toStatus)->setCancelReason($reason);

            $this->auditLog()->write(
                AuditLogModel::ACTION_DELETE,
                RentalOrderMapper::TABLE_NAME,
                $order->getId(),
                $before,
                null
            );

            return $order;
        }

        $updated = $this->mapper()->transactional(function () use ($order, $attrs, $toStatus, $clearOccupancyFromDate): RentalOrderModel {
            if ($clearOccupancyFromDate !== null) {
                $this->occupancyMapper()->clearByOrderFromDate((int)$order->getId(), $clearOccupancyFromDate);
            }
            $this->mapper()->updateAttrsRentalOrder((int)$order->getId(), $attrs, $this->currentUserId());

            return $this->getRentalOrder((int)$order->getId());
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            RentalOrderMapper::TABLE_NAME,
            $updated->getId(),
            ['status' => $fromStatus],
            [
                'status'       => $toStatus,
                'cancelReason' => $this->nullIfBlank($formData['cancelReason'] ?? null),
                'startHourMeter' => $this->floatOrNull($formData['startHourMeter'] ?? null),
                'endHourMeter'   => $this->floatOrNull($formData['endHourMeter'] ?? null),
            ]
        );

        return $updated;
    }

    public function activateFromDispatch(int $orderId, float $startHourMeter, ?int $actorId = null): RentalOrderModel
    {
        if ($startHourMeter < 0) {
            throw new ValidationException(['startHourMeter' => 'Chi so gio may dau khong duoc am.']);
        }

        $order = $this->getRentalOrder($orderId);
        if ($order->getStatus() === RentalOrderConst::STATUS_RENTING) {
            return $order;
        }

        if ($order->getStatus() === RentalOrderConst::STATUS_NEW) {
            $order = $this->changeStatusFromSystemCore(
                $order,
                RentalOrderConst::STATUS_WAITING_DELIVERY,
                [],
                $actorId,
                ['source' => 'dispatch']
            );
        }

        return $this->changeStatusFromSystemCore(
            $order,
            RentalOrderConst::STATUS_RENTING,
            [
                'startHourMeter' => $startHourMeter,
                'handoverAt'     => DateModel::getUtcNow(),
            ],
            $actorId,
            ['source' => 'dispatch', 'startHourMeter' => $startHourMeter]
        );
    }

    public function recoverFromDispatch(int $orderId, float $endHourMeter, ?string $actualEndDate = null, ?int $actorId = null): RentalOrderModel
    {
        if ($endHourMeter < 0) {
            throw new ValidationException(['endHourMeter' => 'Chi so gio may cuoi khong duoc am.']);
        }

        $actualEndDate = $this->nullIfBlank($actualEndDate) ?? (new DateTimeImmutable('today'))->format('Y-m-d');
        if (!$this->isDate($actualEndDate)) {
            throw new ValidationException(['actualEndDate' => 'Ngay thu hoi thuc te khong hop le.']);
        }

        $order = $this->getRentalOrder($orderId);
        if ($order->getStatus() === RentalOrderConst::STATUS_RECOVERED) {
            return $order;
        }

        if ($order->getStartHourMeter() !== null && $endHourMeter < (float)$order->getStartHourMeter()) {
            throw new ValidationException(['endHourMeter' => 'Chi so gio may cuoi khong duoc nho hon chi so dau.']);
        }

        if ($order->getStatus() === RentalOrderConst::STATUS_RENTING) {
            $order = $this->changeStatusFromSystemCore(
                $order,
                RentalOrderConst::STATUS_WAITING_RECOVERY,
                [],
                $actorId,
                ['source' => 'dispatch']
            );
        }

        return $this->changeStatusFromSystemCore(
            $order,
            RentalOrderConst::STATUS_RECOVERED,
            [
                'endHourMeter'   => $endHourMeter,
                'actualEndDate'  => $actualEndDate,
                'recoveredAt'    => DateModel::getUtcNow(),
            ],
            $actorId,
            ['source' => 'dispatch', 'endHourMeter' => $endHourMeter],
            $actualEndDate
        );
    }

    public function swapGeneratorFromDispatch(
        int $orderId,
        int $oldGeneratorId,
        int $newGeneratorId,
        float $oldHourMeter,
        float $newHourMeter,
        ?string $swapDate = null,
        ?int $actorId = null
    ): RentalOrderModel {
        if ($oldGeneratorId <= 0) {
            throw new ValidationException(['oldGeneratorId' => 'May cu khong hop le.']);
        }
        if ($newGeneratorId <= 0) {
            throw new ValidationException(['newGeneratorId' => 'May moi khong hop le.']);
        }
        if ($oldGeneratorId === $newGeneratorId) {
            throw new ValidationException(['newGeneratorId' => 'May moi phai khac may cu.']);
        }
        if ($oldHourMeter < 0) {
            throw new ValidationException(['oldHourMeter' => 'Chi so gio may cu khong duoc am.']);
        }
        if ($newHourMeter < 0) {
            throw new ValidationException(['newHourMeter' => 'Chi so gio may moi khong duoc am.']);
        }

        $swapDate = $this->nullIfBlank($swapDate) ?? (new DateTimeImmutable('today'))->format('Y-m-d');
        if (!$this->isDate($swapDate)) {
            throw new ValidationException(['actualEndDate' => 'Ngay doi may thuc te khong hop le.']);
        }

        $order = $this->getRentalOrder($orderId);
        if ((int)$order->getGeneratorId() === $newGeneratorId) {
            return $order;
        }

        if ($order->getStatus() !== RentalOrderConst::STATUS_RENTING) {
            throw new ValidationException(['status' => 'Chi duoc doi may cho don dang thue.']);
        }
        if ((int)$order->getGeneratorId() !== $oldGeneratorId) {
            throw new ValidationException(['oldGeneratorId' => 'May cu khong khop voi don thue hien tai.']);
        }
        if ($order->getStartHourMeter() !== null && $oldHourMeter < (float)$order->getStartHourMeter()) {
            throw new ValidationException(['oldHourMeter' => 'Chi so gio may cu khong duoc nho hon chi so dau.']);
        }

        $startDate = (string)$order->getStartDate();
        if ($this->isDate($startDate) && strcmp($swapDate, $startDate) < 0) {
            throw new ValidationException(['actualEndDate' => 'Ngay doi may khong duoc truoc ngay bat dau thue.']);
        }

        $expectedEndDate = (string)$order->getExpectedEndDate();
        if (!$this->isDate($expectedEndDate)) {
            throw new ValidationException(['expectedEndDate' => 'Ngay ket thuc du kien khong hop le.']);
        }

        if (strcmp($swapDate, $expectedEndDate) < 0) {
            $conflict = $this->occupancyMapper()->findConflict($newGeneratorId, $swapDate, $expectedEndDate, (int)$order->getId());
            if ($conflict !== null) {
                throw new ValidationException(['newGeneratorId' => $this->overlapMessage($conflict)]);
            }
        }

        $before = $this->rentalOrderAuditValues($order);
        $updated = $this->mapper()->transactional(function () use (
            $order,
            $oldGeneratorId,
            $newGeneratorId,
            $oldHourMeter,
            $newHourMeter,
            $swapDate,
            $expectedEndDate,
            $actorId,
            $before
        ): RentalOrderModel {
            $this->occupancyMapper()->clearByOrderFromDate((int)$order->getId(), $swapDate);
            $this->insertGeneratorOccupancyOrFail(
                $newGeneratorId,
                (int)$order->getId(),
                strcmp($swapDate, $expectedEndDate) < 0 ? self::daysInHalfOpenRange($swapDate, $expectedEndDate) : [],
                $actorId,
                'newGeneratorId'
            );

            $this->mapper()->updateAttrsRentalOrder((int)$order->getId(), [
                'generatorId'    => $newGeneratorId,
                'startHourMeter' => $newHourMeter,
                'endHourMeter'   => null,
            ], $actorId);
            $updated = $this->getRentalOrder((int)$order->getId());

            $this->auditLog()->write(
                AuditLogModel::ACTION_UPDATE,
                RentalOrderMapper::TABLE_NAME,
                $updated->getId(),
                $before,
                $this->rentalOrderAuditValues($updated) + [
                    'source'         => 'dispatch',
                    'swapDate'       => $swapDate,
                    'oldGeneratorId' => $oldGeneratorId,
                    'oldHourMeter'   => $oldHourMeter,
                    'newGeneratorId' => $newGeneratorId,
                    'newHourMeter'   => $newHourMeter,
                ]
            );

            return $updated;
        });

        return $updated;
    }

    /**
     * @param array<string, mixed> $attrs
     * @param array<string, mixed> $auditExtra
     */
    private function changeStatusFromSystemCore(
        RentalOrderModel $order,
        string $toStatus,
        array $attrs,
        ?int $actorId,
        array $auditExtra = [],
        ?string $clearOccupancyFromDate = null
    ): RentalOrderModel {
        $fromStatus = (string)$order->getStatus();
        if ($fromStatus === $toStatus) {
            return $order;
        }

        if (!RentalOrderConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Khong the chuyen don thue tu "%s" sang "%s".',
                RentalOrderConst::statusLabel($fromStatus),
                RentalOrderConst::statusLabel($toStatus)
            )]);
        }

        $attrs = array_merge(['status' => $toStatus], $attrs);

        return $this->mapper()->transactional(function () use ($order, $attrs, $actorId, $fromStatus, $toStatus, $auditExtra, $clearOccupancyFromDate): RentalOrderModel {
            if ($clearOccupancyFromDate !== null) {
                $this->occupancyMapper()->clearByOrderFromDate((int)$order->getId(), $clearOccupancyFromDate);
            }
            $this->mapper()->updateAttrsRentalOrder((int)$order->getId(), $attrs, $actorId);
            $updated = $this->getRentalOrder((int)$order->getId());

            $this->auditLog()->write(
                AuditLogModel::ACTION_STATUS_CHANGED,
                RentalOrderMapper::TABLE_NAME,
                $updated->getId(),
                ['status' => $fromStatus],
                array_merge(['status' => $toStatus], $auditExtra)
            );

            return $updated;
        });
    }

    private function insertOccupancyOrFail(RentalOrderModel $order, string $startDate, string $endDate): void
    {
        try {
            $this->occupancyMapper()->insertOccupancyDays(
                (int)$order->getGeneratorId(),
                (int)$order->getId(),
                self::daysInHalfOpenRange($startDate, $endDate),
                GeneratorOccupancyConst::HOLD_RENT,
                GeneratorOccupancyConst::SOURCE_RENTAL_ORDER,
                (int)$order->getId(),
                null,
                $this->currentUserId()
            );
        } catch (Throwable $e) {
            if (GeneratorOccupancyMapper::isDuplicateKey($e)) {
                throw new ValidationException(['startDate' => 'Máy đã có lịch chiếm dụng trong khoảng ngày này. Chọn máy khác hoặc đổi ngày.']);
            }
            throw $e;
        }
    }

    /**
     * @param string[] $dates
     */
    private function insertGeneratorOccupancyOrFail(
        int $generatorId,
        int $rentalOrderId,
        array $dates,
        ?int $actorId,
        string $errorField
    ): void {
        try {
            $this->occupancyMapper()->insertOccupancyDays(
                $generatorId,
                $rentalOrderId,
                $dates,
                GeneratorOccupancyConst::HOLD_RENT,
                GeneratorOccupancyConst::SOURCE_RENTAL_ORDER,
                $rentalOrderId,
                null,
                $actorId
            );
        } catch (Throwable $e) {
            if (GeneratorOccupancyMapper::isDuplicateKey($e)) {
                throw new ValidationException([$errorField => 'May da co lich chiem dung trong khoang ngay nay. Chon may khac hoac doi ngay.']);
            }
            throw $e;
        }
    }

    /** @return string[] */
    public static function daysInHalfOpenRange(string $startDate, string $endDate): array
    {
        $start = new DateTimeImmutable($startDate);
        $end = new DateTimeImmutable($endDate);
        $dates = [];
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    private function overlapMessage(GeneratorOccupancyModel $conflict): string
    {
        return sprintf(
            'Máy đã có lịch chiếm dụng ngày %s bởi đơn #%d.',
            (string)$conflict->getOccupiedDate(),
            (int)$conflict->getRentalOrderId()
        );
    }

    private function formValues(RentalOrderModel $item): array
    {
        return [
            'id'              => $item->getId(),
            'orderNo'         => $item->getOrderNo(),
            'contractId'      => $item->getContractId(),
            'customerId'      => $item->getCustomerId(),
            'siteId'          => $item->getSiteId(),
            'generatorId'     => $item->getGeneratorId(),
            'startDate'       => $item->getStartDate(),
            'expectedEndDate' => $item->getExpectedEndDate(),
            'unitPrice'       => $item->getUnitPrice(),
            'durationTier'    => $item->getDurationTier(),
            'withOperator'    => $item->getWithOperator() ? 1 : 0,
            'note'            => $item->getNote(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rentalOrderAuditValues(RentalOrderModel $item): array
    {
        return $this->formValues($item) + [
            'status'         => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
            'actualEndDate'  => $item->getActualEndDate(),
            'startHourMeter' => $item->getStartHourMeter(),
            'endHourMeter'   => $item->getEndHourMeter(),
            'extendedTimes'  => $item->getExtendedTimes(),
            'settledAt'      => $item->getSettledAt(),
            'cancelReason'   => $item->getCancelReason(),
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

    private function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (bool)(int)$value;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float)$value;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
