<?php

declare(strict_types=1);

namespace Crm\Model\Site;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class SiteMapper extends AppMapper
{
    public const string TABLE_NAME = 'crm_sites';

    public function getSite(int $id): ?SiteModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => SiteMapper::TABLE_NAME]);
        $select->where(['s.id = ?' => $id]);
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new SiteModel())->exchangeArray((array)$row) : null;
    }

    public function getSiteByCode(int $customerId, string $code, ?int $exceptId = null): ?SiteModel
    {
        $code = strtoupper(trim($code));
        if ($customerId <= 0 || $code === '') {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => SiteMapper::TABLE_NAME]);
        $select->where([
            's.customerId = ?' => $customerId,
            's.code = ?'       => $code,
        ]);
        if ($exceptId !== null) {
            $select->where->notEqualTo('s.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new SiteModel())->exchangeArray((array)$row) : null;
    }

    public function saveSite(SiteModel $item): SiteModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'customerId'        => $item->getCustomerId(),
            'code'              => $item->getCode(),
            'name'              => $item->getName(),
            'address'           => $item->getAddress(),
            'latitude'          => $item->getLatitude(),
            'longitude'         => $item->getLongitude(),
            'contactName'       => $item->getContactName(),
            'contactPhone'      => $item->getContactPhone(),
            'installConditions' => $item->getInstallConditions(),
            'accessNote'        => $item->getAccessNote(),
            'status'            => $item->getStatus(),
            'updatedAt'         => $now,
            'updatedBy'         => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(SiteMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(SiteMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    /** @return SiteModel[] */
    public function fetchByCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['s' => SiteMapper::TABLE_NAME]);
        $select->where(['s.customerId = ?' => $customerId]);
        $select->order('s.name ASC');
        $select->limit(SiteMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new SiteModel())->exchangeArray((array)$row);
        }

        return $result;
    }
}
