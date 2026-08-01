<?php

declare(strict_types=1);

namespace User\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use User\Model\User\UserModel;
use User\Service\AuthService;
use User\Service\UserService;

/**
 * Trang quản lý tài khoản người dùng.
 *
 * Controller mỏng: nhận request → gọi Service → đổ vào ViewModel. Không validate, không SQL,
 * không quy tắc nghiệp vụ, và KHÔNG dựng markup form.
 */
class UserController extends BaseController
{
    /** Danh sách người dùng, có lọc và phân trang. */
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(UserService::class);

        $model = $this->getViewModel();
        try {
            $model->setVariable('paginator', $service->searchUsers($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            // Tham số lọc sai: vẫn hiển thị trang, báo lỗi ngay trên filter bar.
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canEdit'    => $this->canEdit(),
        ]);

        return $model;
    }

    /** Chi tiết một tài khoản: hồ sơ, đổi trạng thái, đặt lại mật khẩu. */
    public function detailAction(): ViewModel
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(UserService::class);
        $user = $service->getUser($id);

        $model = $this->getViewModel();
        $model->setVariables([
            'user'         => $user,
            'canEdit'      => $this->canEdit(),
            'isSelf'       => $user->getId() === $this->currentUserId(),
            'nextStatuses' => $service->nextStatuses($user),
            'statusForm'   => $service->newStatusForm(),
            'passwordForm' => $service->newPasswordForm(),
            'errors'       => [],
        ]);

        return $model;
    }

    /** Hồ sơ của người đang đăng nhập. */
    public function profileAction(): ViewModel
    {
        $service = $this->getContainerEntry(UserService::class);
        $authService = $this->getContainerEntry(AuthService::class);
        $user = $service->getUser((int)$this->currentUserId());

        $model = $this->getViewModel();
        $model->setVariables([
            'user'       => $user,
            'logoutForm' => $authService->newLogoutForm(),
        ]);

        return $model;
    }

    /** Form thêm mới. */
    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(UserService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    /** Form sửa hồ sơ. */
    public function editAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getContainerEntry(UserService::class);
        $user = $service->getUser($id);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $user);
        }

        return $this->formView($service, $user);
    }

    /** Khóa / mở khóa tài khoản (POST từ trang chi tiết). */
    public function changestatusAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;
        $service = $this->getContainerEntry(UserService::class);

        try {
            $user = $service->changeStatus($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển tài khoản %s sang trạng thái "%s".',
                (string)$user->getUsername(),
                $user->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('users', ['action' => 'detail', 'id' => $id]);
    }

    /** Đặt lại mật khẩu cho một tài khoản (POST từ trang chi tiết). */
    public function resetpasswordAction(): mixed
    {
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;
        $service = $this->getContainerEntry(UserService::class);

        try {
            $user = $service->resetPassword($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã đặt lại mật khẩu cho tài khoản %s. Hãy báo mật khẩu mới cho người dùng qua kênh an toàn.',
                (string)$user->getUsername()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('users', ['action' => 'detail', 'id' => $id]);
    }

    // ------------------------------------------------------------------

    /**
     * POST-Redirect-Get: lỗi validate thì render lại form, hợp lệ thì redirect.
     */
    private function handleSave(UserService $service, ?UserModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveUser($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf(
            'Đã lưu tài khoản %s — %s.',
            (string)$saved->getUsername(),
            (string)$saved->getFullName()
        ));

        return $this->redirect()->toRoute('users', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    /**
     * @param array<string, mixed>  $values dữ liệu người dùng vừa gõ (khi có lỗi)
     * @param array<string, string> $errors
     */
    private function formView(UserService $service, ?UserModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('user/user/form');
        $model->setVariables([
            'user'     => $existing,
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
            || $this->isAllowedAction('resetpassword');
    }
}
