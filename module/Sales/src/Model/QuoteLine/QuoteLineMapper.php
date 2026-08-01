<?php

declare(strict_types=1);

namespace Sales\Model\QuoteLine;

use Application\Model\AppMapper;
use Application\Model\DateModel;

class QuoteLineMapper extends AppMapper
{
    public const string TABLE_NAME = 'sal_quote_lines';

    /** @return QuoteLineModel[] */
    public function fetchByQuote(int $quoteId): array
    {
        if ($quoteId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['l' => QuoteLineMapper::TABLE_NAME]);
        $select->where(['l.quoteId = ?' => $quoteId]);
        $select->order('l.id ASC');
        $select->limit(QuoteLineMapper::MAX_RECORD_FETCH_ALL);

        $rows = $dbSql->prepareStatementForSqlObject($select)->execute();
        $result = [];
        foreach ($rows as $row) {
            $result[] = (new QuoteLineModel())->exchangeArray((array)$row);
        }

        return $result;
    }

    public function replaceLines(int $quoteId, array $lines): void
    {
        $this->clearByQuote($quoteId);
        foreach ($lines as $line) {
            $line->setQuoteId($quoteId);
            $this->saveQuoteLine($line);
        }
    }

    public function clearByQuote(int $quoteId): bool
    {
        if ($quoteId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(QuoteLineMapper::TABLE_NAME);
        $delete->where(['quoteId = ?' => $quoteId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public function saveQuoteLine(QuoteLineModel $item): QuoteLineModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();

        $data = [
            'quoteId'       => $item->getQuoteId(),
            'generatorId'   => $item->getGeneratorId(),
            'capacityKva'   => $item->getCapacityKva(),
            'quantity'      => $item->getQuantity(),
            'rentFrom'      => $item->getRentFrom(),
            'rentTo'        => $item->getRentTo(),
            'durationTier'  => $item->getDurationTier(),
            'durationQty'   => $item->getDurationQty(),
            'unitPrice'     => $item->getUnitPrice(),
            'oddDays'       => $item->getOddDays(),
            'oddDayRate'    => $item->getOddDayRate(),
            'lineAmount'    => $item->getLineAmount(),
            'suggestReason' => $item->getSuggestReason(),
            'note'          => $item->getNote(),
            'updatedAt'     => $now,
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;

            $insert = $dbSql->insert(QuoteLineMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());

            return $item;
        }

        $update = $dbSql->update(QuoteLineMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return $item;
    }
}

