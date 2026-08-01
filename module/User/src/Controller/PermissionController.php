<?php

declare(strict_types=1);

namespace User\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use User\Model\User\UserConst;
use User\Service\PermissionService;
use User\Service\UserService;

/**
 * Màn hình phân quyền theo TỪNG NGƯỜI DÙNG — ma trận module × thao tác.
 *
 * Chỉ Quản trị hệ thống, không có ngoại lệ: đây là màn hình cấp quyền cho mọi màn hình
 * khác, mở rộng cho vai trò khác nghĩa là mở rộng toàn hệ thống.
 *
 * Controller mỏng: nhận request → gọi Service → đổ vào ViewModel.
 */
class PermissionController extends BaseController
{
    /** Ma trận quyền của một người dùng. */
    public function indexAction(): ViewModel
    {
        $userId = (int)$this->params()->fromRoute('id', 0);
        $permissionService = $this->getContainerEntry(PermissionService::class);
        $userService = $this->getContainerEntry(UserService::class);

        return $this->matrixView($permissionService, $userService, $userId);
    }

    /** Lưu ma trận quyền (POST). */
    public function saveAction(): mixed
    {
        $userId = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['userId'] = $userId;
        $permissionService = $this->getContainerEntry(PermissionService::class);
        $userService = $this->getContainerEntry(UserService::class);

        try {
            $permissionService->savePermissions($payload);
        } catch (ValidationException $e) {
            return $this->matrixView($permissionService, $userService, $userId, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage('Đã cập nhật phân quyền. Có hiệu lực ngay ở lần thao tác kế tiếp.');

        return $this->redirect()->toRoute('permissions', ['action' => 'index', 'id' => $userId]);
    }

    /** Gỡ mọi ngoại lệ, đưa người dùng về đúng quyền mặc định của vai trò (POST). */
    public function resetAction(): mixed
    {
        $userId = (int)$this->params()->fromRoute('id', 0);
        $permissionService = $this->getContainerEntry(PermissionService::class);
        $userService = $this->getContainerEntry(UserService::class);
        $userService->getUser($userId); // 404 nếu không tồn tại

        $permissionService->resetToRoleDefault($userId);
        $this->flashMessenger()->addSuccessMessage('Đã gỡ mọi ngoại lệ, tài khoản trở về quyền mặc định của vai trò.');

        return $this->redirect()->toRoute('permissions', ['action' => 'index', 'id' => $userId]);
    }

    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $errors
     */
    private function matrixView(
        PermissionService $permissionService,
        UserService $userService,
        int $userId,
        array $values = [],
        array $errors = []
    ): ViewModel
    {
        $user = $userService->getUser($userId);

        $model = $this->getViewModel();
        $model->setTemplate('user/permission/index');
        $model->setVariables([
            'user'              => $user,
            'permissionForm'    => $permissionService->newPermissionForm($userId, $values),
            'resetForm'         => $permissionService->newResetForm($userId),
            // Ba mảng dưới để View hiển thị cạnh nhau: mặc định của vai trò, ngoại lệ đang
            // đặt, và kết quả cuối cùng. Không bắt người quản trị tự cộng trừ trong đầu.
            'roleDefaults'      => $permissionService->defaultPrivilegesOf($user->getRole()),
            'overrides'         => $permissionService->overridesOf($userId),
            'effective'         => $permissionService->effectivePermissions($userId, $user->getRole()),
            'isAdminAccount'    => $user->getRole() === UserConst::ROLE_ADMIN,
            'errors'            => $errors,
        ]);

        return $model;
    }
}
