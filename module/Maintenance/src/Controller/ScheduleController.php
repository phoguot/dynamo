<?php

declare(strict_types=1);

namespace Maintenance\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Maintenance\Model\Schedule\ScheduleModel;
use Maintenance\Service\ScheduleService;

class ScheduleController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(ScheduleService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchSchedules($query));
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
        $service = $this->getContainerEntry(ScheduleService::class);
        $schedule = $service->getSchedule((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'schedule' => $schedule,
            'canEdit'  => $this->isAllowedAction('edit'),
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(ScheduleService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(ScheduleService::class);
        $schedule = $service->getSchedule((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $schedule);
        }

        return $this->formView($service, $schedule);
    }

    private function handleSave(ScheduleService $service, ?ScheduleModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveSchedule($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage('Đã lưu lịch bảo trì.');

        return $this->redirect()->toRoute('maintenance-schedules', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(ScheduleService $service, ?ScheduleModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('maintenance/schedule/form');
        $model->setVariables([
            'schedule' => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
