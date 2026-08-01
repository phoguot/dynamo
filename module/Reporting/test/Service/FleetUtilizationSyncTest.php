<?php

declare(strict_types=1);

namespace ReportingTest\Service;

use PHPUnit\Framework\TestCase;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyMapper;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Service\ReportingService;

class FleetUtilizationSyncTest extends TestCase
{
    public function test_sync_fleet_utilization_daily_computes_rate_and_saves_row(): void
    {
        $mapper = new class extends FleetUtilizationDailyMapper {
            public ?FleetUtilizationDailyModel $saved = null;

            public function saveDaily(FleetUtilizationDailyModel $item): FleetUtilizationDailyModel
            {
                $this->saved = $item;

                return $item->setId(5);
            }
        };
        $service = new class($mapper) extends ReportingService {
            public function __construct(private FleetUtilizationDailyMapper $mapper) {}

            public function getContainerEntry(string $entryName)
            {
                return $entryName === FleetUtilizationDailyMapper::class ? $this->mapper : null;
            }
        };

        $saved = $service->syncFleetUtilizationDaily((new FleetUtilizationDailyModel())
            ->setReportDate('2026-07-31')
            ->setTotalGenerators(5)
            ->setActiveGenerators(4)
            ->setRentedCount(3)
            ->setAvailableCount(1)
            ->setRetiredCount(1));

        self::assertSame(5, $saved->getId());
        self::assertSame(75.0, $saved->getUtilizationRate());
        self::assertNotNull($saved->getComputedAt());
        self::assertSame($saved, $mapper->saved);
    }
}
