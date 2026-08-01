<?php

declare(strict_types=1);

namespace Dispatch\Form\DispatchJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\DispatchJob\DispatchJobConst;

class DispatchJobStatusForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.job.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(DispatchJobConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('failReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
        $this->add(CommonFieldFilters::dynamicField('feeBearer', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(DispatchJobConst::FEE_BEARER_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('actualEndDate', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 10,
        ]));
        $this->add(CommonFieldFilters::dynamicField('startHourMeter', [
            'type' => CommonFieldFilters::TYPE_FLOAT,
        ]));
        $this->add(CommonFieldFilters::dynamicField('endHourMeter', [
            'type' => CommonFieldFilters::TYPE_FLOAT,
        ]));
    }

    public function feeBearerChoices(): array
    {
        return DispatchJobConst::FEE_BEARER_LABELS;
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $actualEndDate = (string)($data['actualEndDate'] ?? '');
        if ($actualEndDate !== '' && !$this->isDate($actualEndDate)) {
            $this->setError('actualEndDate', 'Ngay thu hoi thuc te khong hop le.');
            return false;
        }

        foreach (['startHourMeter', 'endHourMeter'] as $field) {
            if (($data[$field] ?? null) !== null && (float)$data[$field] < 0) {
                $this->setError($field, 'Chi so gio may khong duoc am.');
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
