<?php

declare(strict_types=1);

namespace Application\Filter;

use HTMLPurifier;
use HTMLPurifier_Config;
use Laminas\Filter\FilterInterface;

/**
 * Lọc HTML cho các trường rich text (ghi chú hiện trường, mô tả, điều khoản hợp đồng).
 *
 * Ruột là `ezyang/htmlpurifier` — parse thành cây DOM rồi dựng lại theo whitelist, nên chặn
 * được cả các biến thể mà regex bỏ lọt (`<img src=x onerror=...>`, `<a href="javascript:...">`,
 * thẻ đóng thiếu, thuộc tính có `data:` URI).
 *
 * Lưu ý: đây là lớp phòng thủ thứ hai. Lớp thứ nhất vẫn là escape khi render view.
 */
class SafeHtmlFilter implements FilterInterface
{
    /** Thẻ được phép giữ lại trong nội dung rich text. */
    private const string ALLOWED_HTML =
        'p,br,b,strong,i,em,u,ul,ol,li,h3,h4,h5,blockquote,a[href|title]';

    /** Giao thức được phép trong href — chặn `javascript:`, `data:`, `vbscript:`. */
    private const array ALLOWED_SCHEMES = ['http', 'https', 'mailto'];

    /** Dựng HTMLPurifier tốn thời gian, tái sử dụng trong cùng request. */
    private static ?HTMLPurifier $purifier = null;

    public function filter($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'filter'], $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        return trim(self::purifier()->purify($value));
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', self::ALLOWED_HTML);
        $config->set('URI.AllowedSchemes', array_fill_keys(self::ALLOWED_SCHEMES, true));
        // Link do người dùng nhập luôn mở tab mới và không truyền referrer.
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.TargetNoreferrer', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Cache.SerializerPath', self::cachePath());

        return self::$purifier = new HTMLPurifier($config);
    }

    /**
     * Thư mục cache definition. Không tạo được (chỉ đọc, thiếu quyền) thì tắt cache —
     * chậm hơn nhưng không được phép làm hỏng request.
     */
    private static function cachePath(): ?string
    {
        $path = dirname(__DIR__, 4) . '/data/cache/htmlpurifier';

        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            return null;
        }

        return is_writable($path) ? $path : null;
    }
}
