<?php

declare(strict_types=1);

namespace CrmTest\Listener;

use Crm\Listener\BillingCreditEventHandler;
use Crm\Model\Customer\CustomerModel;
use Crm\Service\CustomerService;
use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Psr\Container\ContainerInterface;

class BillingCreditEventHandlerTest extends TestCase
{
    public function test_credit_exceeded_sets_customer_warning(): void
    {
        $service = new class extends CustomerService {
            public array $calls = [];

            public function setCreditWarningFromBilling(int $customerId, bool $creditWarning, array $context = [], ?int $actorId = null): CustomerModel
            {
                $this->calls[] = [$customerId, $creditWarning, $context, $actorId];
                return (new CustomerModel())->setId($customerId)->setCreditWarning($creditWarning);
            }
        };

        $handler = new BillingCreditEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private CustomerService $service) {}

            public function get(string $id)
            {
                return $this->service;
            }

            public function has(string $id): bool
            {
                return true;
            }
        });

        $handler->handle((new OutboxEventModel())
            ->setEventName('billing.credit.exceeded')
            ->setPayloadJson('{"customerId":12,"creditLimitId":5,"creditLimit":1000,"currentDebt":1300,"overdueAmount":200,"checkedAt":"2026-08-01 00:00:00","actorId":9}'));

        self::assertSame(12, $service->calls[0][0]);
        self::assertTrue($service->calls[0][1]);
        self::assertSame('billing.credit.exceeded', $service->calls[0][2]['source']);
        self::assertSame(5, $service->calls[0][2]['creditLimitId']);
        self::assertSame(9, $service->calls[0][3]);
    }

    public function test_credit_cleared_unsets_customer_warning(): void
    {
        $service = new class extends CustomerService {
            public array $calls = [];

            public function setCreditWarningFromBilling(int $customerId, bool $creditWarning, array $context = [], ?int $actorId = null): CustomerModel
            {
                $this->calls[] = [$customerId, $creditWarning, $actorId];
                return (new CustomerModel())->setId($customerId)->setCreditWarning($creditWarning);
            }
        };

        $handler = new BillingCreditEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private CustomerService $service) {}

            public function get(string $id)
            {
                return $this->service;
            }

            public function has(string $id): bool
            {
                return true;
            }
        });

        $handler->handle((new OutboxEventModel())
            ->setEventName('billing.credit.cleared')
            ->setPayloadJson('{"customerId":12,"creditLimitId":5,"actorId":9}'));

        self::assertSame([[12, false, 9]], $service->calls);
    }
}
