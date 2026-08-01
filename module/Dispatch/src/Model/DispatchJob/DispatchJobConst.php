<?php

declare(strict_types=1);

namespace Dispatch\Model\DispatchJob;

use Application\Model\Constant\AppConstModel;

class DispatchJobConst extends AppConstModel
{
    public const string TYPE_DELIVERY = 'giao';
    public const string TYPE_RECOVERY = 'thu_hoi';
    public const string TYPE_SWAP = 'doi_may';

    public const string EVENT_JOB_STARTED = 'dispatch.job.started';
    public const string EVENT_HANDOVER_COMPLETED = 'dispatch.handover.completed';
    public const string EVENT_RETURN_COMPLETED = 'dispatch.return.completed';
    public const string EVENT_SWAP_COMPLETED = 'dispatch.swap.completed';

    public const array TYPE_LABELS = [
        self::TYPE_DELIVERY => 'Giao máy',
        self::TYPE_RECOVERY => 'Thu hồi',
        self::TYPE_SWAP     => 'Đổi máy',
    ];

    public const string STATUS_NEW = 'moi_tao';
    public const string STATUS_SCHEDULED = 'da_len_lich';
    public const string STATUS_ON_ROUTE = 'dang_di';
    public const string STATUS_WORKING = 'dang_thuc_hien';
    public const string STATUS_COMPLETED = 'hoan_thanh';
    public const string STATUS_FAILED = 'that_bai';
    public const string STATUS_CANCELLED = 'da_huy';

    public const array STATUS_LABELS = [
        self::STATUS_NEW       => 'Mới tạo',
        self::STATUS_SCHEDULED => 'Đã lên lịch',
        self::STATUS_ON_ROUTE  => 'Đang đi',
        self::STATUS_WORKING   => 'Đang thực hiện',
        self::STATUS_COMPLETED => 'Hoàn thành',
        self::STATUS_FAILED    => 'Thất bại',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_NEW       => [self::STATUS_SCHEDULED, self::STATUS_CANCELLED],
        self::STATUS_SCHEDULED => [self::STATUS_ON_ROUTE, self::STATUS_CANCELLED],
        self::STATUS_ON_ROUTE  => [self::STATUS_WORKING, self::STATUS_FAILED],
        self::STATUS_WORKING   => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED    => [self::STATUS_SCHEDULED, self::STATUS_CANCELLED],
        self::STATUS_CANCELLED => [],
    ];

    public const string PRIORITY_LOW = 'thap';
    public const string PRIORITY_NORMAL = 'binh_thuong';
    public const string PRIORITY_HIGH = 'cao';
    public const string PRIORITY_URGENT = 'khan';

    public const array PRIORITY_LABELS = [
        self::PRIORITY_LOW    => 'Thấp',
        self::PRIORITY_NORMAL => 'Bình thường',
        self::PRIORITY_HIGH   => 'Cao',
        self::PRIORITY_URGENT => 'Khẩn',
    ];

    public const string FEE_COMPANY = 'cong_ty';
    public const string FEE_CUSTOMER = 'khach_hang';

    public const array FEE_BEARER_LABELS = [
        self::FEE_COMPANY  => 'Công ty',
        self::FEE_CUSTOMER => 'Khách hàng',
    ];

    public const array SORT_MAP = [
        'jobNo'       => 'j.jobNo',
        'jobType'     => 'j.jobType',
        'scheduledAt' => 'j.scheduledAt',
        'status'      => 'j.status',
        'priority'    => 'j.priority',
        'createdAt'   => 'j.createdAt',
    ];

    public const string SORT_DEFAULT = 'scheduledAt';

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? '-';
    }

    public static function statusLabel(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? '-';
    }

    public static function priorityLabel(?string $priority): string
    {
        return self::PRIORITY_LABELS[$priority] ?? '-';
    }

    public static function feeBearerLabel(?string $feeBearer): string
    {
        return self::FEE_BEARER_LABELS[$feeBearer] ?? '-';
    }

    public static function isValidType(?string $type): bool
    {
        return $type !== null && isset(self::TYPE_LABELS[$type]);
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
