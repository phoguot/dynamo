<?php

declare(strict_types=1);

namespace Billing\Form\Payment;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Billing\Model\Payment\PaymentConst;
use Laminas\Validator\Regex;

class PaymentSaveForm extends AppForm
{
    protected const string FORM_NAME = 'billing.payment.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name' => 'paymentNo',
            'required' => true,
            'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'StringToUpper']],
            'validators' => [
                ['name' => 'NotEmpty', 'break_chain_on_failure' => true, 'options' => ['messages' => ['isEmpty' => 'Bạn chưa nhập số phiếu thu.']]],
                ['name' => Regex::class, 'break_chain_on_failure' => true, 'options' => ['pattern' => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/', 'messages' => [Regex::NOT_MATCH => 'Số phiếu thu không hợp lệ.']]],
            ],
        ]);
        foreach (['invoiceId', 'customerId', 'amount', 'attachmentId'] as $field) {
            $this->add(CommonFieldFilters::intField($field, in_array($field, ['customerId', 'amount'], true)));
        }
        $this->add(CommonFieldFilters::dynamicField('paymentDate', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true, 'maxLength' => 10]));
        $this->add(CommonFieldFilters::dynamicField('method', ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'required' => true, 'enumValues' => array_keys(PaymentConst::METHOD_LABELS)]));
        $this->add(CommonFieldFilters::dynamicField('referenceNo', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 60]));
        $this->add(CommonFieldFilters::dynamicField('note', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 500]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['amount'] ?? 0) <= 0) {
            $this->setError('amount', 'Số tiền thanh toán phải lớn hơn 0.');
            return false;
        }
        if (!$this->isDate((string)($data['paymentDate'] ?? ''))) {
            $this->setError('paymentDate', 'Ngày thanh toán không hợp lệ.');
            return false;
        }
        return true;
    }

    public function methodChoices(): array { return PaymentConst::METHOD_LABELS; }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
