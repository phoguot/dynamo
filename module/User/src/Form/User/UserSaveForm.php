<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Regex;
use User\Model\User\UserConst;

/**
 * Form thêm mới / cập nhật tài khoản — dùng chung cho cả hai, `id` không bắt buộc.
 *
 * Toàn bộ luật validate HÌNH THỨC nằm ở đây. Luật cần TRUY VẤN DB (trùng tên đăng nhập,
 * trùng email, quản trị cuối cùng) nằm ở UserService — form không biết gì về database.
 *
 * Markup của form này ở `module/User/view/partial/user/save-form.phtml`.
 */
class UserSaveForm extends AppForm
{
    protected const string FORM_NAME = 'user.user.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));

        // Tên đăng nhập: chữ thường, số, chấm, gạch dưới, gạch ngang. Không dấu, không khoảng trắng.
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
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập tên đăng nhập.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[a-z0-9][a-z0-9._-]{2,29}$/',
                        'messages' => [
                            Regex::NOT_MATCH => 'Tên đăng nhập chỉ gồm chữ thường, số và các ký tự . _ - '
                                . '(3–30 ký tự, bắt đầu bằng chữ hoặc số).',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('fullName', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));

        $this->add([
            'name'       => 'email',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StringToLower'],
            ],
            'validators' => [
                [
                    'name'                   => EmailAddress::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            EmailAddress::INVALID_FORMAT => 'Email không đúng định dạng.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'phone',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[0-9+][0-9 .()-]{7,19}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số điện thoại không hợp lệ.'],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('role', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(UserConst::ROLE_LABELS),
        ]));

        // Mật khẩu KHÔNG lọc: xem chú thích ở LoginForm.
        // `required` để false vì form dùng chung cho cả sửa — luật thật ở validateBusinessRules().
        $this->add(['name' => 'password', 'required' => false, 'filters' => [], 'validators' => []]);
        $this->add(['name' => 'passwordConfirm', 'required' => false, 'filters' => [], 'validators' => []]);

        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $isCreate = $this->getSubmittedValue('id') === null;
        $password = (string)$this->getSubmittedValue('password', '');
        $confirm  = (string)$this->getSubmittedValue('passwordConfirm', '');

        // Sửa hồ sơ thì không đụng tới mật khẩu — đổi mật khẩu đi đường riêng qua
        // UserService::resetPassword() để thao tác đó luôn hiện rõ trong nhật ký.
        if (!$isCreate) {
            return true;
        }

        if ($password === '') {
            $this->setError('password', 'Bạn chưa đặt mật khẩu cho tài khoản mới.');
            return false;
        }

        if (mb_strlen($password) < UserConst::PASSWORD_MIN_LENGTH) {
            $this->setError('password', sprintf(
                'Mật khẩu phải dài ít nhất %d ký tự.',
                UserConst::PASSWORD_MIN_LENGTH
            ));
            return false;
        }

        if (!hash_equals($password, $confirm)) {
            $this->setError('passwordConfirm', 'Hai lần nhập mật khẩu không khớp nhau.');
            return false;
        }

        return true;
    }

    /** Danh sách vai trò cho ô chọn — view không tự đọc enum. */
    public function roleChoices(): array
    {
        return UserConst::ROLE_LABELS;
    }
}
