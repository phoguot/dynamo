<?php

declare(strict_types=1);

namespace Dispatch\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Dispatch\Model\Vehicle\VehicleModel;
use Dispatch\Service\VehicleService;
use Laminas\View\Model\ViewModel;

class VehicleController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(VehicleService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchVehicles($query));
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
        $service = $this->getContainerEntry(VehicleService::class);
        $vehicle = $service->getVehicle((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'vehicle'      => $vehicle,
            'nextStatuses' => $service->nextStatuses($vehicle),
            'statusForm'   => $service->newStatusForm(),
            'canEdit'      => $this->isAllowedAction('edit'),
            'canChangeStatus' => $this->isAllowedAction('changestatus'),
            'errors'       => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(VehicleService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(VehicleService::class);
        $vehicle = $service->getVehicle((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $vehicle);
        }

        return $this->formView($service, $vehicle);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(VehicleService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $vehicle = $service->changeStatus($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển xe %s sang trạng thái "%s".',
                (string)$vehicle->getCode(),
                $vehicle->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('vehicles', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(VehicleService $service, ?VehicleModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveVehicle($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu xe %s.', (string)$saved->getCode()));

        return $this->redirect()->toRoute('vehicles', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(VehicleService $service, ?VehicleModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('dispatch/vehicle/form');
        $model->setVariables([
            'vehicle'  => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }

}
