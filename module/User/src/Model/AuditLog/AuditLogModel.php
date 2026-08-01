<?php

declare(strict_types=1);

namespace User\Model\AuditLog;

use Application\Model\AppModel;

/**
 * Một dòng nhật ký kiểm toán — bảng usr_audit_logs.
 *
 * APPEND-ONLY: không có setter cho việc sửa, không có hàm update ở mapper. Nhật ký sửa
 * được thì không còn là nhật ký.
 */
class AuditLogModel extends AppModel
{
    // --- Hành động được ghi, theo .claude/rules/security.md mục Audit log ---
    public const string ACTION_LOGIN          = 'dang_nhap';
    public const string ACTION_LOGIN_FAILED   = 'dang_nhap_that_bai';
    public const string ACTION_LOGOUT         = 'dang_xuat';
    public const string ACTION_CREATE         = 'tao_moi';
    public const string ACTION_UPDATE         = 'cap_nhat';
    public const string ACTION_DELETE         = 'xoa';
    public const string ACTION_STATUS_CHANGED = 'doi_trang_thai';
    public const string ACTION_PERMISSION_CHANGED = 'doi_phan_quyen';
    public const string ACTION_PASSWORD_RESET = 'dat_lai_mat_khau';

    /** @var array<string, string> */
    public const array ACTION_LABELS = [
        self::ACTION_LOGIN              => 'Đăng nhập',
        self::ACTION_LOGIN_FAILED       => 'Đăng nhập thất bại',
        self::ACTION_LOGOUT             => 'Đăng xuất',
        self::ACTION_CREATE             => 'Tạo mới',
        self::ACTION_UPDATE             => 'Cập nhật',
        self::ACTION_DELETE             => 'Xóa',
        self::ACTION_STATUS_CHANGED     => 'Đổi trạng thái',
        self::ACTION_PERMISSION_CHANGED => 'Đổi phân quyền',
        self::ACTION_PASSWORD_RESET     => 'Đặt lại mật khẩu',
    ];

    /**
     * Cột được phép sắp xếp trên màn hình đọc nhật ký. Sắp theo `id` chính là theo thời gian
     * ghi (id tự tăng), tránh phải sort trên `createdAt` — hai dòng cùng mili-giây vẫn có thứ tự.
     *
     * @var array<string, string>
     */
    public const array SORT_MAP = [
        'createdAt'  => 'a.id',
        'action'     => 'a.action',
        'objectType' => 'a.objectType',
    ];

    public const string SORT_DEFAULT = 'createdAt';

    protected ?int $id = null;
    protected ?int $userId = null;       // Ai thực hiện; null khi đăng nhập thất bại với tên không tồn tại
    protected ?string $action = null;
    protected ?string $objectType = null; // Ví dụ `usr_users`, `flt_generators`
    protected ?int $objectId = null;
    protected ?string $beforeJson = null;
    protected ?string $afterJson = null;
    protected ?string $ip = null;
    protected ?string $userAgent = null;
    protected ?string $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectType(?string $objectType): self
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function setObjectId(?int $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getBeforeJson(): ?string
    {
        return $this->beforeJson;
    }

    public function setBeforeJson(?string $beforeJson): self
    {
        $this->beforeJson = $beforeJson;
        return $this;
    }

    public function getAfterJson(): ?string
    {
        return $this->afterJson;
    }

    public function setAfterJson(?string $afterJson): self
    {
        $this->afterJson = $afterJson;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getActionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? (string)$this->action;
    }
}
