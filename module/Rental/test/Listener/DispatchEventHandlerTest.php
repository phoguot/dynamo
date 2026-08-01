<?php

declare(strict_types=1);

namespace RentalTest\Listener;

use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Psr\Container\ContainerInterface;
use Rental\Listener\DispatchEventHandler;
use Rental\Model\RentalOrder\RentalOrderModel;
use Rental\Service\RentalOrderService;

class DispatchEventHandlerTest extends TestCase
{
    public function test_handover_event_activates_rental_order(): void
    {
        $service = new class extends RentalOrderService {
            public array $activated = [];

            public function activateFromDispatch(int $orderId, float $startHourMeter, ?int $actorId = null): RentalOrderModel
            {
                $this->activated[] = [$orderId, $startHourMeter, $actorId];
                return (new RentalOrderModel())->setId($orderId);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private RentalOrderService $service) {}

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
            ->setEventName('dispatch.handover.completed')
            ->setPayloadJson('{"rentalOrderId":17,"hourMeter":88.5,"actorId":5}'));

        self::assertSame([[17, 88.5, 5]], $service->activated);
    }

    public function test_return_event_recovers_rental_order(): void
    {
        $service = new class extends RentalOrderService {
            public array $recovered = [];

            public function recoverFromDispatch(int $orderId, float $endHourMeter, ?string $actualEndDate = null, ?int $actorId = null): RentalOrderModel
            {
                $this->recovered[] = [$orderId, $endHourMeter, $actualEndDate, $actorId];
                return (new RentalOrderModel())->setId($orderId);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private RentalOrderService $service) {}

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
            ->setEventName('dispatch.return.completed')
            ->setPayloadJson('{"rentalOrderId":17,"hourMeter":120,"actualEndDate":"2026-08-12","actorId":5}'));

        self::assertSame([[17, 120.0, '2026-08-12', 5]], $service->recovered);
    }

    public function test_swap_event_swaps_rental_order_generator(): void
    {
        $service = new class extends RentalOrderService {
            public array $swapped = [];

            public function swapGeneratorFromDispatch(
                int $orderId,
                int $oldGeneratorId,
                int $newGeneratorId,
                float $oldHourMeter,
                float $newHourMeter,
                ?string $swapDate = null,
                ?int $actorId = null
            ): RentalOrderModel {
                $this->swapped[] = [
                    $orderId,
                    $oldGeneratorId,
                    $newGeneratorId,
                    $oldHourMeter,
                    $newHourMeter,
                    $swapDate,
                    $actorId,
                ];
                return (new RentalOrderModel())->setId($orderId)->setGeneratorId($newGeneratorId);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private RentalOrderService $service) {}

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
            ->setEventName('dispatch.swap.completed')
            ->setPayloadJson('{"rentalOrderId":17,"generatorId":7,"oldGeneratorId":7,"newGeneratorId":8,"oldHourMeter":250,"newHourMeter":12.5,"actualEndDate":"2026-08-12","actorId":5}'));

        self::assertSame([[17, 7, 8, 250.0, 12.5, '2026-08-12', 5]], $service->swapped);
    }
}
