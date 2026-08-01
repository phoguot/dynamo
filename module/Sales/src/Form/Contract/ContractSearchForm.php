<?php

declare(strict_types=1);

namespace Sales\Form\Contract;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\Contract\ContractConst;

class ContractSearchForm extends AppForm
{
    protected const string FORM_NAME = 'sales.contract.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::intField('customerId'));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(ContractConst::STATUS_LABELS),
        ]));
        $this->initInputPaging();
        $this->initSorting(ContractConst::SORT_DEFAULT, 'desc', array_keys(ContractConst::SORT_MAP));
    }

    public function statusChoices(): array
    {
        return ContractConst::STATUS_LABELS;
    }
}

