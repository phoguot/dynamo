<?php

declare(strict_types=1);

namespace Fleet\Model\Generator;

use Application\Model\Constant\AppConstModel;

/**
 * Enum trạng thái và hằng số của máy phát điện.
 *
 * M02 fleet là **chủ sở hữu duy nhất** của trạng thái máy: chỉ service của module này
 * được đổi trạng thái. Module khác muốn máy đổi trạng thái thì phát event, fleet nghe
 * và tự quyết — xem .claude/rules/module-boundaries.md.
 *
 * Giá trị trạng thái lưu VARCHAR(32), không dùng kiểu ENUM của MySQL.
 */
class GeneratorConst extends AppConstModel
{
    public const string EVENT_STATUS_CHANGED = 'fleet.generator.status_changed';
    public const string EVENT_HOUR_METER_UPDATED = 'fleet.generator.hour_meter_updated';

    // --- Trạng thái máy (theo design/04-data-model.md, giá trị chốt ở contracts/enums.md) ---
    /** Ở kho, chưa bị đơn thuê nào giữ */
    public const string STATUS_SAN_SANG        = 'san_sang';
    /** Đơn thuê đã xác nhận, máy được giữ chỗ, chưa rời kho */
    public const string STATUS_DANG_GIU_CHO    = 'dang_giu_cho';
    /** Đang trên xe — đi giao hoặc đang thu hồi về */
    public const string STATUS_DANG_VAN_CHUYEN = 'dang_van_chuyen';
    /** Đang vận hành tại công trình khách hàng */
    public const string STATUS_DANG_THUE       = 'dang_thue';
    /** Bảo trì theo kế hoạch */
    public const string STATUS_DANG_BAO_TRI    = 'dang_bao_tri';
    /** Sửa chữa do hỏng đột xuất */
    public const string STATUS_DANG_SUA_CHUA   = 'dang_sua_chua';
    /** Thanh lý/bán — trạng thái cuối của vòng đời vận hành */
    public const string STATUS_NGUNG           = 'ngung_khai_thac';

    /** @var array<string, string> mã trạng thái => nhãn hiển thị */
    public const array STATUS_LABELS = [
        self::STATUS_SAN_SANG        => 'Sẵn sàng',
        self::STATUS_DANG_GIU_CHO    => 'Đang giữ chỗ',
        self::STATUS_DANG_VAN_CHUYEN => 'Đang vận chuyển',
        self::STATUS_DANG_THUE       => 'Đang thuê',
        self::STATUS_DANG_BAO_TRI    => 'Đang bảo trì',
        self::STATUS_DANG_SUA_CHUA   => 'Đang sửa chữa',
        self::STATUS_NGUNG           => 'Ngừng khai thác',
    ];

    /**
     * State machine trạng thái máy — danh sách chuyển tiếp HỢP LỆ duy nhất.
     * Mọi thay đổi trạng thái phải đi qua GeneratorService::changeStatus().
     *
     * @var array<string, string[]>
     */
    public const array STATUS_TRANSITIONS = [
        self::STATUS_SAN_SANG        => [
            self::STATUS_DANG_GIU_CHO, self::STATUS_DANG_BAO_TRI, self::STATUS_DANG_SUA_CHUA, self::STATUS_NGUNG,
        ],
        // Hủy đơn thuê thì máy quay lại kho, không đi tiếp.
        self::STATUS_DANG_GIU_CHO    => [
            self::STATUS_DANG_VAN_CHUYEN, self::STATUS_SAN_SANG, self::STATUS_DANG_SUA_CHUA,
        ],
        // Đang trên xe: giao xong → dang_thue; thu hồi xong → san_sang.
        self::STATUS_DANG_VAN_CHUYEN => [
            self::STATUS_DANG_THUE, self::STATUS_SAN_SANG, self::STATUS_DANG_SUA_CHUA,
        ],
        // Máy ở công trình muốn ngừng khai thác thì phải thu hồi trước — không có lối tắt.
        self::STATUS_DANG_THUE       => [
            self::STATUS_DANG_VAN_CHUYEN, self::STATUS_DANG_SUA_CHUA,
        ],
        self::STATUS_DANG_BAO_TRI    => [
            self::STATUS_SAN_SANG, self::STATUS_DANG_SUA_CHUA, self::STATUS_NGUNG,
        ],
        self::STATUS_DANG_SUA_CHUA   => [
            self::STATUS_SAN_SANG, self::STATUS_DANG_BAO_TRI, self::STATUS_NGUNG,
        ],
        self::STATUS_NGUNG           => [],
    ];

    /** Trạng thái mà máy còn có thể nhận đơn thuê mới. */
    public const array STATUS_KHA_DUNG = [self::STATUS_SAN_SANG];

    // --- Loại nhiên liệu ---
    public const string FUEL_DIESEL = 'diesel';
    public const string FUEL_XANG   = 'xang';
    public const string FUEL_GAS    = 'gas';

    public const array FUEL_LABELS = [
        self::FUEL_DIESEL => 'Dầu diesel',
        self::FUEL_XANG   => 'Xăng',
        self::FUEL_GAS    => 'Gas',
    ];

    /**
     * Cột sắp xếp cho phép trên trang danh sách: key client gửi => tên cột thật.
     * Whitelist bắt buộc — cấm nội suy tên cột từ tham số (.claude/rules/security.md).
     *
     * @var array<string, string>
     */
    public const array SORT_MAP = [
        'code'      => 'g.code',
        'name'      => 'g.name',
        'capacity'  => 'g.capacityKva',
        'status'    => 'g.status',
        'hourMeter' => 'g.hourMeter',
        'createdAt' => 'g.createdAt',
    ];

    public const string SORT_DEFAULT = 'code';

    /**
     * Thông số kỹ thuật linh hoạt lưu ở cột JSON `extraContent`.
     * Key không nằm trong danh sách này bị bỏ qua khi ghi.
     *
     * @var array<string, string>
     */
    public static array $allowedExtraFields = [
        'dienAp'        => 'string', // Điện áp, vd 380V/3 pha
        'tanSo'         => 'int',    // Hz
        'mucTieuHao'    => 'float',  // lít/giờ ở 75% tải
        'dungTichBinh'  => 'float',  // lít
        'kichThuoc'     => 'string', // DxRxC (mm)
        'trongLuong'    => 'int',    // kg
        'namSanXuat'    => 'int',
        'voChongOn'     => 'bool',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '—';
    }

    public static function fuelLabel(?string $fuelType): string
    {
        return self::FUEL_LABELS[$fuelType] ?? '—';
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS_LABELS[$status]);
    }

    /** Có được chuyển từ $from sang $to không. */
    public static function canTransit(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }
}
