<?php

declare(strict_types=1);

namespace Sales\Model\Contract;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class ContractMapper extends AppMapper
{
    public const string TABLE_NAME = 'sal_contracts';

    public function searchContracts(ContractModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            ContractConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            ContractConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new ContractModel());
    }

    private function buildSearchSelect(ContractModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['c' => ContractMapper::TABLE_NAME]);

        if ($criteria->getCustomerId() !== null) {
            $select->where(['c.customerId = ?' => $criteria->getCustomerId()]);
        }
        if ($criteria->getStatus() !== null) {
            $select->where(['c.status = ?' => $criteria->getStatus()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('c.contractNo', $this->escapeLike($keyword) . '%');
        }

        return $select;
    }

    public function getContract(int $id): ?ContractModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['c.id = ?' => $id]);
    }

    public function getContractByNo(string $contractNo, ?int $exceptId = null): ?ContractModel
    {
        $contractNo = strtoupper(trim($contractNo));
        if ($contractNo === '') {
            return null;
        }

        return $this->fetchOne(['c.contractNo = ?' => $contractNo], $exceptId);
    }

    public function saveContract(ContractModel $item): ContractModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'contractNo'           => $item->getContractNo(),
            'quoteId'              => $item->getQuoteId(),
            'customerId'           => $item->getCustomerId(),
            'siteId'               => $item->getSiteId(),
            'signedDate'           => $item->getSignedDate(),
            'effectiveFrom'        => $item->getEffectiveFrom(),
            'effectiveTo'          => $item->getEffectiveTo(),
            'status'               => $item->getStatus(),
            'totalAmount'          => $item->getTotalAmount(),
            'depositAmount'        => $item->getDepositAmount(),
            'paymentTermDays'      => $item->getPaymentTermDays(),
            'billingCycle'         => $item->getBillingCycle(),
            'creditOverrideBy'     => $item->getCreditOverrideBy(),
            'creditOverrideReason' => $item->getCreditOverrideReason(),
            'terms'                => $item->getTerms(),
            'cancelledAt'          => $item->getCancelledAt(),
            'cancelledBy'          => $item->getCancelledBy(),
            'cancelReason'         => $item->getCancelReason(),
            'updatedAt'            => $now,
            'updatedBy'            => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(ContractMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(ContractMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsContract(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(ContractMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteContract(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(ContractMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?ContractModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['c' => ContractMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('c.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new ContractModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
