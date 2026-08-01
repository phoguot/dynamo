<?php

declare(strict_types=1);

namespace Sales\Model\PriceListItem;

use Application\Model\Constant\AppConstModel;

class PriceListItemConst extends AppConstModel
{
    public const string TIER_DAY = 'ngay';
    public const string TIER_WEEK = 'tuan';
    public const string TIER_MONTH = 'thang';

    public const array DURATION_LABELS = [
        self::TIER_DAY   => 'Ngày',
        self::TIER_WEEK  => 'Tuần',
        self::TIER_MONTH => 'Tháng',
    ];

    public static function durationLabel(?string $tier): string
    {
        return self::DURATION_LABELS[$tier] ?? '-';
    }

    public static function isValidDurationTier(?string $tier): bool
    {
        return $tier !== null && isset(self::DURATION_LABELS[$tier]);
    }
}

