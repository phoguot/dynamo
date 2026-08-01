<?php

declare(strict_types=1);

namespace Maintenance\Model\MaintenanceJob;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class MaintenanceJobMapper extends AppMapper
{
    public const string TABLE_NAME = 'mnt_jobs';

    public function searchMaintenanceJobs(MaintenanceJobModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            MaintenanceJobConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            MaintenanceJobConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new MaintenanceJobModel());
    }

    private function buildSearchSelect(MaintenanceJobModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['j' => MaintenanceJobMapper::TABLE_NAME]);

        foreach ([
            'j.generatorId = ?' => $criteria->getGeneratorId(),
            'j.scheduleId = ?'  => $criteria->getScheduleId(),
            'j.jobType = ?'     => $criteria->getJobType(),
            'j.priority = ?'    => $criteria->getPriority(),
            'j.status = ?'      => $criteria->getStatus(),
            'j.assigneeId = ?'  => $criteria->getAssigneeId(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('j.jobNo', $this->escapeLike($keyword) . '%');
        }

        return $select;
    }

    public function getMaintenanceJob(int $id): ?MaintenanceJobModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['j.id = ?' => $id]);
    }

    public function getMaintenanceJobByNo(string $jobNo, ?int $exceptId = null): ?MaintenanceJobModel
    {
        $jobNo = strtoupper(trim($jobNo));
        if ($jobNo === '') {
            return null;
        }

        return $this->fetchOne(['j.jobNo = ?' => $jobNo], $exceptId);
    }

    public function getMaintenanceJobByIdempotencyKey(string $key, ?int $exceptId = null): ?MaintenanceJobModel
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        return $this->fetchOne(['j.idempotencyKey = ?' => $key], $exceptId);
    }

    public function saveMaintenanceJob(MaintenanceJobModel $item): MaintenanceJobModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'jobNo'            => $item->getJobNo(),
            'generatorId'      => $item->getGeneratorId(),
            'scheduleId'       => $item->getScheduleId(),
            'jobType'          => $item->getJobType(),
            'priority'         => $item->getPriority(),
            'status'           => $item->getStatus(),
            'triggerReason'    => $item->getTriggerReason(),
            'triggerHourMeter' => $item->getTriggerHourMeter(),
            'idempotencyKey'   => $item->getIdempotencyKey(),
            'scheduledDate'    => $item->getScheduledDate(),
            'startedAt'        => $item->getStartedAt(),
            'completedAt'      => $item->getCompletedAt(),
            'assigneeId'       => $item->getAssigneeId(),
            'laborCost'        => $item->getLaborCost(),
            'partsCost'        => $item->getPartsCost(),
            'totalCost'        => $item->getTotalCost(),
            'findings'         => $item->getFindings(),
            'cancelledAt'      => $item->getCancelledAt(),
            'cancelReason'     => $item->getCancelReason(),
            'updatedAt'        => $now,
            'updatedBy'        => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(MaintenanceJobMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(MaintenanceJobMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsMaintenanceJob(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(MaintenanceJobMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteMaintenanceJob(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(MaintenanceJobMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?MaintenanceJobModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['j' => MaintenanceJobMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('j.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new MaintenanceJobModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
