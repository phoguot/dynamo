<?php

declare(strict_types=1);

namespace Rental\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Rental\Model\RentalOrder\RentalOrderConst;
use Rental\Model\RentalOrder\RentalOrderModel;
use Rental\Service\RentalOrderService;

class RentalOrderController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(RentalOrderService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchRentalOrders($query));
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
        $service = $this->getContainerEntry(RentalOrderService::class);
        $order = $service->getRentalOrder((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'order'        => $order,
            'occupancy'    => $service->occupancyOf($order),
            'nextStatuses' => $service->nextStatuses($order),
            'statusForm'   => $service->newStatusForm(),
            'extendForm'   => $service->newExtendForm($order),
            'canExtend'    => $service->canExtend($order),
            'canEdit'      => $this->canEdit(),
            'errors'       => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(RentalOrderService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(RentalOrderService::class);
        $order = $service->getRentalOrder((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $order);
        }

        return $this->formView($service, $order);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(RentalOrderService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $order = $service->changeStatus($payload);
            if (($payload['status'] ?? null) === RentalOrderConst::STATUS_CANCELLED) {
                $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa đơn thuê %s.', (string)$order->getOrderNo()));

                return $this->redirect()->toRoute('rental-orders');
            }
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển đơn thuê %s sang trạng thái "%s".',
                (string)$order->getOrderNo(),
                $order->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('rental-orders', ['action' => 'detail', 'id' => $id]);
    }

    public function extendAction(): mixed
    {
        $service = $this->getContainerEntry(RentalOrderService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $order = $service->extendRentalOrder($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã gia hạn đơn thuê %s đến ngày %s.',
                (string)$order->getOrderNo(),
                (string)$order->getExpectedEndDate()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('rental-orders', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(RentalOrderService $service, ?RentalOrderModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveRentalOrder($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu đơn thuê %s.', (string)$saved->getOrderNo()));

        return $this->redirect()->toRoute('rental-orders', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(RentalOrderService $service, ?RentalOrderModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('rental/rental-order/form');
        $model->setVariables([
            'order'    => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }

    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('changestatus')
            || $this->isAllowedAction('extend');
    }
}
