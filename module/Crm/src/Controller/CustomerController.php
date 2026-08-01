<?php

declare(strict_types=1);

namespace Crm\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Crm\Model\Customer\CustomerModel;
use Crm\Service\CustomerService;
use Laminas\View\Model\ViewModel;

class CustomerController extends BaseController
{
    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('changestatus');
    }

    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(CustomerService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchCustomers($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canEdit'    => $this->canEdit(),
        ]);

        return $model;
    }

    public function detailAction(): ViewModel
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(CustomerService::class);
        $customer = $service->getCustomer($id);

        $model = $this->getViewModel();
        $model->setVariables([
            'customer'   => $customer,
            'sites'      => $service->sitesOf($customer),
            'contacts'   => $service->contactsOf($customer),
            'statusForm' => $service->newStatusForm(),
            'canEdit'    => $this->canEdit(),
            'errors'     => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(CustomerService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(CustomerService::class);
        $customer = $service->getCustomer($id);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $customer);
        }

        return $this->formView($service, $customer);
    }

    public function changestatusAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;
        $service = $this->getContainerEntry(CustomerService::class);

        try {
            $customer = $service->changeStatus($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển khách hàng %s sang trạng thái "%s".',
                (string)$customer->getCode(),
                $customer->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('customers', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(CustomerService $service, ?CustomerModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveCustomer($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf(
            'Đã lưu khách hàng %s — %s.',
            (string)$saved->getCode(),
            (string)$saved->getName()
        ));

        return $this->redirect()->toRoute('customers', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(CustomerService $service, ?CustomerModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('crm/customer/form');
        $model->setVariables([
            'customer' => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
