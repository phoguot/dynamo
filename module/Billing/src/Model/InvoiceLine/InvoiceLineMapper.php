<?php

declare(strict_types=1);

namespace Billing\Model\InvoiceLine;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Laminas\Db\Sql\Expression;

class InvoiceLineMapper extends AppMapper
{
    public const string TABLE_NAME = 'bil_invoice_lines';

    /** @return InvoiceLineModel[] */
    public function fetchByInvoice(int $invoiceId): array
    {
        if ($invoiceId <= 0) {
            return [];
        }
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['l' => InvoiceLineMapper::TABLE_NAME]);
        $select->where(['l.invoiceId = ?' => $invoiceId]);
        $select->order(['l.id ASC']);
        $result = [];
        foreach ($dbSql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[] = (new InvoiceLineModel())->exchangeArray((array)$row);
        }
        return $result;
    }

    public function getInvoiceLine(int $id): ?InvoiceLineModel
    {
        if ($id <= 0) {
            return null;
        }
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['l' => InvoiceLineMapper::TABLE_NAME]);
        $select->where(['l.id = ?' => $id]);
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (new InvoiceLineModel())->exchangeArray((array)$row) : null;
    }

    public function saveInvoiceLine(InvoiceLineModel $item): InvoiceLineModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'invoiceId'   => $item->getInvoiceId(),
            'lineType'    => $item->getLineType(),
            'generatorId' => $item->getGeneratorId(),
            'description' => $item->getDescription(),
            'quantity'    => $item->getQuantity(),
            'unit'        => $item->getUnit(),
            'unitPrice'   => $item->getUnitPrice(),
            'lineAmount'  => $item->getLineAmount(),
            'isVatable'   => $item->getIsVatable(),
            'updatedAt'   => $now,
        ];
        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $insert = $dbSql->insert(InvoiceLineMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }
        $update = $dbSql->update(InvoiceLineMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return $item;
    }

    public function deleteInvoiceLine(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(InvoiceLineMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();
    }

    public function clearByInvoice(int $invoiceId): bool
    {
        if ($invoiceId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(InvoiceLineMapper::TABLE_NAME);
        $delete->where(['invoiceId = ?' => $invoiceId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    /** @return array{total:int, vatable:int, rent:int, surcharge:int} */
    public function totalsByInvoice(int $invoiceId): array
    {
        if ($invoiceId <= 0) {
            return ['total' => 0, 'vatable' => 0, 'rent' => 0, 'surcharge' => 0];
        }
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['l' => InvoiceLineMapper::TABLE_NAME]);
        $select->columns([
            'total' => new Expression('COALESCE(SUM(lineAmount), 0)'),
            'vatable' => new Expression('COALESCE(SUM(CASE WHEN isVatable = 1 THEN lineAmount ELSE 0 END), 0)'),
            'rent' => new Expression("COALESCE(SUM(CASE WHEN lineType = 'tien_thue' THEN lineAmount ELSE 0 END), 0)"),
        ]);
        $select->where(['l.invoiceId = ?' => $invoiceId]);
        $row = (array)$dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return [
            'total' => (int)($row['total'] ?? 0),
            'vatable' => (int)($row['vatable'] ?? 0),
            'rent' => (int)($row['rent'] ?? 0),
            'surcharge' => max(0, (int)($row['total'] ?? 0) - (int)($row['rent'] ?? 0)),
        ];
    }
}
