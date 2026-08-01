<?php

declare(strict_types=1);

namespace Sales\Form\Quote;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;

class QuoteSaveForm extends AppForm
{
    protected const string FORM_NAME = 'sales.quote.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name'       => 'quoteNo',
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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập số báo giá.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số báo giá chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.'],
                    ],
                ],
            ],
        ]);

        foreach (['customerId', 'siteId', 'priceListId'] as $field) {
            $this->add(CommonFieldFilters::intField($field, $field === 'customerId'));
        }

        foreach (['rentFrom', 'rentTo', 'validUntil'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'required'  => in_array($field, ['rentFrom', 'rentTo'], true),
                'maxLength' => 10,
            ]));
        }

        foreach ([
            'rentAmount', 'deliveryFee', 'installFee', 'otherFee', 'discountAmount',
            'vatRate', 'vatAmount', 'depositAmount',
        ] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }

        $this->add(CommonFieldFilters::objectArrayField('lines'));
        $this->add(CommonFieldFilters::dynamicField('terms', [
            'type'      => CommonFieldFilters::TYPE_RICH_TEXT,
            'maxLength' => CommonFieldFilters::LEN_CONTENT,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        foreach (['rentFrom' => 'Ngày bắt đầu thuê', 'rentTo' => 'Ngày kết thúc thuê'] as $field => $label) {
            if (!$this->isDate((string)($data[$field] ?? ''))) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
        }

        if (strcmp((string)$data['rentFrom'], (string)$data['rentTo']) >= 0) {
            $this->setError('rentTo', 'Ngày kết thúc phải sau ngày bắt đầu thuê.');
            return false;
        }

        foreach (['rentAmount', 'deliveryFee', 'installFee', 'otherFee', 'discountAmount', 'vatRate', 'vatAmount', 'depositAmount'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Giá trị tiền và phần trăm không được âm.');
                return false;
            }
        }

        return true;
    }

    private function isDate(string $value): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}
