<?php

declare(strict_types=1);

namespace Billing\Model\InvoiceLine;

use Application\Model\Constant\AppConstModel;

class InvoiceLineConst extends AppConstModel
{
    public const string TYPE_RENT = 'tien_thue';
    public const string TYPE_DELIVERY = 'van_chuyen';
    public const string TYPE_INSTALL = 'lap_dat';
    public const string TYPE_FUEL = 'nhien_lieu';
    public const string TYPE_OVERTIME = 'qua_gio';
    public const string TYPE_COMPENSATION = 'boi_thuong';
    public const string TYPE_OTHER = 'khac';

    public const array TYPE_LABELS = [
        self::TYPE_RENT         => 'Tiền thuê',
        self::TYPE_DELIVERY     => 'Vận chuyển',
        self::TYPE_INSTALL      => 'Lắp đặt',
        self::TYPE_FUEL         => 'Nhiên liệu',
        self::TYPE_OVERTIME     => 'Quá giờ',
        self::TYPE_COMPENSATION => 'Bồi thường',
        self::TYPE_OTHER        => 'Khác',
    ];

    public const string UNIT_DAY = 'ngay';
    public const string UNIT_MONTH = 'thang';
    public const string UNIT_LITER = 'lit';
    public const string UNIT_HOUR = 'gio';
    public const string UNIT_TIME = 'lan';

    public const array UNIT_LABELS = [
        self::UNIT_DAY   => 'Ngày',
        self::UNIT_MONTH => 'Tháng',
        self::UNIT_LITER => 'Lít',
        self::UNIT_HOUR  => 'Giờ',
        self::UNIT_TIME  => 'Lần',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? '-';
    }

    public static function unitLabel(?string $unit): string
    {
        return self::UNIT_LABELS[$unit] ?? '-';
    }
}
