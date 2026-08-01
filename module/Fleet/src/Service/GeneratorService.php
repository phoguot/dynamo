<?php

declare(strict_types=1);

namespace Fleet\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\AppMessage;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Fleet\Form\Generator\GeneratorSaveForm;
use Fleet\Form\Generator\GeneratorSearchForm;
use Fleet\Form\Generator\GeneratorStatusForm;
use Fleet\Model\Generator\GeneratorConst;
use Fleet\Model\Generator\GeneratorMapper;
use Fleet\Model\Generator\GeneratorModel;
use Platform\Service\OutboxEventService;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Service\ReportingService;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

/**
 * Nghiá»‡p vá»¥ Ä‘á»™i mÃ¡y phÃ¡t Ä‘iá»‡n.
 *
 * ÄÃ¢y lÃ  nÆ¡i duy nháº¥t:
 * - cháº¡y Form (validate) vÃ  nÃ©m ValidationException,
 * - thá»±c thi state machine tráº¡ng thÃ¡i mÃ¡y (GeneratorConst::STATUS_TRANSITIONS),
 * - quyáº¿t Ä‘á»‹nh mÃ¡y nÃ o Ä‘ang kháº£ dá»¥ng.
 *
 * Controller khÃ´ng Ä‘Æ°á»£c láº·p láº¡i báº¥t ká»³ Ä‘iá»u nÃ o á»Ÿ trÃªn.
 */
class GeneratorService extends AppServiceFactory
{
    private function mapper(): GeneratorMapper
    {
        return $this->getContainerEntry(GeneratorMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    // ------------------------------------------------------------------
    //  Form cho táº§ng view
    //  Controller khÃ´ng tá»± `new` form â€” há»i service sá»Ÿ há»¯u nghiá»‡p vá»¥.
    //  Form tráº£ vá» á»Ÿ Ä‘Ã¢y chá»‰ Ä‘á»ƒ RENDER (token CSRF, danh sÃ¡ch chá»n, giÃ¡ trá»‹ cÅ©);
    //  viá»‡c validate váº«n do chÃ­nh form lÃ m bÃªn trong cÃ¡c hÃ m nghiá»‡p vá»¥ bÃªn dÆ°á»›i.
    // ------------------------------------------------------------------

    /**
     * Form thÃªm/sá»­a mÃ¡y, Ä‘Ã£ Ä‘á»• sáºµn giÃ¡ trá»‹.
     *
     * @param array<string, mixed> $values dá»¯ liá»‡u ngÆ°á»i dÃ¹ng vá»«a gÃµ, Æ°u tiÃªn hÆ¡n báº£n ghi cÅ©
     */
    public function newSaveForm(?GeneratorModel $existing = null, array $values = []): GeneratorSaveForm
    {
        $form = new GeneratorSaveForm($this->getContainer());
        $form->setData($values);

        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    /** @param array<string, mixed> $query tham sá»‘ lá»c hiá»‡n táº¡i trÃªn URL */
    public function newSearchForm(array $query = []): GeneratorSearchForm
    {
        $form = new GeneratorSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): GeneratorStatusForm
    {
        return new GeneratorStatusForm($this->getContainer());
    }

    /**
     * Tráº¡ng thÃ¡i cÃ³ thá»ƒ chuyá»ƒn tá»›i tá»« tráº¡ng thÃ¡i hiá»‡n táº¡i.
     * State machine thuá»™c vá» service, view chá»‰ nháº­n danh sÃ¡ch Ä‘Ã£ tÃ­nh sáºµn.
     *
     * @return array<string, string> mÃ£ tráº¡ng thÃ¡i => nhÃ£n hiá»ƒn thá»‹
     */
    public function nextStatuses(GeneratorModel $generator): array
    {
        $allowed = GeneratorConst::STATUS_TRANSITIONS[(string)$generator->getStatus()] ?? [];

        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = GeneratorConst::statusLabel($status);
        }

        return $result;
    }

    /**
     * GiÃ¡ trá»‹ cá»§a báº£n ghi theo Ä‘Ãºng tÃªn field cá»§a form.
     *
     * @return array<string, mixed>
     */
    private function formValues(GeneratorModel $item): array
    {
        return [
            'id'              => $item->getId(),
            'code'            => $item->getCode(),
            'name'            => $item->getName(),
            'serialNumber'    => $item->getSerialNumber(),
            'manufacturer'    => $item->getManufacturer(),
            'model'           => $item->getModel(),
            'capacityKva'     => $item->getCapacityKva(),
            'fuelType'        => $item->getFuelType(),
            'hourMeter'       => $item->getHourMeter(),
            'currentLocation' => $item->getCurrentLocation(),
            'latitude'        => $item->getLatitude(),
            'longitude'       => $item->getLongitude(),
            'note'            => $item->getNote(),
        ];
    }

    /**
     * Danh sÃ¡ch mÃ¡y cho trang danh sÃ¡ch.
     *
     * @param array<string, mixed> $payload tham sá»‘ thÃ´ tá»« request
     */
    public function searchGenerators(array $payload = []): Paginator
    {
        $form = new GeneratorSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();

        $criteria = new GeneratorModel();
        $criteria->setKeyword($this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));
        $criteria->setFuelType($this->nullIfBlank($formData['fuelType'] ?? null));
        $criteria->setCapacityFrom($this->intOrNull($formData['capacityFrom'] ?? null));
        $criteria->setCapacityTo($this->intOrNull($formData['capacityTo'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? GeneratorConst::SORT_DEFAULT;
        $paging['dir']  = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchGenerators($criteria, $paging);
    }

    /**
     * Chi tiáº¿t má»™t mÃ¡y. KhÃ´ng tÃ¬m tháº¥y â‡’ NotFoundException (BaseController Ä‘á»•i thÃ nh trang 404).
     */
    public function getGenerator(int $id): GeneratorModel
    {
        $item = $this->mapper()->getGenerator($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /**
     * ThÃªm má»›i hoáº·c cáº­p nháº­t mÃ¡y.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException dá»¯ liá»‡u sai hoáº·c trÃ¹ng mÃ£ mÃ¡y / serial
     */
    public function saveGenerator(array $payload = []): GeneratorModel
    {
        $form = new GeneratorSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getGenerator($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $errors = [];

        $code = (string)($formData['code'] ?? '');
        if ($mapper->getGeneratorByCode($code, $id) !== null) {
            $errors['code'] = 'MÃ£ mÃ¡y nÃ y Ä‘Ã£ Ä‘Æ°á»£c dÃ¹ng cho mÃ¡y khÃ¡c.';
        }

        $serialNumber = $this->nullIfBlank($formData['serialNumber'] ?? null);
        if ($serialNumber !== null && $mapper->getGeneratorBySerial($serialNumber, $id) !== null) {
            $errors['serialNumber'] = 'Sá»‘ serial nÃ y Ä‘Ã£ tá»“n táº¡i trong Ä‘á»™i mÃ¡y.';
        }

        // Loáº¡i nhiÃªn liá»‡u Ä‘Ã£ Ä‘Æ°á»£c GeneratorSaveForm cháº·n theo enum â€” á»Ÿ Ä‘Ã¢y chá»‰ cÃ²n luáº­t cáº§n DB.

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $before = $existing?->getRespGenerator();

        $item = $existing ?? new GeneratorModel();
        $item->setCode($code);
        $item->setName((string)($formData['name'] ?? ''));
        $item->setSerialNumber($serialNumber);
        $item->setManufacturer($this->nullIfBlank($formData['manufacturer'] ?? null));
        $item->setModel($this->nullIfBlank($formData['model'] ?? null));
        $item->setCapacityKva($this->intOrNull($formData['capacityKva'] ?? null));
        $item->setFuelType($this->nullIfBlank($formData['fuelType'] ?? null));
        $item->setCurrentLocation($this->nullIfBlank($formData['currentLocation'] ?? null));
        $item->setLatitude($this->floatOrNull($formData['latitude'] ?? null));
        $item->setLongitude($this->floatOrNull($formData['longitude'] ?? null));
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());

        // MÃ¡y má»›i luÃ´n vÃ o kho á»Ÿ tráº¡ng thÃ¡i sáºµn sÃ ng; Ä‘á»•i tráº¡ng thÃ¡i pháº£i Ä‘i qua changeStatus().
        if ($existing === null) {
            $item->setStatus(GeneratorConst::STATUS_SAN_SANG);
            $item->setHourMeter((float)($formData['hourMeter'] ?? 0));
            $item->setCreatedBy($this->currentUserId());
        }

        foreach ($this->extractSpecs($payload) as $key => $value) {
            $item->addExtraField($key, $value);
        }

        return $mapper->transactional(function () use ($mapper, $item, $existing, $before): GeneratorModel {
            $saved = $mapper->saveGenerator($item);

            $this->auditLog()->write(
                $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
                GeneratorMapper::TABLE_NAME,
                $saved->getId(),
                $before,
                $saved->getRespGenerator()
            );

            $this->syncFleetUtilizationDaily();

            return $saved;
        });
    }

    /**
     * Chuyá»ƒn tráº¡ng thÃ¡i mÃ¡y theo state machine.
     *
     * ÄÃ¢y lÃ  **cá»­a duy nháº¥t** Ä‘á»•i tráº¡ng thÃ¡i mÃ¡y. Module khÃ¡c khÃ´ng gá»i hÃ m nÃ y trá»±c tiáº¿p
     * báº±ng SQL mÃ  phÃ¡t domain event, listener cá»§a fleet gá»i vÃ o Ä‘Ã¢y.
     *
     * @param array<string, mixed> $payload
     */
    public function changeStatus(array $payload = []): GeneratorModel
    {
        $form = new GeneratorStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $toStatus = (string)($formData['status'] ?? '');
        $reason = $this->nullIfBlank($formData['reason'] ?? null);

        return $this->changeStatusCore($id, $toStatus, $reason, $this->currentUserId());
    }

    public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
    {
        return $this->changeStatusCore($id, $toStatus, $reason, $actorId);
    }

    private function changeStatusCore(int $id, string $toStatus, ?string $reason, ?int $actorId): GeneratorModel
    {
        $item = $this->getGenerator($id);
        $fromStatus = (string)$item->getStatus();

        if (!GeneratorConst::isValidStatus($toStatus)) {
            throw new ValidationException(['status' => AppMessage::STATUS_INVALID]);
        }

        if ($fromStatus === $toStatus) {
            return $item; // idempotent: nghe event trÃ¹ng khÃ´ng Ä‘Æ°á»£c coi lÃ  lá»—i
        }

        if (!GeneratorConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException([
                'status' => sprintf(
                    'KhÃ´ng thá»ƒ chuyá»ƒn mÃ¡y tá»« "%s" sang "%s".',
                    GeneratorConst::statusLabel($fromStatus),
                    GeneratorConst::statusLabel($toStatus)
                ),
            ]);
        }

        $mapper = $this->mapper();
        $mapper->transactional(function () use ($mapper, $id, $item, $fromStatus, $toStatus, $reason, $actorId): void {
            $mapper->updateAttrsGenerator($id, ['status' => $toStatus], $actorId);
            $item->setStatus($toStatus);

            $this->auditLog()->write(
                AuditLogModel::ACTION_STATUS_CHANGED,
                GeneratorMapper::TABLE_NAME,
                $id,
                ['status' => $fromStatus],
                ['status' => $toStatus, 'reason' => $reason]
            );

            $this->recordStatusChangedEvent($id, $fromStatus, $toStatus, $reason, $actorId);
            $this->syncFleetUtilizationDaily();
        });

        return $item;
    }

    /**
     * Cáº­p nháº­t giá» mÃ¡y. Giá» mÃ¡y chá»‰ Ä‘Æ°á»£c tÄƒng â€” sá»‘ nhá» hÆ¡n lÃ  nháº­p nháº§m chá»‰ sá»‘.
     */
    public function updateHourMeter(int $id, float $hourMeter, ?int $actorId = null): GeneratorModel
    {
        $item = $this->getGenerator($id);
        $actorId ??= $this->currentUserId();
        $beforeHourMeter = (float)$item->getHourMeter();

        if ($hourMeter < $beforeHourMeter) {
            throw new ValidationException([
                'hourMeter' => sprintf(
                    'Giá» mÃ¡y má»›i (%.1f) nhá» hÆ¡n chá»‰ sá»‘ Ä‘ang lÆ°u (%.1f). Kiá»ƒm tra láº¡i chá»‰ sá»‘ Ä‘á»“ng há»“.',
                    $hourMeter,
                    $beforeHourMeter
                ),
            ]);
        }

        if ($hourMeter === $beforeHourMeter) {
            return $item;
        }

        $this->mapper()->transactional(function () use ($id, $item, $beforeHourMeter, $hourMeter, $actorId): void {
            $this->mapper()->updateAttrsGenerator($id, ['hourMeter' => $hourMeter], $actorId);
            $item->setHourMeter($hourMeter);

            $this->auditLog()->write(
                AuditLogModel::ACTION_UPDATE,
                GeneratorMapper::TABLE_NAME,
                $id,
                ['hourMeter' => $beforeHourMeter],
                ['hourMeter' => $hourMeter]
            );

            $this->recordHourMeterUpdatedEvent($id, $beforeHourMeter, $hourMeter, $actorId);
        });

        return $item;
    }

    /**
     * API cÃ´ng khai cho module khÃ¡c: mÃ¡y nÃ y cÃ³ Ä‘ang á»Ÿ tráº¡ng thÃ¡i nháº­n Ä‘Æ°á»£c Ä‘Æ¡n thuÃª khÃ´ng.
     *
     * CHÃš Ã: chá»‰ tráº£ lá»i vá» TRáº NG THÃI mÃ¡y. Viá»‡c mÃ¡y cÃ³ báº­n lá»‹ch trong khoáº£ng ngÃ y cá»¥ thá»ƒ
     * hay khÃ´ng lÃ  dá»¯ liá»‡u cá»§a M05 rental (rental.checkAvailability) â€” fleet khÃ´ng biáº¿t
     * vÃ  khÃ´ng Ä‘Æ°á»£c Ä‘oÃ¡n.
     */
    public function isAvailableForRent(int $id): bool
    {
        $item = $this->mapper()->getGenerator($id);

        return $item !== null && in_array((string)$item->getStatus(), GeneratorConst::STATUS_KHA_DUNG, true);
    }

    public function syncFleetUtilizationDaily(?string $reportDate = null): ?FleetUtilizationDailyModel
    {
        $reporting = $this->getContainerEntry(ReportingService::class);
        if (!$reporting instanceof ReportingService) {
            return null;
        }

        $counts = $this->mapper()->summarizeStatusCounts();
        $retiredCount = $counts['retiredCount'];
        $totalGenerators = $counts['totalGenerators'];
        $activeGenerators = max(0, $totalGenerators - $retiredCount);

        $item = (new FleetUtilizationDailyModel())
            ->setReportDate($reportDate ?? DateModel::getCurrentDate())
            ->setWarehouseCode(null)
            ->setTotalGenerators($totalGenerators)
            ->setActiveGenerators($activeGenerators)
            ->setRentedCount($counts['rentedCount'])
            ->setAvailableCount($counts['availableCount'])
            ->setHeldCount($counts['heldCount'])
            ->setTransitCount($counts['transitCount'])
            ->setMaintenanceCount($counts['maintenanceCount'])
            ->setRepairCount($counts['repairCount'])
            ->setRetiredCount($retiredCount);

        return $reporting->syncFleetUtilizationDaily($item);
    }

    private function recordStatusChangedEvent(
        int $generatorId,
        string $fromStatus,
        string $toStatus,
        ?string $reason,
        ?int $actorId
    ): void {
        if (!$this->hasEventHandlers(GeneratorConst::EVENT_STATUS_CHANGED)) {
            return;
        }

        $outbox = $this->getContainerEntry(OutboxEventService::class);
        if (!$outbox instanceof OutboxEventService) {
            return;
        }

        $outbox->recordEvent(GeneratorConst::EVENT_STATUS_CHANGED, $generatorId, [
            'generatorId' => $generatorId,
            'fromStatus'  => $fromStatus,
            'toStatus'    => $toStatus,
            'reason'      => $reason,
            'changedBy'   => $actorId,
        ]);
    }

    private function recordHourMeterUpdatedEvent(
        int $generatorId,
        float $fromHourMeter,
        float $toHourMeter,
        ?int $actorId
    ): void {
        if (!$this->hasEventHandlers(GeneratorConst::EVENT_HOUR_METER_UPDATED)) {
            return;
        }

        $outbox = $this->getContainerEntry(OutboxEventService::class);
        if (!$outbox instanceof OutboxEventService) {
            return;
        }

        $outbox->recordEvent(GeneratorConst::EVENT_HOUR_METER_UPDATED, $generatorId, [
            'generatorId'    => $generatorId,
            'fromHourMeter'  => $fromHourMeter,
            'toHourMeter'    => $toHourMeter,
            'changedAt'      => DateModel::getUtcNow(),
            'actorId'        => $actorId,
        ]);
    }

    private function hasEventHandlers(string $eventName): bool
    {
        $config = $this->getContainerEntry('config');
        if (!is_array($config)) {
            return false;
        }

        $handlers = $config['platform_event_handlers'][$eventName] ?? [];
        if (is_array($handlers)) {
            return $handlers !== [];
        }

        return (is_string($handlers) && $handlers !== '') || is_object($handlers);
    }

    /**
     * Láº¥y thÃ´ng sá»‘ ká»¹ thuáº­t tá»« payload thÃ´; GeneratorConst tá»± whitelist vÃ  cast kiá»ƒu.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function extractSpecs(array $payload): array
    {
        $specs = $payload['specs'] ?? [];
        if (is_string($specs)) {
            $specs = json_decode($specs, true);
        }

        return is_array($specs) ? $specs : [];
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

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float)$value;
    }
}
