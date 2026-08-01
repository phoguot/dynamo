<?php

declare(strict_types=1);

namespace Crm\Form\Site;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Crm\Model\Site\SiteConst;
use Laminas\Validator\Regex;

class SiteSaveForm extends AppForm
{
    protected const string FORM_NAME = 'crm.site.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('customerId', true));

        $this->add([
            'name'       => 'code',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StringToUpper'],
            ],
            'validators' => [
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Mã công trình chỉ gồm chữ in hoa, số và dấu gạch ngang (2-30 ký tự).'],
                    ],
                ],
            ],
        ]);

        $this->add(CommonFieldFilters::dynamicField('name', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 200,
        ]));

        $this->add(CommonFieldFilters::dynamicField('address', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
        $this->add(CommonFieldFilters::dynamicField('contactName', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::dynamicField('contactPhone', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 20,
        ]));
        $this->add(CommonFieldFilters::dynamicField('installConditions', [
            'type'      => CommonFieldFilters::TYPE_RICH_TEXT,
            'maxLength' => CommonFieldFilters::LEN_CONTENT,
        ]));
        $this->add(CommonFieldFilters::dynamicField('accessNote', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(SiteConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('latitude', ['type' => CommonFieldFilters::TYPE_FLOAT]));
        $this->add(CommonFieldFilters::dynamicField('longitude', ['type' => CommonFieldFilters::TYPE_FLOAT]));
    }

    protected function validateBusinessRules(): bool
    {
        $hasLatitude = $this->getSubmittedValue('latitude') !== null;
        $hasLongitude = $this->getSubmittedValue('longitude') !== null;
        if ($hasLatitude !== $hasLongitude) {
            $this->setError('latitude', 'Phải nhập đủ cả vĩ độ và kinh độ, hoặc bỏ trống cả hai.');
            return false;
        }

        return true;
    }

    /** @return array<string, string> */
    public function statusChoices(): array
    {
        return SiteConst::STATUS_LABELS;
    }
}
