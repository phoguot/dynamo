<?php

declare(strict_types=1);

namespace User\Form\Auth;

use Application\Form\AppForm;
use Laminas\Validator\NotEmpty;

/**
 * Form đăng nhập.
 *
 * Có CSRF (POST, đổi trạng thái phiên). Người CHƯA đăng nhập vẫn lấy được token vì bí mật
 * CSRF sinh theo phiên ẩn danh ngay lần đầu vào trang — xem AuthContextService::getCsrfSecret().
 *
 * Mật khẩu KHÔNG qua StripTags/StringTrim: lọc mật khẩu là âm thầm đổi thứ người dùng gõ,
 * dẫn tới "mật khẩu đúng mà không đăng nhập được" và không ai tìm ra vì sao.
 */
class LoginForm extends AppForm
{
    protected const string FORM_NAME = 'user.auth.login';

    protected function initFields(): void
    {
        $this->add([
            'name'       => 'username',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StringToLower'],
            ],
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [NotEmpty::IS_EMPTY => 'Bạn chưa nhập tên đăng nhập.'],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'password',
            'required'   => true,
            'filters'    => [],
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [NotEmpty::IS_EMPTY => 'Bạn chưa nhập mật khẩu.'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Đường dẫn quay lại sau khi đăng nhập, do BaseController gắn vào khi chuyển hướng.
     * Chỉ chấp nhận đường dẫn NỘI BỘ — mở cửa cho URL tuyệt đối là tạo lỗ open redirect,
     * kẻ tấn công gửi link `/login?next=https://trang-gia-mao` là xong.
     */
    public function safeNextUrl(string $default = '/'): string
    {
        $next = (string)$this->getSubmittedValue('next', '');

        if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
            return $default;
        }

        return $next;
    }
}
