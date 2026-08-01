<?php

declare(strict_types=1);

namespace FleetTest\Listener;

use Fleet\Listener\MaintenanceEventHandler;
use Fleet\Model\Generator\GeneratorConst;
use Fleet\Model\Generator\GeneratorModel;
use Fleet\Service\GeneratorService;
use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Psr\Container\ContainerInterface;

class MaintenanceEventHandlerTest extends TestCase
{
    public function test_started_maintenance_job_moves_generator_to_maintenance(): void
    {
        $service = $this->fakeGeneratorService(GeneratorConst::STATUS_SAN_SANG);
        $handler = $this->handler($service);

        $handler->handle((new OutboxEventModel())
            ->setEventName('maintenance.job.status_changed')
            ->setPayloadJson('{"jobId":21,"generatorId":7,"jobType":"bao_tri","fromStatus":"da_len_lich","toStatus":"dang_thuc_hien","actorId":4}'));

        self::assertSame([[7, GeneratorConst::STATUS_DANG_BAO_TRI, 4]], $service->statuses);
    }

    public function test_started_repair_job_moves_generator_to_repair(): void
    {
        $service = $this->fakeGeneratorService(GeneratorConst::STATUS_DANG_THUE);
        $handler = $this->handler($service);

        $handler->handle((new OutboxEventModel())
            ->setEventName('maintenance.job.status_changed')
            ->setPayloadJson('{"jobId":22,"generatorId":7,"jobType":"sua_chua","fromStatus":"da_len_lich","toStatus":"dang_thuc_hien","actorId":4}'));

        self::assertSame([[7, GeneratorConst::STATUS_DANG_SUA_CHUA, 4]], $service->statuses);
    }

    public function test_completed_working_job_moves_generator_back_to_ready(): void
    {
        $service = $this->fakeGeneratorService(GeneratorConst::STATUS_DANG_BAO_TRI);
        $handler = $this->handler($service);

        $handler->handle((new OutboxEventModel())
            ->setEventName('maintenance.job.status_changed')
            ->setPayloadJson('{"jobId":23,"generatorId":7,"jobType":"bao_tri","fromStatus":"dang_thuc_hien","toStatus":"hoan_thanh","actorId":4}'));

        self::assertSame([[7, GeneratorConst::STATUS_SAN_SANG, 4]], $service->statuses);
    }

    public function test_non_operational_status_change_is_ignored(): void
    {
        $service = $this->fakeGeneratorService(GeneratorConst::STATUS_SAN_SANG);
        $handler = $this->handler($service);

        $handler->handle((new OutboxEventModel())
            ->setEventName('maintenance.job.status_changed')
            ->setPayloadJson('{"jobId":24,"generatorId":7,"jobType":"bao_tri","fromStatus":"cho_lich","toStatus":"da_len_lich","actorId":4}'));

        self::assertSame([], $service->statuses);
    }

    private function fakeGeneratorService(string $initialStatus): GeneratorService
    {
        return new class($initialStatus) extends GeneratorService {
            public string $status;
            public array $statuses = [];

            public function __construct(string $initialStatus)
            {
                $this->status = $initialStatus;
            }

            public function getGenerator(int $id): GeneratorModel
            {
                return (new GeneratorModel())->setId($id)->setStatus($this->status);
            }

            public function changeStatusFromSystem(int $id, string $toStatus, ?string $reason = null, ?int $actorId = null): GeneratorModel
            {
                $this->statuses[] = [$id, $toStatus, $actorId];
                $this->status = $toStatus;

                return $this->getGenerator($id);
            }
        };
    }

    private function handler(GeneratorService $service): MaintenanceEventHandler
    {
        $handler = new MaintenanceEventHandler();
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

        return $handler;
    }
}
