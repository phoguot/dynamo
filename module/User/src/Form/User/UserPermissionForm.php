<?php

declare(strict_types=1);

namespace User\Form\User;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Filter\Callback as CallbackFilter;
use User\Model\Permission\PermissionConst;

/**
 * Form ma trận phân quyền của MỘT người dùng.
 *
 * Markup gửi lên dạng `perm[<resource>|<privilege>] = "" | allow | deny`:
 * - `""`      — theo mặc định của vai trò (không tạo dòng ngoại lệ nào)
 * - `allow`   — cấp thêm quyền này cho riêng người đó
 * - `deny`    — cắt quyền này của riêng người đó, dù vai trò có
 *
 * Form chỉ lọc rác về hình thức (bỏ ô để trống, ép về chuỗi). Việc kiểm cặp
 * resource/privilege có thật trong danh mục hay không nằm ở
 * `PermissionService::saveOverrides()` — nơi có đủ ngữ cảnh để báo lỗi ra đúng ô.
 */
class UserPermissionForm extends AppForm
{
    protected const string FORM_NAME = 'user.permission.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('userId', true));

        $this->add([
            'name'       => 'perm',
            'required'   => false,
            'filters'    => [
                [
                    'name'    => CallbackFilter::class,
                    'options' => [
                        'callback' => static function ($value): array {
                            if (!is_array($value)) {
                                return [];
                            }

                            $result = [];
                            foreach ($value as $key => $effect) {
                                $effect = is_string($effect) ? trim($effect) : '';
                                // Ô để trống = "theo vai trò" ⇒ không sinh dòng ngoại lệ.
                                if ($effect === '') {
                                    continue;
                                }
                                $result[(string)$key] = $effect;
                            }

                            return $result;
                        },
                    ],
                ],
            ],
            'validators' => [],
        ]);
    }

    /**
     * Danh mục quyền gom theo module, để view dựng bảng.
     *
     * @return array<string, array{label:string, resources:array<string, array{label:string, privileges:array<string,string>}>}>
     */
    public function permissionCatalog(): array
    {
        return PermissionConst::groupedByModule();
    }

    /** @return array<string, string> giá trị ô chọn hiệu lực */
    public function effectChoices(): array
    {
        return PermissionConst::EFFECT_LABELS;
    }
}
