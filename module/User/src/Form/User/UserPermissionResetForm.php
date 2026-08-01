<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

/**
 * Form "gỡ toàn bộ ngoại lệ quyền".
 *
 * Tách thành một form riêng thay vì thêm một nút vào form ma trận, vì `FORM_NAME` riêng
 * nghĩa là CSRF token riêng: token của form lưu ma trận không dùng để gỡ sạch quyền được.
 * Đây là thao tác khó hoàn tác nên đáng để tách.
 */
class UserPermissionResetForm extends AppForm
{
    protected const string FORM_NAME = 'user.permission.reset';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('userId', true));
    }
}
