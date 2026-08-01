<?php

declare(strict_types=1);

namespace Rental\Model\RentalOrder;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class RentalOrderMapper extends AppMapper
{
    public const string TABLE_NAME = 'rnt_rental_orders';

    public function searchRentalOrders(RentalOrderModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            RentalOrderConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            RentalOrderConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new RentalOrderModel());
    }

    private function buildSearchSelect(RentalOrderModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['o' => RentalOrderMapper::TABLE_NAME]);

        if ($criteria->getCustomerId() !== null) {
            $select->where(['o.customerId = ?' => $criteria->getCustomerId()]);
        }
        if ($criteria->getGeneratorId() !== null) {
            $select->where(['o.generatorId = ?' => $criteria->getGeneratorId()]);
        }
        if ($criteria->getStatus() !== null) {
            $select->where(['o.status = ?' => $criteria->getStatus()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('o.orderNo', $this->escapeLike($keyword) . '%');
        }

        return $select;
    }

    public function getRentalOrder(int $id): ?RentalOrderModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['o.id = ?' => $id]);
    }

    public function getRentalOrderByNo(string $orderNo, ?int $exceptId = null): ?RentalOrderModel
    {
        $orderNo = strtoupper(trim($orderNo));
        if ($orderNo === '') {
            return null;
        }

        return $this->fetchOne(['o.orderNo = ?' => $orderNo], $exceptId);
    }

    public function saveRentalOrder(RentalOrderModel $item): RentalOrderModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'orderNo'         => $item->getOrderNo(),
            'contractId'      => $item->getContractId(),
            'customerId'      => $item->getCustomerId(),
            'siteId'          => $item->getSiteId(),
            'generatorId'     => $item->getGeneratorId(),
            'startDate'       => $item->getStartDate(),
            'expectedEndDate' => $item->getExpectedEndDate(),
            'actualEndDate'   => $item->getActualEndDate(),
            'status'          => $item->getStatus(),
            'startHourMeter'  => $item->getStartHourMeter(),
            'endHourMeter'    => $item->getEndHourMeter(),
            'handoverAt'      => $item->getHandoverAt(),
            'recoveredAt'     => $item->getRecoveredAt(),
            'unitPrice'       => $item->getUnitPrice(),
            'durationTier'    => $item->getDurationTier(),
            'withOperator'    => (int)($item->getWithOperator() ?? false),
            'extendedTimes'   => $item->getExtendedTimes(),
            'settledAt'       => $item->getSettledAt(),
            'cancelledAt'     => $item->getCancelledAt(),
            'cancelledBy'     => $item->getCancelledBy(),
            'cancelReason'    => $item->getCancelReason(),
            'note'            => $item->getNote(),
            'updatedAt'       => $now,
            'updatedBy'       => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(RentalOrderMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(RentalOrderMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsRentalOrder(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(RentalOrderMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteRentalOrder(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(RentalOrderMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?RentalOrderModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['o' => RentalOrderMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('o.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new RentalOrderModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
