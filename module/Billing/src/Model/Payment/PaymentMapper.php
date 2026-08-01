<?php

declare(strict_types=1);

namespace Billing\Model\Payment;

use Application\Model\AppMapper;
use Application\Model\DateModel;
use Application\Paginator\Paginator;
use Laminas\Db\Sql\Expression;

class PaymentMapper extends AppMapper
{
    public const string TABLE_NAME = 'bil_payments';

    public function searchPayments(PaymentModel $criteria, array $paging = []): Paginator
    {
        $select = $this->getDbSql()->select(['p' => PaymentMapper::TABLE_NAME]);
        foreach ([
            'p.invoiceId = ?'  => $criteria->getInvoiceId(),
            'p.customerId = ?' => $criteria->getCustomerId(),
            'p.status = ?'     => $criteria->getStatus(),
        ] as $condition => $value) {
            if ($value !== null) {
                $select->where([$condition => $value]);
            }
        }
        $keyword = trim((string)$criteria->getOption('keyword'));
        if ($keyword !== '') {
            $select->where->like('p.paymentNo', $this->escapeLike($keyword) . '%');
        }
        $this->applySort($select, PaymentConst::SORT_MAP, $paging['sort'] ?? null, $paging['dir'] ?? null, PaymentConst::SORT_DEFAULT);
        return $this->preparePaginator($select, $paging, new PaymentModel());
    }

    public function getPayment(int $id): ?PaymentModel
    {
        return $id > 0 ? $this->fetchOne(['p.id = ?' => $id]) : null;
    }

    public function getPaymentByNo(string $paymentNo, ?int $exceptId = null): ?PaymentModel
    {
        $paymentNo = strtoupper(trim($paymentNo));
        return $paymentNo !== '' ? $this->fetchOne(['p.paymentNo = ?' => $paymentNo], $exceptId) : null;
    }

    public function sumRecordedByInvoice(int $invoiceId, ?int $exceptPaymentId = null): int
    {
        if ($invoiceId <= 0) {
            return 0;
        }
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PaymentMapper::TABLE_NAME]);
        $select->columns(['total' => new Expression('COALESCE(SUM(amount), 0)')]);
        $select->where(['p.invoiceId = ?' => $invoiceId, 'p.status = ?' => PaymentConst::STATUS_RECORDED]);
        if ($exceptPaymentId !== null) {
            $select->where->notEqualTo('p.id', $exceptPaymentId);
        }
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return (int)($row['total'] ?? 0);
    }

    public function sumRecordedByMonth(int $year, int $month, ?int $customerId = null): int
    {
        [$startDate, $endDate] = $this->monthRange($year, $month);
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PaymentMapper::TABLE_NAME]);
        $select->columns(['total' => new Expression('COALESCE(SUM(p.amount), 0)')]);
        $select->where(['p.status = ?' => PaymentConst::STATUS_RECORDED]);
        $select->where->greaterThanOrEqualTo('p.paymentDate', $startDate);
        $select->where->lessThan('p.paymentDate', $endDate);
        if ($customerId !== null) {
            $select->where(['p.customerId = ?' => $customerId]);
        }
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();

        return (int)($row['total'] ?? 0);
    }

    /**
     * @return list<array{customerId:int, periodYear:int, periodMonth:int}>
     */
    public function fetchRecordedPaymentReportingKeysByInvoice(int $invoiceId): array
    {
        if ($invoiceId <= 0) {
            return [];
        }

        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PaymentMapper::TABLE_NAME]);
        $select->columns(['customerId', 'paymentDate']);
        $select->where(['p.invoiceId = ?' => $invoiceId, 'p.status = ?' => PaymentConst::STATUS_RECORDED]);
        $keys = [];
        foreach ($dbSql->prepareStatementForSqlObject($select)->execute() as $row) {
            $row = (array)$row;
            $customerId = (int)($row['customerId'] ?? 0);
            $paymentDate = (string)($row['paymentDate'] ?? '');
            $key = $this->reportingKeyFromDate($customerId, $paymentDate);
            if ($key !== null) {
                $keys[$customerId . '-' . $key['periodYear'] . '-' . $key['periodMonth']] = $key;
            }
        }

        return array_values($keys);
    }

    public function savePayment(PaymentModel $item): PaymentModel
    {
        $dbSql = $this->getDbSql();
        $now = DateModel::getUtcNow();
        $data = [
            'paymentNo'    => $item->getPaymentNo(),
            'invoiceId'    => $item->getInvoiceId(),
            'customerId'   => $item->getCustomerId(),
            'amount'       => $item->getAmount(),
            'paymentDate'  => $item->getPaymentDate(),
            'method'       => $item->getMethod(),
            'referenceNo'  => $item->getReferenceNo(),
            'attachmentId' => $item->getAttachmentId(),
            'status'       => $item->getStatus(),
            'cancelledAt'  => $item->getCancelledAt(),
            'cancelledBy'  => $item->getCancelledBy(),
            'cancelReason' => $item->getCancelReason(),
            'note'         => $item->getNote(),
            'updatedAt'    => $now,
            'updatedBy'    => $item->getUpdatedBy(),
        ];
        if (!$item->getId()) {
            $data['createdAt'] = $now;
            $data['createdBy'] = $item->getCreatedBy();
            $insert = $dbSql->insert(PaymentMapper::TABLE_NAME);
            $insert->values($data);
            $result = $dbSql->prepareStatementForSqlObject($insert)->execute();
            $item->setId((int)$result->getGeneratedValue());
            return $item;
        }
        $update = $dbSql->update(PaymentMapper::TABLE_NAME);
        $update->set($data);
        $update->where(['id = ?' => $item->getId()]);
        $dbSql->prepareStatementForSqlObject($update)->execute();
        return $item;
    }

    public function deletePayment(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(PaymentMapper::TABLE_NAME);
        $delete->where(['id = ?' => $id]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    public function deleteByInvoice(int $invoiceId): bool
    {
        if ($invoiceId <= 0) {
            return false;
        }

        $dbSql = $this->getDbSql();
        $delete = $dbSql->delete(PaymentMapper::TABLE_NAME);
        $delete->where(['invoiceId = ?' => $invoiceId]);
        $dbSql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

    private function fetchOne(array $where, ?int $exceptId = null): ?PaymentModel
    {
        $dbSql = $this->getDbSql();
        $select = $dbSql->select(['p' => PaymentMapper::TABLE_NAME]);
        $select->where($where);
        if ($exceptId !== null) {
            $select->where->notEqualTo('p.id', $exceptId);
        }
        $select->limit(1);
        $row = $dbSql->prepareStatementForSqlObject($select)->execute()->current();
        return $row ? (new PaymentModel())->exchangeArray((array)$row) : null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
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
