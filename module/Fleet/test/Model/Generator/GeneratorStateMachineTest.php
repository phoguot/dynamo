<?php

declare(strict_types=1);

namespace FleetTest\Model\Generator;

use Fleet\Model\Generator\GeneratorConst;
use PHPUnit\Framework\TestCase;

/**
 * State machine trạng thái máy — bắt buộc test cả chuyển tiếp hợp lệ và chuyển tiếp bị chặn
 * (.claude/rules/testing.md).
 */
class GeneratorStateMachineTest extends TestCase
{
    public function test_moi_trang_thai_deu_co_nhan_hien_thi(): void
    {
        foreach (array_keys(GeneratorConst::STATUS_TRANSITIONS) as $status) {
            self::assertArrayHasKey($status, GeneratorConst::STATUS_LABELS, $status . ' thiếu nhãn');
        }

        self::assertSame(
            array_keys(GeneratorConst::STATUS_LABELS),
            array_keys(GeneratorConst::STATUS_TRANSITIONS),
            'Danh sách trạng thái và bảng chuyển tiếp phải khớp nhau'
        );
    }

    public function test_moi_dich_den_deu_la_trang_thai_hop_le(): void
    {
        foreach (GeneratorConst::STATUS_TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                self::assertTrue(
                    GeneratorConst::isValidStatus($to),
                    sprintf('%s trỏ tới trạng thái không tồn tại: %s', $from, $to)
                );
            }
        }
    }

    /**
     * @dataProvider chuyenTiepHopLe
     */
    public function test_cho_phep_chuyen_tiep_hop_le(string $from, string $to): void
    {
        self::assertTrue(GeneratorConst::canTransit($from, $to));
    }

    public static function chuyenTiepHopLe(): array
    {
        return [
            'giu cho khi don thue duoc xac nhan' => [GeneratorConst::STATUS_SAN_SANG, GeneratorConst::STATUS_DANG_GIU_CHO],
            'xe roi kho di giao'                 => [GeneratorConst::STATUS_DANG_GIU_CHO, GeneratorConst::STATUS_DANG_VAN_CHUYEN],
            'ban giao xong tai cong trinh'       => [GeneratorConst::STATUS_DANG_VAN_CHUYEN, GeneratorConst::STATUS_DANG_THUE],
            'bat dau thu hoi'                    => [GeneratorConst::STATUS_DANG_THUE, GeneratorConst::STATUS_DANG_VAN_CHUYEN],
            'thu hoi ve kho'                     => [GeneratorConst::STATUS_DANG_VAN_CHUYEN, GeneratorConst::STATUS_SAN_SANG],
            'huy don thue'                       => [GeneratorConst::STATUS_DANG_GIU_CHO, GeneratorConst::STATUS_SAN_SANG],
            'hong dot xuat tai cong trinh'       => [GeneratorConst::STATUS_DANG_THUE, GeneratorConst::STATUS_DANG_SUA_CHUA],
            'bao tri xong'                       => [GeneratorConst::STATUS_DANG_BAO_TRI, GeneratorConst::STATUS_SAN_SANG],
            'thanh ly may hong'                  => [GeneratorConst::STATUS_DANG_SUA_CHUA, GeneratorConst::STATUS_NGUNG],
        ];
    }

    /**
     * @dataProvider chuyenTiepBiChan
     */
    public function test_chan_chuyen_tiep_khong_hop_le(string $from, string $to): void
    {
        self::assertFalse(GeneratorConst::canTransit($from, $to));
    }

    public static function chuyenTiepBiChan(): array
    {
        return [
            // Mốc thu hồi là mốc chốt giờ máy và chốt tiền — không được nhảy cóc.
            'dang thue khong ve thang kho'          => [GeneratorConst::STATUS_DANG_THUE, GeneratorConst::STATUS_SAN_SANG],
            'dang thue khong ngung khai thac thang' => [GeneratorConst::STATUS_DANG_THUE, GeneratorConst::STATUS_NGUNG],
            'dang thue khong quay lai giu cho'      => [GeneratorConst::STATUS_DANG_THUE, GeneratorConst::STATUS_DANG_GIU_CHO],
            'san sang khong nhay thang dang thue'   => [GeneratorConst::STATUS_SAN_SANG, GeneratorConst::STATUS_DANG_THUE],
            'may bao tri khong cho thue ngay'       => [GeneratorConst::STATUS_DANG_BAO_TRI, GeneratorConst::STATUS_DANG_THUE],
            'ngung khai thac la trang thai cuoi'    => [GeneratorConst::STATUS_NGUNG, GeneratorConst::STATUS_SAN_SANG],
        ];
    }

    public function test_chi_may_san_sang_moi_nhan_don_thue_moi(): void
    {
        self::assertSame([GeneratorConst::STATUS_SAN_SANG], GeneratorConst::STATUS_KHA_DUNG);

        foreach ([
            GeneratorConst::STATUS_DANG_GIU_CHO,
            GeneratorConst::STATUS_DANG_VAN_CHUYEN,
            GeneratorConst::STATUS_DANG_THUE,
            GeneratorConst::STATUS_DANG_BAO_TRI,
            GeneratorConst::STATUS_DANG_SUA_CHUA,
            GeneratorConst::STATUS_NGUNG,
        ] as $status) {
            self::assertNotContains($status, GeneratorConst::STATUS_KHA_DUNG);
        }
    }

    public function test_cot_sap_xep_chi_lay_tu_whitelist(): void
    {
        self::assertArrayHasKey(GeneratorConst::SORT_DEFAULT, GeneratorConst::SORT_MAP);

        // Giá trị người dùng gửi lên không nằm trong whitelist thì không có cột tương ứng.
        self::assertArrayNotHasKey('g.code; DROP TABLE flt_generators', GeneratorConst::SORT_MAP);
    }
}
