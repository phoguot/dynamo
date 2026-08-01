<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use User\Model\User\UserConst;

/**
 * Form quản trị đặt lại mật khẩu cho một tài khoản.
 *
 * Mật khẩu KHÔNG qua filter nào: lọc mật khẩu là âm thầm đổi thứ người dùng gõ, dẫn tới
 * "đặt mật khẩu xong không đăng nhập được" và không ai tìm ra vì sao.
 */
class UserPasswordForm extends AppForm
{
    protected const string FORM_NAME = 'user.user.password';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));

        $this->add(['name' => 'password', 'required' => false, 'filters' => [], 'validators' => []]);
        $this->add(['name' => 'passwordConfirm', 'required' => false, 'filters' => [], 'validators' => []]);
    }

    protected function validateBusinessRules(): bool
    {
        $password = (string)$this->getSubmittedValue('password', '');
        $confirm  = (string)$this->getSubmittedValue('passwordConfirm', '');

        if ($password === '') {
            $this->setError('password', 'Bạn chưa nhập mật khẩu mới.');
            return false;
        }

        if (mb_strlen($password) < UserConst::PASSWORD_MIN_LENGTH) {
            $this->setError('password', sprintf(
                'Mật khẩu phải dài ít nhất %d ký tự.',
                UserConst::PASSWORD_MIN_LENGTH
            ));
            return false;
        }

        // hash_equals thay vì `!==`: so sánh chuỗi bí mật luôn dùng hàm thời gian hằng số,
        // kể cả khi ở đây rò rỉ thông tin không đáng kể — để thói quen đúng lan ra chỗ khác.
        if (!hash_equals($password, $confirm)) {
            $this->setError('passwordConfirm', 'Hai lần nhập mật khẩu không khớp nhau.');
            return false;
        }

        return true;
    }
}
