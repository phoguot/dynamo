<?php

declare(strict_types=1);

namespace MaintenanceTest\Service;

use Application\Model\AppConst;
use Application\Service\AuthContextService;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;
use Maintenance\Model\MaintenanceJob\MaintenanceJobMapper;
use Maintenance\Model\MaintenanceJob\MaintenanceJobModel;
use Maintenance\Model\PartUsed\PartUsedMapper;
use Maintenance\Service\MaintenanceJobService;
use PHPUnit\Framework\TestCase;
use Platform\Model\OutboxEvent\OutboxEventModel;
use Platform\Service\OutboxEventService;
use Psr\Container\ContainerInterface;
use User\Service\AuditLogService;

class MaintenanceJobStatusEventTest extends TestCase
{
    public function test_change_status_records_maintenance_status_changed_event(): void
    {
        $job = (new MaintenanceJobModel())
            ->setId(21)
            ->setJobNo('MNT-21')
            ->setGeneratorId(7)
            ->setJobType(MaintenanceJobConst::TYPE_REPAIR)
            ->setStatus(MaintenanceJobConst::STATUS_SCHEDULED)
            ->setScheduledDate('2026-08-01');
        $mapper = new class($job) extends MaintenanceJobMapper {
            public array $updates = [];

            public function __construct(private MaintenanceJobModel $job) {}

            public function getMaintenanceJob(int $id): ?MaintenanceJobModel
            {
                return $id === 21 ? $this->job : null;
            }

            public function updateAttrsMaintenanceJob(int $id, array $data, ?int $actorId = null): bool
            {
                $this->updates[] = [$id, $data, $actorId];
                if (isset($data['status'])) {
                    $this->job->setStatus((string)$data['status']);
                }
                if (isset($data['startedAt'])) {
                    $this->job->setStartedAt((string)$data['startedAt']);
                }

                return true;
            }

            public function transactional(callable $fn): mixed
            {
                return $fn();
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
        $auth = new class extends AuthContextService {
            public function getUserId(): ?int
            {
                return 9;
            }

            public function getCsrfToken(string $formName = AppConst::CSRF_FORM_DEFAULT): string
            {
                return 'token-' . $formName;
            }

            public function isValidCsrfToken(?string $token, string $formName = AppConst::CSRF_FORM_DEFAULT): bool
            {
                return $token === $this->getCsrfToken($formName);
            }
        };
        $container = new class($mapper, $outbox, $auth) implements ContainerInterface {
            public function __construct(
                private MaintenanceJobMapper $mapper,
                private OutboxEventService $outbox,
                private AuthContextService $auth
            ) {}

            public function get(string $id)
            {
                return match ($id) {
                    MaintenanceJobMapper::class => $this->mapper,
                    PartUsedMapper::class => new class extends PartUsedMapper {},
                    AuditLogService::class => new class extends AuditLogService {
                        public array $writes = [];

                        public function write(string $action, string $objectType, ?int $objectId, mixed $before = null, mixed $after = null, ?int $actorId = null): void
                        {
                            $this->writes[] = [$action, $objectType, $objectId];
                        }
                    },
                    OutboxEventService::class => $this->outbox,
                    AuthContextService::class => $this->auth,
                    default => null,
                };
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        $service = new MaintenanceJobService();
        $service->setContainer($container);
        $updated = $service->changeStatus([
            AppConst::FIELD_CSRF_TOKEN => $auth->getCsrfToken('maintenance.job.status'),
            'id' => 21,
            'status' => MaintenanceJobConst::STATUS_WORKING,
        ]);

        self::assertSame(MaintenanceJobConst::STATUS_WORKING, $updated->getStatus());
        self::assertSame([[21, ['status' => MaintenanceJobConst::STATUS_WORKING, 'startedAt' => $updated->getStartedAt()], 9]], $mapper->updates);
        self::assertSame(MaintenanceJobConst::EVENT_STATUS_CHANGED, $outbox->events[0][0]);
        self::assertSame(21, $outbox->events[0][1]);
        self::assertSame(7, $outbox->events[0][2]['generatorId']);
        self::assertSame(MaintenanceJobConst::TYPE_REPAIR, $outbox->events[0][2]['jobType']);
        self::assertSame(MaintenanceJobConst::STATUS_SCHEDULED, $outbox->events[0][2]['fromStatus']);
        self::assertSame(MaintenanceJobConst::STATUS_WORKING, $outbox->events[0][2]['toStatus']);
        self::assertSame(9, $outbox->events[0][2]['actorId']);
    }
}
