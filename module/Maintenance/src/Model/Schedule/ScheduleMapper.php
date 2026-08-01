<?php

declare(strict_types=1);

namespace Maintenance\Model\Schedule;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class ScheduleMapper extends AppMapper
{
    public const string TABLE_NAME = 'mnt_schedules';

    public function searchSchedules(ScheduleModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            ScheduleConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            ScheduleConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new ScheduleModel());
    }

    private function buildSearchSelect(ScheduleModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['s' => ScheduleMapper::TABLE_NAME]);
        foreach ([
            's.generatorId = ?'  => $criteria->getGeneratorId(),
            's.scheduleType = ?' => $criteria->getScheduleType(),
            's.isActive = ?'     => $criteria->getIsActive(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }

        return $select;
    }

    public function getSchedule(int $id): ?ScheduleModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['s.id = ?' => $id]);
    }

    public function getScheduleByGeneratorAndType(int $generatorId, string $scheduleType, ?int $exceptId = null): ?ScheduleModel
    {
        if ($generatorId <= 0 || !ScheduleConst::isValidType($scheduleType)) {
            return null;
        }

        return $this->fetchOne([
            's.generatorId = ?'  => $generatorId,
            's.scheduleType = ?' => $scheduleType,
        ], $exceptId);
    }

    /**
     * @return list<ScheduleModel>
     */
    public function fetchDueByHourMeter(int $generatorId, float $hourMeter): array
    {
        if ($generatorId <= 0 || $hourMeter < 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => ScheduleMapper::TABLE_NAME]);
        $select->where([
            's.generatorId = ?' => $generatorId,
            's.isActive = ?'    => 1,
        ]);
        $select->where->in('s.scheduleType', [ScheduleConst::TYPE_HOUR, ScheduleConst::TYPE_BOTH]);
        $select->where->isNotNull('s.nextDueHour');
        $select->where->lessThanOrEqualTo('s.nextDueHour', $hourMeter);
        $select->order(['s.nextDueHour ASC', 's.id ASC']);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $items = [];
        foreach ($rows as $row) {
            $items[] = (new ScheduleModel())->exchangeArray((array)$row);
        }

        return $items;
    }

    public function updateAttrsSchedule(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(ScheduleMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function saveSchedule(ScheduleModel $item): ScheduleModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'generatorId'      => $item->getGeneratorId(),
            'scheduleType'     => $item->getScheduleType(),
            'intervalHours'    => $item->getIntervalHours(),
            'intervalDays'     => $item->getIntervalDays(),
            'lastServiceHour'  => $item->getLastServiceHour(),
            'lastServiceDate'  => $item->getLastServiceDate(),
            'nextDueHour'      => $item->getNextDueHour(),
            'nextDueDate'      => $item->getNextDueDate(),
            'isActive'         => $item->getIsActive(),
            'updatedAt'        => $now,
            'updatedBy'        => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(ScheduleMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(ScheduleMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?ScheduleModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => ScheduleMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('s.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new ScheduleModel())->exchangeArray((array)$row) : null;
    }
}
