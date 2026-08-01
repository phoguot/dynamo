<?php

declare(strict_types=1);

namespace Crm\Model\Customer;

use Application\Model\Constant\AppConstModel;

class CustomerConst extends AppConstModel
{
    public const string TYPE_DOANH_NGHIEP = 'doanh_nghiep';
    public const string TYPE_CA_NHAN = 'ca_nhan';

    /** @var array<string, string> */
    public const array TYPE_LABELS = [
        self::TYPE_DOANH_NGHIEP => 'Doanh nghiệp',
        self::TYPE_CA_NHAN      => 'Cá nhân',
    ];

    public const string STATUS_HOAT_DONG = 'hoat_dong';
    public const string STATUS_NGUNG = 'ngung_giao_dich';

    /** @var array<string, string> */
    public const array STATUS_LABELS = [
        self::STATUS_HOAT_DONG => 'Đang giao dịch',
        self::STATUS_NGUNG     => 'Ngừng giao dịch',
    ];

    /** @var array<string, string> */
    public const array SORT_MAP = [
        'code'      => 'c.code',
        'name'      => 'c.name',
        'type'      => 'c.customerType',
        'status'    => 'c.status',
        'createdAt' => 'c.createdAt',
    ];

    public const string SORT_DEFAULT = 'name';

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? '—';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '—';
    }

    public static function isValidType(?string $type): bool
    {
        return $type !== null && isset(self::TYPE_LABELS[$type]);
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS_LABELS[$status]);
    }
}
