<?php

declare(strict_types=1);

namespace Platform\Model\Notification;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Platform\Model\PlatformConst;

class NotificationMapper extends AppMapper
{
    public const string TABLE_NAME = 'pfm_notifications';

    public function searchInbox(NotificationModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['n' => NotificationMapper::TABLE_NAME]);
        $select->where(['n.userId = ?' => $criteria->getUserId()]);
        if ($criteria->getReadAt() === 'unread') {
            $select->where->isNull('n.readAt');
        }
        foreach ([
            'n.channel = ?'    => $criteria->getChannel(),
            'n.objectType = ?' => $criteria->getObjectType(),
            'n.objectId = ?'   => $criteria->getObjectId(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $this->applySort($select, PlatformConst::NOTIFICATION_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, PlatformConst::NOTIFICATION_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new NotificationModel());
    }

    public function countUnread(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['n' => NotificationMapper::TABLE_NAME]);
        $select->columns(['total' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        $select->where(['n.userId = ?' => $userId]);
        $select->where->isNull('n.readAt');
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return (int)($row['total'] ?? 0);
    }

    public function insertNotification(NotificationModel $item): NotificationModel
    {
        $dbSql = $this->getDbSql();
        $insert = $dbSql->insert(NotificationMapper::TABLE_NAME);
        $insert->values([
            'userId'     => $item->getUserId(),
            'channel'    => $item->getChannel() ?? PlatformConst::NOTIFICATION_CHANNEL_IN_APP,
            'title'      => $item->getTitle(),
            'body'       => $item->getBody(),
            'linkUrl'    => $item->getLinkUrl(),
            'objectType' => $item->getObjectType(),
            'objectId'   => $item->getObjectId(),
            'readAt'     => $item->getReadAt(),
            'createdAt'  => $item->getCreatedAt() ?? DateModel::getUtcNow(),
        ]);
        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }

    public function markRead(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }
        $dbSql = $this->getDbSql();
        $update = $dbSql->update(NotificationMapper::TABLE_NAME);
        $update->set(['readAt' => DateModel::getUtcNow()]);
        $update->where(['id = ?' => $id, 'userId = ?' => $userId]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }
}
