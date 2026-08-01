<?php

declare(strict_types=1);

namespace Rental\Model\RentalOrder;

use Application\Model\Constant\AppConstModel;

class RentalOrderConst extends AppConstModel
{
    public const string STATUS_NEW = 'moi_tao';
    public const string STATUS_WAITING_DELIVERY = 'cho_giao';
    public const string STATUS_RENTING = 'dang_thue';
    public const string STATUS_WAITING_RECOVERY = 'cho_thu_hoi';
    public const string STATUS_RECOVERED = 'da_thu_hoi';
    public const string STATUS_SETTLED = 'da_quyet_toan';
    public const string STATUS_CANCELLED = 'da_huy';

    public const array STATUS_LABELS = [
        self::STATUS_NEW              => 'Mới tạo',
        self::STATUS_WAITING_DELIVERY => 'Chờ giao',
        self::STATUS_RENTING          => 'Đang thuê',
        self::STATUS_WAITING_RECOVERY => 'Chờ thu hồi',
        self::STATUS_RECOVERED        => 'Đã thu hồi',
        self::STATUS_SETTLED          => 'Đã quyết toán',
        self::STATUS_CANCELLED        => 'Đã hủy',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_NEW              => [self::STATUS_WAITING_DELIVERY, self::STATUS_CANCELLED],
        self::STATUS_WAITING_DELIVERY => [self::STATUS_RENTING, self::STATUS_CANCELLED],
        self::STATUS_RENTING          => [self::STATUS_WAITING_RECOVERY],
        self::STATUS_WAITING_RECOVERY => [self::STATUS_RECOVERED],
        self::STATUS_RECOVERED        => [self::STATUS_SETTLED],
        self::STATUS_SETTLED          => [],
        self::STATUS_CANCELLED        => [],
    ];

    public const array SORT_MAP = [
        'orderNo'    => 'o.orderNo',
        'customerId' => 'o.customerId',
        'generatorId'=> 'o.generatorId',
        'status'     => 'o.status',
        'startDate'  => 'o.startDate',
        'endDate'    => 'o.expectedEndDate',
        'createdAt'  => 'o.createdAt',
    ];

    public const string SORT_DEFAULT = 'createdAt';

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
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

