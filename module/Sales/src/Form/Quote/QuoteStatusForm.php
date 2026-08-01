<?php

declare(strict_types=1);

namespace Sales\Form\Quote;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\Quote\QuoteConst;

class QuoteStatusForm extends AppForm
{
    protected const string FORM_NAME = 'sales.quote.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(QuoteConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('rejectReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
    }
}

