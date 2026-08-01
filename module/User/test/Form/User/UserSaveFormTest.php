<?php

declare(strict_types=1);

namespace UserTest\Form\User;

use ApplicationTest\Form\FormTestCase;
use User\Form\User\UserSaveForm;
use User\Model\User\UserConst;

/**
 * Luật validate hình thức của form thêm/sửa tài khoản.
 *
 * Luật cần truy vấn DB (trùng tên đăng nhập, quản trị cuối cùng) nằm ở UserService,
 * không test ở đây.
 */
class UserSaveFormTest extends FormTestCase
{
    /** @param array<string, mixed> $data */
    private function form(array $data): UserSaveForm
    {
        $form = new UserSaveForm($this->container);
        $form->setData($data + ['csrfToken' => $this->auth->getCsrfToken('user.user.save')]);

        return $form;
    }

    /** @return array<string, mixed> Bộ dữ liệu hợp lệ tối thiểu để tạo tài khoản mới. */
    private function hopLe(): array
    {
        return [
            'username'        => 'nguyen.van.a',
            'fullName'        => 'Nguyễn Văn A',
            'role'            => UserConst::ROLE_DISPATCHER,
            'password'        => 'mat-khau-du-dai',
            'passwordConfirm' => 'mat-khau-du-dai',
        ];
    }

    public function test_tao_moi_hop_le(): void
    {
        self::assertTrue($this->form($this->hopLe())->isValid());
    }

    public function test_chan_ten_dang_nhap_co_dau_hoac_khoang_trang(): void
    {
        foreach (['nguyễn văn a', 'nguyen van a', 'NGUYEN@A', 'ab'] as $username) {
            $form = $this->form(['username' => $username] + $this->hopLe());

            self::assertFalse($form->isValid(), "Tên đăng nhập \"{$username}\" phải bị chặn");
            self::assertArrayHasKey('username', $form->getMessagesArr());
        }
    }

    public function test_ten_dang_nhap_chuyen_ve_chu_thuong(): void
    {
        $form = $this->form(['username' => 'Nguyen.Van.A'] + $this->hopLe());

        self::assertTrue($form->isValid());
        self::assertSame('nguyen.van.a', $form->getData()['username']);
    }

    public function test_chan_vai_tro_ngoai_enum(): void
    {
        $form = $this->form(['role' => 'giam_doc'] + $this->hopLe());

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('role', $form->getMessagesArr());
    }

    public function test_tao_moi_bat_buoc_co_mat_khau(): void
    {
        $data = $this->hopLe();
        unset($data['password'], $data['passwordConfirm']);

        $form = $this->form($data);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('password', $form->getMessagesArr());
    }

    public function test_chan_mat_khau_qua_ngan(): void
    {
        $short = str_repeat('a', UserConst::PASSWORD_MIN_LENGTH - 1);
        $form = $this->form(['password' => $short, 'passwordConfirm' => $short] + $this->hopLe());

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('password', $form->getMessagesArr());
    }

    public function test_chan_hai_lan_nhap_mat_khau_khac_nhau(): void
    {
        $form = $this->form(['passwordConfirm' => 'mot-mat-khau-khac'] + $this->hopLe());

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('passwordConfirm', $form->getMessagesArr());
    }

    public function test_sua_ho_so_khong_bat_buoc_mat_khau(): void
    {
        // Đổi mật khẩu đi đường riêng qua UserService::resetPassword() để luôn hiện trong
        // nhật ký — sửa hồ sơ không được đụng tới mật khẩu.
        $data = $this->hopLe();
        unset($data['password'], $data['passwordConfirm']);
        $data['id'] = 7;

        self::assertTrue($this->form($data)->isValid());
    }

    public function test_chan_email_sai_dinh_dang(): void
    {
        $form = $this->form(['email' => 'khong-phai-email'] + $this->hopLe());

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('email', $form->getMessagesArr());
    }

    public function test_khong_dung_duoc_token_cua_form_khac(): void
    {
        $form = new UserSaveForm($this->container);
        $form->setData($this->hopLe() + ['csrfToken' => $this->auth->getCsrfToken('user.user.password')]);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('csrfToken', $form->getMessagesArr());
    }
}
