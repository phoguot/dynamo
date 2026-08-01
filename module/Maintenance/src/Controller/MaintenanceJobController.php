<?php

declare(strict_types=1);

namespace Maintenance\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;
use Maintenance\Model\MaintenanceJob\MaintenanceJobModel;
use Maintenance\Service\MaintenanceJobService;

class MaintenanceJobController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchMaintenanceJobs($query));
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
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $job = $service->getMaintenanceJob((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'job'             => $job,
            'parts'           => $service->partsOf($job),
            'nextStatuses'    => $service->nextStatuses($job),
            'statusForm'      => $service->newStatusForm(),
            'partForm'        => $service->newPartForm($job),
            'canEdit'         => $this->isAllowedAction('edit'),
            'canChangeStatus' => $this->isAllowedAction('changestatus'),
            'canAddPart'      => $this->isAllowedAction('addpart'),
            'canDeletePart'   => $this->isAllowedAction('deletepart'),
            'errors'          => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(MaintenanceJobService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $job = $service->getMaintenanceJob((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $job);
        }

        return $this->formView($service, $job);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $job = $service->changeStatus($payload);
            if (($payload['status'] ?? null) === MaintenanceJobConst::STATUS_CANCELLED) {
                $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa phiếu %s.', (string)$job->getJobNo()));

                return $this->redirect()->toRoute('maintenance-jobs');
            }
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển phiếu %s sang trạng thái "%s".',
                (string)$job->getJobNo(),
                $job->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('maintenance-jobs', ['action' => 'detail', 'id' => $id]);
    }

    public function addpartAction(): mixed
    {
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['jobId'] = $id;

        try {
            $service->savePartUsed($payload);
            $this->flashMessenger()->addSuccessMessage('Đã thêm phụ tùng vào phiếu.');
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('maintenance-jobs', ['action' => 'detail', 'id' => $id]);
    }

    public function deletepartAction(): mixed
    {
        $service = $this->getContainerEntry(MaintenanceJobService::class);
        $jobId = (int)$this->params()->fromRoute('id', 0);
        $partId = (int)($this->getAllPostParams()['partId'] ?? 0);

        try {
            $service->deletePartUsed($partId);
            $this->flashMessenger()->addSuccessMessage('Đã xóa phụ tùng khỏi phiếu.');
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('maintenance-jobs', ['action' => 'detail', 'id' => $jobId]);
    }

    private function handleSave(MaintenanceJobService $service, ?MaintenanceJobModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveMaintenanceJob($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu phiếu %s.', (string)$saved->getJobNo()));

        return $this->redirect()->toRoute('maintenance-jobs', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(MaintenanceJobService $service, ?MaintenanceJobModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('maintenance/maintenance-job/form');
        $model->setVariables([
            'job'      => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
