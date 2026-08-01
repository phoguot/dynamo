<?php

declare(strict_types=1);

namespace Crm\Form\Customer;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Crm\Model\Customer\CustomerConst;

class CustomerStatusForm extends AppForm
{
    protected const string FORM_NAME = 'crm.customer.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(CustomerConst::STATUS_LABELS),
        ]));
    }

    /** @return array<string, string> */
    public function statusChoices(): array
    {
        return CustomerConst::STATUS_LABELS;
    }
}
