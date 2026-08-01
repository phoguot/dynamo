<?php

declare(strict_types=1);

namespace BillingTest\Service;

use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\Invoice\InvoiceMapper;
use Billing\Model\Invoice\InvoiceModel;
use Billing\Model\Payment\PaymentMapper;
use Billing\Service\InvoiceService;
use PHPUnit\Framework\TestCase;
use Reporting\Model\ReceivablesSnapshot\ReceivablesSnapshotModel;
use Reporting\Model\RevenueMonthly\RevenueMonthlyModel;
use Reporting\Service\ReportingService;

class InvoiceReportingSyncTest extends TestCase
{
    public function test_refresh_financial_reporting_rebuilds_customer_company_and_receivable_rows(): void
    {
        $invoiceMapper = new class extends InvoiceMapper {
            /** @var list<array{0:int, 1:int, 2:int|null}> */
            public array $revenueCalls = [];
            /** @var list<array{0:int, 1:string}> */
            public array $receivableCalls = [];

            public function summarizeRevenueForMonth(int $year, int $month, ?int $customerId = null): array
            {
                $this->revenueCalls[] = [$year, $month, $customerId];

                return $customerId === null
                    ? ['invoicedAmount' => 1000, 'outstandingAmount' => 250, 'overdueAmount' => 50, 'orderCount' => 2]
                    : ['invoicedAmount' => 600, 'outstandingAmount' => 120, 'overdueAmount' => 20, 'orderCount' => 1];
            }

            public function summarizeReceivablesForCustomer(int $customerId, string $snapshotDate): array
            {
                $this->receivableCalls[] = [$customerId, $snapshotDate];

                return ['bucket0To30' => 70, 'bucket31To60' => 30, 'bucket61To90' => 20, 'bucketOver90' => 0, 'totalDebt' => 120, 'dsoDays' => 18.5];
            }
        };
        $paymentMapper = new class extends PaymentMapper {
            /** @var list<array{0:int, 1:int, 2:int|null}> */
            public array $calls = [];

            public function sumRecordedByMonth(int $year, int $month, ?int $customerId = null): int
            {
                $this->calls[] = [$year, $month, $customerId];

                return $customerId === null ? 700 : 300;
            }
        };
        $reporting = new class extends ReportingService {
            /** @var list<RevenueMonthlyModel> */
            public array $revenues = [];
            /** @var list<ReceivablesSnapshotModel> */
            public array $snapshots = [];

            public function syncRevenueMonth(RevenueMonthlyModel $item): ?RevenueMonthlyModel
            {
                $this->revenues[] = $item;

                return $item;
            }

            public function syncReceivablesSnapshot(ReceivablesSnapshotModel $item): ReceivablesSnapshotModel
            {
                $this->snapshots[] = $item;

                return $item;
            }
        };
        $service = new class extends InvoiceService {
            /** @var array<string, object> */
            public array $services = [];

            public function getContainerEntry(string $entryName)
            {
                return $this->services[$entryName] ?? null;
            }
        };
        $service->services = [
            InvoiceMapper::class => $invoiceMapper,
            PaymentMapper::class => $paymentMapper,
            ReportingService::class => $reporting,
        ];

        $service->refreshFinancialReportingForDate(7, '2026-07-15');

        self::assertSame([[2026, 7, 7], [2026, 7, null]], $invoiceMapper->revenueCalls);
        self::assertSame([[2026, 7, 7], [2026, 7, null]], $paymentMapper->calls);
        self::assertCount(2, $reporting->revenues);

        self::assertSame(7, $reporting->revenues[0]->getCustomerId());
        self::assertSame(600, $reporting->revenues[0]->getInvoicedAmount());
        self::assertSame(300, $reporting->revenues[0]->getCollectedAmount());
        self::assertSame(120, $reporting->revenues[0]->getOutstandingAmount());
        self::assertNull($reporting->revenues[1]->getCustomerId());
        self::assertSame(1000, $reporting->revenues[1]->getInvoicedAmount());
        self::assertSame(700, $reporting->revenues[1]->getCollectedAmount());

        self::assertCount(1, $reporting->snapshots);
        self::assertSame(7, $reporting->snapshots[0]->getCustomerId());
        self::assertSame(120, $reporting->snapshots[0]->getTotalDebt());
        self::assertSame(18.5, $reporting->snapshots[0]->getDsoDays());
    }

    public function test_get_invoice_self_heals_overdue_invoice_and_syncs_reporting(): void
    {
        $invoice = (new InvoiceModel())
            ->setId(5)
            ->setCustomerId(7)
            ->setIssueDate('2026-07-15')
            ->setPeriodFrom('2026-07-01')
            ->setDueDate('2000-01-01')
            ->setStatus(InvoiceConst::STATUS_ISSUED)
            ->setTotalAmount(500)
            ->setRemainAmount(500);
        $invoiceMapper = new class($invoice) extends InvoiceMapper {
            /** @var list<array<string, mixed>> */
            public array $updates = [];

            public function __construct(private InvoiceModel $invoice) {}

            public function getInvoice(int $id): ?InvoiceModel
            {
                return $id === 5 ? $this->invoice : null;
            }

            public function updateAttrsInvoice(int $id, array $data, ?int $actorId = null): bool
            {
                $this->updates[] = $data;
                if (($data['status'] ?? null) === InvoiceConst::STATUS_OVERDUE) {
                    $this->invoice->setStatus(InvoiceConst::STATUS_OVERDUE);
                }

                return true;
            }

            public function summarizeRevenueForMonth(int $year, int $month, ?int $customerId = null): array
            {
                return ['invoicedAmount' => 500, 'outstandingAmount' => 500, 'overdueAmount' => 500, 'orderCount' => 1];
            }

            public function summarizeReceivablesForCustomer(int $customerId, string $snapshotDate): array
            {
                return ['bucket0To30' => 0, 'bucket31To60' => 0, 'bucket61To90' => 0, 'bucketOver90' => 500, 'totalDebt' => 500, 'dsoDays' => 100.0];
            }
        };
        $paymentMapper = new class extends PaymentMapper {
            public function sumRecordedByMonth(int $year, int $month, ?int $customerId = null): int
            {
                return 0;
            }
        };
        $reporting = new class extends ReportingService {
            /** @var list<RevenueMonthlyModel> */
            public array $revenues = [];
            /** @var list<ReceivablesSnapshotModel> */
            public array $snapshots = [];

            public function syncRevenueMonth(RevenueMonthlyModel $item): ?RevenueMonthlyModel
            {
                $this->revenues[] = $item;

                return $item;
            }

            public function syncReceivablesSnapshot(ReceivablesSnapshotModel $item): ReceivablesSnapshotModel
            {
                $this->snapshots[] = $item;

                return $item;
            }
        };
        $service = new class extends InvoiceService {
            /** @var array<string, object> */
            public array $services = [];

            public function getContainerEntry(string $entryName)
            {
                return $this->services[$entryName] ?? null;
            }
        };
        $service->services = [
            InvoiceMapper::class => $invoiceMapper,
            PaymentMapper::class => $paymentMapper,
            ReportingService::class => $reporting,
        ];

        $result = $service->getInvoice(5);

        self::assertSame(InvoiceConst::STATUS_OVERDUE, $result->getStatus());
        self::assertSame([['status' => InvoiceConst::STATUS_OVERDUE]], $invoiceMapper->updates);
        self::assertCount(2, $reporting->revenues);
        self::assertCount(1, $reporting->snapshots);
        self::assertSame(500, $reporting->snapshots[0]->getTotalDebt());
    }

    public function test_refresh_financial_reporting_ignores_invalid_reporting_key(): void
    {
        $reporting = new class extends ReportingService {
            /** @var list<RevenueMonthlyModel> */
            public array $revenues = [];

            public function syncRevenueMonth(RevenueMonthlyModel $item): ?RevenueMonthlyModel
            {
                $this->revenues[] = $item;

                return $item;
            }
        };
        $service = new class($reporting) extends InvoiceService {
            public function __construct(private ReportingService $reporting) {}

            public function getContainerEntry(string $entryName)
            {
                return $entryName === ReportingService::class ? $this->reporting : null;
            }
        };

        $service->refreshFinancialReportingForDate(7, 'bad-date');

        self::assertSame([], $reporting->revenues);
    }
}
