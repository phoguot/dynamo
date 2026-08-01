<?php

declare(strict_types=1);

namespace Fleet\Form\Generator;

use Application\Form\AppForm;
use Application\Filter\CommonFieldFilters;
use Fleet\Model\Generator\GeneratorConst;
use Laminas\Validator\GreaterThan;
use Laminas\Validator\Regex;

/**
 * Form thêm mới / cập nhật máy phát điện — dùng chung cho cả hai, `id` không bắt buộc.
 *
 * Toàn bộ luật validate HÌNH THỨC nằm ở đây. Luật cần TRUY VẤN DB (trùng mã máy, trùng
 * serial) nằm ở GeneratorService — form không biết gì về database.
 *
 * Markup của form này ở `module/Fleet/view/partial/generator/save-form.phtml`.
 */
class GeneratorSaveForm extends AppForm
{
    protected const string FORM_NAME = 'fleet.generator.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));

        // Mã máy: chữ HOA, số, gạch ngang — dùng để tra cứu nhanh ngoài hiện trường.
        $this->add([
            'name'       => 'code',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StringToUpper'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập mã máy.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-]{1,29}$/',
                        'messages' => [
                            Regex::NOT_MATCH => 'Mã máy chỉ gồm chữ in hoa, số và dấu gạch ngang (2–30 ký tự).',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('name', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));

        foreach (['serialNumber', 'manufacturer', 'model', 'currentLocation'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'maxLength' => CommonFieldFilters::LEN_TITLE,
            ]));
        }

        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));

        // Công suất kVA: bắt buộc và phải dương — máy 0 kVA là dữ liệu rác.
        $this->add([
            'name'       => 'capacityKva',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'Digits'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập công suất (kVA).']],
                ],
                [
                    'name'                   => GreaterThan::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'min'      => 0,
                        'messages' => [GreaterThan::NOT_GREATER => 'Công suất phải lớn hơn 0 kVA.'],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('fuelType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(GeneratorConst::FUEL_LABELS),
        ]));

        // Giờ máy chỉ nhập khi tạo mới (máy cũ mua về đã có chỉ số sẵn).
        $this->add([
            'name'       => 'hourMeter',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'ToFloat'],
            ],
            'validators' => [
                [
                    'name'    => GreaterThan::class,
                    'options' => [
                        'min'       => 0,
                        'inclusive' => true,
                        'messages'  => [GreaterThan::NOT_GREATER_INCLUSIVE => 'Giờ máy không được âm.'],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('latitude', [
            'type' => CommonFieldFilters::TYPE_FLOAT,
        ]));

        $this->add(CommonFieldFilters::dynamicField('longitude', [
            'type' => CommonFieldFilters::TYPE_FLOAT,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        // Tọa độ (nếu có) phải đủ cặp — một nửa tọa độ là vô nghĩa trên bản đồ.
        $hasLatitude  = $this->getSubmittedValue('latitude') !== null;
        $hasLongitude = $this->getSubmittedValue('longitude') !== null;

        if ($hasLatitude !== $hasLongitude) {
            $this->setError('latitude', 'Phải nhập đủ cả vĩ độ và kinh độ, hoặc bỏ trống cả hai.');
            return false;
        }

        return true;
    }

    /** Danh sách nhiên liệu cho ô chọn — view không tự đọc enum. */
    public function fuelChoices(): array
    {
        return GeneratorConst::FUEL_LABELS;
    }
}
