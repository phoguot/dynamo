<?php

declare(strict_types=1);

namespace Crm\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Crm\Form\Contact\ContactSaveForm;
use Crm\Model\Contact\ContactMapper;
use Crm\Model\Contact\ContactModel;
use Crm\Model\Customer\CustomerMapper;
use Crm\Model\Customer\CustomerModel;
use Crm\Model\Site\SiteMapper;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class ContactService extends AppServiceFactory
{
    private function mapper(): ContactMapper
    {
        return $this->getContainerEntry(ContactMapper::class);
    }

    private function customerMapper(): CustomerMapper
    {
        return $this->getContainerEntry(CustomerMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    private function siteMapper(): SiteMapper
    {
        return $this->getContainerEntry(SiteMapper::class);
    }

    public function newSaveForm(?ContactModel $existing = null, ?CustomerModel $customer = null, array $values = []): ContactSaveForm
    {
        $form = new ContactSaveForm($this->getContainer());
        $form->setData($values);
        if ($customer !== null) {
            $form->fill(['customerId' => $customer->getId()]);
        }
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function getContact(int $id): ContactModel
    {
        $item = $this->mapper()->getContact($id);
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

    public function saveContact(array $payload = []): ContactModel
    {
        $form = new ContactSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $customerId = (int)($formData['customerId'] ?? 0);
        $this->getCustomer($customerId);

        $siteId = $this->intOrNull($formData['siteId'] ?? null);
        if ($siteId !== null) {
            $site = $this->siteMapper()->getSite($siteId);
            if ($site === null || (int)$site->getCustomerId() !== $customerId) {
                throw new ValidationException(['siteId' => 'Công trình không thuộc khách hàng này.']);
            }
        }

        $existing = null;
        if ($id !== null) {
            $existing = $this->getContact($id);
            if ((int)$existing->getCustomerId() !== $customerId) {
                throw new NotFoundException();
            }
        }

        $before = $existing?->getRespContact();

        $item = $existing ?? new ContactModel();
        $item->setCustomerId($customerId);
        $item->setSiteId($siteId);
        $item->setFullName((string)($formData['fullName'] ?? ''));
        $item->setPosition($this->nullIfBlank($formData['position'] ?? null));
        $item->setPhone($this->nullIfBlank($formData['phone'] ?? null));
        $item->setEmail($this->nullIfBlank($formData['email'] ?? null));
        $item->setIsPrimary((bool)((int)($formData['isPrimary'] ?? 0)));
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());

        if ($existing === null) {
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $this->mapper()->saveContact($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            ContactMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespContact()
        );

        return $saved;
    }

    private function formValues(ContactModel $item): array
    {
        return [
            'id'         => $item->getId(),
            'customerId' => $item->getCustomerId(),
            'siteId'     => $item->getSiteId(),
            'fullName'   => $item->getFullName(),
            'position'   => $item->getPosition(),
            'phone'      => $item->getPhone(),
            'email'      => $item->getEmail(),
            'isPrimary'  => $item->getIsPrimary() ? 1 : 0,
            'note'       => $item->getNote(),
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
}
