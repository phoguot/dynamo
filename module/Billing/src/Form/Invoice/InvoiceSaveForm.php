<?php

declare(strict_types=1);

namespace Billing\Form\Invoice;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;

class InvoiceSaveForm extends AppForm
{
    protected const string FORM_NAME = 'billing.invoice.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name' => 'invoiceNo',
            'required' => true,
            'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'StringToUpper']],
            'validators' => [
                ['name' => 'NotEmpty', 'break_chain_on_failure' => true, 'options' => ['messages' => ['isEmpty' => 'Bạn chưa nhập số hóa đơn.']]],
                ['name' => Regex::class, 'break_chain_on_failure' => true, 'options' => ['pattern' => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/', 'messages' => [Regex::NOT_MATCH => 'Số hóa đơn chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.']]],
            ],
        ]);
        foreach (['customerId', 'contractId', 'rentalOrderId', 'vatRate'] as $field) {
            $this->add(CommonFieldFilters::intField($field, $field === 'customerId'));
        }
        foreach (['periodFrom', 'periodTo', 'issueDate', 'dueDate'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => in_array($field, ['periodFrom', 'periodTo'], true), 'maxLength' => 10]));
        }
        foreach (['rentAmount', 'surchargeAmount', 'discountAmount', 'vatAmount', 'totalAmount'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        $this->add(CommonFieldFilters::dynamicField('note', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 500]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        foreach (['periodFrom' => 'Từ ngày', 'periodTo' => 'Đến ngày', 'issueDate' => 'Ngày phát hành', 'dueDate' => 'Hạn thanh toán'] as $field => $label) {
            $value = (string)($data[$field] ?? '');
            if ($value !== '' && !$this->isDate($value)) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
        }
        if (strcmp((string)$data['periodFrom'], (string)$data['periodTo']) >= 0) {
            $this->setError('periodTo', 'Kỳ hóa đơn phải có ngày kết thúc sau ngày bắt đầu.');
            return false;
        }
        foreach (['rentAmount', 'surchargeAmount', 'discountAmount', 'vatRate', 'vatAmount', 'totalAmount'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Giá trị tiền/tỷ lệ không được âm.');
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
