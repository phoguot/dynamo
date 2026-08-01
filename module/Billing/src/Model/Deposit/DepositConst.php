<?php

declare(strict_types=1);

namespace Billing\Model\Deposit;

use Application\Model\Constant\AppConstModel;

class DepositConst extends AppConstModel
{
    public const string STATUS_HOLDING = 'dang_giu';
    public const string STATUS_PARTIALLY_REFUNDED = 'da_hoan_mot_phan';
    public const string STATUS_REFUNDED = 'da_hoan';
    public const string STATUS_OFFSET = 'da_bu_tru';

    public const array STATUS_LABELS = [
        self::STATUS_HOLDING            => 'Đang giữ',
        self::STATUS_PARTIALLY_REFUNDED => 'Đã hoàn một phần',
        self::STATUS_REFUNDED           => 'Đã hoàn',
        self::STATUS_OFFSET             => 'Đã bù trừ',
    ];

    public const array SORT_MAP = [
        'depositNo'    => 'd.depositNo',
        'customerId'   => 'd.customerId',
        'receivedDate' => 'd.receivedDate',
        'amount'       => 'd.amount',
        'status'       => 'd.status',
        'createdAt'    => 'd.createdAt',
    ];

    public const string SORT_DEFAULT = 'receivedDate';

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }
}
