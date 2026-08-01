<?php

declare(strict_types=1);

namespace Maintenance\Model\Schedule;

use Application\Model\Constant\AppConstModel;

class ScheduleConst extends AppConstModel
{
    public const string TYPE_HOUR = 'gio_may';
    public const string TYPE_DAY = 'ngay';
    public const string TYPE_BOTH = 'ca_hai';

    public const array TYPE_LABELS = [
        self::TYPE_HOUR => 'Theo giờ máy',
        self::TYPE_DAY  => 'Theo ngày',
        self::TYPE_BOTH => 'Giờ máy hoặc ngày',
    ];

    public const array SORT_MAP = [
        'generatorId'  => 's.generatorId',
        'scheduleType' => 's.scheduleType',
        'nextDueHour'  => 's.nextDueHour',
        'nextDueDate'  => 's.nextDueDate',
        'isActive'     => 's.isActive',
        'createdAt'    => 's.createdAt',
    ];

    public const string SORT_DEFAULT = 'generatorId';

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? '-';
    }

    public static function isValidType(?string $type): bool
    {
        return $type !== null && isset(self::TYPE_LABELS[$type]);
    }
}
