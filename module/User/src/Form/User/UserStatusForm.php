<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use User\Model\User\UserConst;

/**
 * Form khóa / mở khóa tài khoản, đặt trên trang chi tiết người dùng.
 *
 * Form này có CSRF token RIÊNG, khác token của form lưu hồ sơ — mỗi form một `FORM_NAME`.
 *
 * Chỉ kiểm HÌNH THỨC (trạng thái có nằm trong enum không). Việc chuyển tiếp có hợp lệ theo
 * state machine hay không do UserService::changeStatus() quyết định.
 */
class UserStatusForm extends AppForm
{
    protected const string FORM_NAME = 'user.user.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));

        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(UserConst::STATUS_LABELS),
        ]));

        // Lý do là bắt buộc: khóa tài khoản một người là việc phải giải thích được về sau.
        $this->add(CommonFieldFilters::dynamicField('reason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
    }
}
