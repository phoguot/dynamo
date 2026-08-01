<?php

declare(strict_types=1);

namespace Billing\Model\Payment;

use Application\Model\Constant\AppConstModel;

class PaymentConst extends AppConstModel
{
    public const string METHOD_CASH = 'tien_mat';
    public const string METHOD_TRANSFER = 'chuyen_khoan';
    public const string METHOD_CARD = 'the';
    public const string METHOD_DEPOSIT_OFFSET = 'bu_tru_coc';

    public const array METHOD_LABELS = [
        self::METHOD_CASH           => 'Tiền mặt',
        self::METHOD_TRANSFER       => 'Chuyển khoản',
        self::METHOD_CARD           => 'Thẻ',
        self::METHOD_DEPOSIT_OFFSET => 'Bù trừ cọc',
    ];

    public const string STATUS_RECORDED = 'da_ghi_nhan';
    public const string STATUS_CANCELLED = 'da_huy';

    public const array STATUS_LABELS = [
        self::STATUS_RECORDED  => 'Đã ghi nhận',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const array SORT_MAP = [
        'paymentNo'   => 'p.paymentNo',
        'customerId'  => 'p.customerId',
        'invoiceId'   => 'p.invoiceId',
        'paymentDate' => 'p.paymentDate',
        'amount'      => 'p.amount',
        'status'      => 'p.status',
        'createdAt'   => 'p.createdAt',
    ];

    public const string SORT_DEFAULT = 'paymentDate';

    public static function methodLabel(?string $method): string
    {
        return self::METHOD_LABELS[$method] ?? '-';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }
}
