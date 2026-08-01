<?php

declare(strict_types=1);

namespace Maintenance\Form\PartUsed;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Maintenance\Model\PartUsed\PartUsedConst;

class PartUsedSaveForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.part.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('jobId', true));
        $this->add(CommonFieldFilters::dynamicField('partCode', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 60,
        ]));
        $this->add(CommonFieldFilters::dynamicField('partName', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'required'  => true,
            'maxLength' => 200,
        ]));
        $this->add(CommonFieldFilters::dynamicField('quantity', [
            'type'     => CommonFieldFilters::TYPE_FLOAT,
            'required' => true,
        ]));
        $this->add(CommonFieldFilters::dynamicField('unit', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(PartUsedConst::UNIT_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('unitPrice'));
        $this->add(CommonFieldFilters::dynamicField('supplier', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 200,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['jobId'] ?? 0) <= 0) {
            $this->setError('jobId', 'Thiếu ID phiếu bảo trì.');
            return false;
        }
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

    public function unitChoices(): array
    {
        return PartUsedConst::UNIT_LABELS;
    }
}
