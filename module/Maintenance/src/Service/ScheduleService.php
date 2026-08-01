<?php

declare(strict_types=1);

namespace Maintenance\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Fleet\Service\GeneratorService;
use Maintenance\Form\Schedule\ScheduleSaveForm;
use Maintenance\Form\Schedule\ScheduleSearchForm;
use Maintenance\Model\Schedule\ScheduleConst;
use Maintenance\Model\Schedule\ScheduleMapper;
use Maintenance\Model\Schedule\ScheduleModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class ScheduleService extends AppServiceFactory
{
    private function mapper(): ScheduleMapper
    {
        return $this->getContainerEntry(ScheduleMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?ScheduleModel $existing = null, array $values = []): ScheduleSaveForm
    {
        $form = new ScheduleSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): ScheduleSearchForm
    {
        $form = new ScheduleSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function searchSchedules(array $payload = []): Paginator
    {
        $form = new ScheduleSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new ScheduleModel();
        $criteria->setGeneratorId($this->positiveIntOrNull($formData['generatorId'] ?? null));
        $criteria->setScheduleType($this->nullIfBlank($formData['scheduleType'] ?? null));
        $criteria->setIsActive($this->boolOrNull($formData['isActive'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? ScheduleConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchSchedules($criteria, $paging);
    }

    public function getSchedule(int $id): ScheduleModel
    {
        $item = $this->mapper()->getSchedule($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function saveSchedule(array $payload = []): ScheduleModel
    {
        $form = new ScheduleSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->positiveIntOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getSchedule($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $generatorId = (int)($formData['generatorId'] ?? 0);
        $this->getContainerEntry(GeneratorService::class)->getGenerator($generatorId);

        $scheduleType = (string)($formData['scheduleType'] ?? '');
        if ($mapper->getScheduleByGeneratorAndType($generatorId, $scheduleType, $id) !== null) {
            throw new ValidationException(['scheduleType' => 'Máy này đã có lịch cùng loại.']);
        }

        $before = $existing !== null ? $this->scheduleAuditValues($existing) : null;

        $item = $existing ?? new ScheduleModel();
        $item->setGeneratorId($generatorId);
        $item->setScheduleType($scheduleType);
        $item->setIntervalHours($this->usesHour($scheduleType) ? $this->positiveFloatOrNull($formData['intervalHours'] ?? null) : null);
        $item->setIntervalDays($this->usesDay($scheduleType) ? $this->positiveIntOrNull($formData['intervalDays'] ?? null) : null);
        $item->setLastServiceHour($this->usesHour($scheduleType) ? $this->floatOrNull($formData['lastServiceHour'] ?? null) : null);
        $item->setLastServiceDate($this->usesDay($scheduleType) ? $this->nullIfBlank($formData['lastServiceDate'] ?? null) : null);
        $item->setNextDueHour($this->usesHour($scheduleType) ? $this->floatOrNull($formData['nextDueHour'] ?? null) : null);
        $item->setNextDueDate($this->usesDay($scheduleType) ? $this->nullIfBlank($formData['nextDueDate'] ?? null) : null);
        $activeValue = $formData['isActive'] ?? 1;
        $item->setIsActive($activeValue === '' ? 1 : ((int)$activeValue === 0 ? 0 : 1));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveSchedule($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            ScheduleMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->scheduleAuditValues($saved)
        );

        return $saved;
    }

    private function formValues(ScheduleModel $item): array
    {
        return [
            'id'              => $item->getId(),
            'generatorId'     => $item->getGeneratorId(),
            'scheduleType'    => $item->getScheduleType(),
            'intervalHours'   => $item->getIntervalHours(),
            'intervalDays'    => $item->getIntervalDays(),
            'lastServiceHour' => $item->getLastServiceHour(),
            'lastServiceDate' => $item->getLastServiceDate(),
            'nextDueHour'     => $item->getNextDueHour(),
            'nextDueDate'     => $item->getNextDueDate(),
            'isActive'        => $item->getIsActive(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleAuditValues(ScheduleModel $item): array
    {
        return $this->formValues($item) + [
            'scheduleTypeLabel' => $item->getTypeLabel(),
        ];
    }

    private function usesHour(string $type): bool
    {
        return in_array($type, [ScheduleConst::TYPE_HOUR, ScheduleConst::TYPE_BOTH], true);
    }

    private function usesDay(string $type): bool
    {
        return in_array($type, [ScheduleConst::TYPE_DAY, ScheduleConst::TYPE_BOTH], true);
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

    private function boolOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)((bool)(int)$value);
    }

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float)$value;
    }

    private function positiveFloatOrNull(mixed $value): ?float
    {
        $value = $this->floatOrNull($value);
        return $value !== null && $value > 0 ? $value : null;
    }
}
