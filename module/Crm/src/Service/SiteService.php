<?php

declare(strict_types=1);

namespace Crm\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Crm\Form\Site\SiteSaveForm;
use Crm\Model\Customer\CustomerMapper;
use Crm\Model\Customer\CustomerModel;
use Crm\Model\Site\SiteConst;
use Crm\Model\Site\SiteMapper;
use Crm\Model\Site\SiteModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class SiteService extends AppServiceFactory
{
    private function mapper(): SiteMapper
    {
        return $this->getContainerEntry(SiteMapper::class);
    }

    private function customerMapper(): CustomerMapper
    {
        return $this->getContainerEntry(CustomerMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newSaveForm(?SiteModel $existing = null, ?CustomerModel $customer = null, array $values = []): SiteSaveForm
    {
        $form = new SiteSaveForm($this->getContainer());
        $form->setData($values);
        if ($customer !== null) {
            $form->fill(['customerId' => $customer->getId()]);
        }
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function getSite(int $id): SiteModel
    {
        $item = $this->mapper()->getSite($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function getCustomer(int $id): CustomerModel
    {
        $customer = $this->customerMapper()->getCustomer($id);
        if ($customer === null) {
            throw new NotFoundException();
        }

        return $customer;
    }

    public function saveSite(array $payload = []): SiteModel
    {
        $form = new SiteSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $customerId = (int)($formData['customerId'] ?? 0);
        $this->getCustomer($customerId);

        $existing = null;
        if ($id !== null) {
            $existing = $this->getSite($id);
            if ((int)$existing->getCustomerId() !== $customerId) {
                throw new NotFoundException();
            }
        }

        $code = $this->nullIfBlank($formData['code'] ?? null);
        if ($code !== null && $this->mapper()->getSiteByCode($customerId, $code, $id) !== null) {
            throw new ValidationException(['code' => 'Mã công trình này đã được dùng cho khách hàng này.']);
        }

        $before = $existing?->getRespSite();

        $item = $existing ?? new SiteModel();
        $item->setCustomerId($customerId);
        $item->setCode($code);
        $item->setName((string)($formData['name'] ?? ''));
        $item->setAddress($this->nullIfBlank($formData['address'] ?? null));
        $item->setLatitude($this->floatOrNull($formData['latitude'] ?? null));
        $item->setLongitude($this->floatOrNull($formData['longitude'] ?? null));
        $item->setContactName($this->nullIfBlank($formData['contactName'] ?? null));
        $item->setContactPhone($this->nullIfBlank($formData['contactPhone'] ?? null));
        $item->setInstallConditions($this->nullIfBlank($formData['installConditions'] ?? null));
        $item->setAccessNote($this->nullIfBlank($formData['accessNote'] ?? null));
        $item->setStatus((string)($formData['status'] ?? SiteConst::STATUS_HOAT_DONG));
        $item->setUpdatedBy($this->currentUserId());

        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $this->mapper()->saveSite($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            SiteMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespSite()
        );

        return $saved;
    }

    private function formValues(SiteModel $item): array
    {
        return [
            'id'                => $item->getId(),
            'customerId'        => $item->getCustomerId(),
            'code'              => $item->getCode(),
            'name'              => $item->getName(),
            'address'           => $item->getAddress(),
            'latitude'          => $item->getLatitude(),
            'longitude'         => $item->getLongitude(),
            'contactName'       => $item->getContactName(),
            'contactPhone'      => $item->getContactPhone(),
            'installConditions' => $item->getInstallConditions(),
            'accessNote'        => $item->getAccessNote(),
            'status'            => $item->getStatus(),
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

    private function floatOrNull(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float)$value;
    }
}
