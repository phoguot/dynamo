<?php

declare(strict_types=1);

namespace Billing\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Billing\Model\Deposit\DepositModel;
use Billing\Service\DepositService;
use Laminas\View\Model\ViewModel;

class DepositController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(DepositService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchDeposits($query));
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
        $service = $this->getContainerEntry(DepositService::class);
        $deposit = $service->getDeposit((int)$this->params()->fromRoute('id', 0));
        $model = $this->getViewModel();
        $model->setVariables([
            'deposit' => $deposit,
            'canEdit' => $this->isAllowedAction('edit'),
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(DepositService::class);
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(DepositService::class);
        $deposit = $service->getDeposit((int)$this->params()->fromRoute('id', 0));
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $deposit);
        }

        return $this->formView($service, $deposit);
    }

    private function handleSave(DepositService $service, ?DepositModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveDeposit($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu phiếu cọc %s.', (string)$saved->getDepositNo()));

        return $this->redirect()->toRoute('deposits', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(DepositService $service, ?DepositModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('billing/deposit/form');
        $model->setVariables([
            'deposit'  => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
