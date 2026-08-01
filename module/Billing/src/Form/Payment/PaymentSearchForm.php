<?php

declare(strict_types=1);

namespace Billing\Form\Payment;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Billing\Model\Payment\PaymentConst;

class PaymentSearchForm extends AppForm
{
    protected const string FORM_NAME = 'billing.payment.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 30]));
        foreach (['invoiceId', 'customerId', 'page', 'limit'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        foreach ([
            'status' => array_keys(PaymentConst::STATUS_LABELS),
            'sort' => array_keys(PaymentConst::SORT_MAP),
            'dir' => ['asc', 'desc'],
        ] as $field => $values) {
            $this->add(CommonFieldFilters::dynamicField($field, ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'enumValues' => $values]));
        }
    }

    public function statusChoices(): array { return PaymentConst::STATUS_LABELS; }
}
