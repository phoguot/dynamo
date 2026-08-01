<?php

declare(strict_types=1);

namespace Rental\Listener;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Platform\Event\OutboxEventHandlerInterface;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Rental\Service\RentalOrderService;
use RuntimeException;

class DispatchEventHandler extends AppServiceFactory implements OutboxEventHandlerInterface
{
    private const string EVENT_HANDOVER_COMPLETED = 'dispatch.handover.completed';
    private const string EVENT_RETURN_COMPLETED = 'dispatch.return.completed';
    private const string EVENT_SWAP_COMPLETED = 'dispatch.swap.completed';

    public function handle(OutboxEventModel $event): void
    {
        $payload = $event->getPayload();
        $actorId = $this->optionalInt($payload, 'actorId');

        match ((string)$event->getEventName()) {
            self::EVENT_HANDOVER_COMPLETED => $this->rentalOrder()->activateFromDispatch(
                $this->requiredInt($payload, 'rentalOrderId'),
                $this->requiredFloat($payload, 'hourMeter'),
                $actorId
            ),
            self::EVENT_RETURN_COMPLETED => $this->rentalOrder()->recoverFromDispatch(
                $this->requiredInt($payload, 'rentalOrderId'),
                $this->requiredFloat($payload, 'hourMeter'),
                $this->optionalString($payload, 'actualEndDate'),
                $actorId
            ),
            self::EVENT_SWAP_COMPLETED => $this->rentalOrder()->swapGeneratorFromDispatch(
                $this->requiredInt($payload, 'rentalOrderId'),
                $this->optionalInt($payload, 'oldGeneratorId') ?? $this->requiredInt($payload, 'generatorId'),
                $this->requiredInt($payload, 'newGeneratorId'),
                $this->requiredFloat($payload, 'oldHourMeter'),
                $this->requiredFloat($payload, 'newHourMeter'),
                $this->optionalString($payload, 'actualEndDate'),
                $actorId
            ),
            default => null,
        };
    }

    private function rentalOrder(): RentalOrderService
    {
        $service = $this->getContainerEntry(RentalOrderService::class);
        if (!$service instanceof RentalOrderService) {
            throw new RuntimeException('RentalOrderService is not registered.');
        }

        return $service;
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

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredFloat(array $payload, string $key): float
    {
        if (!isset($payload[$key]) || $payload[$key] === '' || !is_numeric($payload[$key])) {
            throw new ValidationException([$key => 'Missing or invalid numeric payload value.']);
        }

        return (float)$payload[$key];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalString(array $payload, string $key): ?string
    {
        if (!isset($payload[$key])) {
            return null;
        }

        $value = is_string($payload[$key]) ? trim($payload[$key]) : (string)$payload[$key];
        return $value === '' ? null : $value;
    }
}
