<?php

declare(strict_types=1);

namespace User\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Service\PermissionCheckerInterface;
use User\Form\User\UserPermissionForm;
use User\Form\User\UserPermissionResetForm;
use User\Model\AuditLog\AuditLogModel;
use User\Model\Permission\PermissionConst;
use User\Model\Permission\PermissionMapper;
use User\Model\Permission\PermissionModel;
use User\Model\User\UserConst;
use User\Model\User\UserMapper;
use User\Permission\UserAcl;

/**
 * Trả lời câu hỏi duy nhất: "người này có được làm việc này không".
 *
 * Thứ tự quyết định — dừng ở điều kiện đầu tiên khớp:
 *
 *   1. Action public                                      ⇒ CHO QUA.
 *   2. Resource không nằm trong danh mục ACL              ⇒ TỪ CHỐI.
 *   2. Vai trò `admin`                                      ⇒ CHO QUA, không xét ngoại lệ.
 *      Không được phép tự khóa mình ra khỏi màn hình phân quyền.
 *   3. Có dòng ngoại lệ khớp `(resource, privilege)`          ⇒ theo `effect` của dòng đó.
 *   4. Có dòng ngoại lệ khớp `(resource, *)`                 ⇒ theo `effect` của dòng đó.
 *   5. Còn lại                                              ⇒ theo mặc định của vai trò (UserAcl).
 *
 * Ngoại lệ cụ thể thắng ngoại lệ `*`, và mọi ngoại lệ đều thắng mặc định vai trò.
 */
class PermissionService extends AppServiceFactory implements PermissionCheckerInterface
{
    private ?UserAcl $acl = null;

    /**
     * Cache theo VÒNG ĐỜI REQUEST: guard chạy ở mọi request, không thể mỗi lần lại truy vấn.
     * Không cache xuyên request — quản trị sửa quyền thì phải có hiệu lực ở request kế tiếp,
     * không đợi hết TTL.
     *
     * @var array<int, array<string, string>> userId => ("resource|privilege" => effect)
     */
    private array $overrideCache = [];

    private function acl(): UserAcl
    {
        return $this->acl ??= new UserAcl();
    }

    private function mapper(): PermissionMapper
    {
        return $this->getContainerEntry(PermissionMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    // ------------------------------------------------------------------
    //  Hỏi quyền
    // ------------------------------------------------------------------

    /**
     * Hiện thực PermissionCheckerInterface — điểm vào từ `BaseController::guard()`.
     */
    public function isAllowedAction(string $controllerClass, string $action): bool
    {
        $resource = PermissionConst::resourceFromController($controllerClass);
        if ($resource === null) {
            return false;
        }

        return $this->isAllowed($resource, $this->normalizePrivilege($action));
    }

    public function isPublicAction(string $controllerClass, string $action): bool
    {
        $resource = PermissionConst::resourceFromController($controllerClass);
        if ($resource === null) {
            return false;
        }

        return PermissionConst::isPublicAction($resource, $this->normalizePrivilege($action));
    }

    /**
     * Hỏi quyền cho NGƯỜI ĐANG ĐĂNG NHẬP.
     */
    public function isAllowed(string $resource, string $privilege): bool
    {
        $auth = $this->getAuthContext();

        return $this->isAllowedForUser(
            $auth?->getUserId(),
            $auth?->getRole(),
            $resource,
            $privilege
        );
    }

    /**
     * Hỏi quyền cho MỘT NGƯỜI BẤT KỲ — dùng khi dựng màn hình phân quyền để xem trước
     * kết quả, và trong test.
     */
    public function isAllowedForUser(?int $userId, ?string $role, string $resource, string $privilege): bool
    {
        $overrides = ($userId !== null && $userId > 0) ? $this->loadOverrides($userId) : [];

        return $this->decide($role, $resource, $privilege, $overrides);
    }

    /**
     * Toàn bộ luật quyết định, tách khỏi mọi truy vấn.
     *
     * Tách ra để test được đủ 5 nhánh mà không cần dựng database — đây là đoạn logic mà
     * sai một nhánh là thủng phân quyền toàn hệ thống, nên nó phải dễ test hơn mọi thứ khác.
     *
     * @param array<string, string> $overrides "resource|privilege" => effect
     */
    public function decide(?string $role, string $resource, string $privilege, array $overrides = []): bool
    {
        $privilege = $this->normalizePrivilege($privilege);

        if (PermissionConst::isPublicAction($resource, $privilege)) {
            return true;
        }

        if (PermissionConst::isAuthenticatedAction($resource, $privilege)) {
            return UserConst::isValidRole($role);
        }

        if (!PermissionConst::hasResource($resource)) {
            return false;
        }

        if ($role === UserConst::ROLE_ADMIN) {
            return true;
        }

        $exact = $overrides[$resource . '|' . $privilege] ?? null;
        if ($exact !== null) {
            return $exact === PermissionConst::EFFECT_ALLOW;
        }

        $wildcard = $overrides[$resource . '|' . PermissionConst::PRIVILEGE_ALL] ?? null;
        if ($wildcard !== null) {
            return $wildcard === PermissionConst::EFFECT_ALLOW;
        }

        return $this->acl()->isRoleAllowed($role, $resource, $privilege);
    }

    /**
     * Quyền hiệu lực cuối cùng của một người, gom theo resource.
     * Dùng để màn hình phân quyền hiển thị "kết quả thật sự", không bắt người quản trị
     * tự cộng trừ trong đầu giữa mặc định vai trò và ngoại lệ.
     *
     * @return array<string, array<string, bool>> resource => (privilege => được phép)
     */
    public function effectivePermissions(?int $userId, ?string $role): array
    {
        $result = [];
        foreach (PermissionConst::RESOURCES as $resource => $meta) {
            foreach (array_keys($meta['privileges']) as $privilege) {
                $result[$resource][$privilege] = $this->isAllowedForUser($userId, $role, $resource, $privilege);
            }
        }

        return $result;
    }

    /**
     * Ngoại lệ đang lưu của một người, dạng phẳng để View tick sẵn ô.
     *
     * @return array<string, string> "resource|privilege" => effect
     */
    public function overridesOf(int $userId): array
    {
        return $this->loadOverrides($userId);
    }

    public function defaultPrivilegesOf(?string $role): array
    {
        return $this->acl()->defaultPrivilegesOf($role);
    }

    // ------------------------------------------------------------------
    //  Sửa quyền
    // ------------------------------------------------------------------

    /**
     * Form ma trận phân quyền, đã đổ sẵn ngoại lệ đang lưu.
     *
     * @param array<string, mixed> $values dữ liệu người dùng vừa gõ, ưu tiên hơn giá trị đã lưu
     */
    public function newPermissionForm(int $userId, array $values = []): UserPermissionForm
    {
        $form = new UserPermissionForm($this->getContainer());
        $form->setData($values);
        $form->fill([
            'userId' => $userId,
            'perm'   => $this->loadOverrides($userId),
        ]);

        return $form;
    }

    /** Form gỡ sạch ngoại lệ — CSRF token riêng, xem chú thích ở chính class đó. */
    public function newResetForm(int $userId): UserPermissionResetForm
    {
        $form = new UserPermissionResetForm($this->getContainer());
        $form->fill(['userId' => $userId]);

        return $form;
    }

    /**
     * Điểm vào từ Controller: validate rồi lưu.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException
     */
    public function savePermissions(array $payload = []): int
    {
        $form = new UserPermissionForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $userId = (int)($formData['userId'] ?? 0);

        $this->saveOverrides($userId, is_array($formData['perm'] ?? null) ? $formData['perm'] : []);

        return $userId;
    }

    /**
     * Thay toàn bộ ngoại lệ quyền của một người.
     *
     * @param array<string, string> $overrides "resource|privilege" => effect
     * @throws ValidationException khi có cặp resource/privilege không tồn tại trong danh mục
     */
    public function saveOverrides(int $userId, array $overrides): void
    {
        $userMapper = $this->getContainerEntry(UserMapper::class);
        $target = $userMapper?->getUser($userId);
        if ($target === null) {
            throw new ValidationException(['userId' => 'Không tìm thấy người dùng cần phân quyền.']);
        }

        // Quản trị luôn có mọi quyền (bước 2 ở trên) nên mọi dòng ngoại lệ đặt cho họ đều
        // vô nghĩa. Chặn ngay để người quản trị không tưởng là đã cắt được quyền của nhau.
        if ($target->getRole() === UserConst::ROLE_ADMIN) {
            throw new ValidationException([
                'userId' => 'Tài khoản Quản trị hệ thống luôn có toàn quyền, không phân quyền riêng được. '
                    . 'Muốn giới hạn thì đổi vai trò của tài khoản này trước.',
            ]);
        }

        $models = [];
        $errors = [];
        foreach ($overrides as $key => $effect) {
            [$resource, $privilege] = array_pad(explode('|', (string)$key, 2), 2, null);

            if (!PermissionConst::isValidPair($resource, $privilege)) {
                $errors[$key] = sprintf('Quyền "%s" không có trong danh mục.', (string)$key);
                continue;
            }

            if (!PermissionConst::isValidEffect($effect)) {
                $errors[$key] = sprintf('Hiệu lực "%s" không hợp lệ.', (string)$effect);
                continue;
            }

            $models[] = (new PermissionModel())
                ->setUserId($userId)
                ->setResource($resource)
                ->setPrivilege($privilege)
                ->setEffect($effect);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $before = $this->loadOverrides($userId);
        $actorId = $this->currentUserId();

        $this->mapper()->replaceForUser($userId, $models, $actorId);
        unset($this->overrideCache[$userId]);

        $this->auditLog()->write(
            AuditLogModel::ACTION_PERMISSION_CHANGED,
            UserMapper::TABLE_NAME,
            $userId,
            $before,
            $this->loadOverrides($userId)
        );
    }

    /** Gỡ mọi ngoại lệ, đưa người dùng về đúng mặc định của vai trò. */
    public function resetToRoleDefault(int $userId): void
    {
        $before = $this->loadOverrides($userId);

        $this->mapper()->deleteByUser($userId);
        unset($this->overrideCache[$userId]);

        $this->auditLog()->write(
            AuditLogModel::ACTION_PERMISSION_CHANGED,
            UserMapper::TABLE_NAME,
            $userId,
            $before,
            []
        );
    }

    // ------------------------------------------------------------------

    /** @return array<string, string> "resource|privilege" => effect */
    private function loadOverrides(int $userId): array
    {
        if (isset($this->overrideCache[$userId])) {
            return $this->overrideCache[$userId];
        }

        $flat = [];
        foreach ($this->mapper()?->fetchByUser($userId) ?? [] as $permission) {
            $flat[$permission->getResource() . '|' . $permission->getPrivilege()] = (string)$permission->getEffect();
        }

        return $this->overrideCache[$userId] = $flat;
    }

    /**
     * Chuẩn hóa tên action về đúng dạng privilege trong danh mục.
     *
     * Laminas cho phép URL viết `change-status` hoặc `change_status` mà vẫn gọi tới
     * `changeStatusAction()`. Không chuẩn hóa thì cùng một action lại ra hai chuỗi quyền
     * khác nhau, và người dùng lách được bằng cách đổi cách gõ URL.
     */
    private function normalizePrivilege(string $action): string
    {
        return strtolower(str_replace(['-', '_'], '', trim($action)));
    }
}
