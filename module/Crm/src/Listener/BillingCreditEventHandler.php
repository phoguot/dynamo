<?php

declare(strict_types=1);

namespace Crm\Listener;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Crm\Service\CustomerService;
use Platform\Event\OutboxEventHandlerInterface;
use Platform\Model\OutboxEvent\OutboxEventModel;
use RuntimeException;

class BillingCreditEventHandler extends AppServiceFactory implements OutboxEventHandlerInterface
{
    private const string EVENT_CREDIT_EXCEEDED = 'billing.credit.exceeded';
    private const string EVENT_CREDIT_CLEARED = 'billing.credit.cleared';

    public function handle(OutboxEventModel $event): void
    {
        $payload = $event->getPayload();
        $customerId = $this->requiredInt($payload, 'customerId');
        $actorId = $this->optionalInt($payload, 'actorId');

        match ((string)$event->getEventName()) {
            self::EVENT_CREDIT_EXCEEDED => $this->customer()->setCreditWarningFromBilling(
                $customerId,
                true,
                $this->context($event),
                $actorId
            ),
            self::EVENT_CREDIT_CLEARED => $this->customer()->setCreditWarningFromBilling(
                $customerId,
                false,
                $this->context($event),
                $actorId
            ),
            default => null,
        };
    }

    private function customer(): CustomerService
    {
        $service = $this->getContainerEntry(CustomerService::class);
        if (!$service instanceof CustomerService) {
            throw new RuntimeException('CustomerService is not registered.');
        }

        return $service;
    }

    /**
     * @return array<string, mixed>
     */
    private function context(OutboxEventModel $event): array
    {
        $payload = $event->getPayload();

        return [
            'source'        => (string)$event->getEventName(),
            'creditLimitId' => $this->optionalInt($payload, 'creditLimitId'),
            'creditLimit'   => isset($payload['creditLimit']) ? (int)$payload['creditLimit'] : null,
            'currentDebt'   => isset($payload['currentDebt']) ? (int)$payload['currentDebt'] : null,
            'overdueAmount' => isset($payload['overdueAmount']) ? (int)$payload['overdueAmount'] : null,
            'checkedAt'     => isset($payload['checkedAt']) ? (string)$payload['checkedAt'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredInt(array $payload, string $key): int
    {
        $value = $this->optionalInt($payload, $key);
        if ($value === null) {
            throw new ValidationException([$key => 'Missing or invalid integer payload value.']);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalInt(array $payload, string $key): ?int
    {
        if (!isset($payload[$key]) || $payload[$key] === '') {
            return null;
        }

        $value = (int)$payload[$key];
        return $value > 0 ? $value : null;
    }
}
