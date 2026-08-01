<?php

declare(strict_types=1);

namespace Sales\Form\PriceList;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\PriceList\PriceListConst;

class PriceListSearchForm extends AppForm
{
    protected const string FORM_NAME = 'sales.price_list.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::intField('isActive'));
        $this->initInputPaging();
        $this->initSorting(PriceListConst::SORT_DEFAULT, 'desc', array_keys(PriceListConst::SORT_MAP));
    }
}

