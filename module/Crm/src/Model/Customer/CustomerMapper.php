<?php

declare(strict_types=1);

namespace Crm\Model\Customer;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class CustomerMapper extends AppMapper
{
    public const string TABLE_NAME = 'crm_customers';

    /**
     * @param array{page?:int, pageSize?:int, sort?:string, dir?:string} $paging
     */
    public function searchCustomers(CustomerModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            CustomerConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            CustomerConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new CustomerModel());
    }

    private function buildSearchSelect(CustomerModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['c' => CustomerMapper::TABLE_NAME]);

        if ($criteria->getCustomerType() !== null) {
            $select->where(['c.customerType = ?' => $criteria->getCustomerType()]);
        }

        if ($criteria->getStatus() !== null) {
            $select->where(['c.status = ?' => $criteria->getStatus()]);
        }

        if ($criteria->getSalesOwnerId() !== null) {
            $select->where(['c.salesOwnerId = ?' => $criteria->getSalesOwnerId()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $prefix = $this->escapeLike($keyword) . '%';
            $select->where->nest()
                ->like('c.code', $prefix)
                ->or->like('c.name', $prefix)
                ->or->like('c.taxCode', $prefix)
                ->or->like('c.phone', $prefix)
                ->unnest();
        }

        return $select;
    }

    public function getCustomer(int $id): ?CustomerModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['c.id = ?' => $id]);
    }

    public function getCustomerByCode(string $code, ?int $exceptId = null): ?CustomerModel
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        return $this->fetchOne(['c.code = ?' => $code], $exceptId);
    }

    public function saveCustomer(CustomerModel $item): CustomerModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'code'          => $item->getCode(),
            'name'          => $item->getName(),
            'customerType'  => $item->getCustomerType(),
            'taxCode'       => $item->getTaxCode(),
            'idNumber'      => $item->getIdNumber(),
            'address'       => $item->getAddress(),
            'phone'         => $item->getPhone(),
            'email'         => $item->getEmail(),
            'bankAccount'   => $item->getBankAccount(),
            'salesOwnerId'  => $item->getSalesOwnerId(),
            'creditWarning' => (int)($item->getCreditWarning() ?? false),
            'status'        => $item->getStatus(),
            'note'          => $item->getNote(),
            'updatedAt'     => $now,
            'updatedBy'     => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(CustomerMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(CustomerMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsCustomer(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(CustomerMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?CustomerModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['c' => CustomerMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('c.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new CustomerModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
