<?php

declare(strict_types=1);

namespace UserTest\Permission;

use Application\Controller\IndexController;
use Billing\Controller\CreditLimitController;
use Billing\Controller\DepositController;
use Billing\Controller\InvoiceController;
use Billing\Controller\PaymentController;
use Crm\Controller\ContactController;
use Crm\Controller\CustomerController;
use Crm\Controller\SiteController;
use Dispatch\Controller\DispatchJobController;
use Dispatch\Controller\VehicleController;
use Fleet\Controller\GeneratorController;
use Maintenance\Controller\MaintenanceJobController;
use Maintenance\Controller\ScheduleController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Rental\Controller\RentalOrderController;
use Reporting\Controller\DashboardController;
use Reporting\Controller\ReportController;
use Sales\Controller\ContractController;
use Sales\Controller\PriceListItemController;
use Sales\Controller\PriceListController;
use Sales\Controller\QuoteController;
use User\Controller\AuthController;
use User\Controller\PermissionController;
use User\Controller\UserController;
use User\Model\Permission\PermissionConst;

/**
 * Danh mục quyền và cách suy resource từ tên class Controller.
 *
 * Việc suy tên là điểm nối mong manh nhất của cả cơ chế: sai ở đây thì guard im lặng cho
 * qua mọi màn hình mà không ai nhận ra. Test bám đúng các controller CÓ THẬT trong repo.
 */
class PermissionConstTest extends TestCase
{
    public function test_suy_resource_tu_ten_controller_that(): void
    {
        self::assertSame('fleet:generator', PermissionConst::resourceFromController(GeneratorController::class));
        self::assertSame('sales:pricelist', PermissionConst::resourceFromController(PriceListController::class));
        self::assertSame('sales:pricelistitem', PermissionConst::resourceFromController(PriceListItemController::class));
        self::assertSame('sales:quote', PermissionConst::resourceFromController(QuoteController::class));
        self::assertSame('sales:contract', PermissionConst::resourceFromController(ContractController::class));
        self::assertSame('rental:rentalorder', PermissionConst::resourceFromController(RentalOrderController::class));
        self::assertSame('dispatch:vehicle', PermissionConst::resourceFromController(VehicleController::class));
        self::assertSame('dispatch:dispatchjob', PermissionConst::resourceFromController(DispatchJobController::class));
        self::assertSame('maintenance:schedule', PermissionConst::resourceFromController(ScheduleController::class));
        self::assertSame('maintenance:maintenancejob', PermissionConst::resourceFromController(MaintenanceJobController::class));
        self::assertSame('billing:creditlimit', PermissionConst::resourceFromController(CreditLimitController::class));
        self::assertSame('billing:invoice', PermissionConst::resourceFromController(InvoiceController::class));
        self::assertSame('billing:payment', PermissionConst::resourceFromController(PaymentController::class));
        self::assertSame('billing:deposit', PermissionConst::resourceFromController(DepositController::class));
        self::assertSame('reporting:dashboard', PermissionConst::resourceFromController(DashboardController::class));
        self::assertSame('reporting:report', PermissionConst::resourceFromController(ReportController::class));
        self::assertSame('crm:customer', PermissionConst::resourceFromController(CustomerController::class));
        self::assertSame('crm:site', PermissionConst::resourceFromController(SiteController::class));
        self::assertSame('crm:contact', PermissionConst::resourceFromController(ContactController::class));
        self::assertSame('user:user', PermissionConst::resourceFromController(UserController::class));
        self::assertSame('user:permission', PermissionConst::resourceFromController(PermissionController::class));
        self::assertSame('user:auth', PermissionConst::resourceFromController(AuthController::class));
        self::assertSame('application:index', PermissionConst::resourceFromController(IndexController::class));
    }

    public function test_ten_class_khong_dung_quy_uoc_tra_ve_null(): void
    {
        self::assertNull(PermissionConst::resourceFromController('Fleet\\Service\\GeneratorService'));
        self::assertNull(PermissionConst::resourceFromController('KhongPhaiClass'));
        self::assertNull(PermissionConst::resourceFromController(''));
        self::assertNull(PermissionConst::resourceFromController(null));
    }

    public function test_moi_controller_dang_co_deu_nam_trong_danh_muc(): void
    {
        // Quên khai controller mới vào RESOURCES nghĩa là màn hình đó không chịu sự quản lý
        // của phân quyền chi tiết. Test này bắt đúng chỗ đó.
        foreach ($this->realControllerClasses() as $controller) {
            $resource = PermissionConst::resourceFromController($controller);

            if (PermissionConst::hasResource($resource)) {
                self::assertTrue(true);
                continue;
            }

            self::assertTrue(
                isset(PermissionConst::PUBLIC_ACTIONS[$resource]) || isset(PermissionConst::AUTHENTICATED_ACTIONS[$resource]),
                "Controller {$controller} chưa được khai trong RESOURCES, PUBLIC_ACTIONS hoặc AUTHENTICATED_ACTIONS"
            );
        }
    }

    public function test_moi_action_public_deu_duoc_acl_quan_ly(): void
    {
        foreach ($this->realControllerClasses() as $controller) {
            $resource = PermissionConst::resourceFromController($controller);
            $reflection = new ReflectionClass($controller);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $controller || !str_ends_with($method->getName(), 'Action')) {
                    continue;
                }

                $privilege = strtolower(substr($method->getName(), 0, -6));
                $isManaged = PermissionConst::isValidPair($resource, $privilege)
                    || PermissionConst::isPublicAction($resource, $privilege)
                    || PermissionConst::isAuthenticatedAction($resource, $privilege);

                self::assertTrue(
                    $isManaged,
                    "Action {$controller}::{$method->getName()} chưa được khai trong ACL M01"
                );
            }
        }
    }

    public function test_cap_resource_privilege_hop_le(): void
    {
        self::assertTrue(PermissionConst::isValidPair('fleet:generator', 'index'));
        self::assertTrue(PermissionConst::isValidPair('fleet:generator', PermissionConst::PRIVILEGE_ALL));

        self::assertFalse(PermissionConst::isValidPair('fleet:generator', 'xoa_sach'));
        self::assertFalse(PermissionConst::isValidPair('module:bia', 'index'));
        self::assertFalse(PermissionConst::isValidPair(null, 'index'));
    }

    public function test_hieu_luc_chi_nhan_allow_hoac_deny(): void
    {
        self::assertTrue(PermissionConst::isValidEffect(PermissionConst::EFFECT_ALLOW));
        self::assertTrue(PermissionConst::isValidEffect(PermissionConst::EFFECT_DENY));

        self::assertFalse(PermissionConst::isValidEffect('maybe'));
        self::assertFalse(PermissionConst::isValidEffect(''));
        self::assertFalse(PermissionConst::isValidEffect(null));
    }

    public function test_moi_resource_deu_thuoc_mot_module_co_nhan(): void
    {
        foreach (PermissionConst::RESOURCES as $resource => $meta) {
            self::assertArrayHasKey(
                $meta['module'],
                PermissionConst::MODULE_LABELS,
                "Resource {$resource} trỏ tới module chưa có nhãn"
            );
            self::assertNotSame([], $meta['privileges'], "Resource {$resource} không có thao tác nào");
        }
    }

    public function test_gom_theo_module_giu_du_so_resource(): void
    {
        $grouped = PermissionConst::groupedByModule();

        $count = 0;
        foreach ($grouped as $module) {
            $count += count($module['resources']);
        }

        self::assertSame(count(PermissionConst::RESOURCES), $count);
    }

    /** @return list<class-string> */
    private function realControllerClasses(): array
    {
        $root = dirname(__DIR__, 4);
        $files = glob($root . '/module/*/src/Controller/*Controller.php') ?: [];
        $classes = [];
        foreach ($files as $file) {
            $module = basename(dirname($file, 3));
            $controller = basename($file, '.php');
            $class = $module . '\\Controller\\' . $controller;
            if (class_exists($class)) {
                $reflection = new ReflectionClass($class);
                if ($reflection->isAbstract() || !$this->hasDeclaredPublicAction($reflection)) {
                    continue;
                }
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes;
    }

    private function hasDeclaredPublicAction(ReflectionClass $reflection): bool
    {
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName()
                && str_ends_with($method->getName(), 'Action')) {
                return true;
            }
        }

        return false;
    }
}
