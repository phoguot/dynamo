<?php

declare(strict_types=1);

namespace User\Service;

use Application\Exception\NotFoundException;
use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use User\Form\User\UserPasswordForm;
use User\Form\User\UserSaveForm;
use User\Form\User\UserSearchForm;
use User\Form\User\UserStatusForm;
use User\Model\AuditLog\AuditLogModel;
use User\Model\User\UserConst;
use User\Model\User\UserMapper;
use User\Model\User\UserModel;

/**
 * Nghiệp vụ tài khoản người dùng.
 *
 * Đây là nơi duy nhất:
 * - chạy Form (validate) và ném ValidationException,
 * - thực thi state machine trạng thái tài khoản (UserConst::STATUS_TRANSITIONS),
 * - giữ các bất biến an toàn: không tự khóa mình, không xóa người quản trị cuối cùng.
 *
 * Controller không được lặp lại bất kỳ điều nào ở trên.
 */
class UserService extends AppServiceFactory
{
    private function mapper(): UserMapper
    {
        return $this->getContainerEntry(UserMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    // ------------------------------------------------------------------
    //  Form cho tầng view
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $values dữ liệu người dùng vừa gõ, ưu tiên hơn bản ghi cũ */
    public function newSaveForm(?UserModel $existing = null, array $values = []): UserSaveForm
    {
        $form = new UserSaveForm($this->getContainer());
        $form->setData($values);

        if ($existing !== null) {
            $form->fill($this->formValues($existing));
        }

        return $form;
    }

    /** @param array<string, mixed> $query tham số lọc hiện tại trên URL */
    public function newSearchForm(array $query = []): UserSearchForm
    {
        $form = new UserSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    public function newStatusForm(): UserStatusForm
    {
        return new UserStatusForm($this->getContainer());
    }

    public function newPasswordForm(): UserPasswordForm
    {
        return new UserPasswordForm($this->getContainer());
    }

    /**
     * Trạng thái có thể chuyển tới. State machine thuộc về service, view chỉ nhận danh sách.
     *
     * @return array<string, string>
     */
    public function nextStatuses(UserModel $user): array
    {
        $allowed = UserConst::STATUS_TRANSITIONS[(string)$user->getStatus()] ?? [];

        $result = [];
        foreach ($allowed as $status) {
            $result[$status] = UserConst::statusLabel($status);
        }

        return $result;
    }

    // ------------------------------------------------------------------
    //  Đọc
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $payload tham số thô từ request */
    public function searchUsers(array $payload = []): Paginator
    {
        $form = new UserSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();

        $criteria = new UserModel();
        $criteria->setKeyword($this->nullIfBlank($formData['keyword'] ?? null));
        $criteria->setRole($this->nullIfBlank($formData['role'] ?? null));
        $criteria->setStatus($this->nullIfBlank($formData['status'] ?? null));

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? UserConst::SORT_DEFAULT;
        $paging['dir']  = $formData['dir'] ?? 'asc';

        return $this->mapper()->searchUsers($criteria, $paging);
    }

    /** Không tìm thấy ⇒ NotFoundException (BaseController đổi thành trang 404). */
    public function getUser(int $id): UserModel
    {
        $item = $this->mapper()->getUser($id);
        if ($item === null) {
            throw new NotFoundException();
        }

        return $item;
    }

    // ------------------------------------------------------------------
    //  Ghi
    // ------------------------------------------------------------------

    /**
     * Thêm mới hoặc cập nhật tài khoản.
     *
     * Mật khẩu chỉ đặt khi TẠO MỚI. Đổi mật khẩu của người khác đi đường riêng
     * (`resetPassword`) để thao tác đó luôn hiện rõ trong nhật ký, không lẫn vào một lần
     * "sửa hồ sơ" bình thường.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException
     */
    public function saveUser(array $payload = []): UserModel
    {
        $form = new UserSaveForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = $this->intOrNull($formData['id'] ?? null);
        $mapper = $this->mapper();

        $existing = null;
        if ($id !== null) {
            $existing = $mapper->getUser($id);
            if ($existing === null) {
                throw new NotFoundException();
            }
        }

        $errors = [];

        $username = strtolower(trim((string)($formData['username'] ?? '')));
        if ($mapper->getUserByUsername($username, $id) !== null) {
            $errors['username'] = 'Tên đăng nhập này đã có người dùng.';
        }

        $email = $this->nullIfBlank($formData['email'] ?? null);
        if ($email !== null && $mapper->getUserByEmail($email, $id) !== null) {
            $errors['email'] = 'Email này đã gắn với tài khoản khác.';
        }

        $role = (string)($formData['role'] ?? '');

        // Hạ vai trò người quản trị cuối cùng ⇒ không còn ai vào được màn hình phân quyền.
        if ($existing !== null
            && $existing->getRole() === UserConst::ROLE_ADMIN
            && $role !== UserConst::ROLE_ADMIN
            && $mapper->countActiveAdmins($existing->getId()) === 0
        ) {
            $errors['role'] = 'Đây là tài khoản Quản trị hệ thống đang hoạt động duy nhất. '
                . 'Hãy cấp quyền quản trị cho một người khác trước khi đổi vai trò của tài khoản này.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $before = $existing?->getRespUser();

        $item = $existing ?? new UserModel();
        $item->setUsername($username);
        $item->setFullName((string)($formData['fullName'] ?? ''));
        $item->setEmail($email);
        $item->setPhone($this->nullIfBlank($formData['phone'] ?? null));
        $item->setRole($role);
        $item->setNote($this->nullIfBlank($formData['note'] ?? null));
        $item->setUpdatedBy($this->currentUserId());

        if ($existing === null) {
            $item->setStatus(UserConst::STATUS_HOAT_DONG);
            $authService = $this->getContainerEntry(AuthService::class);
            $item->setPasswordHash($authService->hashPassword((string)($formData['password'] ?? '')));
            $item->setCreatedBy($this->currentUserId());
        }

        $saved = $mapper->saveUser($item);

        $this->auditLog()->write(
            $existing === null ? AuditLogModel::ACTION_CREATE : AuditLogModel::ACTION_UPDATE,
            UserMapper::TABLE_NAME,
            $saved->getId(),
            $before,
            $saved->getRespUser()
        );

        return $saved;
    }

    /**
     * Đổi trạng thái tài khoản theo state machine. Đây là cửa duy nhất khóa/mở khóa.
     *
     * @param array<string, mixed> $payload
     */
    public function changeStatus(array $payload = []): UserModel
    {
        $form = new UserStatusForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $toStatus = (string)($formData['status'] ?? '');

        $item = $this->getUser($id);
        $fromStatus = (string)$item->getStatus();

        // Tự khóa tài khoản của chính mình là cách nhanh nhất để mất quyền vào lại.
        if ($id === $this->currentUserId() && $toStatus !== UserConst::STATUS_HOAT_DONG) {
            throw new ValidationException([
                'status' => 'Không thể tự khóa tài khoản đang đăng nhập. Nhờ một quản trị viên khác thực hiện.',
            ]);
        }

        if ($fromStatus === $toStatus) {
            return $item; // idempotent
        }

        if (!UserConst::canTransit($fromStatus, $toStatus)) {
            throw new ValidationException([
                'status' => sprintf(
                    'Không thể chuyển tài khoản từ "%s" sang "%s".',
                    UserConst::statusLabel($fromStatus),
                    UserConst::statusLabel($toStatus)
                ),
            ]);
        }

        // Khóa người quản trị đang hoạt động cuối cùng ⇒ hệ thống mất quản trị.
        if ($item->getRole() === UserConst::ROLE_ADMIN
            && $toStatus !== UserConst::STATUS_HOAT_DONG
            && $this->mapper()->countActiveAdmins($id) === 0
        ) {
            throw new ValidationException([
                'status' => 'Đây là tài khoản Quản trị hệ thống đang hoạt động duy nhất, không được khóa.',
            ]);
        }

        $this->mapper()->updateAttrsUser($id, ['status' => $toStatus], $this->currentUserId());
        $item->setStatus($toStatus);

        $this->auditLog()->write(
            AuditLogModel::ACTION_STATUS_CHANGED,
            UserMapper::TABLE_NAME,
            $id,
            ['status' => $fromStatus],
            ['status' => $toStatus, 'reason' => $this->nullIfBlank($formData['reason'] ?? null)]
        );

        return $item;
    }

    /**
     * Quản trị đặt lại mật khẩu cho một tài khoản.
     *
     * @param array<string, mixed> $payload
     */
    public function resetPassword(array $payload = []): UserModel
    {
        $form = new UserPasswordForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $id = (int)($formData['id'] ?? 0);
        $item = $this->getUser($id);

        $this->mapper()->updatePassword(
            $id,
            $this->getContainerEntry(AuthService::class)->hashPassword((string)($formData['password'] ?? '')),
            $this->currentUserId()
        );

        // Chỉ ghi việc ĐÃ đặt lại mật khẩu; không ghi mật khẩu cũ, mới, hay độ dài.
        $this->auditLog()->write(
            AuditLogModel::ACTION_PASSWORD_RESET,
            UserMapper::TABLE_NAME,
            $id,
            null,
            ['username' => $item->getUsername()]
        );

        return $item;
    }

    // ------------------------------------------------------------------
    //  API công khai cho module khác
    // ------------------------------------------------------------------

    /**
     * Tên hiển thị của một người dùng — dùng để render cột "người tạo", "người duyệt".
     *
     * Module khác gọi hàm này thay vì JOIN sang `usr_users`
     * (.claude/rules/module-boundaries.md).
     */
    public function getDisplayName(?int $userId): ?string
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }

        return $this->mapper()->getUser($userId)?->getFullName();
    }

    /**
     * Giá trị của bản ghi theo đúng tên field của form.
     *
     * @return array<string, mixed>
     */
    private function formValues(UserModel $item): array
    {
        return [
            'id'       => $item->getId(),
            'username' => $item->getUsername(),
            'fullName' => $item->getFullName(),
            'email'    => $item->getEmail(),
            'phone'    => $item->getPhone(),
            'role'     => $item->getRole(),
            'note'     => $item->getNote(),
        ];
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int)$value;
    }
}
