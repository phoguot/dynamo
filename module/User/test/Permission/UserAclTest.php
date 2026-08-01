<?php

declare(strict_types=1);

namespace UserTest\Permission;

use PHPUnit\Framework\TestCase;
use User\Model\Permission\PermissionConst;
use User\Model\User\UserConst;
use User\Permission\UserAcl;

/**
 * Quyền mặc định theo vai trò.
 *
 * Đây là lớp phân quyền duy nhất không cần database, nên test được trực tiếp — mọi thay đổi
 * ROLE_DEFAULTS phải đi kèm test ở đây.
 */
class UserAclTest extends TestCase
{
    private UserAcl $acl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->acl = new UserAcl();
    }

    public function test_quan_tri_co_moi_quyen(): void
    {
        foreach (PermissionConst::RESOURCES as $resource => $meta) {
            foreach (array_keys($meta['privileges']) as $privilege) {
                self::assertTrue(
                    $this->acl->isRoleAllowed(UserConst::ROLE_ADMIN, $resource, $privilege),
                    sprintf('Quản trị phải có quyền %s trên %s', $privilege, $resource)
                );
            }
        }
    }

    public function test_quan_ly_xem_duoc_nhung_khong_sua_duoc_nguoi_dung(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'create'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'edit'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'resetpassword'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:user', 'delete'));
    }

    public function test_chi_quan_tri_xoa_duoc_tai_khoan(): void
    {
        // Xóa tài khoản là quyền chỉ Quản trị hệ thống có — mọi vai trò khác bị từ chối.
        foreach (array_keys(UserConst::ROLE_LABELS) as $role) {
            self::assertSame(
                $role === UserConst::ROLE_ADMIN,
                $this->acl->isRoleAllowed($role, 'user:user', 'delete'),
                sprintf('Vai trò %s không được phép xóa tài khoản', $role)
            );
        }
    }

    public function test_chi_quan_tri_vao_duoc_man_hinh_phan_quyen(): void
    {
        foreach (array_keys(UserConst::ROLE_LABELS) as $role) {
            $expected = $role === UserConst::ROLE_ADMIN;

            self::assertSame(
                $expected,
                $this->acl->isRoleAllowed($role, 'user:permission', 'save'),
                sprintf('Vai trò %s không được quyền sửa phân quyền', $role)
            );
        }
    }

    public function test_kinh_doanh_chi_xem_doi_may_khong_sua(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'fleet:generator', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'fleet:generator', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'fleet:generator', 'edit'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'fleet:generator', 'changestatus'));
    }

    public function test_quyen_mac_dinh_cho_don_thue(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'rental:rentalorder', 'changestatus'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'rental:rentalorder', 'create'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'rental:rentalorder', 'changestatus'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'rental:rentalorder', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'rental:rentalorder', 'changestatus'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'rental:rentalorder', 'edit'));
    }

    public function test_ky_thuat_vien_chi_duoc_xu_ly_lenh_dieu_phoi(): void
    {
        self::assertSame(
            ['index', 'detail', 'changestatus'],
            $this->acl->defaultPrivilegesOf(UserConst::ROLE_TECHNICIAN)['dispatch:dispatchjob'] ?? []
        );
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'dispatch:dispatchjob', 'assign'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'dispatch:vehicle', 'index'));
    }

    public function test_quyen_mac_dinh_cho_dieu_phoi(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'dispatch:vehicle', 'changestatus'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'dispatch:dispatchjob', 'assign'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'dispatch:dispatchjob', 'detail'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'dispatch:dispatchjob', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'dispatch:dispatchjob', 'changestatus'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'dispatch:dispatchjob', 'assign'));
    }

    public function test_quyen_mac_dinh_cho_bao_tri(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'maintenance:schedule', 'edit'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'maintenance:maintenancejob', 'addpart'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'maintenance:maintenancejob', 'changestatus'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'maintenance:maintenancejob', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'maintenance:schedule', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'maintenance:maintenancejob', 'edit'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'maintenance:maintenancejob', 'addpart'));
    }

    public function test_quyen_mac_dinh_cho_billing(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'billing:invoice', 'changestatus'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'billing:payment', 'cancel'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'billing:deposit', 'create'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'billing:invoice', 'detail'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'billing:invoice', 'create'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'billing:payment', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'billing:creditlimit', 'detail'));
    }

    public function test_quyen_mac_dinh_cho_reporting(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'reporting:dashboard', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'reporting:report', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'reporting:report', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'reporting:dashboard', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'reporting:dashboard', 'index'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'reporting:report', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'reporting:dashboard', 'index'));
    }

    public function test_nhat_ky_kiem_toan_chi_admin_quan_ly_ke_toan_xem_duoc(): void
    {
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ADMIN, 'user:auditlog', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'user:auditlog', 'index'));
        self::assertTrue($this->acl->isRoleAllowed(UserConst::ROLE_ACCOUNTANT, 'user:auditlog', 'index'));

        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_SALES, 'user:auditlog', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_DISPATCHER, 'user:auditlog', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_TECHNICIAN, 'user:auditlog', 'index'));
    }

    public function test_vai_tro_khong_ton_tai_bi_tu_choi(): void
    {
        self::assertFalse($this->acl->isRoleAllowed('vai_tro_bia', 'fleet:generator', 'index'));
        self::assertFalse($this->acl->isRoleAllowed(null, 'fleet:generator', 'index'));
    }

    public function test_resource_khong_ton_tai_bi_tu_choi(): void
    {
        // Lưu ý: từ chối ở tầng ACL. Tầng PermissionService lại CHO QUA resource không có
        // trong danh mục — hai tầng khác nhau có chủ đích, xem chú thích ở PermissionService.
        self::assertFalse($this->acl->isRoleAllowed(UserConst::ROLE_MANAGER, 'module:khong_co', 'index'));
    }

    public function test_moi_resource_khai_trong_role_defaults_deu_co_trong_danh_muc(): void
    {
        foreach (UserAcl::ROLE_DEFAULTS as $role => $resources) {
            self::assertArrayHasKey($role, UserConst::ROLE_LABELS, "Vai trò lạ trong ROLE_DEFAULTS: {$role}");

            foreach ($resources as $resource => $privileges) {
                self::assertTrue(
                    PermissionConst::hasResource($resource),
                    "Resource lạ trong ROLE_DEFAULTS: {$resource}"
                );

                foreach ($privileges as $privilege) {
                    self::assertTrue(
                        PermissionConst::isValidPair($resource, $privilege),
                        "Privilege lạ: {$resource} => {$privilege}"
                    );
                }
            }
        }
    }
}
