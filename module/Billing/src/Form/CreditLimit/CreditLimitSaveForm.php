<?php

declare(strict_types=1);

namespace Billing\Form\CreditLimit;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

class CreditLimitSaveForm extends AppForm
{
    protected const string FORM_NAME = 'billing.credit.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('customerId', true));
        foreach (['creditLimit', 'currentDebt', 'overdueAmount'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        $this->add(CommonFieldFilters::intField('isBlocked'));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['customerId'] ?? 0) <= 0) {
            $this->setError('customerId', 'Bạn chưa nhập ID khách hàng.');
            return false;
        }
        foreach (['creditLimit', 'currentDebt', 'overdueAmount'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Số tiền không được âm.');
                return false;
            }
        }
        return true;
    }
}
