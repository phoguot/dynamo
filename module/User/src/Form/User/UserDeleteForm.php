<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

/**
 * Form xóa vĩnh viễn một tài khoản, đặt trên trang chi tiết người dùng.
 *
 * Form này có CSRF token RIÊNG (mỗi form một `FORM_NAME`) — token của form khóa/mở khóa hay
 * đặt lại mật khẩu KHÔNG dùng được cho thao tác xóa.
 *
 * Chỉ kiểm HÌNH THỨC: có id, có gõ tên đăng nhập xác nhận. Việc tên xác nhận có khớp tài khoản
 * hay không, và các bất biến an toàn (không tự xóa mình, không xóa admin cuối cùng) do
 * UserService::deleteUser() quyết định vì cần truy vấn DB.
 */
class UserDeleteForm extends AppForm
{
    protected const string FORM_NAME = 'user.user.delete';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));

        // Bắt gõ lại tên đăng nhập để xác nhận: xóa là thao tác không thể hoàn tác, một cú
        // bấm nhầm không được phép xóa được tài khoản.
        $this->add(CommonFieldFilters::dynamicField('confirmUsername', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 30,
        ]));
    }
}
