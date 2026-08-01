<?php

declare(strict_types=1);

namespace Billing\Form\Deposit;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Billing\Model\Deposit\DepositConst;

class DepositSearchForm extends AppForm
{
    protected const string FORM_NAME = 'billing.deposit.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 30]));
        foreach (['customerId', 'contractId', 'rentalOrderId', 'page', 'limit'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        foreach ([
            'status' => array_keys(DepositConst::STATUS_LABELS),
            'sort' => array_keys(DepositConst::SORT_MAP),
            'dir' => ['asc', 'desc'],
        ] as $field => $values) {
            $this->add(CommonFieldFilters::dynamicField($field, ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'enumValues' => $values]));
        }
    }

    public function statusChoices(): array { return DepositConst::STATUS_LABELS; }
}
