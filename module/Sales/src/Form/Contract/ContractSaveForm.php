<?php

declare(strict_types=1);

namespace Sales\Form\Contract;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;
use Sales\Model\Contract\ContractConst;

class ContractSaveForm extends AppForm
{
    protected const string FORM_NAME = 'sales.contract.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name'       => 'contractNo',
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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập số hợp đồng.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số hợp đồng chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.'],
                    ],
                ],
            ],
        ]);

        foreach (['quoteId', 'customerId', 'siteId', 'totalAmount', 'depositAmount', 'paymentTermDays'] as $field) {
            $this->add(CommonFieldFilters::intField($field, $field === 'customerId'));
        }

        foreach (['signedDate', 'effectiveFrom', 'effectiveTo'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'required'  => in_array($field, ['effectiveFrom', 'effectiveTo'], true),
                'maxLength' => 10,
            ]));
        }

        $this->add(CommonFieldFilters::dynamicField('billingCycle', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(ContractConst::BILLING_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('creditOverrideReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
        $this->add(CommonFieldFilters::dynamicField('terms', [
            'type'      => CommonFieldFilters::TYPE_RICH_TEXT,
            'maxLength' => CommonFieldFilters::LEN_CONTENT,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        foreach (['effectiveFrom' => 'Ngày hiệu lực từ', 'effectiveTo' => 'Ngày hiệu lực đến'] as $field => $label) {
            if (!$this->isDate((string)($data[$field] ?? ''))) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
        }

        if (strcmp((string)$data['effectiveFrom'], (string)$data['effectiveTo']) >= 0) {
            $this->setError('effectiveTo', 'Ngày hiệu lực đến phải sau ngày hiệu lực từ.');
            return false;
        }

        foreach (['totalAmount', 'depositAmount', 'paymentTermDays'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Giá trị không được âm.');
                return false;
            }
        }

        return true;
    }

    public function billingChoices(): array
    {
        return ContractConst::BILLING_LABELS;
    }

    private function isDate(string $value): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}

