<?php

declare(strict_types=1);

namespace UserTest\Model;

use PHPUnit\Framework\TestCase;
use User\Model\User\UserConst;

/**
 * State machine trạng thái tài khoản.
 *
 * Theo .claude/rules/testing.md: test MỌI chuyển tiếp hợp lệ và chặn được chuyển tiếp
 * không hợp lệ.
 */
class UserStateMachineTest extends TestCase
{
    public static function chuyenTiepHopLe(): array
    {
        return [
            'khóa tạm một tài khoản đang dùng'  => [UserConst::STATUS_HOAT_DONG, UserConst::STATUS_TAM_KHOA],
            'cho nghỉ việc thẳng từ đang dùng'  => [UserConst::STATUS_HOAT_DONG, UserConst::STATUS_NGUNG],
            'mở khóa lại'                       => [UserConst::STATUS_TAM_KHOA, UserConst::STATUS_HOAT_DONG],
            'người đang bị khóa thì nghỉ việc'  => [UserConst::STATUS_TAM_KHOA, UserConst::STATUS_NGUNG],
        ];
    }

    /** @dataProvider chuyenTiepHopLe */
    public function test_cho_phep_chuyen_tiep_hop_le(string $from, string $to): void
    {
        self::assertTrue(UserConst::canTransit($from, $to));
    }

    public function test_ngung_su_dung_la_trang_thai_cuoi(): void
    {
        // Người đã nghỉ việc mà quay lại thì tạo tài khoản mới, không hồi sinh tài khoản cũ:
        // audit log phải gắn đúng một con người trong một giai đoạn.
        self::assertSame([], UserConst::STATUS_TRANSITIONS[UserConst::STATUS_NGUNG]);
        self::assertFalse(UserConst::canTransit(UserConst::STATUS_NGUNG, UserConst::STATUS_HOAT_DONG));
        self::assertFalse(UserConst::canTransit(UserConst::STATUS_NGUNG, UserConst::STATUS_TAM_KHOA));
    }

    public function test_chan_trang_thai_khong_co_that(): void
    {
        self::assertFalse(UserConst::canTransit(UserConst::STATUS_HOAT_DONG, 'trang_thai_bia'));
        self::assertFalse(UserConst::isValidStatus('trang_thai_bia'));
        self::assertFalse(UserConst::isValidStatus(null));
    }

    public function test_chi_trang_thai_hoat_dong_moi_dang_nhap_duoc(): void
    {
        self::assertSame([UserConst::STATUS_HOAT_DONG], UserConst::STATUS_DANG_NHAP_DUOC);

        foreach ([UserConst::STATUS_TAM_KHOA, UserConst::STATUS_NGUNG] as $status) {
            self::assertNotContains($status, UserConst::STATUS_DANG_NHAP_DUOC);
        }
    }

    public function test_moi_trang_thai_deu_co_nhan_va_dong_chuyen_tiep(): void
    {
        foreach (array_keys(UserConst::STATUS_LABELS) as $status) {
            self::assertArrayHasKey(
                $status,
                UserConst::STATUS_TRANSITIONS,
                "Trạng thái {$status} chưa khai trong STATUS_TRANSITIONS"
            );
        }

        // Không có đích nào trỏ tới trạng thái không tồn tại.
        foreach (UserConst::STATUS_TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                self::assertTrue(
                    UserConst::isValidStatus($to),
                    "Chuyển tiếp {$from} → {$to} trỏ tới trạng thái không có thật"
                );
            }
        }
    }

    public function test_sau_vai_tro_khop_contracts(): void
    {
        // Giá trị chốt ở contracts/enums.md — đổi ở một nơi mà quên nơi kia là lỗi hay gặp.
        self::assertSame(
            ['admin', 'quan_ly', 'kinh_doanh', 'dieu_phoi', 'ky_thuat_vien', 'ke_toan'],
            array_keys(UserConst::ROLE_LABELS)
        );
    }
}
