<?php

declare(strict_types=1);

namespace Crm\Model\Site;

use Application\Model\Constant\AppConstModel;

class SiteConst extends AppConstModel
{
    public const string STATUS_HOAT_DONG = 'hoat_dong';
    public const string STATUS_DA_DONG = 'da_dong';

    /** @var array<string, string> */
    public const array STATUS_LABELS = [
        self::STATUS_HOAT_DONG => 'Đang sử dụng',
        self::STATUS_DA_DONG   => 'Đã đóng',
    ];

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '—';
    }
}
