<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\AppConst;
use Laminas\Session\Container as SessionContainer;

/**
 * Ngữ cảnh người đăng nhập của request hiện tại, đọc từ session cookie.
 *
 * Đây là **đường ghép tạm** giữa tầng nền và M01 identity: M01 sẽ là nơi duy nhất
 * ghi vào session (login/logout, sinh lại session id, quản lý idn_sessions). Application
 * chỉ đọc, không tự quyết ai là ai, không truy vấn bảng của M01.
 *
 * Vai trò lấy theo design/02-roles-permissions.md.
 */
class AuthContextService
{
    public const string ROLE_ADMIN      = 'admin';        // Quản trị hệ thống
    public const string ROLE_MANAGER    = 'quan_ly';      // Quản lý điều hành
    public const string ROLE_SALES      = 'kinh_doanh';   // Kinh doanh
    public const string ROLE_DISPATCHER = 'dieu_phoi';    // Điều phối
    public const string ROLE_TECHNICIAN = 'ky_thuat_vien';// Kỹ thuật viên hiện trường
    public const string ROLE_ACCOUNTANT = 'ke_toan';      // Kế toán

    /** Vai trò được xem số liệu tài chính đầy đủ — xem .claude/rules/security.md. */
    public const array ROLES_FINANCE = [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_ACCOUNTANT];

    private ?SessionContainer $session = null;

    private function session(): SessionContainer
    {
        return $this->session ??= new SessionContainer(AppConst::SESSION_NAMESPACE);
    }

    /** @return array{id:int, name:string, role:string}|null */
    public function getIdentity(): ?array
    {
        $identity = $this->session()->offsetGet(AppConst::SESSION_KEY_USER);
        return is_array($identity) && !empty($identity['id']) ? $identity : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->getIdentity() !== null;
    }

    public function getUserId(): ?int
    {
        $identity = $this->getIdentity();
        return $identity !== null ? (int)$identity['id'] : null;
    }

    public function getUserName(): ?string
    {
        return $this->getIdentity()['name'] ?? null;
    }

    public function getRole(): ?string
    {
        return $this->getIdentity()['role'] ?? null;
    }

    public function hasRole(string ...$roles): bool
    {
        $role = $this->getRole();
        return $role !== null && in_array($role, $roles, true);
    }

    /** Được xem doanh thu, công nợ, giá vốn hay không. */
    public function canSeeFinance(): bool
    {
        return $this->hasRole(...self::ROLES_FINANCE);
    }

    /**
     * Token CSRF RIÊNG cho từng form, dẫn xuất bằng HMAC từ một bí mật duy nhất của phiên.
     *
     * Vì sao dẫn xuất chứ không sinh ngẫu nhiên rồi cất từng token vào session:
     * - Token của form A không dùng lại được cho form B ⇒ vẫn tách theo từng form.
     * - Không tiêu thụ token, không phình session ⇒ mở nhiều tab, bấm Back rồi submit lại,
     *   hay submit lại sau khi form báo lỗi đều KHÔNG dính lỗi "phiên hết hạn" oan.
     * - Token chỉ đổi khi phiên đổi (đăng nhập/đăng xuất sinh lại session id).
     *
     * Mỗi form tự khai `getFormName()`; xem Application\Form\AppForm.
     */
    public function getCsrfToken(string $formName = AppConst::CSRF_FORM_DEFAULT): string
    {
        return hash_hmac('sha256', $formName, $this->getCsrfSecret());
    }

    public function isValidCsrfToken(?string $token, string $formName = AppConst::CSRF_FORM_DEFAULT): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->getCsrfToken($formName), $token);
    }

    /** Bí mật gốc của phiên; sinh một lần, không bao giờ gửi xuống client. */
    private function getCsrfSecret(): string
    {
        $secret = $this->session()->offsetGet(AppConst::SESSION_KEY_CSRF);
        if (!is_string($secret) || $secret === '') {
            $secret = bin2hex(random_bytes(32));
            $this->session()->offsetSet(AppConst::SESSION_KEY_CSRF, $secret);
        }

        return $secret;
    }
}
