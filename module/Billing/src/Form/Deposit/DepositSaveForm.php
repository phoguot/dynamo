<?php

declare(strict_types=1);

namespace Billing\Form\Deposit;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;

class DepositSaveForm extends AppForm
{
    protected const string FORM_NAME = 'billing.deposit.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name' => 'depositNo',
            'required' => true,
            'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'StringToUpper']],
            'validators' => [
                ['name' => 'NotEmpty', 'break_chain_on_failure' => true, 'options' => ['messages' => ['isEmpty' => 'Bạn chưa nhập số cọc.']]],
                ['name' => Regex::class, 'break_chain_on_failure' => true, 'options' => ['pattern' => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/', 'messages' => [Regex::NOT_MATCH => 'Số cọc không hợp lệ.']]],
            ],
        ]);
        foreach (['customerId', 'contractId', 'rentalOrderId', 'amount', 'deductedAmount', 'refundedAmount'] as $field) {
            $this->add(CommonFieldFilters::intField($field, in_array($field, ['customerId', 'amount'], true)));
        }
        foreach (['receivedDate', 'refundedDate'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => $field === 'receivedDate', 'maxLength' => 10]));
        }
        $this->add(CommonFieldFilters::dynamicField('deductReason', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 500]));
        $this->add(CommonFieldFilters::dynamicField('note', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 500]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['amount'] ?? 0) <= 0) {
            $this->setError('amount', 'Số tiền cọc phải lớn hơn 0.');
            return false;
        }
        foreach (['deductedAmount', 'refundedAmount'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Số tiền không được âm.');
                return false;
            }
        }
        if ((int)($data['deductedAmount'] ?? 0) + (int)($data['refundedAmount'] ?? 0) > (int)($data['amount'] ?? 0)) {
            $this->setError('refundedAmount', 'Tổng tiền trừ và hoàn không được vượt tiền cọc.');
            return false;
        }
        foreach (['receivedDate' => 'Ngày nhận cọc', 'refundedDate' => 'Ngày hoàn cọc'] as $field => $label) {
            $value = (string)($data[$field] ?? '');
            if ($value !== '' && !$this->isDate($value)) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
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
