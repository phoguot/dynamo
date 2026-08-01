<?php
declare(strict_types=1);

namespace Application\Model\Constant;

/**
 * Base class cho các Const class khai báo extraContent fields
 * - Subclass định nghĩa $allowedExtraFields với key => type
 * - EXT_* constants khai báo tên các key
 */
class AppConstModel
{
    public static array $allowedExtraFields = [];

    public static function isAllowedExtraField(string $key): bool
    {
        return isset(static::$allowedExtraFields[$key]);
    }

    public static function castValueField(string $key, mixed $value): mixed
    {
        $castType = static::$allowedExtraFields[$key] ?? null;
        return match ($castType) {
            'int'    => (int)$value,
            'float'  => (float)$value,
            'bool'   => (bool)$value,
            'string' => (string)$value,
            default  => $value,
        };
    }

    /**
     * Trả về mảng chỉ gồm các key hợp lệ, đã cast đúng type, bỏ qua giá trị null/rỗng
     */
    public static function getExtraFieldsArray(mixed $extraFields): array
    {
        $fields = is_array($extraFields) ? $extraFields : [];
        $result = [];
        foreach ($fields as $key => $value) {
            if (isset(static::$allowedExtraFields[$key]) && $value !== null && $value !== '') {
                $result[$key] = static::castValueField($key, $value);
            }
        }
        return $result;
    }
}
