<?php

declare(strict_types=1);

namespace Dispatch\Model\DispatchJob;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class DispatchJobMapper extends AppMapper
{
    public const string TABLE_NAME = 'dsp_jobs';

    public function searchDispatchJobs(DispatchJobModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            DispatchJobConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            DispatchJobConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new DispatchJobModel());
    }

    private function buildSearchSelect(DispatchJobModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['j' => DispatchJobMapper::TABLE_NAME]);

        foreach ([
            'j.jobType = ?'       => $criteria->getJobType(),
            'j.rentalOrderId = ?' => $criteria->getRentalOrderId(),
            'j.generatorId = ?'   => $criteria->getGeneratorId(),
            'j.vehicleId = ?'     => $criteria->getVehicleId(),
            'j.status = ?'        => $criteria->getStatus(),
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

    public function getDispatchJob(int $id): ?DispatchJobModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['j.id = ?' => $id]);
    }

    public function getDispatchJobByNo(string $jobNo, ?int $exceptId = null): ?DispatchJobModel
    {
        $jobNo = strtoupper(trim($jobNo));
        if ($jobNo === '') {
            return null;
        }

        return $this->fetchOne(['j.jobNo = ?' => $jobNo], $exceptId);
    }

    public function saveDispatchJob(DispatchJobModel $item): DispatchJobModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'jobNo'          => $item->getJobNo(),
            'jobType'        => $item->getJobType(),
            'rentalOrderId'  => $item->getRentalOrderId(),
            'generatorId'    => $item->getGeneratorId(),
            'newGeneratorId' => $item->getNewGeneratorId(),
            'siteId'         => $item->getSiteId(),
            'vehicleId'      => $item->getVehicleId(),
            'scheduledAt'    => $item->getScheduledAt(),
            'departedAt'     => $item->getDepartedAt(),
            'arrivedAt'      => $item->getArrivedAt(),
            'completedAt'    => $item->getCompletedAt(),
            'status'         => $item->getStatus(),
            'failReason'     => $item->getFailReason(),
            'feeBearer'      => $item->getFeeBearer(),
            'priority'       => $item->getPriority(),
            'note'           => $item->getNote(),
            'updatedAt'      => $now,
            'updatedBy'      => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(DispatchJobMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(DispatchJobMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsDispatchJob(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(DispatchJobMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteDispatchJob(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(DispatchJobMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?DispatchJobModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['j' => DispatchJobMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('j.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new DispatchJobModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
