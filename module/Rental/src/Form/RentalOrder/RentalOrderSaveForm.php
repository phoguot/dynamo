<?php

declare(strict_types=1);

namespace Rental\Form\RentalOrder;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;
use Sales\Model\PriceListItem\PriceListItemConst;

class RentalOrderSaveForm extends AppForm
{
    protected const string FORM_NAME = 'rental.order.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name'       => 'orderNo',
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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập số đơn thuê.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số đơn thuê chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.'],
                    ],
                ],
            ],
        ]);

        foreach (['contractId', 'customerId', 'siteId', 'generatorId', 'unitPrice'] as $field) {
            $this->add(CommonFieldFilters::intField($field, in_array($field, ['customerId', 'generatorId'], true)));
        }

        foreach (['startDate', 'expectedEndDate'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'required'  => true,
                'maxLength' => 10,
            ]));
        }

        $this->add(CommonFieldFilters::dynamicField('durationTier', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(PriceListItemConst::DURATION_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('withOperator'));
        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        foreach (['startDate' => 'Ngày bắt đầu', 'expectedEndDate' => 'Ngày kết thúc dự kiến'] as $field => $label) {
            if (!$this->isDate((string)($data[$field] ?? ''))) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
        }
        if (strcmp((string)$data['startDate'], (string)$data['expectedEndDate']) >= 0) {
            $this->setError('expectedEndDate', 'Ngày kết thúc dự kiến phải sau ngày bắt đầu.');
            return false;
        }
        if ((int)($data['unitPrice'] ?? 0) < 0) {
            $this->setError('unitPrice', 'Đơn giá không được âm.');
            return false;
        }

        return true;
    }

    public function durationChoices(): array
    {
        return PriceListItemConst::DURATION_LABELS;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
