<?php

namespace Application\Filter;

use Laminas\InputFilter\FileInput;
use Laminas\Validator\Callback;
use Laminas\Validator\File\Extension;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;


/**
 * Bộ filter + validator dùng chung cho các InputFilter của hệ thống
 * ================================================================
 * - Chuẩn hóa việc định nghĩa field: text / int / float / enum / json / file…
 * - Giảm lặp code giữa các InputFilter (Promotion, POS, CRM…)
 * - Tất cả method đều ở dạng static → không cần khởi tạo class
 * - Đảm bảo đồng nhất rule và dễ bảo trì khi mở rộng
 *
 * @author  ToanNV
 * @since   2025-11
 */
final class CommonFieldFilters
{
    /**
     * TYPE_TEXT
     * ------------------------------
     * - Dùng cho field chuỗi THUẦN VĂN BẢN: tên, mã, mô tả ngắn, ghi chú…
     * - Filter: Trim + StripTags (gỡ sạch mọi thẻ — field này không được chứa HTML)
     * - Validator: StringLength (giới hạn ký tự)
     */
    public const  string TYPE_TEXT = 'text';

    /**
     * TYPE_RICH_TEXT
     * ------------------------------
     * - Dùng cho field ĐƯỢC PHÉP chứa HTML: điều khoản hợp đồng, mô tả dài có định dạng.
     * - Filter: Trim + SafeHtmlFilter (HTMLPurifier — giữ thẻ trong whitelist, bỏ thuộc tính độc)
     * - Validator: StringLength (giới hạn ký tự)
     *
     * KHÔNG kèm StripTags: StripTags chạy trước sẽ gỡ hết thẻ, purifier không còn gì để lọc.
     */
    public const  string TYPE_RICH_TEXT = 'richText';

    /**
     * TYPE_INT
     * ------------------------------
     * - Dùng cho các field số nguyên: ID, sortOrder, priority…
     * - Filter: Digits → giữ lại 0–9
     * - Validator: NotEmpty (nếu required)
     */
    public const  string TYPE_INT = 'int';

    /**
     * TYPE_FLOAT
     * ------------------------------
     * - Dùng cho giá trị số có thập phân: price, amount, percent…
     * - Filter: ToFloat
     * - Validator: NotEmpty (nếu required)
     */
    public const  string TYPE_FLOAT = 'float';

    /**
     * TYPE_ENUM_STRING
     * ------------------------------
     * - Dùng cho enum trạng thái dạng chuỗi của dự án (`san_sang`, `dang_thue`…)
     *   theo contracts/enums.md. KHÔNG dùng TYPE_ENUM cho các giá trị này vì
     *   TYPE_ENUM lọc Digits và sẽ ăn mất phần chữ.
     * - Filter: Trim
     * - Validator: InArray (bắt buộc có enumValues)
     */
    public const  string TYPE_ENUM_STRING = 'enumString';

    /**
     * TYPE_ENUM
     * ------------------------------
     * - Dùng khi field nhận 1 trong số ít giá trị cố định DẠNG SỐ
     *   Ví dụ: status (0|1), applyFor (1|2|3)
     * - Filter: Digits
     * - Validator: InArray (bắt buộc có enumValues)
     *
     * Cách dùng:
     *   CommonFieldFilters::dynamicField('status', [
     *       'type'       => CommonFieldFilters::TYPE_ENUM,
     *       'required'   => true,
     *       'enumValues' => [0,1]
     *   ]);
     */
    public const  string TYPE_ENUM = 'enum';

    // ============================
    //  LENGTH PRESETS (SEO/UI)
    // ============================

    /** Meta Title (SEO): tối đa ~70 ký tự */
    public const  int LEN_META_TITLE = 70;

    /** Meta Description (SEO): tối đa ~160 ký tự */
    public const  int LEN_META_DESCRIPTION = 160;

    /** Meta Keywords (SEO): tối đa ~320 ký tự */
    public const  int LEN_META_KEYWORDS = 320;

    /** Title ngắn / tên chương trình / tên sản phẩm: tối đa 120 ký tự */
    public const  int LEN_TITLE = 120;

    /** Description ngắn / ghi chú: tối đa 255 ký tự */
    public const  int LEN_DESCRIPTION = 255;

    /** Nội dung dài (content/body) tối đa */
    public const  int LEN_CONTENT = 5000;

    /** Giới hạn mặc định cho chuỗi JSON raw: 2MB (tính theo byte) */
    public const  int MAX_JSON_BYTES = 2097152;


    /**
     * Tạo cấu hình Filter + Validator cho field dạng text/meta/title/description.
     * -------------------------------------------------------------------------
     * - Tự động trim và gỡ sạch thẻ HTML (field văn bản thuần).
     * - Validator kiểm tra độ dài tối đa.
     * - Field ĐƯỢC PHÉP chứa HTML thì dùng dynamicField(..., TYPE_RICH_TEXT), không dùng hàm này.
     *
     * @param bool $required Có bắt buộc phải nhập hay không
     * @param int  $maxLength Giới hạn ký tự tối đa cho field
     * @return array            Cấu hình InputFilter
     */
    public static function textField(
        string $name,
        bool   $required = false,
        int    $maxLength = self::LEN_DESCRIPTION
    ): array
    {
        return [
            'name'       => $name,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'                   => 'StringLength',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'max'      => $maxLength,
                        'messages' => [
                            'stringLengthTooLong' =>
                                "Nội dung không hợp lệ (tối đa {$maxLength} ký tự)",
                        ],
                    ],
                ],
            ],
        ];
    }


    /**
     * Field chuỗi RAW — chỉ trim + (tùy chọn) NotEmpty.
     * -------------------------------------------------------------------------
     * - KHÔNG StripTags / HTMLPurifier / StringLength: giữ nguyên văn giá trị.
     * - Dùng cho payload dạng jsonstring FE gửi (content builder…) cần lưu raw;
     *   giới hạn độ dài / kiểm tra JSON để tầng service xử lý.
     *
     * @param bool $required Có bắt buộc phải nhập hay không
     * @return array            Cấu hình InputFilter
     */
    public static function rawStringField(
        string $name,
        bool   $required = false
    ): array
    {
        return [
            'name'       => $name,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
            ],
            'validators' => $required
                ? [[
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => "Trường '{$name}' không được để trống",
                        ],
                    ],
                ]]
                : [],
        ];
    }


    /**
     * Field nhận chuỗi JSON object từ FE, lưu nguyên văn (không decode)
     *
     * - FE phải gửi chuỗi JSON (JSON.stringify), không gửi object
     * - Nếu FE gửi object → bị decode thành array → validator chặn lại
     *
     * $config gồm:
     * - required => bool
     * - maxBytes => int (giới hạn dung lượng chuỗi, mặc định 2MB)
     */
    public static function jsonObjectStringField(
        string $name,
        array  $config = []
    ): array
    {
        $required = $config['required'] ?? false;
        $maxBytes = $config['maxBytes'] ?? self::MAX_JSON_BYTES;

        return [
            'name'       => $name,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => "Trường '{$name}' không được để trống",
                        ],
                    ],
                ],
                [
                    'name'                   => Callback::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'callback' => function ($value) use ($maxBytes) {
                            return is_string($value)
                                && strlen($value) <= $maxBytes
                                && is_object(json_decode($value));
                        },
                        'messages' => [
                            Callback::INVALID_VALUE =>
                                "Trường '{$name}' phải là chuỗi JSON object, tối đa {$maxBytes} byte",
                        ],
                    ],
                ],
            ],
        ];
    }


    /**
     * Tạo filter cho field dạng số nguyên (ID, counter, sort order...).
     * -------------------------------------------------------------------------
     * - Chỉ ép kiểu integer, không validate min/max (tùy case bổ sung sau).
     *
     * @param bool $required
     * @return array
     */
    public static function intField(string $name, bool $required = false): array
    {
        return [
            'name'     => $name,
            'required' => $required,
            'filters'  => [
                ['name' => 'ToInt'],
            ],
        ];
    }


    /**
     * Tạo InputFilter cho upload file Excel
     */
    public static function fileUploadField(
        string $name,
        bool   $required = false,
        array  $allowedExtensions = ['xls', 'xlt', 'xlsx', 'xlsm', 'xltx']
    ): array
    {
        return [
            'name'       => $name,
            'type'       => FileInput::class,
            'required'   => $required,
            'validators' => [
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Bạn chưa chọn file',
                        ],
                    ],
                ],
                [
                    'name'                   => Extension::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'extension' => $allowedExtensions,
                        'messages'  => [
                            Extension::FALSE_EXTENSION => 'File Excel không đúng định dạng',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Field nhận JSON từ FE (object hoặc JSON string)
     *
     * - FE có thể gửi: array, stdClass, hoặc chuỗi JSON
     * - Tự động decode nếu là string
     */
    public static function jsonPayloadField(
        string $name,
        bool   $required = false
    ): array
    {
        return [
            'name'       => $name,
            'required'   => $required,
            'filters'    => [
                // Normalize về string để xử lý
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                [
                    'name'    => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => function ($value) {
                            if (is_array($value)) {
                                return $value; // FE gửi object → OK
                            }

                            if (is_string($value) && $value !== '') {
                                $decoded = json_decode($value, true);
                                return json_last_error() === JSON_ERROR_NONE
                                    ? $decoded
                                    : null;
                            }

                            return null;
                        }
                    ]
                ]
            ],
            'validators' => [
                // Bắt buộc nếu $required = true
                [
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'Dữ liệu không được để trống',
                        ]
                    ],
                ],

                // Validate JSON decode OK
                [
                    'name'                   => Callback::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'callback' => function ($value) {
                            return is_array($value); // Sau filter phải là array
                        },
                        'messages' => [
                            Callback::INVALID_VALUE => 'Payload không phải JSON hợp lệ',
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Tạo filter động cho 1 field theo config.
     *
     * $config gồm:
     * - required       => bool
     * - type           => "text"|"int"|"float"|"enum"
     * - enumValues     => array (chỉ khi type=enum)
     * - maxLength      => int (chỉ khi type=text)
     */
    public static function dynamicField(
        string $fieldName,
        array  $config = []
    ): array
    {
        $required = $config['required'] ?? false;
        $type = $config['type'] ?? 'int';
        $enumValues = $config['enumValues'] ?? [];
        $maxLength = $config['maxLength'] ?? self::LEN_DESCRIPTION;

        // -------------------------
        //  FILTERS
        // -------------------------
        $filters = [['name' => 'StringTrim']];

        switch ($type) {
            case self::TYPE_TEXT:
                $filters[] = ['name' => 'StripTags'];
                break;

            case self::TYPE_RICH_TEXT:
                $filters[] = ['name' => SafeHtmlFilter::class];
                break;

            case 'float':
                $filters[] = ['name' => 'ToFloat'];
                break;

            case 'enum':
            case 'int':
                $filters[] = ['name' => 'Digits'];
                break;

            case self::TYPE_ENUM_STRING:
                // Chỉ trim — giá trị đã bị InArray chặn nên không cần lọc thêm.
                break;
        }

        // -------------------------
        // VALIDATORS
        // -------------------------
        $validators = [];

        if ($required) {
            $validators[] = [
                'name'                   => NotEmpty::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'messages' => [
                        NotEmpty::IS_EMPTY => "Trường '{$fieldName}' không được để trống"
                    ]
                ]
            ];
        }

        if ($type === self::TYPE_TEXT || $type === self::TYPE_RICH_TEXT) {
            $validators[] = [
                'name'                   => 'StringLength',
                'break_chain_on_failure' => true,
                'options'                => [
                    'max'      => $maxLength,
                    'messages' => [
                        'stringLengthTooLong' =>
                            "Trường '{$fieldName}' vượt quá {$maxLength} ký tự",
                    ]
                ]
            ];
        }

        if (($type === 'enum' || $type === self::TYPE_ENUM_STRING) && $enumValues) {
            $validators[] = [
                'name'                   => InArray::class,
                'break_chain_on_failure' => true,
                'options'                => [
                    'haystack' => $enumValues,
                    'messages' => [
                        InArray::NOT_IN_ARRAY => "Giá trị của '{$fieldName}' không hợp lệ"
                    ]
                ]
            ];
        }

        return [
            'name'       => $fieldName,
            'required'   => $required,
            'filters'    => $filters,
            'validators' => $validators
        ];
    }




    // ========================================================
    //  INTERNAL — Hàm dựng filter cho field dạng ARRAY
    //  - $itemCaster: hàm ép kiểu từng phần tử trong mảng
    // ========================================================
    private static function _buildArrayField(
        string   $name,
        bool     $required,
        callable $itemCaster
    ): array
    {
        return [
            'name'     => $name,
            'required' => $required,

            'filters' => [
                ['name' => 'StringTrim'],

                [
                    'name'    => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => function ($value) use ($itemCaster) {

                            // FE gửi array trực tiếp
                            if (is_array($value)) {
                                return array_values(
                                    array_filter(array_map($itemCaster, $value))
                                );
                            }

                            // FE gửi JSON string
                            if (is_string($value) && $value !== '') {
                                $decoded = json_decode($value, true);

                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    return array_values(
                                        array_filter(array_map($itemCaster, $decoded))
                                    );
                                }
                            }

                            return [];
                        }
                    ]
                ],
            ],

            'validators' => $required
                ? [[
                    'name'                   => NotEmpty::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => "Trường '{$name}' không được để trống"
                        ]
                    ]
                ]]
                : [],
        ];
    }

    // ========================================================
    //  ARRAY<int> — Mảng các số nguyên
    //  - FE gửi [1,2,3] hoặc ["1","2"] đều ép về int[]
    // ========================================================
    public static function intArrayField(
        string $name,
        bool   $required = false
    ): array
    {
        return self::_buildArrayField($name, $required, function ($v) {
            return is_numeric($v) ? intval($v) : null;
        });
    }

    // ========================================================
    //  ARRAY<string> — Mảng chuỗi
    //  - FE gửi ["a","b"] hoặc [1,2] → tất cả ép về string[]
    // ========================================================
    public static function stringArrayField(
        string $name,
        bool   $required = false
    ): array
    {
        return self::_buildArrayField($name, $required, function ($v) {
            $str = trim((string)$v);
            return $str !== '' ? $str : null;
        });
    }

    // ========================================================
    //  ARRAY<object> — Mảng object (array associative)
    //  - FE gửi object hoặc JSON → decode về array
    //  - Loại bỏ phần tử không phải object
    // ========================================================
    public static function objectArrayField(
        string $name,
        bool   $required = false
    ): array
    {
        return self::_buildArrayField($name, $required, function ($v) {
            if (is_array($v)) {
                return $v;
            }
            if (is_object($v)) {
                return (array)$v;
            }
            return null;
        });
    }
}