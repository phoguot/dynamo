<?php

declare(strict_types=1);

namespace MaintenanceTest\Model;

use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;
use Maintenance\Model\PartUsed\PartUsedConst;
use Maintenance\Model\Schedule\ScheduleConst;
use PHPUnit\Framework\TestCase;

class MaintenanceStateMachineTest extends TestCase
{
    public function test_moi_trang_thai_phieu_bao_tri_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(MaintenanceJobConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, MaintenanceJobConst::STATUS_LABELS, $status . ' thieu nhan');
        }

        self::assertSame(array_keys(MaintenanceJobConst::STATUS_LABELS), array_keys(MaintenanceJobConst::STATUS_TRANSITIONS));
    }

    public function test_maintenance_job_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_WAITING_SCHEDULE, MaintenanceJobConst::STATUS_SCHEDULED));
        self::assertTrue(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_WAITING_SCHEDULE, MaintenanceJobConst::STATUS_CANCELLED));
        self::assertTrue(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_SCHEDULED, MaintenanceJobConst::STATUS_WORKING));
        self::assertTrue(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_WORKING, MaintenanceJobConst::STATUS_COMPLETED));
    }

    public function test_maintenance_job_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_WAITING_SCHEDULE, MaintenanceJobConst::STATUS_WORKING));
        self::assertFalse(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_SCHEDULED, MaintenanceJobConst::STATUS_COMPLETED));
        self::assertFalse(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_COMPLETED, MaintenanceJobConst::STATUS_CANCELLED));
        self::assertFalse(MaintenanceJobConst::canTransit(MaintenanceJobConst::STATUS_CANCELLED, MaintenanceJobConst::STATUS_SCHEDULED));
    }

    public function test_enum_bao_tri_co_day_du_gia_tri_schema(): void
    {
        self::assertSame('maintenance.job.status_changed', MaintenanceJobConst::EVENT_STATUS_CHANGED);
        self::assertSame(['gio_may', 'ngay', 'ca_hai'], array_keys(ScheduleConst::TYPE_LABELS));
        self::assertSame(['bao_tri', 'sua_chua', 'kiem_tra_sau_thue'], array_keys(MaintenanceJobConst::TYPE_LABELS));
        self::assertSame(['thap', 'binh_thuong', 'cao', 'khan'], array_keys(MaintenanceJobConst::PRIORITY_LABELS));
        self::assertSame(['cai', 'bo', 'lit', 'kg'], array_keys(PartUsedConst::UNIT_LABELS));
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(ScheduleConst::SORT_DEFAULT, ScheduleConst::SORT_MAP);
        self::assertArrayHasKey(MaintenanceJobConst::SORT_DEFAULT, MaintenanceJobConst::SORT_MAP);
        self::assertArrayNotHasKey('j.jobNo; DROP TABLE mnt_jobs', MaintenanceJobConst::SORT_MAP);
        self::assertArrayNotHasKey('s.generatorId; DROP TABLE mnt_schedules', ScheduleConst::SORT_MAP);
    }
}
