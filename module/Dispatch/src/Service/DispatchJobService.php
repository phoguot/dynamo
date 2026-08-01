<?php

declare(strict_types=1);

namespace Dispatch\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Crm\Service\SiteService;
use DateTimeImmutable;
use Dispatch\Form\Assignment\AssignmentSaveForm;
use Dispatch\Form\DispatchJob\DispatchJobSaveForm;
use Dispatch\Form\DispatchJob\DispatchJobSearchForm;
use Dispatch\Form\DispatchJob\DispatchJobStatusForm;
use Dispatch\Model\Assignment\AssignmentConst;
use Dispatch\Model\Assignment\AssignmentMapper;
use Dispatch\Model\Assignment\AssignmentModel;
use Dispatch\Model\DispatchJob\DispatchJobConst;
use Dispatch\Model\DispatchJob\DispatchJobMapper;
use Dispatch\Model\DispatchJob\DispatchJobModel;
use Dispatch\Model\Vehicle\VehicleConst;
use Fleet\Service\GeneratorService;
use Platform\Service\OutboxEventService;
use Rental\Service\RentalOrderService;
use User\Model\AuditLog\AuditLogModel;
use User\Model\User\UserConst;
use User\Service\AuditLogService;
use User\Service\UserService;

class DispatchJobService extends AppServiceFactory
{
    private function mapper(): DispatchJobMapper
    {
        return $this->getContainerEntry(DispatchJobMapper::class);
    }

    private function assignmentMapper(): AssignmentMapper
    {
        return $this->getContainerEntry(AssignmentMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?DispatchJobModel $existing = null, array $values = []): DispatchJobSaveForm
    {
        $form = new DispatchJobSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): DispatchJobSearchForm
    {
        $form = new DispatchJobSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): DispatchJobStatusForm
    {
        return new DispatchJobStatusForm($this->getContainer());
    }

    public function newAssignmentForm(DispatchJobModel $job): AssignmentSaveForm
    {
        $form = new AssignmentSaveForm($this->getContainer());
        $form->setData([]);
        $form->fill(['jobId' => $job->getId()]);

        return $form;
    }

    public function searchDispatchJobs(array $payload = []): Paginator
    {
        $form = new DispatchJobSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new DispatchJobModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setJobType($this->nullIfBlank($formData['jobType'] ?? null));
        $criteria->setRentalOrderId($this->positiveIntOrNull($formData['rentalOrderId'] ?? null));
        $criteria->setGeneratorId($this->positiveIntOrNull($formData['generatorId'] ?? null));
        $criteria->setVehicleId($this->positiveIntOrNull($formData['vehicleId'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? DispatchJobConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchDispatchJobs($criteria, $paging);
    }

    public function getDispatchJob(int $id): DispatchJobModel
    {
        $item = $this->mapper()->getDispatchJob($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return AssignmentModel[] */
    public function assignmentsOf(DispatchJobModel $job): array
    {
        return $this->assignmentMapper()->fetchByJob((int)$job->getId());
    }

    public function nextStatuses(DispatchJobModel $job): array
    {
        $allowed = DispatchJobConst::STATUS_TRANSITIONS[(string)$job->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = DispatchJobConst::statusLabel($status);
        }

        return $result;
    }

    public function saveDispatchJob(array $payload = []): DispatchJobModel
    {
        $form = new DispatchJobSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->positiveIntOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getDispatchJob($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if (!in_array($existing->getStatus(), [DispatchJobConst::STATUS_NEW, DispatchJobConst::STATUS_SCHEDULED], true)) {
                throw new ValidationException(['status' => 'Chỉ được sửa lệnh mới tạo hoặc đã lên lịch.']);
            }
        }

        $jobNo = (string)($formData['jobNo'] ?? '');
        if ($mapper->getDispatchJobByNo($jobNo, $id) !== null) {
            throw new ValidationException(['jobNo' => 'Số lệnh này đã được dùng.']);
        }

        $rentalOrder = $this->getContainerEntry(RentalOrderService::class)->getRentalOrder((int)($formData['rentalOrderId'] ?? 0));
        $generatorId = (int)($formData['generatorId'] ?? 0);
        $this->getContainerEntry(GeneratorService::class)->getGenerator($generatorId);

        if ((int)$rentalOrder->getGeneratorId() !== $generatorId) {
            throw new ValidationException(['generatorId' => 'Máy của lệnh phải khớp máy đang gắn với đơn thuê.']);
        }

        $jobType = (string)($formData['jobType'] ?? '');
        $newGeneratorId = $jobType === DispatchJobConst::TYPE_SWAP
            ? $this->positiveIntOrNull($formData['newGeneratorId'] ?? null)
            : null;
        if ($newGeneratorId !== null) {
            $this->getContainerEntry(GeneratorService::class)->getGenerator($newGeneratorId);
        }

        $siteId = $this->positiveIntOrNull($formData['siteId'] ?? null);
        if ($siteId !== null) {
            $this->getContainerEntry(SiteService::class)->getSite($siteId);
        }

        $vehicleId = $this->positiveIntOrNull($formData['vehicleId'] ?? null);
        if ($vehicleId !== null) {
            $vehicle = $this->getContainerEntry(VehicleService::class)->getVehicle($vehicleId);
            if (in_array($vehicle->getStatus(), [VehicleConst::STATUS_MAINTENANCE, VehicleConst::STATUS_STOPPED], true)) {
                throw new ValidationException(['vehicleId' => 'Xe này không khả dụng để xếp lịch.']);
            }
        }

        $before = $existing !== null ? $this->dispatchJobAuditValues($existing) : null;

        $item = $existing ?? new DispatchJobModel();
        $item->setJobNo($jobNo);
        $item->setJobType($jobType);
        $item->setRentalOrderId((int)$rentalOrder->getId());
        $item->setGeneratorId($generatorId);
        $item->setNewGeneratorId($newGeneratorId);
        $item->setSiteId($siteId);
        $item->setVehicleId($vehicleId);
        $item->setScheduledAt($this->normalizeDateTime($formData['scheduledAt'] ?? null));
        $item->setStatus($existing?->getStatus() ?? DispatchJobConst::STATUS_NEW);
        $item->setPriority((string)($formData['priority'] ?? DispatchJobConst::PRIORITY_NORMAL));
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveDispatchJob($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            DispatchJobMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->dispatchJobAuditValues($saved)
        );

        return $saved;
    }

    public function changeStatus(array $payload = []): DispatchJobModel
    {
        $form = new DispatchJobStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $job = $this->getDispatchJob((int)($formData['id'] ?? 0));
        $fromStatus = (string)$job->getStatus();
        $toStatus = (string)($formData['status'] ?? '');

        if ($fromStatus === $toStatus) {
            return $job;
        }
        if (!DispatchJobConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển lệnh từ "%s" sang "%s".',
                DispatchJobConst::statusLabel($fromStatus),
                DispatchJobConst::statusLabel($toStatus)
            )]);
        }
        if ($toStatus === DispatchJobConst::STATUS_SCHEDULED && $job->getScheduledAt() === null) {
            throw new ValidationException(['status' => 'Cần nhập lịch hẹn trước khi chuyển sang đã lên lịch.']);
        }

        $actorId = $this->currentUserId();
        $attrs = ['status' => $toStatus];
        $events = [];
        if ($toStatus === DispatchJobConst::STATUS_ON_ROUTE) {
            $departedAt = DateModel::getUtcNow();
            $attrs['departedAt'] = $departedAt;
            $events[] = [
                'eventName' => DispatchJobConst::EVENT_JOB_STARTED,
                'payload'   => $this->dispatchEventPayload($job, [
                    'startedAt' => $departedAt,
                    'actorId'   => $actorId,
                ]),
            ];
        }
        if ($toStatus === DispatchJobConst::STATUS_WORKING) {
            $attrs['arrivedAt'] = DateModel::getUtcNow();
        }
        if ($toStatus === DispatchJobConst::STATUS_COMPLETED) {
            $completedAt = DateModel::getUtcNow();
            $attrs['completedAt'] = $completedAt;
            $events[] = $this->completedDispatchEvent($job, $formData, $completedAt, $actorId);
        }
        if ($toStatus === DispatchJobConst::STATUS_FAILED) {
            $reason = $this->nullIfBlank($formData['failReason'] ?? null);
            if ($reason === null) {
                throw new ValidationException(['failReason' => 'Cần nhập lý do lệnh thất bại.']);
            }
            $attrs['failReason'] = $reason;
            $attrs['feeBearer'] = $this->nullIfBlank($formData['feeBearer'] ?? null);
        }

        if ($toStatus === DispatchJobConst::STATUS_CANCELLED) {
            $before = $this->dispatchJobAuditValues($job);
            $this->mapper()->transactional(function () use ($job): void {
                $this->assignmentMapper()->clearByJob((int)$job->getId());
                $this->mapper()->deleteDispatchJob((int)$job->getId());
            });
            $job->setStatus($toStatus);

            $this->auditLog()->write(
                AuditLogModel::ACTION_DELETE,
                DispatchJobMapper::TABLE_NAME,
                $job->getId(),
                $before,
                null
            );

            return $job;
        }

        $updated = $this->mapper()->transactional(function () use ($job, $attrs, $actorId, $fromStatus, $toStatus, $formData, $events): DispatchJobModel {
            $this->mapper()->updateAttrsDispatchJob((int)$job->getId(), $attrs, $actorId);
            $updated = $this->getDispatchJob((int)$job->getId());

            $this->auditLog()->write(
                AuditLogModel::ACTION_STATUS_CHANGED,
                DispatchJobMapper::TABLE_NAME,
                $updated->getId(),
                ['status' => $fromStatus],
                [
                    'status'     => $toStatus,
                    'failReason' => $this->nullIfBlank($formData['failReason'] ?? null),
                    'feeBearer'  => $this->nullIfBlank($formData['feeBearer'] ?? null),
                ]
            );

            $this->recordDispatchEvents((int)$updated->getId(), $events);

            return $updated;
        });

        return $updated;
    }

    public function saveAssignment(array $payload = []): AssignmentModel
    {
        $form = new AssignmentSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $jobId = (int)($formData['jobId'] ?? 0);
        $this->getDispatchJob($jobId);

        $user = $this->getContainerEntry(UserService::class)->getUser((int)($formData['userId'] ?? 0));
        $roleInJob = (string)($formData['roleInJob'] ?? '');
        if ($roleInJob === AssignmentConst::ROLE_TECHNICIAN && !in_array($user->getRole(), [UserConst::ROLE_TECHNICIAN, UserConst::ROLE_DISPATCHER], true)) {
            throw new ValidationException(['userId' => 'Người được gán kỹ thuật phải là kỹ thuật viên hoặc điều phối.']);
        }

        if ($this->assignmentMapper()->getDuplicate($jobId, (int)$user->getId(), $roleInJob) !== null) {
            throw new ValidationException(['userId' => 'Người này đã được gán cùng vai trò trong lệnh.']);
        }

        $item = new AssignmentModel();
        $item->setJobId($jobId);
        $item->setUserId((int)$user->getId());
        $item->setRoleInJob($roleInJob);
        $item->setIsLead((bool)(int)($formData['isLead'] ?? 0));
        $item->setCreatedBy($this->currentUserId());

        $saved = $this->assignmentMapper()->transactional(function () use ($item): AssignmentModel {
            if ($item->getIsLead()) {
                $this->assignmentMapper()->clearLeadByJob((int)$item->getJobId());
            }

            return $this->assignmentMapper()->saveAssignment($item);
        });

        $this->auditLog()->write(
            AuditLogModel::ACTION_CREATE,
            AssignmentMapper::TABLE_NAME,
            $saved->getId(),
            null,
            $this->assignmentAuditValues($saved)
        );

        return $saved;
    }

    public function deleteAssignment(int $assignmentId): void
    {
        $item = $this->assignmentMapper()->getAssignment($assignmentId);
        if ($item === null) {
            throw new NotFoundException();
        }

        $before = $this->assignmentAuditValues($item);
        $this->assignmentMapper()->deleteAssignment($assignmentId);

        $this->auditLog()->write(
            AuditLogModel::ACTION_DELETE,
            AssignmentMapper::TABLE_NAME,
            $assignmentId,
            $before,
            null
        );
    }

    /**
     * @param array<string, mixed> $formData
     * @return array{eventName:string,payload:array<string,mixed>}
     */
    private function completedDispatchEvent(
        DispatchJobModel $job,
        array $formData,
        string $completedAt,
        ?int $actorId
    ): array {
        if ($job->getJobType() === DispatchJobConst::TYPE_DELIVERY) {
            $startHour = $this->floatOrNull($formData['startHourMeter'] ?? null);
            if ($startHour === null) {
                throw new ValidationException(['startHourMeter' => 'Can nhap chi so gio may dau khi hoan thanh lenh giao.']);
            }

            return [
                'eventName' => DispatchJobConst::EVENT_HANDOVER_COMPLETED,
                'payload'   => $this->dispatchEventPayload($job, [
                    'hourMeter'   => $startHour,
                    'completedAt' => $completedAt,
                    'recordedAt'  => $completedAt,
                    'actorId'     => $actorId,
                ]),
            ];
        }

        if ($job->getJobType() === DispatchJobConst::TYPE_RECOVERY) {
            $endHour = $this->floatOrNull($formData['endHourMeter'] ?? null);
            if ($endHour === null) {
                throw new ValidationException(['endHourMeter' => 'Can nhap chi so gio may cuoi khi hoan thanh lenh thu hoi.']);
            }

            $actualEndDate = $this->nullIfBlank($formData['actualEndDate'] ?? null)
                ?? (new DateTimeImmutable('today'))->format('Y-m-d');

            return [
                'eventName' => DispatchJobConst::EVENT_RETURN_COMPLETED,
                'payload'   => $this->dispatchEventPayload($job, [
                    'hourMeter'     => $endHour,
                    'actualEndDate' => $actualEndDate,
                    'completedAt'   => $completedAt,
                    'recordedAt'    => $completedAt,
                    'actorId'       => $actorId,
                ]),
            ];
        }

        if ($job->getJobType() === DispatchJobConst::TYPE_SWAP) {
            $oldHour = $this->floatOrNull($formData['endHourMeter'] ?? null);
            if ($oldHour === null) {
                throw new ValidationException(['endHourMeter' => 'Can nhap chi so gio may cu khi hoan thanh lenh doi may.']);
            }

            $newHour = $this->floatOrNull($formData['startHourMeter'] ?? null);
            if ($newHour === null) {
                throw new ValidationException(['startHourMeter' => 'Can nhap chi so gio may moi khi hoan thanh lenh doi may.']);
            }

            $newGeneratorId = $job->getNewGeneratorId() !== null ? (int)$job->getNewGeneratorId() : null;
            if ($newGeneratorId === null || $newGeneratorId <= 0) {
                throw new ValidationException(['newGeneratorId' => 'Lenh doi may can co may moi.']);
            }

            $actualEndDate = $this->nullIfBlank($formData['actualEndDate'] ?? null)
                ?? (new DateTimeImmutable('today'))->format('Y-m-d');

            return [
                'eventName' => DispatchJobConst::EVENT_SWAP_COMPLETED,
                'payload'   => $this->dispatchEventPayload($job, [
                    'oldGeneratorId' => (int)$job->getGeneratorId(),
                    'oldHourMeter'   => $oldHour,
                    'newGeneratorId' => $newGeneratorId,
                    'newHourMeter'   => $newHour,
                    'actualEndDate'  => $actualEndDate,
                    'completedAt'    => $completedAt,
                    'recordedAt'     => $completedAt,
                    'actorId'        => $actorId,
                ]),
            ];
        }

        throw new ValidationException(['jobType' => 'Loai lenh dieu phoi khong hop le.']);
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function dispatchEventPayload(DispatchJobModel $job, array $extra = []): array
    {
        return array_merge([
            'jobId'         => (int)$job->getId(),
            'jobNo'         => (string)$job->getJobNo(),
            'jobType'       => (string)$job->getJobType(),
            'rentalOrderId' => (int)$job->getRentalOrderId(),
            'generatorId'   => (int)$job->getGeneratorId(),
            'newGeneratorId'=> $job->getNewGeneratorId() !== null ? (int)$job->getNewGeneratorId() : null,
        ], $extra);
    }

    /**
     * @param list<array{eventName:string,payload:array<string,mixed>}> $events
     */
    private function recordDispatchEvents(int $aggregateId, array $events): void
    {
        if ($events === []) {
            return;
        }

        $outbox = $this->getContainerEntry(OutboxEventService::class);
        if (!$outbox instanceof OutboxEventService) {
            return;
        }

        foreach ($events as $event) {
            $outbox->recordEvent($event['eventName'], $aggregateId, $event['payload']);
        }
    }

    private function formValues(DispatchJobModel $item): array
    {
        return [
            'id'             => $item->getId(),
            'jobNo'          => $item->getJobNo(),
            'jobType'        => $item->getJobType(),
            'rentalOrderId'  => $item->getRentalOrderId(),
            'generatorId'    => $item->getGeneratorId(),
            'newGeneratorId' => $item->getNewGeneratorId(),
            'siteId'         => $item->getSiteId(),
            'vehicleId'      => $item->getVehicleId(),
            'scheduledAt'    => $this->htmlDateTime($item->getScheduledAt()),
            'priority'       => $item->getPriority(),
            'note'           => $item->getNote(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchJobAuditValues(DispatchJobModel $item): array
    {
        return $this->formValues($item) + [
            'status'     => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
            'departedAt' => $item->getDepartedAt(),
            'arrivedAt'  => $item->getArrivedAt(),
            'completedAt' => $item->getCompletedAt(),
            'failReason' => $item->getFailReason(),
            'feeBearer'  => $item->getFeeBearer(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentAuditValues(AssignmentModel $item): array
    {
        return [
            'id'        => $item->getId(),
            'jobId'     => $item->getJobId(),
            'userId'    => $item->getUserId(),
            'roleInJob' => ['id' => $item->getRoleInJob(), 'name' => $item->getRoleLabel()],
            'isLead'    => (bool)$item->getIsLead(),
        ];
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = $this->nullIfBlank($value);
        if ($value === null) {
            return null;
        }
        $value = str_replace('T', ' ', $value);

        return strlen($value) === 16 ? $value . ':00' : $value;
    }

    private function htmlDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace(' ', 'T', substr($value, 0, 16));
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

    private function positiveIntOrNull(mixed $value): ?int
    {
        $value = $this->intOrNull($value);
        return $value !== null && $value > 0 ? $value : null;
    }
}
