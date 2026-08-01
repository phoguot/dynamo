<?php

declare(strict_types=1);

namespace Crm\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Crm\Model\Site\SiteModel;
use Crm\Service\SiteService;
use Laminas\View\Model\ViewModel;

class SiteController extends BaseController
{
    public function createAction(): mixed
    {
        $customerId = (int)($this->params()->fromQuery('customerId', 0));
        $service = $this->getContainerEntry(SiteService::class);
        $customer = $service->getCustomer($customerId);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null, $customer);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(SiteService::class);
        $site = $service->getSite((int)$this->params()->fromRoute('id', 0));
        $customer = $service->getCustomer((int)$site->getCustomerId());

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $site);
        }

        return $this->formView($service, $site, $customer);
    }

    private function handleSave(SiteService $service, ?SiteModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
            $payload['customerId'] = $existing->getCustomerId();
        }

        try {
            $saved = $service->saveSite($payload);
        } catch (ValidationException $e) {
            $customer = $service->getCustomer((int)($payload['customerId'] ?? 0));
            return $this->formView($service, $existing, $customer, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu công trình %s.', (string)$saved->getName()));

        return $this->redirect()->toRoute('customers', ['action' => 'detail', 'id' => (int)$saved->getCustomerId()]);
    }

    private function formView(SiteService $service, ?SiteModel $existing, mixed $customer, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('crm/site/form');
        $model->setVariables([
            'site'     => $existing,
            'customer' => $customer,
            'saveForm' => $service->newSaveForm($existing, $customer, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
