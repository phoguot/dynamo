<?php

declare(strict_types=1);

namespace Dispatch\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Dispatch\Model\DispatchJob\DispatchJobConst;
use Dispatch\Model\DispatchJob\DispatchJobModel;
use Dispatch\Service\DispatchJobService;
use Laminas\View\Model\ViewModel;

class DispatchJobController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(DispatchJobService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchDispatchJobs($query));
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
        $service = $this->getContainerEntry(DispatchJobService::class);
        $job = $service->getDispatchJob((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'job'            => $job,
            'assignments'    => $service->assignmentsOf($job),
            'nextStatuses'   => $service->nextStatuses($job),
            'statusForm'     => $service->newStatusForm(),
            'assignmentForm' => $service->newAssignmentForm($job),
            'canEdit'        => $this->isAllowedAction('edit'),
            'canChangeStatus' => $this->isAllowedAction('changestatus'),
            'canAssign'      => $this->isAllowedAction('assign'),
            'canUnassign'    => $this->isAllowedAction('unassign'),
            'errors'         => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(DispatchJobService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(DispatchJobService::class);
        $job = $service->getDispatchJob((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $job);
        }

        return $this->formView($service, $job);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(DispatchJobService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $job = $service->changeStatus($payload);
            if (($payload['status'] ?? null) === DispatchJobConst::STATUS_CANCELLED) {
                $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa lệnh %s.', (string)$job->getJobNo()));

                return $this->redirect()->toRoute('dispatch-jobs');
            }
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển lệnh %s sang trạng thái "%s".',
                (string)$job->getJobNo(),
                $job->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('dispatch-jobs', ['action' => 'detail', 'id' => $id]);
    }

    public function assignAction(): mixed
    {
        $service = $this->getContainerEntry(DispatchJobService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['jobId'] = $id;

        try {
            $service->saveAssignment($payload);
            $this->flashMessenger()->addSuccessMessage('Đã gán người vào lệnh.');
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('dispatch-jobs', ['action' => 'detail', 'id' => $id]);
    }

    public function unassignAction(): mixed
    {
        $service = $this->getContainerEntry(DispatchJobService::class);
        $jobId = (int)$this->params()->fromRoute('id', 0);
        $assignmentId = (int)($this->getAllPostParams()['assignmentId'] ?? 0);

        $service->deleteAssignment($assignmentId);
        $this->flashMessenger()->addSuccessMessage('Đã bỏ phân công khỏi lệnh.');

        return $this->redirect()->toRoute('dispatch-jobs', ['action' => 'detail', 'id' => $jobId]);
    }

    private function handleSave(DispatchJobService $service, ?DispatchJobModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveDispatchJob($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu lệnh %s.', (string)$saved->getJobNo()));

        return $this->redirect()->toRoute('dispatch-jobs', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(DispatchJobService $service, ?DispatchJobModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('dispatch/dispatch-job/form');
        $model->setVariables([
            'job'      => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }

}
