<?php

declare(strict_types=1);

namespace Platform\Model\OutboxEvent;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Platform\Model\PlatformConst;

class OutboxEventMapper extends AppMapper
{
    public const string TABLE_NAME = 'pfm_outbox_events';

    public function searchEvents(OutboxEventModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['e' => OutboxEventMapper::TABLE_NAME]);
        foreach ([
            'e.eventName = ?'   => $criteria->getEventName(),
            'e.aggregateId = ?' => $criteria->getAggregateId(),
            'e.status = ?'      => $criteria->getStatus(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $this->applySort($select, PlatformConst::OUTBOX_SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, PlatformConst::OUTBOX_SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new OutboxEventModel());
    }

    /**
     * @return list<OutboxEventModel>
     */
    public function fetchPendingEvents(int $limit = 50): array
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['e' => OutboxEventMapper::TABLE_NAME]);
        $select->where(['e.status = ?' => PlatformConst::OUTBOX_STATUS_PENDING]);
        $select->order('e.id ASC');
        $select->limit(max(1, min(500, $limit)));
        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();

        $events = [];
        foreach ($rows as $row) {
            $events[] = (new OutboxEventModel())->exchangeArray((array)$row);
        }

        return $events;
    }

    public function insertEvent(OutboxEventModel $item): OutboxEventModel
    {
        $dbSql = $this->getDbSql();
        $insert = $dbSql->insert(OutboxEventMapper::TABLE_NAME);
        $insert->values([
            'eventName'   => $item->getEventName(),
            'aggregateId' => $item->getAggregateId(),
            'payloadJson' => $item->getPayloadJson(),
            'status'      => $item->getStatus() ?? PlatformConst::OUTBOX_STATUS_PENDING,
            'attempts'    => $item->getAttempts() ?? 0,
            'lastError'   => $item->getLastError(),
            'publishedAt' => $item->getPublishedAt(),
            'createdAt'   => $item->getCreatedAt() ?? DateModel::getUtcNow(),
        ]);
        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }

    public function markPublished(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $dbSql = $this->getDbSql();
        $update = $dbSql->update(OutboxEventMapper::TABLE_NAME);
        $update->set([
            'status'      => PlatformConst::OUTBOX_STATUS_PUBLISHED,
            'publishedAt' => DateModel::getUtcNow(),
            'lastError'   => null,
        ]);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function markFailed(int $id, string $lastError): bool
    {
        if ($id <= 0) {
            return false;
        }
        $dbSql = $this->getDbSql();
        $update = $dbSql->update(OutboxEventMapper::TABLE_NAME);
        $update->set([
            'status'    => PlatformConst::OUTBOX_STATUS_FAILED,
            'attempts'  => new \Laminas\Db\Sql\Expression('attempts + 1'),
            'lastError' => mb_substr($lastError, 0, 1000),
        ]);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }
}
