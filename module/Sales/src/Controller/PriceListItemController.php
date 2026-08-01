<?php

declare(strict_types=1);

namespace Sales\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Sales\Model\PriceListItem\PriceListItemModel;
use Sales\Service\PriceListService;

class PriceListItemController extends BaseController
{
    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(PriceListService::class);
        $priceList = $service->getPriceList((int)$this->params()->fromQuery('priceListId', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null, $priceList);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(PriceListService::class);
        $item = $service->getPriceListItem((int)$this->params()->fromRoute('id', 0));
        $priceList = $service->getPriceList((int)$item->getPriceListId());

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $item);
        }

        return $this->formView($service, $item, $priceList);
    }

    private function handleSave(PriceListService $service, ?PriceListItemModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
            $payload['priceListId'] = $existing->getPriceListId();
        }

        try {
            $saved = $service->savePriceListItem($payload);
        } catch (ValidationException $e) {
            $priceList = $service->getPriceList((int)($payload['priceListId'] ?? 0));
            return $this->formView($service, $existing, $priceList, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage('Đã lưu dòng bảng giá.');

        return $this->redirect()->toRoute('price-lists', ['action' => 'detail', 'id' => (int)$saved->getPriceListId()]);
    }

    private function formView(
        PriceListService $service,
        ?PriceListItemModel $existing,
        mixed $priceList,
        array $values = [],
        array $errors = []
    ): ViewModel {
        $model = $this->getViewModel();
        $model->setTemplate('sales/price-list/item-form');
        $model->setVariables([
            'priceList' => $priceList,
            'item'      => $existing,
            'saveForm'  => $service->newItemSaveForm($existing, $priceList, $values),
            'errors'    => $errors,
        ]);

        return $model;
    }
}

