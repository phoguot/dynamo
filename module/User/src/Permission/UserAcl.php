<?php

declare(strict_types=1);

namespace User\Permission;

use User\Model\Permission\PermissionConst;
use User\Model\User\UserConst;

/**
 * QUYỀN MẶC ĐỊNH THEO VAI TRÒ.
 *
 * Đây là lớp thứ hai trong ba lớp phân quyền của hệ thống:
 *
 *   1. `PermissionConst::RESOURCES`    — danh mục action được hệ thống quản lý.
 *   2. `UserAcl` (file này)            — mặc định theo 6 vai trò, sửa phải sửa code + deploy.
 *   3. `usr_user_permissions`          — ngoại lệ cho TỪNG người, quản trị tự sửa trên UI.
 *
 * Vì sao vai trò vẫn nằm trong code chứ không đưa hết vào DB: 6 vai trò là quyết định
 * nghiệp vụ ổn định (design/02-roles-permissions.md), còn ngoại lệ theo người mới là thứ
 * thay đổi hằng tuần. Đưa cả hai vào DB thì không ai review được thay đổi quyền qua PR.
 *
 * Không dùng `laminas-permissions-acl`: bài toán ở đây là tra bảng hai chiều
 * (vai trò × resource) không có kế thừa vai trò, thêm một dependency chỉ để làm việc đó
 * là đắt hơn giá trị nhận được. Danh mục resource/privilege lấy từ PermissionConst.
 */
class UserAcl
{
    /**
     * Quyền mặc định của từng vai trò.
     *
     * `PermissionConst::PRIVILEGE_ALL` nghĩa là toàn bộ privilege của resource đó.
     * Vai trò không có tên trong một resource ⇒ KHÔNG có quyền (mặc định từ chối).
     *
     * `admin` cố ý không liệt kê — xem isRoleAllowed().
     *
     * @var array<string, array<string, string[]>> vai trò => (resource => privilege[])
     */
    public const array ROLE_DEFAULTS = [
        UserConst::ROLE_MANAGER    => [
            'fleet:generator' => [PermissionConst::PRIVILEGE_ALL],
            'crm:customer'    => [PermissionConst::PRIVILEGE_ALL],
            'crm:site'        => [PermissionConst::PRIVILEGE_ALL],
            'crm:contact'     => [PermissionConst::PRIVILEGE_ALL],
            'sales:pricelist' => [PermissionConst::PRIVILEGE_ALL],
            'sales:pricelistitem' => [PermissionConst::PRIVILEGE_ALL],
            'sales:quote'     => [PermissionConst::PRIVILEGE_ALL],
            'sales:contract'  => [PermissionConst::PRIVILEGE_ALL],
            'rental:rentalorder' => [PermissionConst::PRIVILEGE_ALL],
            'dispatch:vehicle' => [PermissionConst::PRIVILEGE_ALL],
            'dispatch:dispatchjob' => [PermissionConst::PRIVILEGE_ALL],
            'maintenance:schedule' => [PermissionConst::PRIVILEGE_ALL],
            'maintenance:maintenancejob' => [PermissionConst::PRIVILEGE_ALL],
            'billing:creditlimit' => [PermissionConst::PRIVILEGE_ALL],
            'billing:invoice' => [PermissionConst::PRIVILEGE_ALL],
            'billing:payment' => [PermissionConst::PRIVILEGE_ALL],
            'billing:deposit' => [PermissionConst::PRIVILEGE_ALL],
            'reporting:dashboard' => [PermissionConst::PRIVILEGE_ALL],
            'reporting:report' => [PermissionConst::PRIVILEGE_ALL],
            'user:user'       => ['index', 'detail'],
            'user:auditlog'   => ['index'],
        ],
        UserConst::ROLE_SALES      => [
            'fleet:generator' => ['index', 'detail'],
            'crm:customer'    => [PermissionConst::PRIVILEGE_ALL],
            'crm:site'        => [PermissionConst::PRIVILEGE_ALL],
            'crm:contact'     => [PermissionConst::PRIVILEGE_ALL],
            'sales:pricelist' => ['index', 'detail'],
            'sales:quote'     => ['index', 'detail', 'create', 'edit'],
            'sales:contract'  => ['index', 'detail'],
            'rental:rentalorder' => ['index', 'detail', 'create', 'edit', 'extend'],
            'dispatch:dispatchjob' => ['index', 'detail'],
            'maintenance:maintenancejob' => ['index', 'detail'],
            'billing:creditlimit' => ['index', 'detail'],
            'billing:invoice' => ['index', 'detail'],
            'billing:payment' => ['index', 'detail'],
            'billing:deposit' => ['index', 'detail', 'create'],
            'reporting:dashboard' => ['index'],
        ],
        UserConst::ROLE_DISPATCHER => [
            'fleet:generator' => ['index', 'detail'],
            'crm:customer'    => ['index', 'detail'],
            'rental:rentalorder' => ['index', 'detail', 'changestatus', 'extend'],
            'dispatch:vehicle' => [PermissionConst::PRIVILEGE_ALL],
            'dispatch:dispatchjob' => [PermissionConst::PRIVILEGE_ALL],
            'maintenance:schedule' => ['index', 'detail'],
            'maintenance:maintenancejob' => [PermissionConst::PRIVILEGE_ALL],
            'reporting:dashboard' => ['index'],
        ],
        UserConst::ROLE_TECHNICIAN => [
            'dispatch:dispatchjob' => ['index', 'detail', 'changestatus'],
            'maintenance:maintenancejob' => ['index', 'detail', 'changestatus', 'addpart', 'deletepart'],
        ],
        UserConst::ROLE_ACCOUNTANT => [
            'fleet:generator' => ['index', 'detail'],
            'crm:customer'    => ['index', 'detail'],
            'sales:contract'  => ['index', 'detail'],
            'rental:rentalorder' => ['index', 'detail'],
            'dispatch:dispatchjob' => ['index', 'detail'],
            'maintenance:maintenancejob' => ['index', 'detail'],
            'billing:creditlimit' => [PermissionConst::PRIVILEGE_ALL],
            'billing:invoice' => [PermissionConst::PRIVILEGE_ALL],
            'billing:payment' => [PermissionConst::PRIVILEGE_ALL],
            'billing:deposit' => [PermissionConst::PRIVILEGE_ALL],
            'reporting:dashboard' => [PermissionConst::PRIVILEGE_ALL],
            'reporting:report' => [PermissionConst::PRIVILEGE_ALL],
            'user:auditlog'   => ['index'],
        ],
    ];

    /**
     * Vai trò này có quyền mặc định trên resource/privilege đó không.
     *
     * CHÚ Ý: đây mới là mặc định theo vai trò. Câu trả lời cuối cùng cho một con người cụ
     * thể phải hỏi `PermissionService::isAllowed()` — nơi cộng thêm ngoại lệ từng người.
     */
    public function isRoleAllowed(?string $role, ?string $resource, ?string $privilege = null): bool
    {
        if (!UserConst::isValidRole($role) || !PermissionConst::hasResource($resource)) {
            return false;
        }

        // Quản trị hệ thống có mọi quyền, và điều này KHÔNG cấu hình ngược lại được.
        // Cho phép chặn admin nghĩa là chỉ cần một lần tick nhầm trên màn hình phân quyền
        // là không còn ai vào được màn hình phân quyền để sửa lại — hệ thống tự khóa mình.
        if ($role === UserConst::ROLE_ADMIN) {
            return true;
        }

        $granted = self::ROLE_DEFAULTS[$role][$resource] ?? null;
        if ($granted === null) {
            return false;
        }

        if (in_array(PermissionConst::PRIVILEGE_ALL, $granted, true)) {
            return true;
        }

        // Hỏi "có quyền gì đó trên resource này không" — dùng khi dựng menu.
        if ($privilege === null || $privilege === PermissionConst::PRIVILEGE_ALL) {
            return $granted !== [];
        }

        return in_array($privilege, $granted, true);
    }

    /**
     * Toàn bộ quyền mặc định của một vai trò, đã trải phẳng `*` thành từng privilege.
     * Dùng để màn hình phân quyền hiển thị "mặc định của vai trò" bên cạnh ngoại lệ riêng.
     *
     * @return array<string, string[]> resource => privilege[]
     */
    public function defaultPrivilegesOf(?string $role): array
    {
        if (!UserConst::isValidRole($role)) {
            return [];
        }

        $result = [];
        foreach (PermissionConst::RESOURCES as $resource => $meta) {
            $granted = [];
            foreach (array_keys($meta['privileges']) as $privilege) {
                if ($this->isRoleAllowed($role, $resource, $privilege)) {
                    $granted[] = $privilege;
                }
            }
            if ($granted !== []) {
                $result[$resource] = $granted;
            }
        }

        return $result;
    }
}
