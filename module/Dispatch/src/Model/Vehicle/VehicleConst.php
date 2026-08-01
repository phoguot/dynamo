<?php

declare(strict_types=1);

namespace Dispatch\Model\Vehicle;

use Application\Model\Constant\AppConstModel;

class VehicleConst extends AppConstModel
{
    public const string TYPE_TRUCK = 'xe_tai';
    public const string TYPE_CRANE_TRUCK = 'xe_cau';
    public const string TYPE_PICKUP = 'xe_ban_tai';

    public const array TYPE_LABELS = [
        self::TYPE_TRUCK       => 'Xe tải',
        self::TYPE_CRANE_TRUCK => 'Xe cẩu',
        self::TYPE_PICKUP      => 'Xe bán tải',
    ];

    public const string STATUS_READY = 'san_sang';
    public const string STATUS_RUNNING = 'dang_chay';
    public const string STATUS_MAINTENANCE = 'bao_duong';
    public const string STATUS_STOPPED = 'ngung_khai_thac';

    public const array STATUS_LABELS = [
        self::STATUS_READY       => 'Sẵn sàng',
        self::STATUS_RUNNING     => 'Đang chạy',
        self::STATUS_MAINTENANCE => 'Bảo dưỡng',
        self::STATUS_STOPPED     => 'Ngừng khai thác',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_READY       => [self::STATUS_RUNNING, self::STATUS_MAINTENANCE, self::STATUS_STOPPED],
        self::STATUS_RUNNING     => [self::STATUS_READY, self::STATUS_MAINTENANCE],
        self::STATUS_MAINTENANCE => [self::STATUS_READY, self::STATUS_STOPPED],
        self::STATUS_STOPPED     => [],
    ];

    public const array SORT_MAP = [
        'code'        => 'v.code',
        'plateNumber' => 'v.plateNumber',
        'vehicleType' => 'v.vehicleType',
        'status'      => 'v.status',
        'createdAt'   => 'v.createdAt',
    ];

    public const string SORT_DEFAULT = 'code';

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? '-';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }

    public static function isValidType(?string $type): bool
    {
        return $type !== null && isset(self::TYPE_LABELS[$type]);
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
