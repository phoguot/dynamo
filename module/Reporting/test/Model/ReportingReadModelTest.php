<?php

declare(strict_types=1);

namespace ReportingTest\Model;

use PHPUnit\Framework\TestCase;
use Reporting\Model\FleetUtilizationDaily\FleetUtilizationDailyModel;
use Reporting\Model\RevenueMonthly\RevenueMonthlyModel;
use Reporting\Model\ReportingConst;

class ReportingReadModelTest extends TestCase
{
    public function test_utilization_rate_tinh_dung_va_chong_chia_cho_0(): void
    {
        self::assertSame(0.0, ReportingConst::utilizationRate(3, 0));
        self::assertSame(75.0, ReportingConst::utilizationRate(3, 4));
        self::assertSame(33.33, ReportingConst::utilizationRate(1, 3));
    }

    public function test_fleet_status_count_total_cong_du_bay_nhom_trang_thai(): void
    {
        $model = (new FleetUtilizationDailyModel())
            ->setAvailableCount(2)
            ->setHeldCount(1)
            ->setTransitCount(3)
            ->setRentedCount(4)
            ->setMaintenanceCount(5)
            ->setRepairCount(6)
            ->setRetiredCount(7);

        self::assertSame(28, $model->getStatusCountTotal());
    }

    public function test_collection_rate_tinh_tren_so_da_phat_hanh(): void
    {
        $model = (new RevenueMonthlyModel())
            ->setInvoicedAmount(400)
            ->setCollectedAmount(125);

        self::assertSame(31.25, $model->getCollectionRate());
        self::assertSame(0.0, (new RevenueMonthlyModel())->getCollectionRate());
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(ReportingConst::FLEET_SORT_DEFAULT, ReportingConst::FLEET_SORT_MAP);
        self::assertArrayHasKey(ReportingConst::REVENUE_SORT_DEFAULT, ReportingConst::REVENUE_SORT_MAP);
        self::assertArrayHasKey(ReportingConst::RECEIVABLES_SORT_DEFAULT, ReportingConst::RECEIVABLES_SORT_MAP);

        self::assertArrayNotHasKey('u.reportDate; DROP TABLE rpt_fleet_utilization_daily', ReportingConst::FLEET_SORT_MAP);
        self::assertArrayNotHasKey('r.periodYear; DROP TABLE rpt_revenue_monthly', ReportingConst::REVENUE_SORT_MAP);
        self::assertArrayNotHasKey('s.snapshotDate; DROP TABLE rpt_receivables_snapshot', ReportingConst::RECEIVABLES_SORT_MAP);
    }
}
