<?php

declare(strict_types=1);

namespace Crm\Model\Contact;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class ContactMapper extends AppMapper
{
    public const string TABLE_NAME = 'crm_contacts';

    public function getContact(int $id): ?ContactModel
    {
        if ($id <= 0) {
            return null;
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['ct' => ContactMapper::TABLE_NAME]);
        $select->where(['ct.id = ?' => $id]);
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new ContactModel())->exchangeArray((array)$row) : null;
    }

    public function saveContact(ContactModel $item): ContactModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'customerId' => $item->getCustomerId(),
            'siteId'     => $item->getSiteId(),
            'fullName'   => $item->getFullName(),
            'position'   => $item->getPosition(),
            'phone'      => $item->getPhone(),
            'email'      => $item->getEmail(),
            'isPrimary'  => (int)($item->getIsPrimary() ?? false),
            'note'       => $item->getNote(),
            'updatedAt'  => $now,
            'updatedBy'  => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(ContactMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(ContactMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    /** @return ContactModel[] */
    public function fetchByCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['ct' => ContactMapper::TABLE_NAME]);
        $select->where(['ct.customerId = ?' => $customerId]);
        $select->order(['ct.isPrimary DESC', 'ct.fullName ASC']);
        $select->limit(ContactMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new ContactModel())->exchangeArray((array)$row);
        }

        return $result;
    }
}
