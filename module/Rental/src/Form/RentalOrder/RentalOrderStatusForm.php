<?php

declare(strict_types=1);

namespace Rental\Form\RentalOrder;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Rental\Model\RentalOrder\RentalOrderConst;

class RentalOrderStatusForm extends AppForm
{
    protected const string FORM_NAME = 'rental.order.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(RentalOrderConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('cancelReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
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

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $actualEndDate = (string)($data['actualEndDate'] ?? '');
        if ($actualEndDate !== '' && !$this->isDate($actualEndDate)) {
            $this->setError('actualEndDate', 'Ngày thu hồi thực tế không hợp lệ.');
            return false;
        }

        foreach (['startHourMeter' => 'Chỉ số giờ máy đầu', 'endHourMeter' => 'Chỉ số giờ máy cuối'] as $field => $label) {
            if (($data[$field] ?? null) !== null && (float)$data[$field] < 0) {
                $this->setError($field, "{$label} không được âm.");
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
