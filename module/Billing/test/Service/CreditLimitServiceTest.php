<?php

declare(strict_types=1);

namespace BillingTest\Service;

use Billing\Model\CreditLimit\CreditLimitConst;
use Billing\Model\CreditLimit\CreditLimitMapper;
use Billing\Model\CreditLimit\CreditLimitModel;
use Billing\Model\Invoice\InvoiceMapper;
use Billing\Service\CreditLimitService;
use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Platform\Service\OutboxEventService;
use User\Service\AuditLogService;

class CreditLimitServiceTest extends TestCase
{
    public function test_refresh_credit_status_from_billing_updates_debt_and_emits_exceeded_event(): void
    {
        $creditLimit = (new CreditLimitModel())
            ->setId(3)
            ->setCustomerId(7)
            ->setCreditLimit(100)
            ->setCurrentDebt(50)
            ->setOverdueAmount(0)
            ->setIsBlocked(0);
        $creditMapper = new class($creditLimit) extends CreditLimitMapper {
            public ?CreditLimitModel $saved = null;

            public function __construct(private CreditLimitModel $item) {}

            public function getCreditLimitByCustomer(int $customerId, ?int $exceptId = null): ?CreditLimitModel
            {
                return $customerId === 7 ? $this->item : null;
            }

            public function saveCreditLimit(CreditLimitModel $item): CreditLimitModel
            {
                $this->saved = $item;

                return $item;
            }
        };
        $invoiceMapper = new class extends InvoiceMapper {
            public function summarizeCreditDebtForCustomer(int $customerId, string $today): array
            {
                return ['currentDebt' => 150, 'overdueAmount' => 25];
            }
        };
        $outbox = new class extends OutboxEventService {
            /** @var list<array{0:string, 1:int|null, 2:array<string,mixed>}> */
            public array $events = [];

            public function recordEvent(string $eventName, ?int $aggregateId, array $payload): OutboxEventModel
            {
                $this->events[] = [$eventName, $aggregateId, $payload];

                return (new OutboxEventModel())->setEventName($eventName)->setAggregateId($aggregateId);
            }
        };
        $audit = new class extends AuditLogService {
            /** @var list<array{0:string, 1:string, 2:int|null}> */
            public array $writes = [];

            public function write(string $action, string $objectType, ?int $objectId, mixed $before = null, mixed $after = null, ?int $actorId = null): void
            {
                $this->writes[] = [$action, $objectType, $objectId];
            }
        };
        $service = new class extends CreditLimitService {
            /** @var array<string, object> */
            public array $services = [];

            public function getContainerEntry(string $entryName)
            {
                return $this->services[$entryName] ?? null;
            }
        };
        $service->services = [
            CreditLimitMapper::class => $creditMapper,
            InvoiceMapper::class => $invoiceMapper,
            OutboxEventService::class => $outbox,
            AuditLogService::class => $audit,
        ];

        $updated = $service->refreshCreditStatusFromBilling(7, 9);

        self::assertNotNull($updated);
        self::assertSame(150, $updated->getCurrentDebt());
        self::assertSame(25, $updated->getOverdueAmount());
        self::assertTrue($updated->isBlocked());
        self::assertSame(CreditLimitConst::EVENT_CREDIT_EXCEEDED, $outbox->events[0][0]);
        self::assertSame(3, $outbox->events[0][1]);
        self::assertSame(7, $outbox->events[0][2]['customerId']);
        self::assertSame(150, $outbox->events[0][2]['currentDebt']);
        self::assertCount(1, $audit->writes);
    }
}
