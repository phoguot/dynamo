<?php

declare(strict_types=1);

namespace Crm\Form\Contact;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\EmailAddress;

class ContactSaveForm extends AppForm
{
    protected const string FORM_NAME = 'crm.contact.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('customerId', true));
        $this->add(CommonFieldFilters::intField('siteId'));

        $this->add(CommonFieldFilters::dynamicField('fullName', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::dynamicField('position', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::dynamicField('phone', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 20,
        ]));
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
        $this->add(CommonFieldFilters::dynamicField('isPrimary', [
            'type'       => CommonFieldFilters::TYPE_ENUM,
            'enumValues' => [0, 1],
        ]));
        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_DESCRIPTION,
        ]));
    }
}
