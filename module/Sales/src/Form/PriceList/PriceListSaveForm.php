<?php

declare(strict_types=1);

namespace Sales\Form\PriceList;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;

class PriceListSaveForm extends AppForm
{
    protected const string FORM_NAME = 'sales.price_list.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập mã bảng giá.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Mã bảng giá chỉ gồm chữ in hoa, số và dấu gạch ngang (2-30 ký tự).'],
                    ],
                ],
            ],
        ]);
        $this->add(CommonFieldFilters::dynamicField('name', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 200,
        ]));
        $this->add(CommonFieldFilters::dynamicField('validFrom', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 10,
        ]));
        $this->add(CommonFieldFilters::dynamicField('validTo', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 10,
        ]));
        $this->add(CommonFieldFilters::intField('isActive'));
        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if (!$this->isDate((string)($data['validFrom'] ?? ''))) {
            $this->setError('validFrom', 'Ngày bắt đầu hiệu lực không hợp lệ.');
            return false;
        }

        $validTo = (string)($data['validTo'] ?? '');
        if ($validTo !== '' && !$this->isDate($validTo)) {
            $this->setError('validTo', 'Ngày kết thúc hiệu lực không hợp lệ.');
            return false;
        }

        if ($validTo !== '' && strcmp((string)$data['validFrom'], $validTo) > 0) {
            $this->setError('validTo', 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
            return false;
        }

        return true;
    }

    private function isDate(string $value): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}

