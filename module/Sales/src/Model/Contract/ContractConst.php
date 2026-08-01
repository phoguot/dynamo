<?php

declare(strict_types=1);

namespace Sales\Model\Contract;

use Application\Model\Constant\AppConstModel;

class ContractConst extends AppConstModel
{
    public const string STATUS_DRAFT = 'nhap';
    public const string STATUS_SIGNED = 'da_ky';
    public const string STATUS_ACTIVE = 'dang_hieu_luc';
    public const string STATUS_CLOSED = 'da_dong';
    public const string STATUS_CANCELLED = 'da_huy';

    public const string BILLING_MONTH = 'thang';
    public const string BILLING_RENT_PERIOD = 'ky_thue';

    public const array STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Nháp',
        self::STATUS_SIGNED    => 'Đã ký',
        self::STATUS_ACTIVE    => 'Đang hiệu lực',
        self::STATUS_CLOSED    => 'Đã đóng',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const array BILLING_LABELS = [
        self::BILLING_MONTH       => 'Theo tháng',
        self::BILLING_RENT_PERIOD => 'Theo kỳ thuê',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_DRAFT     => [self::STATUS_SIGNED, self::STATUS_CANCELLED],
        self::STATUS_SIGNED    => [self::STATUS_ACTIVE, self::STATUS_CANCELLED],
        self::STATUS_ACTIVE    => [self::STATUS_CLOSED, self::STATUS_CANCELLED],
        self::STATUS_CLOSED    => [],
        self::STATUS_CANCELLED => [],
    ];

    public const array SORT_MAP = [
        'contractNo'   => 'c.contractNo',
        'customerId'   => 'c.customerId',
        'status'       => 'c.status',
        'effectiveFrom'=> 'c.effectiveFrom',
        'total'        => 'c.totalAmount',
        'createdAt'    => 'c.createdAt',
    ];

    public const string SORT_DEFAULT = 'createdAt';

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }

    public static function billingLabel(?string $cycle): string
    {
        return self::BILLING_LABELS[$cycle] ?? '-';
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS_LABELS[$status]);
    }

    public static function isValidBillingCycle(?string $cycle): bool
    {
        return $cycle !== null && isset(self::BILLING_LABELS[$cycle]);
    }

    public static function canTransit(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }
}

