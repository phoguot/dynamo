<?php

declare(strict_types=1);

namespace Maintenance\Service;

use DateInterval;
use DateTimeImmutable;
use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Fleet\Service\GeneratorService;
use Maintenance\Form\MaintenanceJob\MaintenanceJobSaveForm;
use Maintenance\Form\MaintenanceJob\MaintenanceJobSearchForm;
use Maintenance\Form\MaintenanceJob\MaintenanceJobStatusForm;
use Maintenance\Form\PartUsed\PartUsedSaveForm;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;
use Maintenance\Model\MaintenanceJob\MaintenanceJobMapper;
use Maintenance\Model\MaintenanceJob\MaintenanceJobModel;
use Maintenance\Model\PartUsed\PartUsedMapper;
use Maintenance\Model\PartUsed\PartUsedModel;
use Maintenance\Model\Schedule\ScheduleConst;
use Maintenance\Model\Schedule\ScheduleMapper;
use Maintenance\Model\Schedule\ScheduleModel;
use Platform\Service\OutboxEventService;
use User\Model\AuditLog\AuditLogModel;
use User\Model\User\UserConst;
use User\Service\AuditLogService;
use User\Service\UserService;

class MaintenanceJobService extends AppServiceFactory
{
    private function mapper(): MaintenanceJobMapper
    {
        return $this->getContainerEntry(MaintenanceJobMapper::class);
    }

    private function partMapper(): PartUsedMapper
    {
        return $this->getContainerEntry(PartUsedMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?MaintenanceJobModel $existing = null, array $values = []): MaintenanceJobSaveForm
    {
        $form = new MaintenanceJobSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): MaintenanceJobSearchForm
    {
        $form = new MaintenanceJobSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): MaintenanceJobStatusForm
    {
        return new MaintenanceJobStatusForm($this->getContainer());
    }

    public function newPartForm(MaintenanceJobModel $job): PartUsedSaveForm
    {
        $form = new PartUsedSaveForm($this->getContainer());
        $form->setData([]);
        $form->fill(['jobId' => $job->getId()]);

        return $form;
    }

    public function searchMaintenanceJobs(array $payload = []): Paginator
    {
        $form = new MaintenanceJobSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new MaintenanceJobModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setGeneratorId($this->positiveIntOrNull($formData['generatorId'] ?? null));
        $criteria->setScheduleId($this->positiveIntOrNull($formData['scheduleId'] ?? null));
        $criteria->setJobType($this->nullIfBlank($formData['jobType'] ?? null));
        $criteria->setPriority($this->nullIfBlank($formData['priority'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));
        $criteria->setAssigneeId($this->positiveIntOrNull($formData['assigneeId'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? MaintenanceJobConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchMaintenanceJobs($criteria, $paging);
    }

    public function getMaintenanceJob(int $id): MaintenanceJobModel
    {
        $item = $this->mapper()->getMaintenanceJob($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return PartUsedModel[] */
    public function partsOf(MaintenanceJobModel $job): array
    {
        return $this->partMapper()->fetchByJob((int)$job->getId());
    }

    public function nextStatuses(MaintenanceJobModel $job): array
    {
        $allowed = MaintenanceJobConst::STATUS_TRANSITIONS[(string)$job->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = MaintenanceJobConst::statusLabel($status);
        }

        return $result;
    }

    public function saveMaintenanceJob(array $payload = []): MaintenanceJobModel
    {
        $form = new MaintenanceJobSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->positiveIntOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getMaintenanceJob($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if (!in_array($existing->getStatus(), [MaintenanceJobConst::STATUS_WAITING_SCHEDULE, MaintenanceJobConst::STATUS_SCHEDULED], true)) {
                throw new ValidationException(['status' => 'Chỉ được sửa phiếu chờ lịch hoặc đã lên lịch.']);
            }
        }

        $jobNo = (string)($formData['jobNo'] ?? '');
        if ($mapper->getMaintenanceJobByNo($jobNo, $id) !== null) {
            throw new ValidationException(['jobNo' => 'Số phiếu này đã được dùng.']);
        }

        $idempotencyKey = $this->nullIfBlank($formData['idempotencyKey'] ?? null);
        if ($idempotencyKey !== null && $mapper->getMaintenanceJobByIdempotencyKey($idempotencyKey, $id) !== null) {
            throw new ValidationException(['idempotencyKey' => 'Idempotency key này đã được dùng.']);
        }

        $generatorId = (int)($formData['generatorId'] ?? 0);
        $this->getContainerEntry(GeneratorService::class)->getGenerator($generatorId);

        $jobType = (string)($formData['jobType'] ?? '');
        $scheduleId = $jobType === MaintenanceJobConst::TYPE_MAINTENANCE
            ? $this->positiveIntOrNull($formData['scheduleId'] ?? null)
            : null;
        if ($scheduleId !== null) {
            $schedule = $this->getContainerEntry(ScheduleMapper::class)->getSchedule($scheduleId);
            if ($schedule === null) {
                throw new NotFoundException();
            }
            if ((int)$schedule->getGeneratorId() !== $generatorId) {
                throw new ValidationException(['scheduleId' => 'Lịch bảo trì phải thuộc cùng máy với phiếu.']);
            }
        }

        $assigneeId = $this->positiveIntOrNull($formData['assigneeId'] ?? null);
        if ($assigneeId !== null) {
            $user = $this->getContainerEntry(UserService::class)->getUser($assigneeId);
            if (!in_array($user->getRole(), [UserConst::ROLE_TECHNICIAN, UserConst::ROLE_DISPATCHER], true)) {
                throw new ValidationException(['assigneeId' => 'Người thực hiện phải là kỹ thuật viên hoặc điều phối.']);
            }
        }

        $before = $existing !== null ? $this->maintenanceJobAuditValues($existing) : null;

        $item = $existing ?? new MaintenanceJobModel();
        $item->setJobNo($jobNo);
        $item->setGeneratorId($generatorId);
        $item->setScheduleId($scheduleId);
        $item->setJobType($jobType);
        $item->setPriority((string)($formData['priority'] ?? MaintenanceJobConst::PRIORITY_NORMAL));
        $item->setStatus($existing?->getStatus() ?? MaintenanceJobConst::STATUS_WAITING_SCHEDULE);
        $item->setTriggerReason($this->nullIfBlank($formData['triggerReason'] ?? null));
        $item->setTriggerHourMeter($this->floatOrNull($formData['triggerHourMeter'] ?? null));
        $item->setIdempotencyKey($idempotencyKey);
        $item->setScheduledDate($this->nullIfBlank($formData['scheduledDate'] ?? null));
        $item->setAssigneeId($assigneeId);
        $item->setLaborCost(max(0, (int)($formData['laborCost'] ?? 0)));
        $item->setPartsCost($existing?->getPartsCost() ?? 0);
        $item->setTotalCost($item->getLaborCost() + $item->getPartsCost());
        $item->setFindings($this->nullIfBlank($formData['findings'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveMaintenanceJob($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            MaintenanceJobMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->maintenanceJobAuditValues($saved)
        );

        return $saved;
    }

    /**
     * @return list<MaintenanceJobModel>
     */
    public function createDueJobsFromHourMeter(int $generatorId, float $hourMeter, ?int $actorId = null): array
    {
        if ($generatorId <= 0 || $hourMeter < 0) {
            return [];
        }

        $scheduleMapper = $this->getContainerEntry(ScheduleMapper::class);
        if (!$scheduleMapper instanceof ScheduleMapper) {
            return [];
        }

        $schedules = $scheduleMapper->fetchDueByHourMeter($generatorId, $hourMeter);
        if ($schedules === []) {
            return [];
        }

        return $this->mapper()->transactional(function () use ($schedules, $hourMeter, $actorId): array {
            $created = [];
            foreach ($schedules as $schedule) {
                $dueHour = (float)$schedule->getNextDueHour();
                $idempotencyKey = $this->hourDueIdempotencyKey((int)$schedule->getId(), $dueHour);
                if ($this->mapper()->getMaintenanceJobByIdempotencyKey($idempotencyKey) !== null) {
                    continue;
                }

                $job = (new MaintenanceJobModel())
                    ->setJobNo($this->hourDueJobNo((int)$schedule->getId(), $dueHour))
                    ->setGeneratorId((int)$schedule->getGeneratorId())
                    ->setScheduleId((int)$schedule->getId())
                    ->setJobType(MaintenanceJobConst::TYPE_MAINTENANCE)
                    ->setPriority(MaintenanceJobConst::PRIORITY_HIGH)
                    ->setStatus(MaintenanceJobConst::STATUS_WAITING_SCHEDULE)
                    ->setTriggerReason('hour_meter_due')
                    ->setTriggerHourMeter($hourMeter)
                    ->setIdempotencyKey($idempotencyKey)
                    ->setCreatedBy($actorId)
                    ->setUpdatedBy($actorId);

                $saved = $this->mapper()->saveMaintenanceJob($job);
                $this->auditLog()->write(
                    AuditLogModel::ACTION_CREATE,
                    MaintenanceJobMapper::TABLE_NAME,
                    $saved->getId(),
                    null,
                    $this->maintenanceJobAuditValues($saved),
                    $actorId
                );

                $created[] = $saved;
            }

            return $created;
        });
    }

    public function changeStatus(array $payload = []): MaintenanceJobModel
    {
        $form = new MaintenanceJobStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $job = $this->getMaintenanceJob((int)($formData['id'] ?? 0));
        $fromStatus = (string)$job->getStatus();
        $toStatus = (string)($formData['status'] ?? '');

        if ($fromStatus === $toStatus) {
            return $job;
        }
        if (!MaintenanceJobConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển phiếu từ "%s" sang "%s".',
                MaintenanceJobConst::statusLabel($fromStatus),
                MaintenanceJobConst::statusLabel($toStatus)
            )]);
        }
        if ($toStatus === MaintenanceJobConst::STATUS_SCHEDULED && $job->getScheduledDate() === null) {
            throw new ValidationException(['status' => 'Cần nhập ngày lên lịch trước khi chuyển sang đã lên lịch.']);
        }

        $actorId = $this->currentUserId();
        $changedAt = DateModel::getUtcNow();
        $attrs = ['status' => $toStatus];
        if ($toStatus === MaintenanceJobConst::STATUS_WORKING) {
            $attrs['startedAt'] = $changedAt;
        }
        if ($toStatus === MaintenanceJobConst::STATUS_COMPLETED) {
            $laborCost = $this->nullIfBlank($formData['laborCost'] ?? null) === null
                ? $job->getLaborCost()
                : max(0, (int)$formData['laborCost']);
            $partsCost = $this->partMapper()->sumLineAmountByJob((int)$job->getId());
            $attrs['completedAt'] = $changedAt;
            $attrs['laborCost'] = $laborCost;
            $attrs['partsCost'] = $partsCost;
            $attrs['totalCost'] = $laborCost + $partsCost;
            $attrs['findings'] = $this->nullIfBlank($formData['findings'] ?? null) ?? $job->getFindings();
        }
        if ($toStatus === MaintenanceJobConst::STATUS_CANCELLED) {
            $reason = $this->nullIfBlank($formData['cancelReason'] ?? null);
            if ($reason === null) {
                throw new ValidationException(['cancelReason' => 'Cần nhập lý do hủy phiếu.']);
            }

            $before = $this->maintenanceJobAuditValues($job) + ['cancelReason' => $reason];
            $this->mapper()->transactional(function () use ($job, $before, $fromStatus, $toStatus, $changedAt, $actorId): void {
                $this->partMapper()->clearByJob((int)$job->getId());
                $this->mapper()->deleteMaintenanceJob((int)$job->getId());

                $this->auditLog()->write(
                    AuditLogModel::ACTION_DELETE,
                    MaintenanceJobMapper::TABLE_NAME,
                    $job->getId(),
                    $before,
                    null
                );

                $this->recordMaintenanceStatusChangedEvent($job, $fromStatus, $toStatus, $changedAt, $actorId);
            });
            $job->setStatus($toStatus)->setCancelReason($reason);

            return $job;
        }

        $updated = $this->mapper()->transactional(function () use ($job, $attrs, $actorId, $fromStatus, $toStatus, $formData, $changedAt): MaintenanceJobModel {
            $this->mapper()->updateAttrsMaintenanceJob((int)$job->getId(), $attrs, $actorId);
            $updated = $this->getMaintenanceJob((int)$job->getId());

            $this->auditLog()->write(
                AuditLogModel::ACTION_STATUS_CHANGED,
                MaintenanceJobMapper::TABLE_NAME,
                $updated->getId(),
                ['status' => $fromStatus],
                [
                    'status'       => $toStatus,
                    'laborCost'    => $attrs['laborCost'] ?? null,
                    'partsCost'    => $attrs['partsCost'] ?? null,
                    'totalCost'    => $attrs['totalCost'] ?? null,
                    'cancelReason' => $this->nullIfBlank($formData['cancelReason'] ?? null),
                ]
            );

            $this->recordMaintenanceStatusChangedEvent($updated, $fromStatus, $toStatus, $changedAt, $actorId);
            if ($toStatus === MaintenanceJobConst::STATUS_COMPLETED) {
                $this->refreshScheduleAfterCompletion($updated, $actorId);
            }

            return $updated;
        });

        return $updated;
    }

    public function savePartUsed(array $payload = []): PartUsedModel
    {
        $form = new PartUsedSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $job = $this->getMaintenanceJob((int)($formData['jobId'] ?? 0));
        $this->assertJobCanEditParts($job);

        $quantity = (float)($formData['quantity'] ?? 0);
        $unitPrice = max(0, (int)($formData['unitPrice'] ?? 0));

        $item = new PartUsedModel();
        $item->setJobId((int)$job->getId());
        $item->setPartCode($this->nullIfBlank($formData['partCode'] ?? null));
        $item->setPartName((string)($formData['partName'] ?? ''));
        $item->setQuantity($quantity);
        $item->setUnit($this->nullIfBlank($formData['unit'] ?? null));
        $item->setUnitPrice($unitPrice);
        $item->setLineAmount((int)round($quantity * $unitPrice));
        $item->setSupplier($this->nullIfBlank($formData['supplier'] ?? null));
        $item->setCreatedBy($this->currentUserId());

        $saved = $this->partMapper()->savePartUsed($item);
        $this->refreshJobCosts($job);

        $this->auditLog()->write(
            AuditLogModel::ACTION_CREATE,
            PartUsedMapper::TABLE_NAME,
            $saved->getId(),
            null,
            $this->partUsedAuditValues($saved)
        );

        return $saved;
    }

    public function deletePartUsed(int $partId): void
    {
        $part = $this->partMapper()->getPartUsed($partId);
        if ($part === null) {
            throw new NotFoundException();
        }

        $job = $this->getMaintenanceJob((int)$part->getJobId());
        $this->assertJobCanEditParts($job);
        $before = $this->partUsedAuditValues($part);
        $this->partMapper()->deletePartUsed($partId);
        $this->refreshJobCosts($job);

        $this->auditLog()->write(
            AuditLogModel::ACTION_DELETE,
            PartUsedMapper::TABLE_NAME,
            $partId,
            $before,
            null
        );
    }

    private function refreshJobCosts(MaintenanceJobModel $job): void
    {
        $partsCost = $this->partMapper()->sumLineAmountByJob((int)$job->getId());
        $this->mapper()->updateAttrsMaintenanceJob((int)$job->getId(), [
            'partsCost' => $partsCost,
            'totalCost' => $job->getLaborCost() + $partsCost,
        ], $this->currentUserId());
    }

    private function assertJobCanEditParts(MaintenanceJobModel $job): void
    {
        if (in_array($job->getStatus(), [MaintenanceJobConst::STATUS_COMPLETED, MaintenanceJobConst::STATUS_CANCELLED], true)) {
            throw new ValidationException(['status' => 'Phiếu đã hoàn thành hoặc đã hủy không được sửa phụ tùng.']);
        }
    }

    private function formValues(MaintenanceJobModel $item): array
    {
        return [
            'id'               => $item->getId(),
            'jobNo'            => $item->getJobNo(),
            'generatorId'      => $item->getGeneratorId(),
            'scheduleId'       => $item->getScheduleId(),
            'jobType'          => $item->getJobType(),
            'priority'         => $item->getPriority(),
            'triggerReason'    => $item->getTriggerReason(),
            'triggerHourMeter' => $item->getTriggerHourMeter(),
            'idempotencyKey'   => $item->getIdempotencyKey(),
            'scheduledDate'    => $item->getScheduledDate(),
            'assigneeId'       => $item->getAssigneeId(),
            'laborCost'        => $item->getLaborCost(),
            'findings'         => $item->getFindings(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceJobAuditValues(MaintenanceJobModel $item): array
    {
        return $this->formValues($item) + [
            'status'    => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
            'partsCost' => $item->getPartsCost(),
            'totalCost' => $item->getTotalCost(),
        ];
    }

    private function recordMaintenanceStatusChangedEvent(
        MaintenanceJobModel $job,
        string $fromStatus,
        string $toStatus,
        string $changedAt,
        ?int $actorId
    ): void {
        $outbox = $this->getContainerEntry(OutboxEventService::class);
        if (!$outbox instanceof OutboxEventService) {
            return;
        }

        $outbox->recordEvent(
            MaintenanceJobConst::EVENT_STATUS_CHANGED,
            (int)$job->getId(),
            $this->maintenanceStatusEventPayload($job, $fromStatus, $toStatus, $changedAt, $actorId)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceStatusEventPayload(
        MaintenanceJobModel $job,
        string $fromStatus,
        string $toStatus,
        string $changedAt,
        ?int $actorId
    ): array {
        return [
            'jobId'         => (int)$job->getId(),
            'jobNo'         => (string)$job->getJobNo(),
            'generatorId'   => (int)$job->getGeneratorId(),
            'scheduleId'    => $job->getScheduleId() !== null ? (int)$job->getScheduleId() : null,
            'jobType'       => (string)$job->getJobType(),
            'fromStatus'    => $fromStatus,
            'toStatus'      => $toStatus,
            'scheduledDate' => $job->getScheduledDate(),
            'assigneeId'    => $job->getAssigneeId() !== null ? (int)$job->getAssigneeId() : null,
            'changedAt'     => $changedAt,
            'actorId'       => $actorId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function partUsedAuditValues(PartUsedModel $item): array
    {
        return [
            'id'         => $item->getId(),
            'jobId'      => $item->getJobId(),
            'partCode'   => $item->getPartCode(),
            'partName'   => $item->getPartName(),
            'quantity'   => $item->getQuantity(),
            'unit'       => ['id' => $item->getUnit(), 'name' => $item->getUnitLabel()],
            'unitPrice'  => $item->getUnitPrice(),
            'lineAmount' => $item->getLineAmount(),
            'supplier'   => $item->getSupplier(),
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

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float)$value;
    }
}
