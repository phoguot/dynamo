<?php

declare(strict_types=1);

namespace Platform\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Platform\Model\Notification\NotificationMapper;
use Platform\Model\Notification\NotificationModel;
use Platform\Model\PlatformConst;

class NotificationService extends AppServiceFactory
{
    private function mapper(): NotificationMapper
    {
        return $this->getContainerEntry(NotificationMapper::class);
    }

    public function searchInbox(array $criteria = []): Paginator
    {
        $userId = $this->positiveIntOrNull($criteria['userId'] ?? null) ?? $this->currentUserId();
        if ($userId === null) {
            throw new ValidationException(['userId' => 'Thiếu người nhận thông báo.']);
        }

        $item = (new NotificationModel())
            ->setUserId($userId)
            ->setChannel($this->nullIfBlank($criteria['channel'] ?? null))
            ->setObjectType($this->nullIfBlank($criteria['objectType'] ?? null))
            ->setObjectId($this->positiveIntOrNull($criteria['objectId'] ?? null));
        if (($criteria['readState'] ?? null) === 'unread') {
            $item->setReadAt('unread');
        }

        $paging = PaginatorUtil::fromFormData($criteria);
        $paging['sort'] = $criteria['sort'] ?? PlatformConst::NOTIFICATION_SORT_DEFAULT;
        $paging['dir'] = $criteria['dir'] ?? 'desc';

        return $this->mapper()->searchInbox($item, $paging);
    }

    public function countUnread(?int $userId = null): int
    {
        $userId ??= $this->currentUserId();

        return $userId !== null ? $this->mapper()->countUnread($userId) : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createNotification(array $data = []): NotificationModel
    {
        $userId = $this->positiveIntOrNull($data['userId'] ?? null);
        $channel = $this->boundedString($data['channel'] ?? PlatformConst::NOTIFICATION_CHANNEL_IN_APP, 16);
        $title = $this->boundedString($data['title'] ?? null, 200);
        $body = $this->boundedString($data['body'] ?? null, 1000);
        $linkUrl = $this->boundedString($data['linkUrl'] ?? null, 500);
        $objectType = $this->boundedString($data['objectType'] ?? null, 64);
        $objectId = $this->positiveIntOrNull($data['objectId'] ?? null);

        $errors = [];
        if ($userId === null) { $errors['userId'] = 'Thiếu người nhận thông báo.'; }
        if (!in_array($channel, PlatformConst::NOTIFICATION_CHANNELS, true)) { $errors['channel'] = 'Kênh thông báo không hợp lệ.'; }
        if ($title === null) { $errors['title'] = 'Tiêu đề thông báo không được để trống.'; }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $item = (new NotificationModel())
            ->setUserId($userId)
            ->setChannel($channel)
            ->setTitle($title)
            ->setBody($body)
            ->setLinkUrl($linkUrl)
            ->setObjectType($objectType)
            ->setObjectId($objectId);

        return $this->mapper()->insertNotification($item);
    }

    public function markRead(int $id, ?int $userId = null): bool
    {
        $userId ??= $this->currentUserId();
        if ($userId === null) {
            throw new ValidationException(['userId' => 'Thiếu người đọc thông báo.']);
        }

        return $this->mapper()->markRead($id, $userId);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;
        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function boundedString(mixed $value, int $maxLength): ?string
    {
        $value = $this->nullIfBlank($value);
        return $value !== null ? mb_substr($value, 0, $maxLength) : null;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
