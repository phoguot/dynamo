<?php

declare(strict_types=1);

namespace Billing\Model\Invoice;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class InvoiceMapper extends AppMapper
{
    public const string TABLE_NAME = 'bil_invoices';

    public function searchInvoices(InvoiceModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['i' => InvoiceMapper::TABLE_NAME]);
        foreach ([
            'i.customerId = ?'    => $criteria->getCustomerId(),
            'i.contractId = ?'    => $criteria->getContractId(),
            'i.rentalOrderId = ?' => $criteria->getRentalOrderId(),
            'i.status = ?'        => $criteria->getStatus(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('i.invoiceNo', $this->escapeLike($keyword) . '%');
        }
        $this->applySort($select, InvoiceConst::SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, InvoiceConst::SORT_DEFAULT);

        return $this->preparePaginator($select, $paging, new InvoiceModel());
    }

    public function getInvoice(int $id): ?InvoiceModel
    {
        return $id > 0 ? $this->fetchOne(['i.id = ?' => $id]) : null;
    }

    public function getInvoiceByNo(string $invoiceNo, ?int $exceptId = null): ?InvoiceModel
    {
        $invoiceNo = strtoupper(trim($invoiceNo));
        return $invoiceNo !== '' ? $this->fetchOne(['i.invoiceNo = ?' => $invoiceNo], $exceptId) : null;
    }

    public function saveInvoice(InvoiceModel $item): InvoiceModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'invoiceNo'       => $item->getInvoiceNo(),
            'customerId'      => $item->getCustomerId(),
            'contractId'      => $item->getContractId(),
            'rentalOrderId'   => $item->getRentalOrderId(),
            'periodFrom'      => $item->getPeriodFrom(),
            'periodTo'        => $item->getPeriodTo(),
            'issueDate'       => $item->getIssueDate(),
            'dueDate'         => $item->getDueDate(),
            'status'          => $item->getStatus(),
            'rentAmount'      => $item->getRentAmount(),
            'surchargeAmount' => $item->getSurchargeAmount(),
            'discountAmount'  => $item->getDiscountAmount(),
            'vatRate'         => $item->getVatRate(),
            'vatAmount'       => $item->getVatAmount(),
            'totalAmount'     => $item->getTotalAmount(),
            'paidAmount'      => $item->getPaidAmount(),
            'remainAmount'    => $item->getRemainAmount(),
            'voidedAt'        => $item->getVoidedAt(),
            'voidedBy'        => $item->getVoidedBy(),
            'voidReason'      => $item->getVoidReason(),
            'note'            => $item->getNote(),
            'updatedAt'       => $now,
            'updatedBy'       => $item->getUpdatedBy(),
        ];

        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();
            $insert = $dbSql->insert(InvoiceMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }

        $update = $dbSql->update(InvoiceMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return $item;
    }

    public function updateAttrsInvoice(int $id, array $data, ?int $actorId = null): bool
    {
        if ($id <= 0 || $data === []) {
            return false;
        }
        $data['updatedAt'] = DateModel::getUtcNow();
        $data['updatedBy'] = $actorId;
        $dbSql = $this->getDbSql();
        $update = $dbSql->update(InvoiceMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return true;
    }

    /**
     * @return array{invoicedAmount:int, outstandingAmount:int, overdueAmount:int, orderCount:int}
     */
    public function summarizeRevenueForMonth(int $year, int $month, ?int $customerId = null): array
    {
        [$startDate, $endDate] = $this->monthRange($year, $month);
        $today = DateModel::getCurrentDate();
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => InvoiceMapper::TABLE_NAME]);
        $select->columns([
            'invoicedAmount' => new Expression('COALESCE(SUM(i.totalAmount), 0)'),
            'outstandingAmount' => new Expression('COALESCE(SUM(i.remainAmount), 0)'),
            'overdueAmount' => new Expression(
                'COALESCE(SUM(CASE WHEN i.dueDate IS NOT NULL AND i.dueDate < ? AND i.remainAmount > 0 THEN i.remainAmount ELSE 0 END), 0)',
                [$today]
            ),
            'orderCount' => new Expression('COUNT(DISTINCT COALESCE(i.rentalOrderId, i.id))'),
        ]);
        $select->where->in('i.status', $this->revenueStatuses());
        $select->where->expression('COALESCE(i.issueDate, i.periodFrom) >= ?', [$startDate]);
        $select->where->expression('COALESCE(i.issueDate, i.periodFrom) < ?', [$endDate]);
        if ($customerId !== null) {
            $select->where(['i.customerId = ?' => $customerId]);
        }
        $row = (array)$dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return [
            'invoicedAmount' => (int)($row['invoicedAmount'] ?? 0),
            'outstandingAmount' => (int)($row['outstandingAmount'] ?? 0),
            'overdueAmount' => (int)($row['overdueAmount'] ?? 0),
            'orderCount' => (int)($row['orderCount'] ?? 0),
        ];
    }

    /**
     * @return array{bucket0To30:int, bucket31To60:int, bucket61To90:int, bucketOver90:int, totalDebt:int, dsoDays:float}
     */
    public function summarizeReceivablesForCustomer(int $customerId, string $snapshotDate): array
    {
        if ($customerId <= 0) {
            return ['bucket0To30' => 0, 'bucket31To60' => 0, 'bucket61To90' => 0, 'bucketOver90' => 0, 'totalDebt' => 0, 'dsoDays' => 0.0];
        }

        $ageSql = 'GREATEST(DATEDIFF(?, COALESCE(i.dueDate, ?)), 0)';
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => InvoiceMapper::TABLE_NAME]);
        $select->columns([
            'bucket0To30' => new Expression(
                "COALESCE(SUM(CASE WHEN $ageSql <= 30 THEN i.remainAmount ELSE 0 END), 0)",
                [$snapshotDate, $snapshotDate]
            ),
            'bucket31To60' => new Expression(
                "COALESCE(SUM(CASE WHEN $ageSql BETWEEN 31 AND 60 THEN i.remainAmount ELSE 0 END), 0)",
                [$snapshotDate, $snapshotDate]
            ),
            'bucket61To90' => new Expression(
                "COALESCE(SUM(CASE WHEN $ageSql BETWEEN 61 AND 90 THEN i.remainAmount ELSE 0 END), 0)",
                [$snapshotDate, $snapshotDate]
            ),
            'bucketOver90' => new Expression(
                "COALESCE(SUM(CASE WHEN $ageSql > 90 THEN i.remainAmount ELSE 0 END), 0)",
                [$snapshotDate, $snapshotDate]
            ),
            'totalDebt' => new Expression('COALESCE(SUM(i.remainAmount), 0)'),
            'weightedAge' => new Expression(
                "COALESCE(SUM(i.remainAmount * $ageSql), 0)",
                [$snapshotDate, $snapshotDate]
            ),
        ]);
        $select->where(['i.customerId = ?' => $customerId]);
        $select->where->in('i.status', $this->receivableStatuses());
        $select->where->greaterThan('i.remainAmount', 0);
        $row = (array)$dbSql->prepareStatementForSqlObject($select)->execute()->current();
        $totalDebt = (int)($row['totalDebt'] ?? 0);
        $weightedAge = (float)($row['weightedAge'] ?? 0);

        return [
            'bucket0To30' => (int)($row['bucket0To30'] ?? 0),
            'bucket31To60' => (int)($row['bucket31To60'] ?? 0),
            'bucket61To90' => (int)($row['bucket61To90'] ?? 0),
            'bucketOver90' => (int)($row['bucketOver90'] ?? 0),
            'totalDebt' => $totalDebt,
            'dsoDays' => $totalDebt > 0 ? round($weightedAge / $totalDebt, 2) : 0.0,
        ];
    }

    /**
     * @return list<array{customerId:int, periodYear:int, periodMonth:int}>
     */
    public function fetchOverdueReportingKeys(string $today): array
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => InvoiceMapper::TABLE_NAME]);
        $select->columns(['customerId', 'issueDate', 'periodFrom']);
        $select->where->in('i.status', $this->overdueCandidateStatuses());
        $select->where->isNotNull('i.dueDate');
        $select->where->lessThan('i.dueDate', $today);
        $select->where->greaterThan('i.remainAmount', 0);

        $keys = [];
        foreach ($dbSql->prepareStatementForSqlObject($select)->execute() as $row) {
            $row = (array)$row;
            $customerId = (int)($row['customerId'] ?? 0);
            $date = $row['issueDate'] !== null && $row['issueDate'] !== ''
                ? (string)$row['issueDate']
                : (string)($row['periodFrom'] ?? '');
            $key = $this->reportingKeyFromDate($customerId, $date);
            if ($key !== null) {
                $keys[$customerId . '-' . $key['periodYear'] . '-' . $key['periodMonth']] = $key;
            }
        }

        return array_values($keys);
    }

    /**
     * @return array{currentDebt:int, overdueAmount:int}
     */
    public function summarizeCreditDebtForCustomer(int $customerId, string $today): array
    {
        if ($customerId <= 0) {
            return ['currentDebt' => 0, 'overdueAmount' => 0];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => InvoiceMapper::TABLE_NAME]);
        $select->columns([
            'currentDebt' => new Expression('COALESCE(SUM(i.remainAmount), 0)'),
            'overdueAmount' => new Expression(
                'COALESCE(SUM(CASE WHEN i.dueDate IS NOT NULL AND i.dueDate < ? THEN i.remainAmount ELSE 0 END), 0)',
                [$today]
            ),
        ]);
        $select->where(['i.customerId = ?' => $customerId]);
        $select->where->in('i.status', $this->receivableStatuses());
        $select->where->greaterThan('i.remainAmount', 0);
        $row = (array)$dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return [
            'currentDebt' => (int)($row['currentDebt'] ?? 0),
            'overdueAmount' => (int)($row['overdueAmount'] ?? 0),
        ];
    }

    public function markOverdueInvoices(string $today, ?int $actorId = null): bool
    {
        $dbSql = $this->getDbSql();
        $update = $dbSql->update(InvoiceMapper::TABLE_NAME);
        $update->set([
            'status' => InvoiceConst::STATUS_OVERDUE,
            'updatedAt' => DateModel::getUtcNow(),
            'updatedBy' => $actorId,
        ]);
        $update->where->in('status', $this->overdueCandidateStatuses());
        $update->where->isNotNull('dueDate');
        $update->where->lessThan('dueDate', $today);
        $update->where->greaterThan('remainAmount', 0);
        $dbSql->prepareStatementForSqlObject($update)->execute();

        return true;
    }

    public function deleteInvoice(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(InvoiceMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?InvoiceModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['i' => InvoiceMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('i.id', $exceptId);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (new InvoiceModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /** @return list<string> */
    private function revenueStatuses(): array
    {
        return [
            InvoiceConst::STATUS_ISSUED,
            InvoiceConst::STATUS_PARTIALLY_PAID,
            InvoiceConst::STATUS_PAID,
            InvoiceConst::STATUS_OVERDUE,
        ];
    }

    /** @return list<string> */
    private function receivableStatuses(): array
    {
        return [
            InvoiceConst::STATUS_ISSUED,
            InvoiceConst::STATUS_PARTIALLY_PAID,
            InvoiceConst::STATUS_OVERDUE,
        ];
    }

    /** @return list<string> */
    private function overdueCandidateStatuses(): array
    {
        return [
            InvoiceConst::STATUS_ISSUED,
            InvoiceConst::STATUS_PARTIALLY_PAID,
        ];
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function monthRange(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = (new \DateTimeImmutable($start))->modify('first day of next month')->format('Y-m-d');

        return [$start, $end];
    }

    /**
     * @return array{customerId:int, periodYear:int, periodMonth:int}|null
     */
    private function reportingKeyFromDate(int $customerId, string $date): ?array
    {
        if ($customerId <= 0 || !preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $date, $m)) {
            return null;
        }

        return [
            'customerId' => $customerId,
            'periodYear' => (int)$m[1],
            'periodMonth' => (int)$m[2],
        ];
    }
}
