<?php

declare(strict_types=1);

namespace Billing\Form\InvoiceLine;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Billing\Model\InvoiceLine\InvoiceLineConst;

class InvoiceLineSaveForm extends AppForm
{
    protected const string FORM_NAME = 'billing.invoice.line.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('invoiceId', true));
        $this->add(CommonFieldFilters::dynamicField('lineType', ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'required' => true, 'enumValues' => array_keys(InvoiceLineConst::TYPE_LABELS)]));
        $this->add(CommonFieldFilters::intField('generatorId'));
        $this->add(CommonFieldFilters::dynamicField('description', ['type' => CommonFieldFilters::TYPE_TEXT, 'required' => true, 'maxLength' => 255]));
        $this->add(CommonFieldFilters::dynamicField('quantity', ['type' => CommonFieldFilters::TYPE_FLOAT, 'required' => true]));
        $this->add(CommonFieldFilters::dynamicField('unit', ['type' => CommonFieldFilters::TYPE_ENUM_STRING, 'enumValues' => array_keys(InvoiceLineConst::UNIT_LABELS)]));
        $this->add(CommonFieldFilters::intField('unitPrice'));
        $this->add(CommonFieldFilters::intField('isVatable'));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((float)($data['quantity'] ?? 0) <= 0) {
            $this->setError('quantity', 'Số lượng phải lớn hơn 0.');
            return false;
        }
        if ((int)($data['unitPrice'] ?? 0) < 0) {
            $this->setError('unitPrice', 'Đơn giá không được âm.');
            return false;
        }
        return true;
    }

    public function typeChoices(): array { return InvoiceLineConst::TYPE_LABELS; }
    public function unitChoices(): array { return InvoiceLineConst::UNIT_LABELS; }
}
