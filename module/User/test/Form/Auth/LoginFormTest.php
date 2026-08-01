<?php

declare(strict_types=1);

namespace UserTest\Form\Auth;

use ApplicationTest\Form\FormTestCase;
use User\Form\Auth\LoginForm;

class LoginFormTest extends FormTestCase
{
    /** @param array<string, mixed> $data */
    private function form(array $data): LoginForm
    {
        $form = new LoginForm($this->container);
        $form->setData($data);

        return $form;
    }

    private function token(): string
    {
        return $this->auth->getCsrfToken('user.auth.login');
    }

    public function test_dang_nhap_hop_le(): void
    {
        self::assertTrue($this->form([
            'csrfToken' => $this->token(),
            'username'  => 'admin',
            'password'  => 'mat-khau-du-dai',
        ])->isValid());
    }

    public function test_mat_khau_khong_bi_loc(): void
    {
        // Lọc mật khẩu là âm thầm đổi thứ người dùng gõ ⇒ "đúng mật khẩu mà không vào được".
        $password = '  <b>Mật khẩu</b> có dấu & thẻ  ';

        $form = $this->form([
            'csrfToken' => $this->token(),
            'username'  => 'admin',
            'password'  => $password,
        ]);

        self::assertTrue($form->isValid());
        self::assertSame($password, $form->getData()['password']);
    }

    public function test_thieu_tai_khoan_hoac_mat_khau_bi_chan(): void
    {
        $form = $this->form(['csrfToken' => $this->token()]);

        self::assertFalse($form->isValid());
        $errors = $form->getMessagesArr();
        self::assertArrayHasKey('username', $errors);
        self::assertArrayHasKey('password', $errors);
    }

    public function test_form_dang_nhap_van_bat_csrf(): void
    {
        $form = $this->form([
            'username' => 'admin',
            'password' => 'mat-khau-du-dai',
        ]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('csrfToken', $form->getMessagesArr());
    }

    public function test_chan_open_redirect_qua_tham_so_next(): void
    {
        $doc = [
            'https://trang-gia-mao.example'  => '/',
            '//trang-gia-mao.example'        => '/',
            'javascript:alert(1)'            => '/',
            ''                               => '/',
            '/users/detail/7'                => '/users/detail/7',
        ];

        foreach ($doc as $next => $expected) {
            $form = $this->form([
                'csrfToken' => $this->token(),
                'username'  => 'admin',
                'password'  => 'mat-khau-du-dai',
                'next'      => $next,
            ]);

            self::assertSame($expected, $form->safeNextUrl(), "next=\"{$next}\" phải cho ra \"{$expected}\"");
        }
    }
}
