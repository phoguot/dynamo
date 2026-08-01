<?php

declare(strict_types=1);

namespace Dispatch\Model\Assignment;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class AssignmentMapper extends AppMapper
{
    public const string TABLE_NAME = 'dsp_assignments';

    /** @return AssignmentModel[] */
    public function fetchByJob(int $jobId): array
    {
        if ($jobId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['a' => AssignmentMapper::TABLE_NAME]);
        $select->where(['a.jobId = ?' => $jobId]);
        $select->order('a.isLead DESC, a.id ASC');
        $select->limit(AssignmentMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new AssignmentModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    public function getAssignment(int $id): ?AssignmentModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['a' => AssignmentMapper::TABLE_NAME]);
        $select->where(['a.id = ?' => $id]);
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new AssignmentModel())->exchangeArray((array)$row) : null;
    }

    public function getDuplicate(int $jobId, int $userId, string $roleInJob, ?int $exceptId = null): ?AssignmentModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['a' => AssignmentMapper::TABLE_NAME]);
        $select->where([
            'a.jobId = ?'     => $jobId,
            'a.userId = ?'    => $userId,
            'a.roleInJob = ?' => $roleInJob,
        ]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('a.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new AssignmentModel())->exchangeArray((array)$row) : null;
    }

    public function saveAssignment(AssignmentModel $item): AssignmentModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'jobId'      => $item->getJobId(),
            'userId'     => $item->getUserId(),
            'roleInJob'  => $item->getRoleInJob(),
            'isLead'     => (int)($item->getIsLead() ?? false),
            'acceptedAt' => $item->getAcceptedAt(),
            'createdAt'  => $now,
            'createdBy'  => $item->getCreatedBy(),
        ];

        $insert = $dbSql->insert(AssignmentMapper::TABLE_NAME);
        $insert->values($data);
        $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
        $item->setId((int)$result->getGeneratedValue());

        return $item;
    }

    public function clearLeadByJob(int $jobId): bool
    {
        if ($jobId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(AssignmentMapper::TABLE_NAME);
        $update->set(['isLead' => 0]);
        $update->where(['jobId = ?' => $jobId]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteAssignment(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(AssignmentMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public function clearByJob(int $jobId): bool
    {
        if ($jobId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(AssignmentMapper::TABLE_NAME);
        $delete->where(['jobId = ?' => $jobId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }
}
