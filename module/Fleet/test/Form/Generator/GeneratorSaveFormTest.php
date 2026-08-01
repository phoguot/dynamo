<?php

declare(strict_types=1);

namespace FleetTest\Form\Generator;

use ApplicationTest\Form\FormTestCase;
use Fleet\Form\Generator\GeneratorSaveForm;
use Fleet\Form\Generator\GeneratorSearchForm;
use Fleet\Form\Generator\GeneratorStatusForm;

/**
 * Luật validate của form máy phát điện.
 *
 * Ở đây chỉ test luật HÌNH THỨC — luật cần truy vấn DB (trùng mã máy, trùng serial) thuộc
 * GeneratorService và được test riêng cùng mapper.
 */
class GeneratorSaveFormTest extends FormTestCase
{
    /** @param array<string, mixed> $overrides */
    private function saveForm(array $overrides = []): GeneratorSaveForm
    {
        $form = new GeneratorSaveForm($this->container);
        $form->setData($overrides + [
            'csrfToken'   => $this->auth->getCsrfToken('fleet.generator.save'),
            'code'        => 'MP-001',
            'name'        => 'Máy phát 100kVA',
            'capacityKva' => 100,
            'fuelType'    => 'diesel',
        ]);

        return $form;
    }

    public function test_du_lieu_du_va_dung_thi_hop_le(): void
    {
        $form = $this->saveForm();

        self::assertTrue($form->isValid(), json_encode($form->getMessagesArr(), JSON_UNESCAPED_UNICODE));
    }

    public function test_ma_may_duoc_chuyen_thanh_chu_in_hoa(): void
    {
        $form = $this->saveForm(['code' => 'mp-001']);
        $form->isValid();

        self::assertSame('MP-001', $form->getData()['code']);
    }

    public function test_khong_cho_ma_may_co_khoang_trang_hoac_ky_tu_la(): void
    {
        $form = $this->saveForm(['code' => 'MP 001!']);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('code', $form->getMessagesArr());
    }

    public function test_khong_cho_cong_suat_bang_khong(): void
    {
        $form = $this->saveForm(['capacityKva' => 0]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('capacityKva', $form->getMessagesArr());
    }

    public function test_khong_cho_nhien_lieu_ngoai_danh_sach(): void
    {
        $form = $this->saveForm(['fuelType' => 'than_da']);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('fuelType', $form->getMessagesArr());
    }

    public function test_khong_cho_toa_do_le_mot_nua(): void
    {
        $form = $this->saveForm(['latitude' => '10.762622']);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('latitude', $form->getMessagesArr());
    }

    public function test_toa_do_du_cap_thi_hop_le(): void
    {
        $form = $this->saveForm(['latitude' => '10.762622', 'longitude' => '106.660172']);

        self::assertTrue($form->isValid(), json_encode($form->getMessagesArr(), JSON_UNESCAPED_UNICODE));
    }

    public function test_the_html_trong_ten_may_bi_go_bo(): void
    {
        $form = $this->saveForm(['name' => 'Máy <b>100</b> kVA']);
        $form->isValid();

        self::assertStringNotContainsString('<b>', $form->getData()['name']);
    }

    public function test_gia_tri_vua_go_duoc_giu_lai_de_render_lai_form(): void
    {
        $form = $this->saveForm(['code' => 'MP 001!']);
        $form->isValid();

        // Form báo lỗi thì người dùng không phải gõ lại từ đầu.
        self::assertSame('MP 001!', $form->getSubmittedValue('code'));
    }

    public function test_gia_tri_nguoi_dung_go_thang_gia_tri_cu_cua_ban_ghi(): void
    {
        $form = $this->saveForm(['code' => 'MP-999']);
        $form->fill(['code' => 'MP-001', 'note' => 'Ghi chú cũ']);

        self::assertSame('MP-999', $form->getSubmittedValue('code'));
        self::assertSame('Ghi chú cũ', $form->getSubmittedValue('note'));
    }
}
