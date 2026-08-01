<?php

declare(strict_types=1);

namespace User\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Model\AppConst;
use Application\Model\DateModel;
use DateTimeImmutable;
use DateTimeZone;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;
use User\Form\Auth\LoginForm;
use User\Form\Auth\LogoutForm;
use User\Model\AuditLog\AuditLogModel;
use User\Model\User\UserConst;
use User\Model\User\UserMapper;
use User\Model\User\UserModel;

/**
 * Đăng nhập, đăng xuất và vòng đời phiên.
 *
 * Đây là nơi DUY NHẤT ghi vào session identity. `Application\Service\AuthContextService`
 * chỉ ĐỌC — xem chú thích đầu class đó.
 *
 * Chính sách bắt buộc theo .claude/rules/security.md:
 * - Mật khẩu băm bằng `password_hash()` với Argon2id (fallback bcrypt cost 12).
 * - Sinh lại session id ngay sau khi đăng nhập thành công (chống session fixation).
 * - Sai 5 lần liên tiếp ⇒ khóa tạm 15 phút, có ghi audit log.
 */
class AuthService extends AppServiceFactory
{
    /**
     * Câu báo lỗi DUY NHẤT cho mọi trường hợp đăng nhập hỏng: sai tên, sai mật khẩu, tài
     * khoản đã khóa vĩnh viễn. Nói rõ "tên đăng nhập không tồn tại" là tặng cho người dò
     * tài khoản một công cụ liệt kê danh sách nhân sự.
     */
    private const string MSG_INVALID = 'Tên đăng nhập hoặc mật khẩu không đúng.';

    private function mapper(): UserMapper
    {
        return $this->getContainerEntry(UserMapper::class);
    }

    private function auditLog(): AuditLogService
    {
        return $this->getContainerEntry(AuditLogService::class);
    }

    public function newLoginForm(array $values = []): LoginForm
    {
        $form = new LoginForm($this->getContainer());
        $form->setData($values);

        return $form;
    }

    public function newLogoutForm(): LogoutForm
    {
        return new LogoutForm($this->getContainer());
    }

    /**
     * Xác thực và mở phiên.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException mọi trường hợp không đăng nhập được
     */
    public function login(array $payload = []): UserModel
    {
        $form = new LoginForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();
        $username = (string)($formData['username'] ?? '');
        $password = (string)($formData['password'] ?? '');

        $user = $this->mapper()->getUserByUsername($username);

        if ($user === null) {
            // Vẫn ghi nhật ký: chuỗi lần thử với tên không tồn tại là dấu hiệu dò tài khoản.
            $this->auditLog()->write(
                AuditLogModel::ACTION_LOGIN_FAILED,
                UserMapper::TABLE_NAME,
                null,
                null,
                ['username' => $username, 'reason' => 'khong_ton_tai'],
                null
            );

            throw new ValidationException(['password' => self::MSG_INVALID]);
        }

        if ($this->isLocked($user)) {
            throw new ValidationException([
                'password' => sprintf(
                    'Tài khoản đang bị khóa tạm do đăng nhập sai quá %d lần. Vui lòng thử lại sau %d phút.',
                    UserConst::MAX_FAILED_LOGIN,
                    UserConst::LOCK_MINUTES
                ),
            ]);
        }

        if (!$user->isActive()) {
            $this->writeLoginFailed($user, 'tai_khoan_khong_hoat_dong');
            throw new ValidationException(['password' => self::MSG_INVALID]);
        }

        if (!password_verify($password, (string)$user->getPasswordHash())) {
            $this->registerFailedAttempt($user);
            throw new ValidationException(['password' => self::MSG_INVALID]);
        }

        // Thuật toán băm đổi (hoặc cost tăng) thì nâng cấp hash ngay trong lần đăng nhập
        // đúng này — đây là lần duy nhất hệ thống cầm mật khẩu thô một cách hợp lệ.
        if (password_needs_rehash((string)$user->getPasswordHash(), $this->hashAlgorithm(), $this->hashOptions())) {
            $this->mapper()->updatePassword($user->getId(), $this->hashPassword($password), $user->getId());
        }

        $this->openSession($user);

        $this->mapper()->updateAttrsUser($user->getId(), [
            'lastLoginAt'      => DateModel::getUtcNow(),
            'failedLoginCount' => 0,
            'lockedUntil'      => null,
        ], $user->getId());

        $this->auditLog()->write(
            AuditLogModel::ACTION_LOGIN,
            UserMapper::TABLE_NAME,
            $user->getId(),
            null,
            ['username' => $user->getUsername()],
            $user->getId()
        );

        return $user;
    }

    public function logout(): void
    {
        $auth = $this->getAuthContext();
        $userId = $auth?->getUserId();

        if ($userId !== null) {
            $this->auditLog()->write(
                AuditLogModel::ACTION_LOGOUT,
                UserMapper::TABLE_NAME,
                $userId,
                null,
                null,
                $userId
            );
        }

        $session = new SessionContainer(AppConst::SESSION_NAMESPACE);
        $session->getManager()->destroy(['send_expire_cookie' => true]);
    }

    /**
     * Băm mật khẩu theo đúng chính sách. Dùng cả khi tạo người dùng và khi đặt lại mật khẩu.
     */
    public function hashPassword(string $plain): string
    {
        return password_hash($plain, $this->hashAlgorithm(), $this->hashOptions());
    }

    // ------------------------------------------------------------------

    /**
     * Mở phiên mới cho người dùng.
     *
     * `regenerateId(true)` phải chạy TRƯỚC khi ghi identity: đổi id sau khi ghi thì dữ liệu
     * vẫn nằm ở phiên cũ mà kẻ tấn công đang giữ.
     */
    private function openSession(UserModel $user): void
    {
        $session = new SessionContainer(AppConst::SESSION_NAMESPACE);

        $manager = $session->getManager();
        if ($manager instanceof SessionManager) {
            $manager->regenerateId(true);
        }

        $session->offsetSet(AppConst::SESSION_KEY_USER, [
            'id'   => (int)$user->getId(),
            'name' => (string)$user->getFullName(),
            'role' => (string)$user->getRole(),
        ]);

        // Bí mật CSRF gắn với phiên: phiên mới thì token của mọi form cũ hết hiệu lực.
        $session->offsetSet(AppConst::SESSION_KEY_CSRF, bin2hex(random_bytes(32)));
    }

    private function isLocked(UserModel $user): bool
    {
        $lockedUntil = $user->getLockedUntil();
        if ($lockedUntil === null || $lockedUntil === '') {
            return false;
        }

        return $lockedUntil > DateModel::getUtcNow();
    }

    /**
     * Ghi nhận một lần sai mật khẩu; vượt ngưỡng thì khóa tạm.
     */
    private function registerFailedAttempt(UserModel $user): void
    {
        $failed = $user->getFailedLoginCount() + 1;
        $data = ['failedLoginCount' => $failed];

        if ($failed >= UserConst::MAX_FAILED_LOGIN) {
            $data['lockedUntil'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->modify('+' . UserConst::LOCK_MINUTES . ' minutes')
                ->format(DateModel::UTC_DATETIME_FORMAT);
            // Đếm lại từ đầu sau khi khóa, để hết hạn khóa người dùng còn đủ 5 lần thử.
            $data['failedLoginCount'] = 0;
        }

        $this->mapper()->updateAttrsUser($user->getId(), $data, $user->getId());

        $this->writeLoginFailed(
            $user,
            isset($data['lockedUntil']) ? 'sai_mat_khau_va_bi_khoa' : 'sai_mat_khau'
        );
    }

    private function writeLoginFailed(UserModel $user, string $reason): void
    {
        $this->auditLog()->write(
            AuditLogModel::ACTION_LOGIN_FAILED,
            UserMapper::TABLE_NAME,
            $user->getId(),
            null,
            ['username' => $user->getUsername(), 'reason' => $reason],
            $user->getId()
        );
    }

    /** Argon2id nếu bản PHP có, không thì bcrypt — cả hai đều đạt yêu cầu của rule bảo mật. */
    private function hashAlgorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /** @return array<string, int> */
    private function hashOptions(): array
    {
        return defined('PASSWORD_ARGON2ID') ? [] : ['cost' => 12];
    }
}
