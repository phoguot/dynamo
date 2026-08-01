<?php

declare(strict_types=1);

namespace ApplicationTest\Filter;

use Application\Filter\SafeHtmlFilter;
use PHPUnit\Framework\TestCase;

/**
 * SafeHtmlFilter chạy trên HTMLPurifier — phải chặn được cả những biến thể mà bộ lọc
 * bằng regex trước đây bỏ lọt. Ghi chú hiện trường và tên khách hàng là dữ liệu người
 * dùng nhập, coi như thù địch (.claude/rules/security.md).
 */
class SafeHtmlFilterTest extends TestCase
{
    private SafeHtmlFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SafeHtmlFilter();
    }

    public function test_giu_lai_the_dinh_dang_co_ban(): void
    {
        self::assertSame(
            '<p>Máy <strong>MP-001</strong> đã bàn giao</p>',
            $this->filter->filter('<p>Máy <strong>MP-001</strong> đã bàn giao</p>')
        );
    }

    public function test_bo_the_script_va_ca_noi_dung_ben_trong(): void
    {
        $result = $this->filter->filter('<script>alert(1)</script>Ghi chú');

        self::assertStringNotContainsString('script', $result);
        self::assertStringNotContainsString('alert(1)', $result);
        self::assertStringContainsString('Ghi chú', $result);
    }

    public function test_bo_thuoc_tinh_su_kien(): void
    {
        $result = $this->filter->filter('<p onclick="steal()">Xin chào</p>');

        self::assertStringNotContainsString('onclick', $result);
        self::assertStringContainsString('Xin chào', $result);
    }

    public function test_bo_the_img_co_onerror(): void
    {
        // Đây là ca mà strip_tags + regex bỏ lọt: thẻ không đóng, thuộc tính không dấu nháy.
        $result = $this->filter->filter('<img src=x onerror=alert(1)>Ghi chú');

        self::assertStringNotContainsString('onerror', $result);
        self::assertStringNotContainsString('<img', $result);
    }

    public function test_chan_link_javascript(): void
    {
        $result = $this->filter->filter('<a href="javascript:alert(1)">bấm vào</a>');

        self::assertStringNotContainsString('javascript:', $result);
        self::assertStringContainsString('bấm vào', $result);
    }

    public function test_chan_link_data_uri(): void
    {
        $result = $this->filter->filter('<a href="data:text/html;base64,PHNjcmlwdD4=">bấm vào</a>');

        self::assertStringNotContainsString('data:', $result);
    }

    public function test_giu_link_http_va_them_noopener(): void
    {
        $result = $this->filter->filter('<a href="https://vidu.vn">trang web</a>');

        self::assertStringContainsString('https://vidu.vn', $result);
        self::assertStringContainsString('noopener', $result);
    }

    public function test_chuoi_rong_giu_nguyen(): void
    {
        self::assertSame('', $this->filter->filter('   '));
    }

    public function test_loc_tung_phan_tu_cua_mang(): void
    {
        self::assertSame(
            ['Ghi chú', '<p>Ổn</p>'],
            $this->filter->filter(['<script>x</script>Ghi chú', '<p>Ổn</p>'])
        );
    }

    public function test_gia_tri_khong_phai_chuoi_giu_nguyen(): void
    {
        self::assertSame(42, $this->filter->filter(42));
        self::assertNull($this->filter->filter(null));
    }
}
