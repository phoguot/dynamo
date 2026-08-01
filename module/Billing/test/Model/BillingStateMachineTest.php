<?php

declare(strict_types=1);

namespace BillingTest\Model;

use Billing\Model\Deposit\DepositConst;
use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\InvoiceLine\InvoiceLineConst;
use Billing\Model\Payment\PaymentConst;
use PHPUnit\Framework\TestCase;

class BillingStateMachineTest extends TestCase
{
    public function test_moi_trang_thai_hoa_don_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(InvoiceConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, InvoiceConst::STATUS_LABELS, $status . ' thieu nhan');
        }

        self::assertSame(array_keys(InvoiceConst::STATUS_LABELS), array_keys(InvoiceConst::STATUS_TRANSITIONS));
    }

    public function test_invoice_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(InvoiceConst::canTransit(InvoiceConst::STATUS_DRAFT, InvoiceConst::STATUS_WAITING_APPROVAL));
        self::assertTrue(InvoiceConst::canTransit(InvoiceConst::STATUS_WAITING_APPROVAL, InvoiceConst::STATUS_ISSUED));
        self::assertTrue(InvoiceConst::canTransit(InvoiceConst::STATUS_ISSUED, InvoiceConst::STATUS_PARTIALLY_PAID));
        self::assertTrue(InvoiceConst::canTransit(InvoiceConst::STATUS_PARTIALLY_PAID, InvoiceConst::STATUS_PAID));
        self::assertTrue(InvoiceConst::canTransit(InvoiceConst::STATUS_ISSUED, InvoiceConst::STATUS_OVERDUE));
    }

    public function test_invoice_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(InvoiceConst::canTransit(InvoiceConst::STATUS_DRAFT, InvoiceConst::STATUS_PAID));
        self::assertFalse(InvoiceConst::canTransit(InvoiceConst::STATUS_WAITING_APPROVAL, InvoiceConst::STATUS_PAID));
        self::assertFalse(InvoiceConst::canTransit(InvoiceConst::STATUS_PAID, InvoiceConst::STATUS_CANCELLED));
        self::assertFalse(InvoiceConst::canTransit(InvoiceConst::STATUS_CANCELLED, InvoiceConst::STATUS_ISSUED));
    }

    public function test_enum_billing_co_day_du_gia_tri_schema(): void
    {
        self::assertSame(
            ['nhap', 'cho_duyet', 'da_phat_hanh', 'da_thanh_toan_mot_phan', 'da_thanh_toan', 'qua_han', 'da_huy'],
            array_keys(InvoiceConst::STATUS_LABELS)
        );
        self::assertSame(
            ['tien_thue', 'van_chuyen', 'lap_dat', 'nhien_lieu', 'qua_gio', 'boi_thuong', 'khac'],
            array_keys(InvoiceLineConst::TYPE_LABELS)
        );
        self::assertSame(['ngay', 'thang', 'lit', 'gio', 'lan'], array_keys(InvoiceLineConst::UNIT_LABELS));
        self::assertSame(['tien_mat', 'chuyen_khoan', 'the', 'bu_tru_coc'], array_keys(PaymentConst::METHOD_LABELS));
        self::assertSame(['da_ghi_nhan', 'da_huy'], array_keys(PaymentConst::STATUS_LABELS));
        self::assertSame(['dang_giu', 'da_hoan_mot_phan', 'da_hoan', 'da_bu_tru'], array_keys(DepositConst::STATUS_LABELS));
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(InvoiceConst::SORT_DEFAULT, InvoiceConst::SORT_MAP);
        self::assertArrayHasKey(PaymentConst::SORT_DEFAULT, PaymentConst::SORT_MAP);
        self::assertArrayHasKey(DepositConst::SORT_DEFAULT, DepositConst::SORT_MAP);
        self::assertArrayNotHasKey('i.invoiceNo; DROP TABLE bil_invoices', InvoiceConst::SORT_MAP);
        self::assertArrayNotHasKey('p.paymentNo; DROP TABLE bil_payments', PaymentConst::SORT_MAP);
        self::assertArrayNotHasKey('d.depositNo; DROP TABLE bil_deposits', DepositConst::SORT_MAP);
    }
}
