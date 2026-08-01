<?php

declare(strict_types=1);

namespace Platform\Model;

use Application\Model\Constant\AppConstModel;

class PlatformConst extends AppConstModel
{
    public const string ATTACHMENT_KIND_HANDOVER = 'bien_ban';
    public const string ATTACHMENT_KIND_FIELD_PHOTO = 'anh_hien_truong';
    public const string ATTACHMENT_KIND_SIGNATURE = 'chu_ky';
    public const string ATTACHMENT_KIND_CONTRACT_FILE = 'file_hop_dong';
    public const string ATTACHMENT_KIND_OTHER = 'khac';

    public const array ATTACHMENT_KINDS = [
        PlatformConst::ATTACHMENT_KIND_HANDOVER,
        PlatformConst::ATTACHMENT_KIND_FIELD_PHOTO,
        PlatformConst::ATTACHMENT_KIND_SIGNATURE,
        PlatformConst::ATTACHMENT_KIND_CONTRACT_FILE,
        PlatformConst::ATTACHMENT_KIND_OTHER,
    ];

    public const array ATTACHMENT_SORT_MAP = [
        'id'           => 'a.id',
        'ownerType'    => 'a.ownerType',
        'kind'         => 'a.kind',
        'originalName' => 'a.originalName',
        'createdAt'    => 'a.createdAt',
    ];

    public const string ATTACHMENT_SORT_DEFAULT = 'id';

    public const string NOTIFICATION_CHANNEL_IN_APP = 'in_app';
    public const string NOTIFICATION_CHANNEL_EMAIL = 'email';
    public const string NOTIFICATION_CHANNEL_SMS = 'sms';

    public const array NOTIFICATION_CHANNELS = [
        PlatformConst::NOTIFICATION_CHANNEL_IN_APP,
        PlatformConst::NOTIFICATION_CHANNEL_EMAIL,
        PlatformConst::NOTIFICATION_CHANNEL_SMS,
    ];

    public const array NOTIFICATION_SORT_MAP = [
        'id'        => 'n.id',
        'readAt'    => 'n.readAt',
        'createdAt' => 'n.createdAt',
    ];

    public const string NOTIFICATION_SORT_DEFAULT = 'id';

    public const string SETTING_TYPE_STRING = 'string';
    public const string SETTING_TYPE_INT = 'int';
    public const string SETTING_TYPE_BOOL = 'bool';
    public const string SETTING_TYPE_JSON = 'json';

    public const array SETTING_TYPES = [
        PlatformConst::SETTING_TYPE_STRING,
        PlatformConst::SETTING_TYPE_INT,
        PlatformConst::SETTING_TYPE_BOOL,
        PlatformConst::SETTING_TYPE_JSON,
    ];

    public const array SETTING_SORT_MAP = [
        'configKey' => 's.configKey',
        'valueType' => 's.valueType',
        'updatedAt' => 's.updatedAt',
    ];

    public const string SETTING_SORT_DEFAULT = 'configKey';

    public const string OUTBOX_STATUS_PENDING = 'cho_phat';
    public const string OUTBOX_STATUS_PUBLISHED = 'da_phat';
    public const string OUTBOX_STATUS_FAILED = 'that_bai';

    public const array OUTBOX_STATUSES = [
        PlatformConst::OUTBOX_STATUS_PENDING,
        PlatformConst::OUTBOX_STATUS_PUBLISHED,
        PlatformConst::OUTBOX_STATUS_FAILED,
    ];

    public const array OUTBOX_SORT_MAP = [
        'id'        => 'e.id',
        'eventName' => 'e.eventName',
        'status'    => 'e.status',
        'createdAt' => 'e.createdAt',
    ];

    public const string OUTBOX_SORT_DEFAULT = 'id';

    public static function normalizeJson(mixed $payload): string
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '';
            }
            $payload = $decoded;
        }

        if (!is_array($payload) && !is_object($payload)) {
            return '';
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '';
    }

    public static function decodeJson(?string $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }
}
