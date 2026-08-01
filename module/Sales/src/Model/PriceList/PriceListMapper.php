<?php

declare(strict_types=1);

namespace Sales\Model\PriceList;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class PriceListMapper extends AppMapper
{
    public const string TABLE_NAME = 'sal_price_lists';

    public function searchPriceLists(PriceListModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            PriceListConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            PriceListConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new PriceListModel());
    }

    private function buildSearchSelect(PriceListModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['p' => PriceListMapper::TABLE_NAME]);

        if ($criteria->getIsActive() !== null) {
            $select->where(['p.isActive = ?' => (int)$criteria->getIsActive()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $prefix = $this->escapeLike($keyword) . '%';
            $select->where->nest()
                ->like('p.code', $prefix)
                ->or->like('p.name', $prefix)
                ->unnest();
        }

        return $select;
    }

    public function getPriceList(int $id): ?PriceListModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['p.id = ?' => $id]);
    }

    public function getPriceListByCode(string $code, ?int $exceptId = null): ?PriceListModel
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return $this->fetchOne(['p.code = ?' => $code], $exceptId);
    }

    public function savePriceList(PriceListModel $item): PriceListModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'code'      => $item->getCode(),
            'name'      => $item->getName(),
            'validFrom' => $item->getValidFrom(),
            'validTo'   => $item->getValidTo(),
            'isActive'  => (int)($item->getIsActive() ?? true),
            'note'      => $item->getNote(),
            'updatedAt' => $now,
            'updatedBy' => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(PriceListMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(PriceListMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsPriceList(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(PriceListMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?PriceListModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PriceListMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('p.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new PriceListModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

