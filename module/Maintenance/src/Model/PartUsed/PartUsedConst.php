<?php

declare(strict_types=1);

namespace Maintenance\Model\PartUsed;

use Application\Model\Constant\AppConstModel;

class PartUsedConst extends AppConstModel
{
    public const string UNIT_ITEM = 'cai';
    public const string UNIT_SET = 'bo';
    public const string UNIT_LITER = 'lit';
    public const string UNIT_KG = 'kg';

    public const array UNIT_LABELS = [
        self::UNIT_ITEM  => 'Cái',
        self::UNIT_SET   => 'Bộ',
        self::UNIT_LITER => 'Lít',
        self::UNIT_KG    => 'Kg',
    ];

    public static function unitLabel(?string $unit): string
    {
        return self::UNIT_LABELS[$unit] ?? '-';
    }
}
