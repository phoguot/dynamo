<?php

declare(strict_types=1);

namespace Rental\Model\GeneratorOccupancy;

use Application\Model\Constant\AppConstModel;

class GeneratorOccupancyConst extends AppConstModel
{
    public const string HOLD_RENT = 'thue';
    public const string HOLD_QUOTE = 'giu_cho';
    public const string HOLD_TRANSPORT_BUFFER = 'dem_van_chuyen';

    public const string SOURCE_RENTAL_ORDER = 'don_thue';
    public const string SOURCE_QUOTE = 'bao_gia';

    public const array HOLD_LABELS = [
        self::HOLD_RENT             => 'Thuê',
        self::HOLD_QUOTE            => 'Giữ chỗ',
        self::HOLD_TRANSPORT_BUFFER => 'Đệm vận chuyển',
    ];

    public static function holdLabel(?string $holdType): string
    {
        return self::HOLD_LABELS[$holdType] ?? '-';
    }

    public static function isValidHoldType(?string $holdType): bool
    {
        return $holdType !== null && isset(self::HOLD_LABELS[$holdType]);
    }
}

