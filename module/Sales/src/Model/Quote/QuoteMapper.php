<?php

declare(strict_types=1);

namespace Sales\Model\Quote;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Select;

class QuoteMapper extends AppMapper
{
    public const string TABLE_NAME = 'sal_quotes';

    public function searchQuotes(QuoteModel $criteria, array $paging = []): Paginator
    {
        $select = $this->buildSearchSelect($criteria);
        $this->applySort(
            $select,
            QuoteConst::SORT_MAP,
            $paging['sort'] ?? null,
            $paging['dir'] ?? null,
            QuoteConst::SORT_DEFAULT
        );

        return $this->preparePaginator($select, $paging, new QuoteModel());
    }

    private function buildSearchSelect(QuoteModel $criteria): Select
    {
        $select = $this->getDbSql()->select(['q' => QuoteMapper::TABLE_NAME]);

        if ($criteria->getCustomerId() !== null) {
            $select->where(['q.customerId = ?' => $criteria->getCustomerId()]);
        }
        if ($criteria->getStatus() !== null) {
            $select->where(['q.status = ?' => $criteria->getStatus()]);
        }

        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('q.quoteNo', $this->escapeLike($keyword) . '%');
        }

        return $select;
    }

    public function getQuote(int $id): ?QuoteModel
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(['q.id = ?' => $id]);
    }

    public function getQuoteByNo(string $quoteNo, ?int $exceptId = null): ?QuoteModel
    {
        $quoteNo = strtoupper(trim($quoteNo));
        if ($quoteNo === '') {
            return null;
        }

        return $this->fetchOne(['q.quoteNo = ?' => $quoteNo], $exceptId);
    }

    public function saveQuote(QuoteModel $item): QuoteModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'quoteNo'        => $item->getQuoteNo(),
            'customerId'     => $item->getCustomerId(),
            'siteId'         => $item->getSiteId(),
            'priceListId'    => $item->getPriceListId(),
            'rentFrom'       => $item->getRentFrom(),
            'rentTo'         => $item->getRentTo(),
            'status'         => $item->getStatus(),
            'validUntil'     => $item->getValidUntil(),
            'rentAmount'     => $item->getRentAmount(),
            'deliveryFee'    => $item->getDeliveryFee(),
            'installFee'     => $item->getInstallFee(),
            'otherFee'       => $item->getOtherFee(),
            'discountAmount' => $item->getDiscountAmount(),
            'vatRate'        => $item->getVatRate(),
            'vatAmount'      => $item->getVatAmount(),
            'totalAmount'    => $item->getTotalAmount(),
            'depositAmount'  => $item->getDepositAmount(),
            'submittedAt'    => $item->getSubmittedAt(),
            'approvedBy'     => $item->getApprovedBy(),
            'approvedAt'     => $item->getApprovedAt(),
            'rejectReason'   => $item->getRejectReason(),
            'terms'          => $item->getTerms(),
            'updatedAt'      => $now,
            'updatedBy'      => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();

            $insert = $dbSql->insert(QuoteMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(QuoteMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }

    public function updateAttrsQuote(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }

        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;

        $dbSql = $this->getDbSql();
        $update = $dbSql->update(QuoteMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?QuoteModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['q' => QuoteMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('q.id', $exceptId);
        }
        $select->limit(1);

        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return $row ? (new QuoteModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}

