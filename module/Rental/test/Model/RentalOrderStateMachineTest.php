<?php

declare(strict_types=1);

namespace RentalTest\Model;

use PHPUnit\Framework\TestCase;
use Rental\Model\RentalOrder\RentalOrderConst;
use Rental\Service\RentalOrderService;

class RentalOrderStateMachineTest extends TestCase
{
    public function test_moi_trang_thai_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(RentalOrderConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, RentalOrderConst::STATUS_LABELS, $status . ' thiếu nhãn');
        }

        self::assertSame(
            array_keys(RentalOrderConst::STATUS_LABELS),
            array_keys(RentalOrderConst::STATUS_TRANSITIONS),
            'Danh sách trạng thái và bảng chuyển tiếp phải khớp nhau'
        );
    }

    public function test_rental_order_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_NEW, RentalOrderConst::STATUS_WAITING_DELIVERY));
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_WAITING_DELIVERY, RentalOrderConst::STATUS_RENTING));
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_RENTING, RentalOrderConst::STATUS_WAITING_RECOVERY));
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_WAITING_RECOVERY, RentalOrderConst::STATUS_RECOVERED));
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_RECOVERED, RentalOrderConst::STATUS_SETTLED));
        self::assertTrue(RentalOrderConst::canTransit(RentalOrderConst::STATUS_NEW, RentalOrderConst::STATUS_CANCELLED));
    }

    public function test_rental_order_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(RentalOrderConst::canTransit(RentalOrderConst::STATUS_NEW, RentalOrderConst::STATUS_RENTING));
        self::assertFalse(RentalOrderConst::canTransit(RentalOrderConst::STATUS_RENTING, RentalOrderConst::STATUS_RECOVERED));
        self::assertFalse(RentalOrderConst::canTransit(RentalOrderConst::STATUS_RECOVERED, RentalOrderConst::STATUS_RENTING));
        self::assertFalse(RentalOrderConst::canTransit(RentalOrderConst::STATUS_SETTLED, RentalOrderConst::STATUS_CANCELLED));
        self::assertFalse(RentalOrderConst::canTransit(RentalOrderConst::STATUS_CANCELLED, RentalOrderConst::STATUS_WAITING_DELIVERY));
    }

    public function test_khoang_ngay_nua_mo_khong_tinh_ngay_tra(): void
    {
        self::assertSame(
            ['2026-08-10', '2026-08-11', '2026-08-12'],
            RentalOrderService::daysInHalfOpenRange('2026-08-10', '2026-08-13')
        );

        self::assertSame(
            [],
            RentalOrderService::daysInHalfOpenRange('2026-08-13', '2026-08-13')
        );
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(RentalOrderConst::SORT_DEFAULT, RentalOrderConst::SORT_MAP);
        self::assertArrayNotHasKey('o.orderNo; DROP TABLE rnt_rental_orders', RentalOrderConst::SORT_MAP);
    }
}
