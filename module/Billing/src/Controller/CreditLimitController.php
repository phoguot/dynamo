<?php

declare(strict_types=1);

namespace Billing\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Billing\Model\CreditLimit\CreditLimitModel;
use Billing\Service\CreditLimitService;
use Laminas\View\Model\ViewModel;

class CreditLimitController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(CreditLimitService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchCreditLimits($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canCreate'  => $this->isAllowedAction('create'),
            'canEdit'    => $this->isAllowedAction('edit'),
        ]);

        return $model;
    }

    public function detailAction(): ViewModel
    {
        $service = $this->getContainerEntry(CreditLimitService::class);
        $creditLimit = $service->getCreditLimit((int)$this->params()->fromRoute('id', 0));
        $model = $this->getViewModel();
        $model->setVariables([
            'creditLimit' => $creditLimit,
            'canEdit'     => $this->isAllowedAction('edit'),
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(CreditLimitService::class);
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(CreditLimitService::class);
        $creditLimit = $service->getCreditLimit((int)$this->params()->fromRoute('id', 0));
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $creditLimit);
        }

        return $this->formView($service, $creditLimit);
    }

    private function handleSave(CreditLimitService $service, ?CreditLimitModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveCreditLimit($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage('Đã lưu hạn mức công nợ.');

        return $this->redirect()->toRoute('credit-limits', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(CreditLimitService $service, ?CreditLimitModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('billing/credit-limit/form');
        $model->setVariables([
            'creditLimit' => $existing,
            'saveForm'    => $service->newSaveForm($existing, $values),
            'errors'      => $errors,
        ]);

        return $model;
    }
}
