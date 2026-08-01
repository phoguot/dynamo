<?php

declare(strict_types=1);

namespace Sales\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Sales\Model\Contract\ContractConst;
use Sales\Model\Contract\ContractModel;
use Sales\Service\ContractService;

class ContractController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(ContractService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchContracts($query));
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
        $service = $this->getContainerEntry(ContractService::class);
        $contract = $service->getContract((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'contract'     => $contract,
            'nextStatuses' => $service->nextStatuses($contract),
            'statusForm'   => $service->newStatusForm(),
            'canEdit'      => $this->canEdit(),
            'errors'       => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(ContractService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(ContractService::class);
        $contract = $service->getContract((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $contract);
        }

        return $this->formView($service, $contract);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(ContractService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $contract = $service->changeStatus($payload);
            if (($payload['status'] ?? null) === ContractConst::STATUS_CANCELLED) {
                $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa hợp đồng %s.', (string)$contract->getContractNo()));

                return $this->redirect()->toRoute('contracts');
            }
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển hợp đồng %s sang trạng thái "%s".',
                (string)$contract->getContractNo(),
                $contract->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('contracts', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(ContractService $service, ?ContractModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveContract($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu hợp đồng %s.', (string)$saved->getContractNo()));

        return $this->redirect()->toRoute('contracts', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(ContractService $service, ?ContractModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('sales/contract/form');
        $model->setVariables([
            'contract' => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }

    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('changestatus');
    }
}
