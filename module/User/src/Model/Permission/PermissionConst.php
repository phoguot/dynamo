<?php

declare(strict_types=1);

namespace User\Model\Permission;

use Application\Model\Constant\AppConstModel;

/**
 * DANH MỤC QUYỀN của toàn hệ thống — "màn hình nào thuộc module nào, có những thao tác gì".
 *
 * Đây là nguồn sự thật duy nhất cho câu hỏi "user được vào module nào". Mỗi khi một module
 * mới thêm màn hình, khai thêm một dòng vào RESOURCES — KHÔNG rải chuỗi quyền ở nơi khác.
 *
 * Quy ước đặt tên:
 * - resource  = `<module>:<controller>`, ví dụ `fleet:generator` — suy ra từ tên class Controller.
 * - privilege = tên action của controller, ví dụ `index`, `detail`, `create`, `edit`.
 *
 * Vì sao suy ra từ tên class thay vì khai tay: tên route đổi được, tên class thì không đổi
 * lặng lẽ. Thêm action mới mà quên khai ở đây ⇒ action đó KHÔNG bị ACL quản lý, chỉ còn
 * ACL ở tầng nền sẽ từ chối action chưa khai ở đây, trừ action public/authenticated khai riêng.
 */
class PermissionConst extends AppConstModel
{
    /** Privilege đại diện "mọi thao tác của resource này" — chỉ dùng cho dòng override từng user. */
    public const string PRIVILEGE_ALL = '*';

    // --- Hiệu lực của một dòng override ---
    public const string EFFECT_ALLOW = 'allow';
    public const string EFFECT_DENY  = 'deny';

    /** @var array<string, string> */
    public const array EFFECT_LABELS = [
        self::EFFECT_ALLOW => 'Cho phép',
        self::EFFECT_DENY  => 'Từ chối',
    ];

    // --- Mã module, khớp bản đồ module ở docs/01-modules.md ---
    public const string MODULE_USER        = 'user';
    public const string MODULE_FLEET       = 'fleet';
    public const string MODULE_CRM         = 'crm';
    public const string MODULE_SALES       = 'sales';
    public const string MODULE_RENTAL      = 'rental';
    public const string MODULE_DISPATCH    = 'dispatch';
    public const string MODULE_MAINTENANCE = 'maintenance';
    public const string MODULE_BILLING     = 'billing';
    public const string MODULE_REPORTING   = 'reporting';
    public const string MODULE_PLATFORM    = 'platform';

    /**
     * Action công khai, không cần đăng nhập và không hiện trên ma trận phân quyền.
     *
     * @var array<string, string[]>
     */
    public const array PUBLIC_ACTIONS = [
        'application:index' => ['index'],
        'user:auth'         => ['login'],
    ];

    /**
     * Action chỉ cần đăng nhập, không phân quyền theo vai trò/người và không hiện trên ma trận.
     *
     * @var array<string, string[]>
     */
    public const array AUTHENTICATED_ACTIONS = [
        'user:auth' => ['logout'],
        'user:user' => ['profile'],
    ];

    /**
     * Nhãn module hiển thị trên màn hình phân quyền.
     *
     * @var array<string, string>
     */
    public const array MODULE_LABELS = [
        self::MODULE_USER        => 'M01 · Người dùng và phân quyền',
        self::MODULE_FLEET       => 'M02 · Đội máy phát điện',
        self::MODULE_CRM         => 'M03 · Khách hàng và công trình',
        self::MODULE_SALES       => 'M04 · Báo giá và hợp đồng',
        self::MODULE_RENTAL      => 'M05 · Đơn thuê',
        self::MODULE_DISPATCH    => 'M06 · Điều phối giao/thu hồi',
        self::MODULE_MAINTENANCE => 'M07 · Bảo trì và sửa chữa',
        self::MODULE_BILLING     => 'M08 · Hóa đơn và công nợ',
        self::MODULE_REPORTING   => 'M09 · Báo cáo',
        self::MODULE_PLATFORM    => 'M10 · Nền tảng dùng chung',
    ];

    /**
     * Toàn bộ resource được ACL quản lý.
     *
     * Module chưa dựng thì CHƯA khai ở đây — khai trước sẽ tạo ra quyền trỏ vào màn hình
     * không tồn tại, người quản trị tick vào rồi tưởng đã phân quyền xong.
     *
     * @var array<string, array{module:string, label:string, privileges:array<string,string>}>
     */
    public const array RESOURCES = [
        'user:user'       => [
            'module'     => self::MODULE_USER,
            'label'      => 'Quản lý người dùng',
            'privileges' => [
                'index'        => 'Xem danh sách người dùng',
                'detail'       => 'Xem chi tiết người dùng',
                'create'       => 'Thêm người dùng',
                'edit'         => 'Sửa người dùng',
                'changestatus' => 'Khóa / mở khóa tài khoản',
                'resetpassword' => 'Đặt lại mật khẩu',
                'delete'       => 'Xóa tài khoản',
            ],
        ],
        'user:permission' => [
            'module'     => self::MODULE_USER,
            'label'      => 'Phân quyền theo từng người dùng',
            'privileges' => [
                'index' => 'Xem ma trận phân quyền',
                'save'  => 'Sửa quyền của người dùng',
                'reset' => 'Gỡ ngoại lệ, về mặc định vai trò',
            ],
        ],
        'user:auditlog' => [
            'module'     => self::MODULE_USER,
            'label'      => 'Nhật ký kiểm toán',
            'privileges' => [
                'index' => 'Xem nhật ký kiểm toán toàn hệ thống',
            ],
        ],
        'fleet:generator' => [
            'module'     => self::MODULE_FLEET,
            'label'      => 'Đội máy phát điện',
            'privileges' => [
                'index'        => 'Xem danh sách máy',
                'detail'       => 'Xem chi tiết máy',
                'create'       => 'Thêm máy',
                'edit'         => 'Sửa máy',
                'changestatus' => 'Đổi trạng thái máy',
            ],
        ],
        'crm:customer' => [
            'module'     => self::MODULE_CRM,
            'label'      => 'Khách hàng và công trình',
            'privileges' => [
                'index'        => 'Xem danh sách khách hàng',
                'detail'       => 'Xem chi tiết khách hàng',
                'create'       => 'Thêm khách hàng',
                'edit'         => 'Sửa khách hàng',
                'changestatus' => 'Đổi trạng thái khách hàng',
            ],
        ],
        'crm:site' => [
            'module'     => self::MODULE_CRM,
            'label'      => 'Công trình của khách hàng',
            'privileges' => [
                'create' => 'Thêm công trình',
                'edit'   => 'Sửa công trình',
            ],
        ],
        'crm:contact' => [
            'module'     => self::MODULE_CRM,
            'label'      => 'Liên hệ của khách hàng',
            'privileges' => [
                'create' => 'Thêm liên hệ',
                'edit'   => 'Sửa liên hệ',
            ],
        ],
        'sales:pricelist' => [
            'module'     => self::MODULE_SALES,
            'label'      => 'Bảng giá',
            'privileges' => [
                'index'        => 'Xem danh sách bảng giá',
                'detail'       => 'Xem chi tiết bảng giá',
                'create'       => 'Thêm bảng giá',
                'edit'         => 'Sửa bảng giá',
                'toggleactive' => 'Kích hoạt / ngưng dùng bảng giá',
            ],
        ],
        'sales:pricelistitem' => [
            'module'     => self::MODULE_SALES,
            'label'      => 'Dòng bảng giá',
            'privileges' => [
                'create' => 'Thêm dòng bảng giá',
                'edit'   => 'Sửa dòng bảng giá',
            ],
        ],
        'sales:quote' => [
            'module'     => self::MODULE_SALES,
            'label'      => 'Báo giá',
            'privileges' => [
                'index'        => 'Xem danh sách báo giá',
                'detail'       => 'Xem chi tiết báo giá',
                'create'       => 'Thêm báo giá',
                'edit'         => 'Sửa báo giá',
                'changestatus' => 'Gửi duyệt / duyệt / từ chối báo giá',
            ],
        ],
        'sales:contract' => [
            'module'     => self::MODULE_SALES,
            'label'      => 'Hợp đồng',
            'privileges' => [
                'index'        => 'Xem danh sách hợp đồng',
                'detail'       => 'Xem chi tiết hợp đồng',
                'create'       => 'Thêm hợp đồng',
                'edit'         => 'Sửa hợp đồng',
                'changestatus' => 'Đổi trạng thái hợp đồng',
            ],
        ],
        'rental:rentalorder' => [
            'module'     => self::MODULE_RENTAL,
            'label'      => 'Đơn thuê',
            'privileges' => [
                'index'        => 'Xem danh sách đơn thuê',
                'detail'       => 'Xem chi tiết đơn thuê',
                'create'       => 'Thêm đơn thuê',
                'edit'         => 'Sửa đơn thuê',
                'changestatus' => 'Đổi trạng thái đơn thuê',
                'extend'       => 'Gia hạn đơn thuê',
            ],
        ],
        'dispatch:vehicle' => [
            'module'     => self::MODULE_DISPATCH,
            'label'      => 'Xe điều phối',
            'privileges' => [
                'index'        => 'Xem danh sách xe',
                'detail'       => 'Xem chi tiết xe',
                'create'       => 'Thêm xe',
                'edit'         => 'Sửa xe',
                'changestatus' => 'Đổi trạng thái xe',
            ],
        ],
        'dispatch:dispatchjob' => [
            'module'     => self::MODULE_DISPATCH,
            'label'      => 'Lệnh giao/thu hồi',
            'privileges' => [
                'index'        => 'Xem danh sách lệnh',
                'detail'       => 'Xem chi tiết lệnh',
                'create'       => 'Thêm lệnh',
                'edit'         => 'Sửa lệnh',
                'changestatus' => 'Đổi trạng thái lệnh',
                'assign'       => 'Phân công nhân sự',
                'unassign'     => 'Bỏ phân công nhân sự',
            ],
        ],
        'maintenance:schedule' => [
            'module'     => self::MODULE_MAINTENANCE,
            'label'      => 'Lịch bảo trì',
            'privileges' => [
                'index'  => 'Xem danh sách lịch bảo trì',
                'detail' => 'Xem chi tiết lịch bảo trì',
                'create' => 'Thêm lịch bảo trì',
                'edit'   => 'Sửa lịch bảo trì',
            ],
        ],
        'maintenance:maintenancejob' => [
            'module'     => self::MODULE_MAINTENANCE,
            'label'      => 'Phiếu bảo trì/sửa chữa',
            'privileges' => [
                'index'        => 'Xem danh sách phiếu',
                'detail'       => 'Xem chi tiết phiếu',
                'create'       => 'Thêm phiếu',
                'edit'         => 'Sửa phiếu',
                'changestatus' => 'Đổi trạng thái phiếu',
                'addpart'      => 'Thêm phụ tùng vào phiếu',
                'deletepart'   => 'Xóa phụ tùng khỏi phiếu',
            ],
        ],
        'billing:creditlimit' => [
            'module'     => self::MODULE_BILLING,
            'label'      => 'Hạn mức công nợ',
            'privileges' => [
                'index'  => 'Xem danh sách hạn mức',
                'detail' => 'Xem chi tiết hạn mức',
                'create' => 'Thêm hạn mức',
                'edit'   => 'Sửa hạn mức',
            ],
        ],
        'billing:invoice' => [
            'module'     => self::MODULE_BILLING,
            'label'      => 'Hóa đơn',
            'privileges' => [
                'index'        => 'Xem danh sách hóa đơn',
                'detail'       => 'Xem chi tiết hóa đơn',
                'create'       => 'Thêm hóa đơn',
                'edit'         => 'Sửa hóa đơn',
                'changestatus' => 'Đổi trạng thái hóa đơn',
                'addline'      => 'Thêm dòng hóa đơn',
                'deleteline'   => 'Xóa dòng hóa đơn',
            ],
        ],
        'billing:payment' => [
            'module'     => self::MODULE_BILLING,
            'label'      => 'Phiếu thu',
            'privileges' => [
                'index'  => 'Xem danh sách phiếu thu',
                'detail' => 'Xem chi tiết phiếu thu',
                'create' => 'Ghi nhận phiếu thu',
                'cancel' => 'Hủy phiếu thu',
            ],
        ],
        'billing:deposit' => [
            'module'     => self::MODULE_BILLING,
            'label'      => 'Phiếu cọc',
            'privileges' => [
                'index'  => 'Xem danh sách phiếu cọc',
                'detail' => 'Xem chi tiết phiếu cọc',
                'create' => 'Thêm phiếu cọc',
                'edit'   => 'Sửa phiếu cọc',
            ],
        ],
        'reporting:dashboard' => [
            'module'     => self::MODULE_REPORTING,
            'label'      => 'Dashboard điều hành',
            'privileges' => [
                'index' => 'Xem dashboard',
            ],
        ],
        'reporting:report' => [
            'module'     => self::MODULE_REPORTING,
            'label'      => 'Báo cáo tổng hợp',
            'privileges' => [
                'index' => 'Xem báo cáo',
            ],
        ],
    ];

    /**
     * Suy ra resource từ tên class Controller.
     *
     * `Fleet\Controller\GeneratorController` ⇒ `fleet:generator`
     * `User\Controller\UserController`      ⇒ `user:user`
     *
     * Trả về null khi tên class không theo quy ước — khi đó ACL không quản lý màn hình đó.
     */
    public static function resourceFromController(?string $controllerClass): ?string
    {
        if ($controllerClass === null || $controllerClass === '') {
            return null;
        }

        // Bắt đúng dạng <Module>\Controller\<Ten>Controller — không khớp thì không đoán bừa.
        if (!preg_match('/^([A-Za-z0-9_]+)\\\\Controller\\\\([A-Za-z0-9_]+)Controller$/', $controllerClass, $m)) {
            return null;
        }

        return strtolower($m[1]) . ':' . strtolower($m[2]);
    }

    public static function hasResource(?string $resource): bool
    {
        return $resource !== null && isset(self::RESOURCES[$resource]);
    }

    public static function isPublicAction(?string $resource, ?string $privilege): bool
    {
        return $resource !== null
            && $privilege !== null
            && in_array($privilege, self::PUBLIC_ACTIONS[$resource] ?? [], true);
    }

    public static function isAuthenticatedAction(?string $resource, ?string $privilege): bool
    {
        return $resource !== null
            && $privilege !== null
            && in_array($privilege, self::AUTHENTICATED_ACTIONS[$resource] ?? [], true);
    }

    public static function resourceLabel(?string $resource): string
    {
        return self::RESOURCES[$resource]['label'] ?? '—';
    }

    public static function moduleOfResource(?string $resource): ?string
    {
        return self::RESOURCES[$resource]['module'] ?? null;
    }

    public static function moduleLabel(?string $module): string
    {
        return self::MODULE_LABELS[$module] ?? '—';
    }

    /** @return array<string, string> privilege => nhãn */
    public static function privilegesOf(?string $resource): array
    {
        return self::RESOURCES[$resource]['privileges'] ?? [];
    }

    public static function privilegeLabel(?string $resource, ?string $privilege): string
    {
        return self::RESOURCES[$resource]['privileges'][$privilege]
            ?? ($privilege === self::PRIVILEGE_ALL ? 'Toàn bộ thao tác' : '—');
    }

    /** Cặp resource/privilege có tồn tại trong danh mục không — chặn dữ liệu rác trước khi ghi DB. */
    public static function isValidPair(?string $resource, ?string $privilege): bool
    {
        if (!self::hasResource($resource)) {
            return false;
        }

        return $privilege === self::PRIVILEGE_ALL
            || isset(self::RESOURCES[$resource]['privileges'][$privilege]);
    }

    public static function isValidEffect(?string $effect): bool
    {
        return $effect !== null && isset(self::EFFECT_LABELS[$effect]);
    }

    /**
     * Danh mục quyền gom theo module — dùng để dựng màn hình phân quyền.
     *
     * @return array<string, array{label:string, resources:array<string, array{label:string, privileges:array<string,string>}>}>
     */
    public static function groupedByModule(): array
    {
        $grouped = [];
        foreach (self::RESOURCES as $resource => $meta) {
            $module = $meta['module'];
            $grouped[$module]['label'] ??= self::moduleLabel($module);
            $grouped[$module]['resources'][$resource] = [
                'label'      => $meta['label'],
                'privileges' => $meta['privileges'],
            ];
        }

        return $grouped;
    }
}
