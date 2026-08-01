<?php

declare(strict_types=1);

namespace Dispatch\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Dispatch\Form\Vehicle\VehicleSaveForm;
use Dispatch\Form\Vehicle\VehicleSearchForm;
use Dispatch\Form\Vehicle\VehicleStatusForm;
use Dispatch\Model\Vehicle\VehicleConst;
use Dispatch\Model\Vehicle\VehicleMapper;
use Dispatch\Model\Vehicle\VehicleModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;
use User\Service\UserService;

class VehicleService extends AppServiceFactory
{
    private function mapper(): VehicleMapper
    {
        return $this->getContainerEntry(VehicleMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?VehicleModel $existing = null, array $values = []): VehicleSaveForm
    {
        $form = new VehicleSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): VehicleSearchForm
    {
        $form = new VehicleSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): VehicleStatusForm
    {
        return new VehicleStatusForm($this->getContainer());
    }

    public function searchVehicles(array $payload = []): Paginator
    {
        $form = new VehicleSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new VehicleModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setVehicleType($this->nullIfBlank($formData['vehicleType'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? VehicleConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchVehicles($criteria, $paging);
    }

    public function getVehicle(int $id): VehicleModel
    {
        $item = $this->mapper()->getVehicle($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function nextStatuses(VehicleModel $vehicle): array
    {
        $allowed = VehicleConst::STATUS_TRANSITIONS[(string)$vehicle->getStatus()] ?? [];
        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = VehicleConst::statusLabel($status);
        }

        return $result;
    }

    public function saveVehicle(array $payload = []): VehicleModel
    {
        $form = new VehicleSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->positiveIntOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getVehicle($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
            if ($existing->getStatus() === VehicleConst::STATUS_STOPPED) {
                throw new ValidationException(['status' => 'Xe đã ngừng khai thác không được sửa.']);
            }
        }

        $code = (string)($formData['code'] ?? '');
        if ($mapper->getVehicleByCode($code, $id) !== null) {
            throw new ValidationException(['code' => 'Mã xe này đã được dùng.']);
        }

        $plateNumber = (string)($formData['plateNumber'] ?? '');
        if ($mapper->getVehicleByPlate($plateNumber, $id) !== null) {
            throw new ValidationException(['plateNumber' => 'Biển số này đã được dùng.']);
        }

        $driverId = $this->positiveIntOrNull($formData['driverId'] ?? null);
        if ($driverId !== null) {
            $this->getContainerEntry(UserService::class)->getUser($driverId);
        }

        $before = $existing !== null ? $this->vehicleAuditValues($existing) : null;

        $item = $existing ?? new VehicleModel();
        $item->setCode($code);
        $item->setPlateNumber($plateNumber);
        $item->setVehicleType((string)($formData['vehicleType'] ?? ''));
        $item->setCapacityKg($this->positiveIntOrNull($formData['capacityKg'] ?? null));
        $item->setDriverId($driverId);
        $item->setStatus($existing?->getStatus() ?? VehicleConst::STATUS_READY);
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());
        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveVehicle($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            VehicleMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $this->vehicleAuditValues($saved)
        );

        return $saved;
    }

    public function changeStatus(array $payload = []): VehicleModel
    {
        $form = new VehicleStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $vehicle = $this->getVehicle((int)($formData['id'] ?? 0));
        $fromStatus = (string)$vehicle->getStatus();
        $toStatus = (string)($formData['status'] ?? '');

        if ($fromStatus === $toStatus) {
            return $vehicle;
        }
        if (!VehicleConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException(['status' => sprintf(
                'Không thể chuyển xe từ "%s" sang "%s".',
                VehicleConst::statusLabel($fromStatus),
                VehicleConst::statusLabel($toStatus)
            )]);
        }

        $this->mapper()->updateAttrsVehicle((int)$vehicle->getId(), ['status' => $toStatus], $this->currentUserId());
        $updated = $this->getVehicle((int)$vehicle->getId());

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            VehicleMapper::TABLE_NAME,
            $updated->getId(),
            ['status' => $fromStatus],
            ['status' => $toStatus, 'reason' => $this->nullIfBlank($formData['reason'] ?? null)]
        );

        return $updated;
    }

    private function formValues(VehicleModel $item): array
    {
        return [
            'id'          => $item->getId(),
            'code'        => $item->getCode(),
            'plateNumber' => $item->getPlateNumber(),
            'vehicleType' => $item->getVehicleType(),
            'capacityKg'  => $item->getCapacityKg(),
            'driverId'    => $item->getDriverId(),
            'note'        => $item->getNote(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleAuditValues(VehicleModel $item): array
    {
        return $this->formValues($item) + [
            'status' => ['id' => $item->getStatus(), 'name' => $item->getStatusLabel()],
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
