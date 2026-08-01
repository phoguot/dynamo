<?php

declare(strict_types=1);

namespace Dispatch\Model\Assignment;

use Application\Model\Constant\AppConstModel;

class AssignmentConst extends AppConstModel
{
    public const string ROLE_TECHNICIAN = 'ky_thuat';
    public const string ROLE_DRIVER = 'tai_xe';
    public const string ROLE_HELPER = 'phu_viec';

    public const array ROLE_LABELS = [
        self::ROLE_TECHNICIAN => 'Kỹ thuật',
        self::ROLE_DRIVER     => 'Tài xế',
        self::ROLE_HELPER     => 'Phụ việc',
    ];

    public static function roleLabel(?string $role): string
    {
        return self::ROLE_LABELS[$role] ?? '-';
    }

    public static function isValidRole(?string $role): bool
    {
        return $role !== null && isset(self::ROLE_LABELS[$role]);
    }
}
