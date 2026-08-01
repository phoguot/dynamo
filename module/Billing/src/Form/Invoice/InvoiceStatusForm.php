<?php

declare(strict_types=1);

namespace Billing\Form\Invoice;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Billing\Model\Invoice\InvoiceConst;

class InvoiceStatusForm extends AppForm
{
    protected const string FORM_NAME = 'billing.invoice.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'required' => true, 'enumValues' => array_keys(InvoiceConst::STATUS_LABELS)]));
        $this->add(CommonFieldFilters::dynamicField('voidReason', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 500]));
    }
}
