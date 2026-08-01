<?php

declare(strict_types=1);

namespace Maintenance\Model\MaintenanceJob;

use Application\Model\Constant\AppConstModel;

class MaintenanceJobConst extends AppConstModel
{
    public const string EVENT_STATUS_CHANGED = 'maintenance.job.status_changed';

    public const string TYPE_MAINTENANCE = 'bao_tri';
    public const string TYPE_REPAIR = 'sua_chua';
    public const string TYPE_POST_RENTAL_CHECK = 'kiem_tra_sau_thue';

    public const array TYPE_LABELS = [
        self::TYPE_MAINTENANCE       => 'Bảo trì',
        self::TYPE_REPAIR            => 'Sửa chữa',
        self::TYPE_POST_RENTAL_CHECK => 'Kiểm tra sau thuê',
    ];

    public const string STATUS_WAITING_SCHEDULE = 'cho_lich';
    public const string STATUS_SCHEDULED = 'da_len_lich';
    public const string STATUS_WORKING = 'dang_thuc_hien';
    public const string STATUS_COMPLETED = 'hoan_thanh';
    public const string STATUS_CANCELLED = 'da_huy';

    public const array STATUS_LABELS = [
        self::STATUS_WAITING_SCHEDULE => 'Chờ lịch',
        self::STATUS_SCHEDULED        => 'Đã lên lịch',
        self::STATUS_WORKING          => 'Đang thực hiện',
        self::STATUS_COMPLETED        => 'Hoàn thành',
        self::STATUS_CANCELLED        => 'Đã hủy',
    ];

    public const array STATUS_TRANSITIONS = [
        self::STATUS_WAITING_SCHEDULE => [self::STATUS_SCHEDULED, self::STATUS_CANCELLED],
        self::STATUS_SCHEDULED        => [self::STATUS_WORKING, self::STATUS_CANCELLED],
        self::STATUS_WORKING          => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED        => [],
        self::STATUS_CANCELLED        => [],
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

    public const array SORT_MAP = [
        'jobNo'         => 'j.jobNo',
        'generatorId'   => 'j.generatorId',
        'jobType'       => 'j.jobType',
        'priority'      => 'j.priority',
        'status'        => 'j.status',
        'scheduledDate' => 'j.scheduledDate',
        'assigneeId'    => 'j.assigneeId',
        'createdAt'     => 'j.createdAt',
    ];

    public const string SORT_DEFAULT = 'scheduledDate';

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
