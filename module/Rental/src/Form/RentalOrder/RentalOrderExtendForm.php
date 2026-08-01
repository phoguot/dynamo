<?php

declare(strict_types=1);

namespace Rental\Form\RentalOrder;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

class RentalOrderExtendForm extends AppForm
{
    protected const string FORM_NAME = 'rental.order.extend';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('expectedEndDate', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 10,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        if (!$this->isDate((string)($this->getData()['expectedEndDate'] ?? ''))) {
            $this->setError('expectedEndDate', 'Ngày kết thúc mới không hợp lệ.');
            return false;
        }

        return true;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
