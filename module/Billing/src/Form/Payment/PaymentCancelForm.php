<?php

declare(strict_types=1);

namespace Billing\Form\Payment;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

class PaymentCancelForm extends AppForm
{
    protected const string FORM_NAME = 'billing.payment.cancel';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('cancelReason', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true, 'maxLength' => 500]));
    }
}
