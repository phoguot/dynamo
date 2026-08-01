<?php

declare(strict_types=1);

namespace Rental\Model\GeneratorOccupancy;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Throwable;

class GeneratorOccupancyMapper extends AppMapper
{
    public const string TABLE_NAME = 'rnt_generator_occupancy';

    public function hasOverlap(int $generatorId, string $startDate, string $endDate, ?int $exceptOrderId = null): bool
    {
        if ($generatorId <= 0 || $startDate === '' || $endDate === '') {
            return false;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['o' => GeneratorOccupancyMapper::TABLE_NAME]);
        $select->columns(['generatorId']);
        $select->where([
            'o.generatorId = ?'    => $generatorId,
            'o.occupiedDate >= ?'  => $startDate,
            'o.occupiedDate < ?'   => $endDate,
        ]);
        if ($exceptOrderId !== null) {
            $select->where->notEqualTo('o.rentalOrderId', $exceptOrderId);
        }
        $select->limit(1);

        return (bool)$dbSql->prepareStatementForSqlObject($select)->execute()->current();
    }

    public function findConflict(int $generatorId, string $startDate, string $endDate, ?int $exceptOrderId = null): ?GeneratorOccupancyModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['o' => GeneratorOccupancyMapper::TABLE_NAME]);
        $select->where([
            'o.generatorId = ?'    => $generatorId,
            'o.occupiedDate >= ?'  => $startDate,
            'o.occupiedDate < ?'   => $endDate,
        ]);
        if ($exceptOrderId !== null) {
            $select->where->notEqualTo('o.rentalOrderId', $exceptOrderId);
        }
        $select->order('o.occupiedDate ASC');
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new GeneratorOccupancyModel())->exchangeArray((array)$row) : null;
    }

    /** @return GeneratorOccupancyModel[] */
    public function fetchByOrder(int $rentalOrderId): array
    {
        if ($rentalOrderId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['o' => GeneratorOccupancyMapper::TABLE_NAME]);
        $select->where(['o.rentalOrderId = ?' => $rentalOrderId]);
        $select->order('o.occupiedDate ASC');
        $select->limit(GeneratorOccupancyMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new GeneratorOccupancyModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    /**
     * @param string[] $dates
     */
    public function insertOccupancyDays(
        int $generatorId,
        int $rentalOrderId,
        array $dates,
        string $holdType = GeneratorOccupancyConst::HOLD_RENT,
        ?string $sourceType = GeneratorOccupancyConst::SOURCE_RENTAL_ORDER,
        ?int $sourceId = null,
        ?string $expiresAt = null,
        ?int $actorId = null
    ): void {
        if ($generatorId <= 0 || $rentalOrderId <= 0 || $dates === []) {
            return;
        }

        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        foreach ($dates as $date) {
            $insert = $dbSql->insert(GeneratorOccupancyMapper::TABLE_NAME);
            $insert->values([
                'generatorId'    => $generatorId,
                'occupiedDate'   => $date,
                'rentalOrderId'  => $rentalOrderId,
                'holdType'       => $holdType,
                'sourceType'     => $sourceType,
                'sourceId'       => $sourceId,
                'expiresAt'      => $expiresAt,
                'createdAt'      => $now,
                'createdBy'      => $actorId,
            ]);
            $dbSql->prepareStatementForSqlObject($insert)->execute();
        }
    }

    public function clearByOrder(int $rentalOrderId): bool
    {
        if ($rentalOrderId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(GeneratorOccupancyMapper::TABLE_NAME);
        $delete->where(['rentalOrderId = ?' => $rentalOrderId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public function clearByOrderFromDate(int $rentalOrderId, string $fromDate): bool
    {
        if ($rentalOrderId <= 0 || $fromDate === '') {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(GeneratorOccupancyMapper::TABLE_NAME);
        $delete->where([
            'rentalOrderId = ?' => $rentalOrderId,
            'occupiedDate >= ?' => $fromDate,
        ]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public static function isDuplicateKey(Throwable $e): bool
    {
        return str_contains($e->getMessage(), '1062')
            || str_contains(strtolower($e->getMessage()), 'duplicate');
    }
}

