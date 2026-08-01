<?php

declare(strict_types=1);

namespace Sales\Form\Quote;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\Quote\QuoteConst;

class QuoteSearchForm extends AppForm
{
    protected const string FORM_NAME = 'sales.quote.search';
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
            'enumValues' => array_keys(QuoteConst::STATUS_LABELS),
        ]));
        $this->initInputPaging();
        $this->initSorting(QuoteConst::SORT_DEFAULT, 'desc', array_keys(QuoteConst::SORT_MAP));
    }

    public function statusChoices(): array
    {
        return QuoteConst::STATUS_LABELS;
    }
}

