<?php

declare(strict_types=1);

namespace DispatchTest\Model;

use Dispatch\Model\Assignment\AssignmentConst;
use Dispatch\Model\DispatchJob\DispatchJobConst;
use Dispatch\Model\Vehicle\VehicleConst;
use PHPUnit\Framework\TestCase;

class DispatchStateMachineTest extends TestCase
{
    public function test_moi_trang_thai_xe_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(VehicleConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, VehicleConst::STATUS_LABELS, $status . ' thieu nhan');
        }

        self::assertSame(array_keys(VehicleConst::STATUS_LABELS), array_keys(VehicleConst::STATUS_TRANSITIONS));
    }

    public function test_vehicle_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(VehicleConst::canTransit(VehicleConst::STATUS_READY, VehicleConst::STATUS_RUNNING));
        self::assertTrue(VehicleConst::canTransit(VehicleConst::STATUS_READY, VehicleConst::STATUS_MAINTENANCE));
        self::assertTrue(VehicleConst::canTransit(VehicleConst::STATUS_RUNNING, VehicleConst::STATUS_READY));
        self::assertTrue(VehicleConst::canTransit(VehicleConst::STATUS_MAINTENANCE, VehicleConst::STATUS_STOPPED));
    }

    public function test_vehicle_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(VehicleConst::canTransit(VehicleConst::STATUS_STOPPED, VehicleConst::STATUS_READY));
        self::assertFalse(VehicleConst::canTransit(VehicleConst::STATUS_RUNNING, VehicleConst::STATUS_STOPPED));
        self::assertFalse(VehicleConst::canTransit(VehicleConst::STATUS_MAINTENANCE, VehicleConst::STATUS_RUNNING));
    }

    public function test_moi_trang_thai_lenh_dieu_phoi_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(DispatchJobConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, DispatchJobConst::STATUS_LABELS, $status . ' thieu nhan');
        }

        self::assertSame(array_keys(DispatchJobConst::STATUS_LABELS), array_keys(DispatchJobConst::STATUS_TRANSITIONS));
    }

    public function test_dispatch_job_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(DispatchJobConst::canTransit(DispatchJobConst::STATUS_NEW, DispatchJobConst::STATUS_SCHEDULED));
        self::assertTrue(DispatchJobConst::canTransit(DispatchJobConst::STATUS_SCHEDULED, DispatchJobConst::STATUS_ON_ROUTE));
        self::assertTrue(DispatchJobConst::canTransit(DispatchJobConst::STATUS_ON_ROUTE, DispatchJobConst::STATUS_WORKING));
        self::assertTrue(DispatchJobConst::canTransit(DispatchJobConst::STATUS_WORKING, DispatchJobConst::STATUS_COMPLETED));
        self::assertTrue(DispatchJobConst::canTransit(DispatchJobConst::STATUS_FAILED, DispatchJobConst::STATUS_SCHEDULED));
    }

    public function test_dispatch_job_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(DispatchJobConst::canTransit(DispatchJobConst::STATUS_NEW, DispatchJobConst::STATUS_ON_ROUTE));
        self::assertFalse(DispatchJobConst::canTransit(DispatchJobConst::STATUS_SCHEDULED, DispatchJobConst::STATUS_COMPLETED));
        self::assertFalse(DispatchJobConst::canTransit(DispatchJobConst::STATUS_COMPLETED, DispatchJobConst::STATUS_FAILED));
        self::assertFalse(DispatchJobConst::canTransit(DispatchJobConst::STATUS_CANCELLED, DispatchJobConst::STATUS_SCHEDULED));
    }

    public function test_enum_dieu_phoi_co_day_du_gia_tri_schema(): void
    {
        self::assertSame(['giao', 'thu_hoi', 'doi_may'], array_keys(DispatchJobConst::TYPE_LABELS));
        self::assertSame(['thap', 'binh_thuong', 'cao', 'khan'], array_keys(DispatchJobConst::PRIORITY_LABELS));
        self::assertSame(['cong_ty', 'khach_hang'], array_keys(DispatchJobConst::FEE_BEARER_LABELS));
        self::assertSame('dispatch.swap.completed', DispatchJobConst::EVENT_SWAP_COMPLETED);
        self::assertSame(['xe_tai', 'xe_cau', 'xe_ban_tai'], array_keys(VehicleConst::TYPE_LABELS));
        self::assertSame(['ky_thuat', 'tai_xe', 'phu_viec'], array_keys(AssignmentConst::ROLE_LABELS));
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(DispatchJobConst::SORT_DEFAULT, DispatchJobConst::SORT_MAP);
        self::assertArrayHasKey(VehicleConst::SORT_DEFAULT, VehicleConst::SORT_MAP);
        self::assertArrayNotHasKey('j.jobNo; DROP TABLE dsp_jobs', DispatchJobConst::SORT_MAP);
        self::assertArrayNotHasKey('v.code; DROP TABLE dsp_vehicles', VehicleConst::SORT_MAP);
    }
}
