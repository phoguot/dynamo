<?php

declare(strict_types=1);

namespace Sales\Form\PriceList;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\PriceListItem\PriceListItemConst;

class PriceListItemSaveForm extends AppForm
{
    protected const string FORM_NAME = 'sales.price_list_item.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('priceListId', true));
        foreach (['capacityFrom', 'capacityTo', 'minDays', 'unitPrice', 'dailyRate', 'deliveryFee', 'installFee', 'depositAmount'] as $field) {
            $this->add(CommonFieldFilters::intField($field, in_array($field, ['capacityFrom', 'capacityTo', 'minDays', 'unitPrice'], true)));
        }
        $this->add(CommonFieldFilters::dynamicField('durationTier', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(PriceListItemConst::DURATION_LABELS),
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['capacityFrom'] ?? 0) <= 0) {
            $this->setError('capacityFrom', 'Công suất từ phải lớn hơn 0.');
            return false;
        }
        if ((int)($data['capacityTo'] ?? 0) < (int)($data['capacityFrom'] ?? 0)) {
            $this->setError('capacityTo', 'Công suất đến phải lớn hơn hoặc bằng công suất từ.');
            return false;
        }
        if ((int)($data['minDays'] ?? 0) <= 0) {
            $this->setError('minDays', 'Số ngày tối thiểu phải lớn hơn 0.');
            return false;
        }
        foreach (['unitPrice', 'dailyRate', 'deliveryFee', 'installFee', 'depositAmount'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $this->setError($field, 'Giá trị tiền không được âm.');
                return false;
            }
        }

        return true;
    }

    public function durationChoices(): array
    {
        return PriceListItemConst::DURATION_LABELS;
    }
}

