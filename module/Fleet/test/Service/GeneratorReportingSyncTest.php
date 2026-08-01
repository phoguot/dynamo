<?php

declare(strict_types=1);

namespace FleetTest\Service;

use Fleet\Model\Generator\GeneratorMapper;
use Fleet\Service\GeneratorService;
use PHPUnit\Framework\TestCase;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Service\ReportingService;

class GeneratorReportingSyncTest extends TestCase
{
    public function test_sync_fleet_utilization_daily_builds_company_snapshot_from_status_counts(): void
    {
        $mapper = new class extends GeneratorMapper {
            public function summarizeStatusCounts(): array
            {
                return [
                    'totalGenerators' => 7,
                    'availableCount' => 2,
                    'heldCount' => 1,
                    'transitCount' => 1,
                    'rentedCount' => 2,
                    'maintenanceCount' => 0,
                    'repairCount' => 0,
                    'retiredCount' => 1,
                ];
            }
        };
        $reporting = new class extends ReportingService {
            public ?FleetUtilizationDailyModel $synced = null;

            public function syncFleetUtilizationDaily(FleetUtilizationDailyModel $item): FleetUtilizationDailyModel
            {
                $this->synced = $item;

                return $item->setId(9);
            }
        };
        $service = new class($mapper, $reporting) extends GeneratorService {
            public function __construct(private GeneratorMapper $mapper, private ReportingService $reporting) {}

            public function getContainerEntry(string $entryName)
            {
                return match ($entryName) {
                    GeneratorMapper::class => $this->mapper,
                    ReportingService::class => $this->reporting,
                    default => null,
                };
            }
        };

        $synced = $service->syncFleetUtilizationDaily('2026-07-31');

        self::assertSame(9, $synced?->getId());
        self::assertSame('2026-07-31', $reporting->synced?->getReportDate());
        self::assertNull($reporting->synced?->getWarehouseCode());
        self::assertSame(7, $reporting->synced?->getTotalGenerators());
        self::assertSame(6, $reporting->synced?->getActiveGenerators());
        self::assertSame(2, $reporting->synced?->getRentedCount());
        self::assertSame(1, $reporting->synced?->getRetiredCount());
    }
}
