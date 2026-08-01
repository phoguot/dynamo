<?php

declare(strict_types=1);

namespace Billing\Model\CreditLimit;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class CreditLimitMapper extends AppMapper
{
    public const string TABLE_NAME = 'bil_credit_limits';

    public function searchCreditLimits(CreditLimitModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['c' => CreditLimitMapper::TABLE_NAME]);
        foreach ([
            'c.customerId = ?' => $criteria->getCustomerId(),
            'c.isBlocked = ?'  => $criteria->getIsBlocked(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $select->order('c.customerId ASC');

        return $this->preparePaginator($select, $paging, new CreditLimitModel());
    }

    public function getCreditLimit(int $id): ?CreditLimitModel
    {
        return $id > 0 ? $this->fetchOne(['c.id = ?' => $id]) : null;
    }

    public function getCreditLimitByCustomer(int $customerId, ?int $exceptId = null): ?CreditLimitModel
    {
        return $customerId > 0 ? $this->fetchOne(['c.customerId = ?' => $customerId], $exceptId) : null;
    }

    public function saveCreditLimit(CreditLimitModel $item): CreditLimitModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'customerId'    => $item->getCustomerId(),
            'creditLimit'   => $item->getCreditLimit(),
            'currentDebt'   => $item->getCurrentDebt(),
            'overdueAmount' => $item->getOverdueAmount(),
            'isBlocked'     => $item->getIsBlocked(),
            'lastCheckedAt' => $item->getLastCheckedAt(),
            'updatedAt'     => $now,
            'updatedBy'     => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();
            $insert = $dbSql->insert(CreditLimitMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }

        $update = $dbSql->update(CreditLimitMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return $item;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?CreditLimitModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['c' => CreditLimitMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('c.id', $exceptId);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (new CreditLimitModel())->exchangeArray((array)$row) : null;
    }
}
