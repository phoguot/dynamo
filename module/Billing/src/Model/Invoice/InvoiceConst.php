<?php

declare(strict_types=1);

namespace Billing\Model\Invoice;

use Application\Model\Constant\AppConstModel;

class InvoiceConst extends AppConstModel
{
    public const string STATUS_DRAFT = 'nhap';
    public const string STATUS_WAITING_APPROVAL = 'cho_duyet';
    public const string STATUS_ISSUED = 'da_phat_hanh';
    public const string STATUS_PARTIALLY_PAID = 'da_thanh_toan_mot_phan';
    public const string STATUS_PAID = 'da_thanh_toan';
    public const string STATUS_OVERDUE = 'qua_han';
    public const string STATUS_CANCELLED = 'da_huy';

    public const array STATUS_LABELS = [
        self::STATUS_DRAFT            => 'Nháp',
        self::STATUS_WAITING_APPROVAL => 'Chờ duyệt',
        self::STATUS_ISSUED           => 'Đã phát hành',
        self::STATUS_PARTIALLY_PAID   => 'Đã thanh toán một phần',
        self::STATUS_PAID             => 'Đã thanh toán',
        self::STATUS_OVERDUE          => 'Quá hạn',
        self::STATUS_CANCELLED        => 'Đã hủy',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_DRAFT            => [self::STATUS_WAITING_APPROVAL, self::STATUS_CANCELLED],
        self::STATUS_WAITING_APPROVAL => [self::STATUS_ISSUED, self::STATUS_CANCELLED],
        self::STATUS_ISSUED           => [self::STATUS_PARTIALLY_PAID, self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_CANCELLED],
        self::STATUS_PARTIALLY_PAID   => [self::STATUS_PAID, self::STATUS_OVERDUE, self::STATUS_CANCELLED],
        self::STATUS_PAID             => [],
        self::STATUS_OVERDUE          => [self::STATUS_PARTIALLY_PAID, self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_CANCELLED        => [],
    ];

    public const array SORT_MAP = [
        'invoiceNo'  => 'i.invoiceNo',
        'customerId' => 'i.customerId',
        'issueDate'  => 'i.issueDate',
        'dueDate'    => 'i.dueDate',
        'status'     => 'i.status',
        'totalAmount' => 'i.totalAmount',
        'createdAt'  => 'i.createdAt',
    ];

    public const string SORT_DEFAULT = 'createdAt';

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }

    public static function canTransit(string $from, string $to): bool
    {
        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
    }
}
