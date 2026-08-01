<?php

declare(strict_types=1);

namespace Fleet\Form\Generator;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Fleet\Model\Generator\GeneratorConst;

/**
 * Form đổi trạng thái máy trên trang chi tiết.
 *
 * Chỉ kiểm hình thức: trạng thái đích có nằm trong enum không. Việc chuyển tiếp có HỢP LỆ
 * hay không do state machine ở GeneratorService quyết — form không giữ state machine.
 *
 * Có CSRF riêng, tách khỏi form lưu máy: token của form lưu không dùng để đổi trạng thái được.
 */
class GeneratorStatusForm extends AppForm
{
    protected const string FORM_NAME = 'fleet.generator.change-status';

    protected function initFields(): void
    {
        $this->initRequiredFilterNumbers(['id']);

        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(GeneratorConst::STATUS_LABELS),
        ]));

        $this->add(CommonFieldFilters::dynamicField('reason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
    }
}
