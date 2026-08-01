<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use User\Model\User\UserConst;

/**
 * Form lọc trang danh sách người dùng.
 *
 * Gửi bằng GET và KHÔNG đổi dữ liệu ⇒ không cần CSRF. Bắt token ở đây chỉ làm hỏng link
 * chia sẻ và nút Back của trình duyệt.
 *
 * Mọi giá trị dùng để sắp xếp đều đi qua whitelist UserConst::SORT_MAP.
 */
class UserSearchForm extends AppForm
{
    protected const string FORM_NAME = 'user.user.search';

    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));

        $this->add(CommonFieldFilters::dynamicField('role', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(UserConst::ROLE_LABELS),
        ]));

        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(UserConst::STATUS_LABELS),
        ]));

        $this->initInputPaging();
        $this->initSorting(UserConst::SORT_DEFAULT, 'asc', array_keys(UserConst::SORT_MAP));
    }

    /** @return array<string, string> */
    public function roleChoices(): array
    {
        return UserConst::ROLE_LABELS;
    }

    /** @return array<string, string> */
    public function statusChoices(): array
    {
        return UserConst::STATUS_LABELS;
    }
}
