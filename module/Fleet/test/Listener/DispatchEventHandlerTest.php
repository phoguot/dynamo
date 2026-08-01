<?php

declare(strict_types=1);

namespace FleetTest\Listener;

use Fleet\Listener\DispatchEventHandler;
use Fleet\Model\Generator\GeneratorConst;
use Fleet\Model\Generator\GeneratorModel;
use Fleet\Service\GeneratorService;
use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Psr\Container\ContainerInterface;

class DispatchEventHandlerTest extends TestCase
{
    public function test_handover_event_updates_hour_meter_and_moves_generator_to_renting(): void
    {
        $service = new class extends GeneratorService {
            public string $status = GeneratorConst::STATUS_SAN_SANG;
            public array $statuses = [];
            public array $hours = [];

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())->setId($id)->setStatus($this->status);
            }

            public function updateHourMeter(int $id, float $hourMeter, ?int $actorId = null): GeneratorModel
            {
                $this->hours[] = [$id, $hourMeter, $actorId];
                return $this->getGenerator($id)->setHourMeter($hourMeter);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = [$id, $toStatus, $actorId];
                $this->status = $toStatus;
                return $this->getGenerator($id);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private GeneratorService $service) {}

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
            ->setPayloadJson('{"generatorId":7,"hourMeter":123.5,"actorId":4,"jobId":9}'));

        self::assertSame([[7, 123.5, 4]], $service->hours);
        self::assertSame([
            [7, GeneratorConst::STATUS_DANG_GIU_CHO, 4],
            [7, GeneratorConst::STATUS_DANG_VAN_CHUYEN, 4],
            [7, GeneratorConst::STATUS_DANG_THUE, 4],
        ], $service->statuses);
    }

    public function test_return_event_moves_generator_back_to_ready(): void
    {
        $service = new class extends GeneratorService {
            public string $status = GeneratorConst::STATUS_DANG_THUE;
            public array $statuses = [];
            public array $hours = [];

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())->setId($id)->setStatus($this->status)->setHourMeter(200.0);
            }

            public function updateHourMeter(int $id, float $hourMeter, ?int $actorId = null): GeneratorModel
            {
                $this->hours[] = [$id, $hourMeter, $actorId];
                return $this->getGenerator($id)->setHourMeter($hourMeter);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = $toStatus;
                $this->status = $toStatus;
                return $this->getGenerator($id);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private GeneratorService $service) {}

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
            ->setPayloadJson('{"generatorId":7,"hourMeter":150,"actorId":4,"jobId":10}'));

        self::assertSame([
            GeneratorConst::STATUS_DANG_VAN_CHUYEN,
            GeneratorConst::STATUS_SAN_SANG,
        ], $service->statuses);
    }

    public function test_swap_start_event_moves_new_generator_to_transport(): void
    {
        $service = new class extends GeneratorService {
            public array $statusesById = [
                7 => GeneratorConst::STATUS_DANG_THUE,
                8 => GeneratorConst::STATUS_SAN_SANG,
            ];
            public array $statuses = [];

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())
                    ->setId($id)
                    ->setStatus($this->statusesById[$id] ?? GeneratorConst::STATUS_SAN_SANG);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = [$id, $toStatus, $actorId];
                $this->statusesById[$id] = $toStatus;
                return $this->getGenerator($id);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private GeneratorService $service) {}

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
            ->setEventName('dispatch.job.started')
            ->setPayloadJson('{"jobType":"doi_may","generatorId":7,"newGeneratorId":8,"actorId":4,"jobId":11}'));

        self::assertSame([
            [8, GeneratorConst::STATUS_DANG_GIU_CHO, 4],
            [8, GeneratorConst::STATUS_DANG_VAN_CHUYEN, 4],
        ], $service->statuses);
        self::assertSame(GeneratorConst::STATUS_DANG_THUE, $service->statusesById[7]);
    }

    public function test_swap_event_updates_old_and_new_generators(): void
    {
        $service = new class extends GeneratorService {
            public array $statusesById = [
                7 => GeneratorConst::STATUS_DANG_THUE,
                8 => GeneratorConst::STATUS_DANG_VAN_CHUYEN,
            ];
            public array $hoursById = [
                7 => 200.0,
                8 => 10.0,
            ];
            public array $statuses = [];
            public array $hours = [];

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())
                    ->setId($id)
                    ->setStatus($this->statusesById[$id] ?? GeneratorConst::STATUS_SAN_SANG)
                    ->setHourMeter($this->hoursById[$id] ?? 0.0);
            }

            public function updateHourMeter(int $id, float $hourMeter, ?int $actorId = null): GeneratorModel
            {
                $this->hours[] = [$id, $hourMeter, $actorId];
                $this->hoursById[$id] = $hourMeter;
                return $this->getGenerator($id);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = [$id, $toStatus, $actorId];
                $this->statusesById[$id] = $toStatus;
                return $this->getGenerator($id);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private GeneratorService $service) {}

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
            ->setPayloadJson('{"generatorId":7,"oldGeneratorId":7,"newGeneratorId":8,"oldHourMeter":250,"newHourMeter":12.5,"actorId":4,"jobId":12}'));

        self::assertSame([[7, 250.0, 4], [8, 12.5, 4]], $service->hours);
        self::assertSame([
            [7, GeneratorConst::STATUS_DANG_VAN_CHUYEN, 4],
            [7, GeneratorConst::STATUS_SAN_SANG, 4],
            [8, GeneratorConst::STATUS_DANG_THUE, 4],
        ], $service->statuses);
    }

    public function test_handover_event_is_idempotent_when_generator_already_renting(): void
    {
        $service = new class extends GeneratorService {
            public string $status = GeneratorConst::STATUS_DANG_THUE;
            public array $statuses = [];
            public array $hours = [];

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())->setId($id)->setStatus($this->status)->setHourMeter(200.0);
            }

            public function updateHourMeter(int $id, float $hourMeter, ?int $actorId = null): GeneratorModel
            {
                $this->hours[] = [$id, $hourMeter, $actorId];
                return $this->getGenerator($id)->setHourMeter($hourMeter);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = $toStatus;
                return $this->getGenerator($id);
            }
        };

        $handler = new DispatchEventHandler();
        $handler->setContainer(new class($service) implements ContainerInterface {
            public function __construct(private GeneratorService $service) {}

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
            ->setPayloadJson('{"generatorId":7,"hourMeter":123.5,"actorId":4,"jobId":9}'));

        self::assertSame([], $service->statuses);
        self::assertSame([], $service->hours);
    }
}
