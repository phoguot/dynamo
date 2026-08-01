<?php

declare(strict_types=1);

namespace Crm\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Crm\Form\Customer\CustomerSaveForm;
use Crm\Form\Customer\CustomerSearchForm;
use Crm\Form\Customer\CustomerStatusForm;
use Crm\Model\Contact\ContactMapper;
use Crm\Model\Contact\ContactModel;
use Crm\Model\Customer\CustomerConst;
use Crm\Model\Customer\CustomerMapper;
use Crm\Model\Customer\CustomerModel;
use Crm\Model\Site\SiteMapper;
use Crm\Model\Site\SiteModel;
use User\Model\AuditLog\AuditLogModel;
use User\Service\AuditLogService;

class CustomerService extends AppServiceFactory
{
    private function mapper(): CustomerMapper
    {
        return $this->getContainerEntry(CustomerMapper::class);
    }

    private function siteMapper(): SiteMapper
    {
        return $this->getContainerEntry(SiteMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    private function contactMapper(): ContactMapper
    {
        return $this->getContainerEntry(ContactMapper::class);
    }

    public function newSaveForm(?CustomerModel $existing = null, array $values = []): CustomerSaveForm
    {
        $form = new CustomerSaveForm($this->getContainer());
        $form->setData($values);
        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    public function newSearchForm(array $query = []): CustomerSearchForm
    {
        $form = new CustomerSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): CustomerStatusForm
    {
        return new CustomerStatusForm($this->getContainer());
    }

    public function searchCustomers(array $payload = []): Paginator
    {
        $form = new CustomerSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $criteria = new CustomerModel();
        $criteria->addOption('keyword', $this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setCustomerType($this->nullIfBlank($formData['customerType'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? CustomerConst::SORT_DEFAULT;
        $paging['dir'] = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchCustomers($criteria, $paging);
    }

    public function getCustomer(int $id): CustomerModel
    {
        $item = $this->mapper()->getCustomer($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    /** @return SiteModel[] */
    public function sitesOf(CustomerModel $customer): array
    {
        return $this->siteMapper()->fetchByCustomer((int)$customer->getId());
    }

    /** @return ContactModel[] */
    public function contactsOf(CustomerModel $customer): array
    {
        return $this->contactMapper()->fetchByCustomer((int)$customer->getId());
    }

    public function saveCustomer(array $payload = []): CustomerModel
    {
        $form = new CustomerSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getCustomer($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $code = (string)($formData['code'] ?? '');
        if ($mapper->getCustomerByCode($code, $id) !== null) {
            throw new ValidationException(['code' => 'Mã khách hàng này đã được dùng cho khách khác.']);
        }

        $before = $existing?->getRespCustomer();

        $item = $existing ?? new CustomerModel();
        $item->setCode($code);
        $item->setName((string)($formData['name'] ?? ''));
        $item->setCustomerType((string)($formData['customerType'] ?? CustomerConst::TYPE_DOANH_NGHIEP));
        $item->setTaxCode($this->nullIfBlank($formData['taxCode'] ?? null));
        $item->setIdNumber($this->nullIfBlank($formData['idNumber'] ?? null));
        $item->setAddress($this->nullIfBlank($formData['address'] ?? null));
        $item->setPhone($this->nullIfBlank($formData['phone'] ?? null));
        $item->setEmail($this->nullIfBlank($formData['email'] ?? null));
        $item->setBankAccount($this->nullIfBlank($formData['bankAccount'] ?? null));
        $item->setSalesOwnerId($this->intOrNull($formData['salesOwnerId'] ?? null));
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());

        if ($existing === null) {
            $item->setStatus(CustomerConst::STATUS_HOAT_DONG);
            $item->setCreditWarning(false);
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveCustomer($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            CustomerMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespCustomer()
        );

        return $saved;
    }

    public function changeStatus(array $payload = []): CustomerModel
    {
        $form = new CustomerStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $status = (string)($formData['status'] ?? '');
        $item = $this->getCustomer($id);
        $fromStatus = (string)$item->getStatus();

        if (!CustomerConst::isValidStatus($status)) {
            throw new ValidationException(['status' => 'Trạng thái khách hàng không hợp lệ.']);
        }

        if ($fromStatus === $status) {
            return $item;
        }

        $this->mapper()->updateAttrsCustomer($id, ['status' => $status], $this->currentUserId());
        $item->setStatus($status);

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            CustomerMapper::TABLE_NAME,
            $id,
            ['status' => $fromStatus],
            ['status' => $status, 'reason' => $this->nullIfBlank($formData['reason'] ?? null)]
        );

        return $item;
    }

    /**
     * Internal API for Billing credit events. HTTP flows still use forms/CSRF.
     */
    public function setCreditWarningFromBilling(
        int $customerId,
        bool $creditWarning,
        array $context = [],
        ?int $actorId = null
    ): CustomerModel {
        $item = $this->getCustomer($customerId);
        if ((bool)$item->getCreditWarning() === $creditWarning) {
            return $item;
        }

        $before = ['creditWarning' => (bool)$item->getCreditWarning()];
        $this->mapper()->updateAttrsCustomer($customerId, ['creditWarning' => (int)$creditWarning], $actorId);
        $item->setCreditWarning($creditWarning);

        $this->auditLog()->write(
            AuditLogModel::ACTION_UPDATE,
            CustomerMapper::TABLE_NAME,
            $customerId,
            $before,
            array_merge(['creditWarning' => $creditWarning], $context),
            $actorId
        );

        return $item;
    }

    private function formValues(CustomerModel $item): array
    {
        return [
            'id'            => $item->getId(),
            'code'          => $item->getCode(),
            'name'          => $item->getName(),
            'customerType'  => $item->getCustomerType(),
            'taxCode'       => $item->getTaxCode(),
            'idNumber'      => $item->getIdNumber(),
            'address'       => $item->getAddress(),
            'phone'         => $item->getPhone(),
            'email'         => $item->getEmail(),
            'bankAccount'   => $item->getBankAccount(),
            'salesOwnerId'  => $item->getSalesOwnerId(),
            'note'          => $item->getNote(),
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
