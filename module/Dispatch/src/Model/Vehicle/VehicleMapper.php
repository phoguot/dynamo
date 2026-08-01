<?php

declare(strict_types=1);

namespace Dispatch\Model\Vehicle;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class VehicleMapper extends AppMapper
{
    public const string TABLE_NAME = 'dsp_vehicles';

    public function searchVehicles(VehicleModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            VehicleConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            VehicleConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new VehicleModel());
    }

    private function buildSearchSelect(VehicleModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['v' => VehicleMapper::TABLE_NAME]);

        if ($criteria->getVehicleType() !== null) {
            $select->where(['v.vehicleType = ?' => $criteria->getVehicleType()]);
        }
        if ($criteria->getStatus() !== null) {
            $select->where(['v.status = ?' => $criteria->getStatus()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->nest()
                ->like('v.code', $this->escapeLike($keyword) . '%')
                ->or
                ->like('v.plateNumber', $this->escapeLike($keyword) . '%')
                ->unnest();
        }

        return $select;
    }

    public function getVehicle(int $id): ?VehicleModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['v.id = ?' => $id]);
    }

    public function getVehicleByCode(string $code, ?int $exceptId = null): ?VehicleModel
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return $this->fetchOne(['v.code = ?' => $code], $exceptId);
    }

    public function getVehicleByPlate(string $plateNumber, ?int $exceptId = null): ?VehicleModel
    {
        $plateNumber = strtoupper(trim($plateNumber));
        if ($plateNumber === '') {
            return null;
        }

        return $this->fetchOne(['v.plateNumber = ?' => $plateNumber], $exceptId);
    }

    public function saveVehicle(VehicleModel $item): VehicleModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'code'        => $item->getCode(),
            'plateNumber' => $item->getPlateNumber(),
            'vehicleType' => $item->getVehicleType(),
            'capacityKg'  => $item->getCapacityKg(),
            'driverId'    => $item->getDriverId(),
            'status'      => $item->getStatus(),
            'note'        => $item->getNote(),
            'updatedAt'   => $now,
            'updatedBy'   => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(VehicleMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(VehicleMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsVehicle(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(VehicleMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?VehicleModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['v' => VehicleMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('v.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new VehicleModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
