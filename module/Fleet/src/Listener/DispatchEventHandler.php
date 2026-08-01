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

class DispatchEventHandler extends AppServiceFactory implements OutboxEventHandlerInterface
{
    private const string EVENT_JOB_STARTED = 'dispatch.job.started';
    private const string EVENT_HANDOVER_COMPLETED = 'dispatch.handover.completed';
    private const string EVENT_RETURN_COMPLETED = 'dispatch.return.completed';
    private const string EVENT_SWAP_COMPLETED = 'dispatch.swap.completed';

    public function handle(OutboxEventModel $event): void
    {
        $payload = $event->getPayload();
        $actorId = $this->optionalInt($payload, 'actorId');
        $reason = sprintf('dispatch event %s job #%d', (string)$event->getEventName(), $this->optionalInt($payload, 'jobId') ?? 0);

        match ((string)$event->getEventName()) {
            self::EVENT_JOB_STARTED => $this->startJob($payload, $reason, $actorId),
            self::EVENT_HANDOVER_COMPLETED => $this->completeHandover(
                $this->requiredInt($payload, 'generatorId'),
                $this->requiredFloat($payload, 'hourMeter'),
                $reason,
                $actorId
            ),
            self::EVENT_RETURN_COMPLETED => $this->completeReturn(
                $this->requiredInt($payload, 'generatorId'),
                $this->requiredFloat($payload, 'hourMeter'),
                $reason,
                $actorId
            ),
            self::EVENT_SWAP_COMPLETED => $this->completeSwap($payload, $reason, $actorId),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function startJob(array $payload, string $reason, ?int $actorId): void
    {
        $generatorId = (string)($payload['jobType'] ?? '') === 'doi_may'
            ? $this->requiredInt($payload, 'newGeneratorId')
            : $this->requiredInt($payload, 'generatorId');

        $this->moveGenerator($generatorId, GeneratorConst::STATUS_DANG_VAN_CHUYEN, $reason, $actorId);
    }

    private function completeHandover(int $generatorId, float $hourMeter, string $reason, ?int $actorId): void
    {
        if ($this->isAlreadyApplied($generatorId, GeneratorConst::STATUS_DANG_THUE, $hourMeter)) {
            return;
        }

        $this->generator()->updateHourMeter($generatorId, $hourMeter, $actorId);
        $this->moveGenerator($generatorId, GeneratorConst::STATUS_DANG_THUE, $reason, $actorId);
    }

    private function completeReturn(int $generatorId, float $hourMeter, string $reason, ?int $actorId): void
    {
        if ($this->isAlreadyApplied($generatorId, GeneratorConst::STATUS_SAN_SANG, $hourMeter)) {
            return;
        }

        $this->generator()->updateHourMeter($generatorId, $hourMeter, $actorId);
        $this->moveGenerator($generatorId, GeneratorConst::STATUS_SAN_SANG, $reason, $actorId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function completeSwap(array $payload, string $reason, ?int $actorId): void
    {
        $oldGeneratorId = $this->optionalInt($payload, 'oldGeneratorId')
            ?? $this->requiredInt($payload, 'generatorId');
        $newGeneratorId = $this->requiredInt($payload, 'newGeneratorId');
        if ($oldGeneratorId === $newGeneratorId) {
            throw new ValidationException(['newGeneratorId' => 'New generator must be different from old generator.']);
        }

        $oldHourMeter = $this->requiredFloat($payload, 'oldHourMeter');
        $newHourMeter = $this->requiredFloat($payload, 'newHourMeter');

        if (!$this->isAlreadyApplied($oldGeneratorId, GeneratorConst::STATUS_SAN_SANG, $oldHourMeter)) {
            $this->generator()->updateHourMeter($oldGeneratorId, $oldHourMeter, $actorId);
            $this->moveGenerator($oldGeneratorId, GeneratorConst::STATUS_SAN_SANG, $reason, $actorId);
        }

        if (!$this->isAlreadyApplied($newGeneratorId, GeneratorConst::STATUS_DANG_THUE, $newHourMeter)) {
            $this->generator()->updateHourMeter($newGeneratorId, $newHourMeter, $actorId);
            $this->moveGenerator($newGeneratorId, GeneratorConst::STATUS_DANG_THUE, $reason, $actorId);
        }
    }

    private function isAlreadyApplied(int $generatorId, string $targetStatus, float $hourMeter): bool
    {
        $generator = $this->generator()->getGenerator($generatorId);

        return $generator->getStatus() === $targetStatus
            && (float)$generator->getHourMeter() >= $hourMeter;
    }

    private function moveGenerator(int $generatorId, string $targetStatus, string $reason, ?int $actorId): void
    {
        $service = $this->generator();
        $current = (string)$service->getGenerator($generatorId)->getStatus();
        if ($current === $targetStatus) {
            return;
        }

        $path = $this->statusPath($targetStatus);
        $index = array_search($current, $path, true);
        if ($index === false) {
            $service->changeStatusFromSystem($generatorId, $targetStatus, $reason, $actorId);
            return;
        }

        for ($i = $index + 1, $count = count($path); $i < $count; $i++) {
            $service->changeStatusFromSystem($generatorId, $path[$i], $reason, $actorId);
        }
    }

    /**
     * @return list<string>
     */
    private function statusPath(string $targetStatus): array
    {
        return match ($targetStatus) {
            GeneratorConst::STATUS_DANG_VAN_CHUYEN => [
                GeneratorConst::STATUS_SAN_SANG,
                GeneratorConst::STATUS_DANG_GIU_CHO,
                GeneratorConst::STATUS_DANG_VAN_CHUYEN,
            ],
            GeneratorConst::STATUS_DANG_THUE => [
                GeneratorConst::STATUS_SAN_SANG,
                GeneratorConst::STATUS_DANG_GIU_CHO,
                GeneratorConst::STATUS_DANG_VAN_CHUYEN,
                GeneratorConst::STATUS_DANG_THUE,
            ],
            GeneratorConst::STATUS_SAN_SANG => [
                GeneratorConst::STATUS_DANG_THUE,
                GeneratorConst::STATUS_DANG_VAN_CHUYEN,
                GeneratorConst::STATUS_SAN_SANG,
            ],
            default => [$targetStatus],
        };
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
    private function requiredFloat(array $payload, string $key): float
    {
        if (!isset($payload[$key]) || $payload[$key] === '' || !is_numeric($payload[$key])) {
            throw new ValidationException([$key => 'Missing or invalid numeric payload value.']);
        }

        return (float)$payload[$key];
    }
}
