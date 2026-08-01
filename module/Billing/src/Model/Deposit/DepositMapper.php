<?php

declare(strict_types=1);

namespace Billing\Model\Deposit;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;

class DepositMapper extends AppMapper
{
    public const string TABLE_NAME = 'bil_deposits';

    public function searchDeposits(DepositModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['d' => DepositMapper::TABLE_NAME]);
        foreach ([
            'd.customerId = ?'    => $criteria->getCustomerId(),
            'd.contractId = ?'    => $criteria->getContractId(),
            'd.rentalOrderId = ?' => $criteria->getRentalOrderId(),
            'd.status = ?'        => $criteria->getStatus(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('d.depositNo', $this->escapeLike($keyword) . '%');
        }
        $this->applySort($select, DepositConst::SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, DepositConst::SORT_DEFAULT);
        return $this->preparePaginator($select, $paging, new DepositModel());
    }

    public function getDeposit(int $id): ?DepositModel
    {
        return $id > 0 ? $this->fetchOne(['d.id = ?' => $id]) : null;
    }

    public function getDepositByNo(string $depositNo, ?int $exceptId = null): ?DepositModel
    {
        $depositNo = strtoupper(trim($depositNo));
        return $depositNo !== '' ? $this->fetchOne(['d.depositNo = ?' => $depositNo], $exceptId) : null;
    }

    public function saveDeposit(DepositModel $item): DepositModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'depositNo'      => $item->getDepositNo(),
            'customerId'     => $item->getCustomerId(),
            'contractId'     => $item->getContractId(),
            'rentalOrderId'  => $item->getRentalOrderId(),
            'amount'         => $item->getAmount(),
            'receivedDate'   => $item->getReceivedDate(),
            'deductedAmount' => $item->getDeductedAmount(),
            'deductReason'   => $item->getDeductReason(),
            'refundedAmount' => $item->getRefundedAmount(),
            'refundedDate'   => $item->getRefundedDate(),
            'status'         => $item->getStatus(),
            'note'           => $item->getNote(),
            'updatedAt'      => $now,
            'updatedBy'      => $item->getUpdatedBy(),
        ];
        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();
            $insert = $dbSql->insert(DepositMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }
        $update = $dbSql->update(DepositMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return $item;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?DepositModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['d' => DepositMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('d.id', $exceptId);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (new DepositModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
