<?php

declare(strict_types=1);

namespace Sales\Model\PriceListItem;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class PriceListItemMapper extends AppMapper
{
    public const string TABLE_NAME = 'sal_price_list_items';

    /** @return PriceListItemModel[] */
    public function fetchByPriceList(int $priceListId): array
    {
        if ($priceListId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => PriceListItemMapper::TABLE_NAME]);
        $select->where(['i.priceListId = ?' => $priceListId]);
        $select->order(['i.capacityFrom ASC', 'i.durationTier ASC', 'i.minDays ASC']);
        $select->limit(PriceListItemMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new PriceListItemModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    public function resolvePrice(int $priceListId, int $capacityKva, string $durationTier, int $rentDays): ?PriceListItemModel
    {
        if ($priceListId <= 0 || $capacityKva <= 0 || $rentDays <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => PriceListItemMapper::TABLE_NAME]);
        $select->where([
            'i.priceListId = ?'    => $priceListId,
            'i.durationTier = ?'   => $durationTier,
            'i.capacityFrom <= ?'  => $capacityKva,
            'i.capacityTo >= ?'    => $capacityKva,
            'i.minDays <= ?'       => $rentDays,
        ]);
        $select->order('i.minDays DESC');
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new PriceListItemModel())->exchangeArray((array)$row) : null;
    }

    public function getPriceListItem(int $id): ?PriceListItemModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => PriceListItemMapper::TABLE_NAME]);
        $select->where(['i.id = ?' => $id]);
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new PriceListItemModel())->exchangeArray((array)$row) : null;
    }

    public function getDuplicate(PriceListItemModel $item, ?int $exceptId = null): ?PriceListItemModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => PriceListItemMapper::TABLE_NAME]);
        $select->where([
            'i.priceListId = ?'   => $item->getPriceListId(),
            'i.capacityFrom = ?'  => $item->getCapacityFrom(),
            'i.capacityTo = ?'    => $item->getCapacityTo(),
            'i.durationTier = ?'  => $item->getDurationTier(),
            'i.minDays = ?'       => $item->getMinDays(),
        ]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('i.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new PriceListItemModel())->exchangeArray((array)$row) : null;
    }

    public function savePriceListItem(PriceListItemModel $item): PriceListItemModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'priceListId'   => $item->getPriceListId(),
            'capacityFrom'  => $item->getCapacityFrom(),
            'capacityTo'    => $item->getCapacityTo(),
            'durationTier'  => $item->getDurationTier(),
            'minDays'       => $item->getMinDays(),
            'unitPrice'     => $item->getUnitPrice(),
            'dailyRate'     => $item->getDailyRate(),
            'deliveryFee'   => $item->getDeliveryFee(),
            'installFee'    => $item->getInstallFee(),
            'depositAmount' => $item->getDepositAmount(),
            'updatedAt'     => $now,
            'updatedBy'     => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(PriceListItemMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(PriceListItemMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }
}
