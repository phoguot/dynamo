<?php

declare(strict_types=1);

namespace FleetTest\Form\Generator;

use ApplicationTest\Form\FormTestCase;
use Fleet\Form\Generator\GeneratorSearchForm;

/** Form lọc danh sách — không cần CSRF nhưng vẫn phải chặn tham số sắp xếp bịa. */
class GeneratorSearchFormTest extends FormTestCase
{
    public function test_sort_ngoai_whitelist_bi_ep_ve_mac_dinh(): void
    {
        $form = new GeneratorSearchForm($this->container);
        $form->setData(['sort' => 'g.code, (SELECT 1)', 'dir' => 'desc']);

        self::assertTrue($form->isValid());
        // Đây là chốt chặn SQL injection qua tham số sắp xếp.
        self::assertSame('code', $form->getData()['sort']);
    }

    public function test_pagesize_bi_chan_tran(): void
    {
        $form = new GeneratorSearchForm($this->container);
        $form->setData(['pageSize' => 100000]);
        $form->isValid();

        self::assertLessThanOrEqual(200, $form->getData()['pageSize']);
    }

    public function test_khong_cho_cong_suat_tu_lon_hon_den(): void
    {
        $form = new GeneratorSearchForm($this->container);
        $form->setData(['capacityFrom' => 500, 'capacityTo' => 100]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('capacityTo', $form->getMessagesArr());
    }
}

