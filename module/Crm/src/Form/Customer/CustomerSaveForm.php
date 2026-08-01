<?php

declare(strict_types=1);

namespace Crm\Form\Customer;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Crm\Model\Customer\CustomerConst;
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Regex;

class CustomerSaveForm extends AppForm
{
    protected const string FORM_NAME = 'crm.customer.save';

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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập mã khách hàng.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Mã khách hàng chỉ gồm chữ in hoa, số và dấu gạch ngang (2-30 ký tự).'],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('name', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 200,
        ]));

        $this->add(CommonFieldFilters::dynamicField('customerType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(CustomerConst::TYPE_LABELS),
        ]));

        foreach (['taxCode', 'idNumber', 'phone', 'bankAccount'] as $fieldName) {
            $this->add(CommonFieldFilters::dynamicField($fieldName, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'maxLength' => 40,
            ]));
        }

        $this->add([
            'name'       => 'email',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StringToLower'],
            ],
            'validators' => [
                [
                    'name'                   => EmailAddress::class,
                    'break_chain_on_failure' => true,
                    'options'                => ['messages' => [EmailAddress::INVALID_FORMAT => 'Email không đúng định dạng.']],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('address', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));

        $this->add(CommonFieldFilters::intField('salesOwnerId'));

        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_RICH_TEXT,
            'maxLength' => CommonFieldFilters::LEN_CONTENT,
        ]));
    }

    /** @return array<string, string> */
    public function typeChoices(): array
    {
        return CustomerConst::TYPE_LABELS;
    }
}
