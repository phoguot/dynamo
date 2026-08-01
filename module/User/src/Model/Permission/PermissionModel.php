<?php

declare(strict_types=1);

namespace User\Model\Permission;

use Application\Model\AppModel;

/**
 * Một dòng NGOẠI LỆ quyền của một người dùng cụ thể — bảng usr_user_permissions.
 *
 * Bảng này KHÔNG chứa quyền mặc định của vai trò (thứ đó nằm trong `UserAcl`). Ở đây chỉ
 * có phần lệch so với vai trò: cấp thêm (`allow`) hoặc cắt bớt (`deny`) cho đúng một người.
 * Giữ như vậy để khi vai trò đổi quyền mặc định, mọi người dùng hưởng theo ngay — không
 * phải đi sửa hàng trăm dòng đã sao chép ra.
 */
class PermissionModel extends AppModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?string $resource = null;   // `<module>:<controller>`, xem PermissionConst
    protected ?string $privilege = null;  // tên action, hoặc `*` cho toàn bộ resource
    protected ?string $effect = null;     // allow | deny
    protected ?string $createdAt = null;
    protected ?int $createdBy = null;

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

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(?string $resource): self
    {
        $this->resource = $resource !== null ? strtolower(trim($resource)) : null;
        return $this;
    }

    public function getPrivilege(): ?string
    {
        return $this->privilege;
    }

    public function setPrivilege(?string $privilege): self
    {
        $this->privilege = $privilege !== null ? strtolower(trim($privilege)) : null;
        return $this;
    }

    public function getEffect(): ?string
    {
        return $this->effect;
    }

    public function setEffect(?string $effect): self
    {
        $this->effect = $effect;
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

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function isAllow(): bool
    {
        return $this->effect === PermissionConst::EFFECT_ALLOW;
    }

    public function isDeny(): bool
    {
        return $this->effect === PermissionConst::EFFECT_DENY;
    }

    public function getResourceLabel(): string
    {
        return PermissionConst::resourceLabel($this->resource);
    }

    public function getPrivilegeLabel(): string
    {
        return PermissionConst::privilegeLabel($this->resource, $this->privilege);
    }

    public function getEffectLabel(): string
    {
        return PermissionConst::EFFECT_LABELS[$this->effect] ?? '—';
    }
}
