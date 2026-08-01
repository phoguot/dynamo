<?php

declare(strict_types=1);

namespace User\Model\User;

use Application\Model\AppFormat;
use Application\Model\AppModel;

/**
 * Tài khoản người dùng — entity chính của M01 user.
 *
 * POPO: chỉ getter/setter và hàm dựng dữ liệu hiển thị. Không SQL, không quy tắc nghiệp vụ.
 * Tên thuộc tính và cột DB đều dùng camelCase để model nạp dữ liệu trực tiếp.
 *
 * `passwordHash` cố ý KHÔNG có trong getRespUser() và không bao giờ được đưa ra View.
 */
class UserModel extends AppModel
{
    // --- Cột trong bảng usr_users ---
    protected ?int $id = null;
    protected ?string $username = null;     // Tên đăng nhập, duy nhất, chữ thường
    protected ?string $fullName = null;
    protected ?string $email = null;        // Duy nhất, cho phép NULL
    protected ?string $phone = null;
    protected ?string $passwordHash = null;
    protected ?string $role = null;
    protected ?string $status = null;
    protected ?string $lastLoginAt = null;
    protected int $failedLoginCount = 0;
    protected ?string $lockedUntil = null;  // Khóa tạm sau khi sai mật khẩu nhiều lần
    protected ?string $note = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected ?int $createdBy = null;
    protected ?int $updatedBy = null;

    // --- Trường phục vụ tìm kiếm, không lưu DB ---
    protected ?string $keyword = null;

    // -------------------------------------------------------------------------
    // Cột DB

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /** Tên đăng nhập luôn chuẩn hóa về chữ thường — "Admin" và "admin" là một người. */
    public function setUsername(?string $username): self
    {
        $this->username = $username !== null ? strtolower(trim($username)) : null;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email !== null ? strtolower(trim($email)) : null;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?string $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getFailedLoginCount(): int
    {
        return $this->failedLoginCount;
    }

    public function setFailedLoginCount(?int $failedLoginCount): self
    {
        $this->failedLoginCount = (int)$failedLoginCount;
        return $this;
    }

    public function getLockedUntil(): ?string
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?string $lockedUntil): self
    {
        $this->lockedUntil = $lockedUntil;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
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

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Trường tìm kiếm

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): self
    {
        $this->keyword = $keyword;
        return $this;
    }

    // -------------------------------------------------------------------------

    public function getRoleLabel(): string
    {
        return UserConst::roleLabel($this->role);
    }

    public function getStatusLabel(): string
    {
        return UserConst::statusLabel($this->status);
    }

    /** Tài khoản có ở trạng thái cho phép đăng nhập không (chưa xét khóa tạm và mật khẩu). */
    public function isActive(): bool
    {
        return in_array((string)$this->status, UserConst::STATUS_DANG_NHAP_DUOC, true);
    }

    /**
     * Che bớt số điện thoại khi hiển thị ở danh sách — .claude/rules/security.md.
     * Trang chi tiết mới hiện đầy đủ, và chỉ cho vai trò có quyền.
     */
    public function getMaskedPhone(): string
    {
        $phone = (string)$this->phone;
        if ($phone === '') {
            return '—';
        }
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }

        return substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 5)) . substr($phone, -2);
    }

    /**
     * Dữ liệu đã chuẩn hóa để hiển thị. KHÔNG chứa passwordHash — đây là bất biến,
     * đừng thêm vào "cho tiện debug".
     *
     * @return array<string, mixed>
     */
    public function getRespUser(): array
    {
        return [
            'id'          => AppFormat::castIntOrNull($this->id),
            'username'    => AppFormat::castStringOrNull($this->username),
            'fullName'    => AppFormat::castStringOrNull($this->fullName),
            'email'       => AppFormat::castStringOrNull($this->email),
            'phone'       => AppFormat::castStringOrNull($this->phone),
            'role'        => [
                'id'   => AppFormat::castStringOrNull($this->role),
                'name' => $this->getRoleLabel(),
            ],
            'status'      => [
                'id'   => AppFormat::castStringOrNull($this->status),
                'name' => $this->getStatusLabel(),
            ],
            'lastLoginAt' => AppFormat::castStringOrNull($this->lastLoginAt),
            'createdAt'   => AppFormat::castStringOrNull($this->createdAt),
        ];
    }
}
