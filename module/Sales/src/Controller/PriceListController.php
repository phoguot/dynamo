<?php

declare(strict_types=1);

namespace Sales\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Sales\Model\PriceList\PriceListModel;
use Sales\Service\PriceListService;

class PriceListController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(PriceListService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchPriceLists($query));
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
        $service = $this->getContainerEntry(PriceListService::class);
        $priceList = $service->getPriceList((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'priceList' => $priceList,
            'items'     => $service->itemsOf($priceList),
            'toggleForm'=> $service->newSaveForm($priceList),
            'canEdit'   => $this->canEdit(),
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(PriceListService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(PriceListService::class);
        $priceList = $service->getPriceList((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $priceList);
        }

        return $this->formView($service, $priceList);
    }

    public function toggleactiveAction(): mixed
    {
        $service = $this->getContainerEntry(PriceListService::class);
        $id = (int)$this->params()->fromRoute('id', 0);

        try {
            $priceList = $service->toggleActive($id);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển bảng giá %s sang trạng thái %s.',
                (string)$priceList->getCode(),
                $priceList->getActiveLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('price-lists', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(PriceListService $service, ?PriceListModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->savePriceList($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu bảng giá %s.', (string)$saved->getCode()));

        return $this->redirect()->toRoute('price-lists', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(PriceListService $service, ?PriceListModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('sales/price-list/form');
        $model->setVariables([
            'priceList' => $existing,
            'saveForm'  => $service->newSaveForm($existing, $values),
            'errors'    => $errors,
        ]);

        return $model;
    }

    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('toggleactive');
    }
}
