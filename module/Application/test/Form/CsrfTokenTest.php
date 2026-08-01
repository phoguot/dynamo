<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use ApplicationTest\Form\Fixture\CsrfTestForm;
use ApplicationTest\Form\Fixture\ReadOnlyTestForm;

/**
 * CSRF theo từng form.
 *
 * Hai nhóm ràng buộc đối nghịch nhau, phải đúng cả hai:
 * - Chặt: token của form này không dùng được cho form khác.
 * - Không phiền: token ổn định trong cùng phiên, để mở nhiều tab / bấm Back / submit lại
 *   sau khi form báo lỗi đều không bị đá ra "phiên hết hạn".
 */
class CsrfTokenTest extends FormTestCase
{
    public function test_moi_form_co_token_rieng(): void
    {
        self::assertNotSame(
            $this->auth->getCsrfToken('form.a'),
            $this->auth->getCsrfToken('form.b')
        );
    }

    public function test_token_on_dinh_trong_cung_phien(): void
    {
        // Mở hai tab cùng một form thì cả hai tab phải submit được.
        self::assertSame(
            $this->auth->getCsrfToken('form.a'),
            $this->auth->getCsrfToken('form.a')
        );
    }

    public function test_token_dung_lai_duoc_sau_khi_submit(): void
    {
        $token = $this->auth->getCsrfToken('form.a');

        self::assertTrue($this->auth->isValidCsrfToken($token, 'form.a'));
        // Lần thứ hai vẫn hợp lệ: token không bị tiêu thụ.
        self::assertTrue($this->auth->isValidCsrfToken($token, 'form.a'));
    }

    public function test_khong_dung_token_cua_form_khac(): void
    {
        $token = $this->auth->getCsrfToken('form.a');

        self::assertFalse($this->auth->isValidCsrfToken($token, 'form.b'));
    }

    public function test_token_rong_bi_tu_choi(): void
    {
        self::assertFalse($this->auth->isValidCsrfToken(null, 'form.a'));
        self::assertFalse($this->auth->isValidCsrfToken('', 'form.a'));
    }

    public function test_form_tu_chan_khi_thieu_token(): void
    {
        $form = new CsrfTestForm($this->container);
        $form->setData(['name' => 'Nguyễn Văn A']);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('csrfToken', $form->getMessagesArr());
    }

    public function test_form_qua_duoc_khi_dung_token_cua_chinh_no(): void
    {
        $form = new CsrfTestForm($this->container);
        $form->setData([
            'csrfToken' => $this->auth->getCsrfToken(CsrfTestForm::NAME),
            'name'      => 'Nguyễn Văn A',
        ]);

        self::assertTrue($form->isValid(), json_encode($form->getMessagesArr(), JSON_UNESCAPED_UNICODE));
    }

    public function test_form_chi_doc_khong_can_token(): void
    {
        $form = new ReadOnlyTestForm($this->container);
        $form->setData(['name' => 'Nguyễn Văn A']);

        self::assertTrue($form->isValid());
    }

    public function test_token_khong_lot_vao_du_lieu_nghiep_vu(): void
    {
        $form = new CsrfTestForm($this->container);
        $form->setData([
            'csrfToken' => $this->auth->getCsrfToken(CsrfTestForm::NAME),
            'name'      => 'Nguyễn Văn A',
        ]);
        $form->isValid();

        // csrfToken là chuyện của tầng bảo mật, không được đi tiếp xuống Service/Mapper.
        self::assertArrayNotHasKey('csrfToken', $form->getData());
    }
}
