<?php

declare(strict_types=1);

namespace User\Model\User;

use Application\Model\Constant\AppConstModel;
use Application\Service\AuthContextService;

/**
 * Hằng số của tài khoản người dùng — M01 user.
 *
 * VAI TRÒ cố ý vẫn khai ở `Application\Service\AuthContextService`, không chuyển về đây:
 * `BaseController` và các module nghiệp vụ đều đọc vai trò qua ACL, mà tầng nền KHÔNG
 * được phụ thuộc ngược vào module nghiệp vụ. UserConst chỉ bổ sung
 * NHÃN và thứ tự hiển thị. Giá trị chốt ở contracts/enums.md.
 */
class UserConst extends AppConstModel
{
    // --- Vai trò (giá trị lấy nguyên từ tầng nền, không định nghĩa lại) ---
    public const string ROLE_ADMIN      = AuthContextService::ROLE_ADMIN;
    public const string ROLE_MANAGER    = AuthContextService::ROLE_MANAGER;
    public const string ROLE_SALES      = AuthContextService::ROLE_SALES;
    public const string ROLE_DISPATCHER = AuthContextService::ROLE_DISPATCHER;
    public const string ROLE_TECHNICIAN = AuthContextService::ROLE_TECHNICIAN;
    public const string ROLE_ACCOUNTANT = AuthContextService::ROLE_ACCOUNTANT;

    /** @var array<string, string> mã vai trò => nhãn hiển thị */
    public const array ROLE_LABELS = [
        self::ROLE_ADMIN      => 'Quản trị hệ thống',
        self::ROLE_MANAGER    => 'Quản lý điều hành',
        self::ROLE_SALES      => 'Kinh doanh',
        self::ROLE_DISPATCHER => 'Điều phối',
        self::ROLE_TECHNICIAN => 'Kỹ thuật viên hiện trường',
        self::ROLE_ACCOUNTANT => 'Kế toán',
    ];

    // --- Trạng thái tài khoản ---
    /** Đăng nhập được bình thường */
    public const string STATUS_HOAT_DONG = 'hoat_dong';
    /** Tạm khóa có chủ đích (nghỉ phép, đang điều tra) — mở lại được */
    public const string STATUS_TAM_KHOA  = 'tam_khoa';
    /** Đã nghỉ việc — trạng thái cuối để khóa đăng nhập theo nghiệp vụ nhân sự */
    public const string STATUS_NGUNG     = 'ngung_su_dung';

    /** @var array<string, string> */
    public const array STATUS_LABELS = [
        self::STATUS_HOAT_DONG => 'Đang hoạt động',
        self::STATUS_TAM_KHOA  => 'Tạm khóa',
        self::STATUS_NGUNG     => 'Ngừng sử dụng',
    ];

    /**
     * Chuyển tiếp trạng thái hợp lệ. Thực thi ở UserService::changeStatus().
     *
     * `ngung_su_dung` là trạng thái cuối: người đã nghỉ việc mà cần quay lại thì tạo tài
     * khoản mới, không hồi sinh tài khoản cũ — lịch sử audit log phải gắn đúng một con người.
     *
     * @var array<string, string[]>
     */
    public const array STATUS_TRANSITIONS = [
        self::STATUS_HOAT_DONG => [self::STATUS_TAM_KHOA, self::STATUS_NGUNG],
        self::STATUS_TAM_KHOA  => [self::STATUS_HOAT_DONG, self::STATUS_NGUNG],
        self::STATUS_NGUNG     => [],
    ];

    /** Chỉ trạng thái này mới đăng nhập được. */
    public const array STATUS_DANG_NHAP_DUOC = [self::STATUS_HOAT_DONG];

    // --- Chính sách đăng nhập, theo .claude/rules/security.md ---
    /** Sai liên tiếp bao nhiêu lần thì khóa tạm. */
    public const int MAX_FAILED_LOGIN = 5;
    /** Khóa tạm bao nhiêu phút sau khi vượt ngưỡng. */
    public const int LOCK_MINUTES = 15;
    /** Độ dài mật khẩu tối thiểu. */
    public const int PASSWORD_MIN_LENGTH = 10;

    /**
     * Cột được phép sắp xếp trên trang danh sách: key client gửi => tên cột thật.
     * Whitelist bắt buộc — cấm nội suy tên cột từ tham số (.claude/rules/security.md).
     *
     * @var array<string, string>
     */
    public const array SORT_MAP = [
        'username'    => 'u.username',
        'fullName'    => 'u.fullName',
        'role'        => 'u.role',
        'status'      => 'u.status',
        'lastLoginAt' => 'u.lastLoginAt',
        'createdAt'   => 'u.createdAt',
    ];

    public const string SORT_DEFAULT = 'fullName';

    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? '—';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '—';
    }

    public static function isValidRole(?string $role): bool
    {
        return $role !== null && isset(self::ROLE_LABELS[$role]);
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS_LABELS[$status]);
    }

    public static function canTransit(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }
}
