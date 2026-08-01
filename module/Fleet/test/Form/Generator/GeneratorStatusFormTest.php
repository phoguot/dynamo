<?php

declare(strict_types=1);

namespace FleetTest\Form\Generator;

use ApplicationTest\Form\FormTestCase;
use Fleet\Form\Generator\GeneratorStatusForm;

/** Form đổi trạng thái — CSRF riêng, enum trạng thái chặn ở form. */
class GeneratorStatusFormTest extends FormTestCase
{
    public function test_khong_dung_duoc_token_cua_form_luu_may(): void
    {
        $form = new GeneratorStatusForm($this->container);
        $form->setData([
            'csrfToken' => $this->auth->getCsrfToken('fleet.generator.save'),
            'id'        => 1,
            'status'    => 'dang_thue',
        ]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('csrfToken', $form->getMessagesArr());
    }

    public function test_khong_cho_trang_thai_ngoai_enum(): void
    {
        $form = new GeneratorStatusForm($this->container);
        $form->setData([
            'csrfToken' => $this->auth->getCsrfToken('fleet.generator.change-status'),
            'id'        => 1,
            'status'    => 'trang_thai_bia',
        ]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('status', $form->getMessagesArr());
    }

    public function test_thieu_id_bi_chan(): void
    {
        $form = new GeneratorStatusForm($this->container);
        $form->setData([
            'csrfToken' => $this->auth->getCsrfToken('fleet.generator.change-status'),
            'status'    => 'dang_thue',
        ]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('id', $form->getMessagesArr());
    }
}
