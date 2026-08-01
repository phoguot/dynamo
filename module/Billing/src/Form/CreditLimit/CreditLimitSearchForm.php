<?php

declare(strict_types=1);

namespace Billing\Form\CreditLimit;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

class CreditLimitSearchForm extends AppForm
{
    protected const string FORM_NAME = 'billing.credit.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        foreach (['customerId', 'isBlocked', 'page', 'limit'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
    }
}
