<?php

declare(strict_types=1);

namespace Sales\Model\Quote;

use Application\Model\Constant\AppConstModel;

class QuoteConst extends AppConstModel
{
    public const string STATUS_DRAFT = 'nhap';
    public const string STATUS_PENDING = 'cho_duyet';
    public const string STATUS_APPROVED = 'da_duyet';
    public const string STATUS_REJECTED = 'tu_choi';
    public const string STATUS_EXPIRED = 'het_hieu_luc';

    public const array STATUS_LABELS = [
        self::STATUS_DRAFT    => 'Nháp',
        self::STATUS_PENDING  => 'Chờ duyệt',
        self::STATUS_APPROVED => 'Đã duyệt',
        self::STATUS_REJECTED => 'Từ chối',
        self::STATUS_EXPIRED  => 'Hết hiệu lực',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_DRAFT    => [self::STATUS_PENDING, self::STATUS_EXPIRED],
        self::STATUS_PENDING  => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_EXPIRED],
        self::STATUS_APPROVED => [self::STATUS_EXPIRED],
        self::STATUS_REJECTED => [],
        self::STATUS_EXPIRED  => [],
    ];

    public const array SORT_MAP = [
        'quoteNo'    => 'q.quoteNo',
        'customerId' => 'q.customerId',
        'status'     => 'q.status',
        'rentFrom'   => 'q.rentFrom',
        'validUntil' => 'q.validUntil',
        'total'      => 'q.totalAmount',
        'createdAt'  => 'q.createdAt',
    ];

    public const string SORT_DEFAULT = 'createdAt';

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }

    public static function isValidStatus(?string $status): bool
    {
        return $status !== null && isset(self::STATUS_LABELS[$status]);
    }

    public static function canTransit(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }
}

