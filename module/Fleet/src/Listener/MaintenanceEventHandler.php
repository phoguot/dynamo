<?php

declare(strict_types=1);

namespace Fleet\Listener;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Fleet\Model\Generator\GeneratorConst;
use Fleet\Service\GeneratorService;
use Platform\Event\OutboxEventHandlerInterface;
use Platform\Model\OutboxEvent\OutboxEventModel;
use RuntimeException;

class MaintenanceEventHandler extends AppServiceFactory implements OutboxEventHandlerInterface
{
    private const string EVENT_STATUS_CHANGED = 'maintenance.job.status_changed';

    private const string JOB_TYPE_REPAIR = 'sua_chua';

    private const string JOB_STATUS_WORKING = 'dang_thuc_hien';
    private const string JOB_STATUS_COMPLETED = 'hoan_thanh';
    private const string JOB_STATUS_CANCELLED = 'da_huy';

    public function handle(OutboxEventModel $event): void
    {
        if ((string)$event->getEventName() !== self::EVENT_STATUS_CHANGED) {
            return;
        }

        $payload = $event->getPayload();
        $targetStatus = $this->targetGeneratorStatus($payload);
        if ($targetStatus === null) {
            return;
        }

        $generatorId = $this->requiredInt($payload, 'generatorId');
        $actorId = $this->optionalInt($payload, 'actorId');
        $reason = sprintf('maintenance event %s job #%d', (string)$event->getEventName(), $this->optionalInt($payload, 'jobId') ?? 0);

        $this->moveGenerator($generatorId, $targetStatus, $reason, $actorId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function targetGeneratorStatus(array $payload): ?string
    {
        $fromStatus = $this->requiredString($payload, 'fromStatus');
        $toStatus = $this->requiredString($payload, 'toStatus');

        if ($toStatus === self::JOB_STATUS_WORKING) {
            return $this->requiredString($payload, 'jobType') === self::JOB_TYPE_REPAIR
                ? GeneratorConst::STATUS_DANG_SUA_CHUA
                : GeneratorConst::STATUS_DANG_BAO_TRI;
        }

        if (
            $fromStatus === self::JOB_STATUS_WORKING
            && in_array($toStatus, [self::JOB_STATUS_COMPLETED, self::JOB_STATUS_CANCELLED], true)
        ) {
            return GeneratorConst::STATUS_SAN_SANG;
        }

        return null;
    }

    private function moveGenerator(int $generatorId, string $targetStatus, string $reason, ?int $actorId): void
    {
        $service = $this->generator();
        if ($service->getGenerator($generatorId)->getStatus() === $targetStatus) {
            return;
        }

        $service->changeStatusFromSystem($generatorId, $targetStatus, $reason, $actorId);
    }

    private function generator(): GeneratorService
    {
        $service = $this->getContainerEntry(GeneratorService::class);
        if (!$service instanceof GeneratorService) {
            throw new RuntimeException('GeneratorService is not registered.');
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
    private function requiredString(array $payload, string $key): string
    {
        if (!isset($payload[$key])) {
            throw new ValidationException([$key => 'Missing payload value.']);
        }

        $value = is_string($payload[$key]) ? trim($payload[$key]) : (string)$payload[$key];
        if ($value === '') {
            throw new ValidationException([$key => 'Missing payload value.']);
        }

        return $value;
    }
}
