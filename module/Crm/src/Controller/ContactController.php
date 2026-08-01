<?php

declare(strict_types=1);

namespace Crm\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Crm\Model\Contact\ContactModel;
use Crm\Service\ContactService;
use Laminas\View\Model\ViewModel;

class ContactController extends BaseController
{
    public function createAction(): mixed
    {
        $customerId = (int)($this->params()->fromQuery('customerId', 0));
        $service = $this->getContainerEntry(ContactService::class);
        $customer = $service->getCustomer($customerId);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null, $customer);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(ContactService::class);
        $contact = $service->getContact((int)$this->params()->fromRoute('id', 0));
        $customer = $service->getCustomer((int)$contact->getCustomerId());

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $contact);
        }

        return $this->formView($service, $contact, $customer);
    }

    private function handleSave(ContactService $service, ?ContactModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
            $payload['customerId'] = $existing->getCustomerId();
        }

        try {
            $saved = $service->saveContact($payload);
        } catch (ValidationException $e) {
            $customer = $service->getCustomer((int)($payload['customerId'] ?? 0));
            return $this->formView($service, $existing, $customer, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu liên hệ %s.', (string)$saved->getFullName()));

        return $this->redirect()->toRoute('customers', ['action' => 'detail', 'id' => (int)$saved->getCustomerId()]);
    }

    private function formView(ContactService $service, ?ContactModel $existing, mixed $customer, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('crm/contact/form');
        $model->setVariables([
            'contact'  => $existing,
            'customer' => $customer,
            'saveForm' => $service->newSaveForm($existing, $customer, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
