<?php

declare(strict_types=1);

namespace Fleet\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Fleet\Model\Generator\GeneratorModel;
use Fleet\Service\GeneratorService;
use Laminas\View\Model\ViewModel;

/**
 * Trang quản lý đội máy phát điện (HTML render phía server).
 *
 * Controller mỏng: nhận request → gọi Service → đổ vào ViewModel.
 * Không validate, không SQL, không quy tắc nghiệp vụ, và KHÔNG dựng markup form —
 * markup nằm ở `view/partial/generator/*`, luật validate nằm ở `src/Form/Generator/*`.
 */
class GeneratorController extends BaseController
{
    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('changestatus');
    }

    /** Danh sách máy, có lọc và phân trang. */
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(GeneratorService::class);

        $model = $this->getViewModel();
        try {
            $model->setVariable('paginator', $service->searchGenerators($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            // Tham số lọc sai: vẫn hiển thị trang, báo lỗi ngay trên filter bar.
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        // Danh sách chọn của filter bar do chính form cấp — controller không đọc enum.
        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canEdit'    => $this->canEdit(),
        ]);

        return $model;
    }

    /** Chi tiết một máy. */
    public function detailAction(): ViewModel
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(GeneratorService::class);
        $generator = $service->getGenerator($id);

        $model = $this->getViewModel();
        $model->setVariables([
            'generator'    => $generator,
            'canEdit'      => $this->canEdit(),
            'nextStatuses' => $service->nextStatuses($generator),
            'statusForm'   => $service->newStatusForm(),
            'errors'       => [],
        ]);

        return $model;
    }

    /** Form thêm mới. */
    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(GeneratorService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    /** Form sửa. */
    public function editAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(GeneratorService::class);
        $generator = $service->getGenerator($id);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $generator);
        }

        return $this->formView($service, $generator);
    }

    /** Đổi trạng thái máy (POST từ trang chi tiết). */
    public function changestatusAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;
        $service = $this->getContainerEntry(GeneratorService::class);

        try {
            $generator = $service->changeStatus($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển máy %s sang trạng thái "%s".',
                (string)$generator->getCode(),
                $generator->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('generators', ['action' => 'detail', 'id' => $id]);
    }

    /**
     * POST-Redirect-Get: lỗi validate thì render lại form, hợp lệ thì redirect.
     */
    private function handleSave(GeneratorService $service, ?GeneratorModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveGenerator($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf(
            'Đã lưu máy %s — %s.',
            (string)$saved->getCode(),
            (string)$saved->getName()
        ));

        return $this->redirect()->toRoute('generators', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    /**
     * @param array<string, mixed>  $values dữ liệu người dùng vừa gõ (khi có lỗi)
     * @param array<string, string> $errors
     */
    private function formView(GeneratorService $service, ?GeneratorModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('fleet/generator/form');
        $model->setVariables([
            'generator' => $existing,
            'saveForm'  => $service->newSaveForm($existing, $values),
            'errors'    => $errors,
        ]);

        return $model;
    }
}
