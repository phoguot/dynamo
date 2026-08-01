<?php

declare(strict_types=1);

namespace Crm\Form\Customer;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Crm\Model\Customer\CustomerConst;

class CustomerSearchForm extends AppForm
{
    protected const string FORM_NAME = 'crm.customer.search';

    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));

        $this->add(CommonFieldFilters::dynamicField('customerType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(CustomerConst::TYPE_LABELS),
        ]));

        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(CustomerConst::STATUS_LABELS),
        ]));

        $this->initInputPaging();
        $this->initSorting(CustomerConst::SORT_DEFAULT, 'asc', array_keys(CustomerConst::SORT_MAP));
    }

    /** @return array<string, string> */
    public function typeChoices(): array
    {
        return CustomerConst::TYPE_LABELS;
    }

    /** @return array<string, string> */
    public function statusChoices(): array
    {
        return CustomerConst::STATUS_LABELS;
    }
}
