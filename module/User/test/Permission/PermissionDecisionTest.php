<?php

declare(strict_types=1);

namespace UserTest\Permission;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use User\Model\Permission\PermissionConst;
use User\Model\User\UserConst;
use User\Service\PermissionService;

/**
 * Thứ tự quyết định của PermissionService::decide().
 *
 * Đây là đoạn logic mà sai một nhánh là thủng phân quyền toàn hệ thống — mỗi bước trong 5
 * bước phải có ít nhất một test, và phải có test cho việc bước trên THẮNG bước dưới.
 *
 * Không chạm database: `decide()` nhận thẳng mảng ngoại lệ.
 */
class PermissionDecisionTest extends TestCase
{
    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PermissionService();
        $this->service->setContainer(new ServiceManager());
    }

    // --- Bước 1: resource ngoài danh mục ---

    public function test_man_hinh_ngoai_danh_muc_thi_bi_tu_choi(): void
    {
        self::assertFalse($this->service->decide(UserConst::ROLE_TECHNICIAN, 'platform:setting', 'index'));
    }

    public function test_action_public_thi_cho_qua_khi_chua_dang_nhap(): void
    {
        self::assertTrue($this->service->decide(null, 'user:auth', 'login'));
        self::assertTrue($this->service->isPublicAction('User\\Controller\\AuthController', 'login'));
    }

    // --- Bước 2: quản trị ---

    public function test_quan_tri_khong_bi_ngoai_le_chan(): void
    {
        $overrides = ['user:permission|save' => PermissionConst::EFFECT_DENY];

        self::assertTrue(
            $this->service->decide(UserConst::ROLE_ADMIN, 'user:permission', 'save', $overrides),
            'Quản trị phải luôn vào được màn hình phân quyền, nếu không hệ thống tự khóa chính nó'
        );
    }

    // --- Bước 5: mặc định vai trò, khi không có ngoại lệ ---

    public function test_khong_co_ngoai_le_thi_theo_vai_tro(): void
    {
        self::assertTrue($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'index'));
        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'edit'));
    }

    // --- Bước 3: ngoại lệ chính xác ---

    public function test_ngoai_le_allow_cap_them_quyen_vai_tro_khong_co(): void
    {
        $overrides = ['fleet:generator|edit' => PermissionConst::EFFECT_ALLOW];

        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'edit'));
        self::assertTrue($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'edit', $overrides));
    }

    public function test_ngoai_le_deny_cat_quyen_vai_tro_dang_co(): void
    {
        $overrides = ['fleet:generator|index' => PermissionConst::EFFECT_DENY];

        self::assertTrue($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'index'));
        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'index', $overrides));
    }

    // --- Bước 4: ngoại lệ toàn resource ---

    public function test_ngoai_le_sao_cat_toan_bo_man_hinh(): void
    {
        $overrides = ['fleet:generator|*' => PermissionConst::EFFECT_DENY];

        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'index', $overrides));
        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'detail', $overrides));
    }

    public function test_ngoai_le_cu_the_thang_ngoai_le_sao(): void
    {
        $overrides = [
            'fleet:generator|*'      => PermissionConst::EFFECT_DENY,
            'fleet:generator|detail' => PermissionConst::EFFECT_ALLOW,
        ];

        self::assertFalse($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'index', $overrides));
        self::assertTrue($this->service->decide(UserConst::ROLE_SALES, 'fleet:generator', 'detail', $overrides));
    }

    // --- Chuẩn hóa tên action ---

    public function test_khong_lach_duoc_bang_cach_go_khac_ten_action(): void
    {
        $overrides = ['fleet:generator|changestatus' => PermissionConst::EFFECT_DENY];

        foreach (['changestatus', 'change-status', 'change_status', 'ChangeStatus'] as $action) {
            self::assertFalse(
                $this->service->decide(UserConst::ROLE_MANAGER, 'fleet:generator', $action, $overrides),
                "Gõ action kiểu \"{$action}\" vẫn phải bị chặn"
            );
        }
    }

    // --- Chưa đăng nhập ---

    public function test_khong_co_vai_tro_thi_khong_co_quyen(): void
    {
        self::assertFalse($this->service->decide(null, 'fleet:generator', 'index'));
    }

    // --- Điểm vào thật từ BaseController ---

    public function test_suy_quyen_tu_ten_controller(): void
    {
        // Chưa đăng nhập, controller có trong danh mục ⇒ từ chối.
        self::assertFalse(
            $this->service->isAllowedAction(\Fleet\Controller\GeneratorController::class, 'index')
        );

        self::assertTrue($this->service->isPublicAction('Application\\Controller\\IndexController', 'index'));

        // Class không theo quy ước tên ⇒ từ chối, không đoán bừa resource.
        self::assertFalse(
            $this->service->isAllowedAction('Fleet\\Service\\GeneratorService', 'index')
        );
    }
}
