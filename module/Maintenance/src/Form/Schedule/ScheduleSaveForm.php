<?php

declare(strict_types=1);

namespace Maintenance\Form\Schedule;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Maintenance\Model\Schedule\ScheduleConst;

class ScheduleSaveForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.schedule.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add(CommonFieldFilters::intField('generatorId', true));
        $this->add(CommonFieldFilters::dynamicField('scheduleType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(ScheduleConst::TYPE_LABELS),
        ]));
        foreach (['intervalHours', 'lastServiceHour', 'nextDueHour'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, ['type' => CommonFieldFilters::TYPE_FLOAT]));
        }
        $this->add(CommonFieldFilters::intField('intervalDays'));
        foreach (['lastServiceDate', 'nextDueDate'] as $field) {
            $this->add(CommonFieldFilters::dynamicField($field, [
                'type'      => CommonFieldFilters::TYPE_TEXT,
                'maxLength' => 10,
            ]));
        }
        $this->add(CommonFieldFilters::intField('isActive'));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $type = (string)($data['scheduleType'] ?? '');
        $intervalHours = (float)($data['intervalHours'] ?? 0);
        $intervalDays = (int)($data['intervalDays'] ?? 0);

        if ((int)($data['generatorId'] ?? 0) <= 0) {
            $this->setError('generatorId', 'Bạn chưa nhập ID máy.');
            return false;
        }
        if (in_array($type, [ScheduleConst::TYPE_HOUR, ScheduleConst::TYPE_BOTH], true) && $intervalHours <= 0) {
            $this->setError('intervalHours', 'Lịch theo giờ máy cần ngưỡng giờ máy lớn hơn 0.');
            return false;
        }
        if (in_array($type, [ScheduleConst::TYPE_DAY, ScheduleConst::TYPE_BOTH], true) && $intervalDays <= 0) {
            $this->setError('intervalDays', 'Lịch theo ngày cần ngưỡng ngày lớn hơn 0.');
            return false;
        }
        foreach (['lastServiceHour' => 'Giờ bảo trì gần nhất', 'nextDueHour' => 'Giờ tới hạn'] as $field => $label) {
            if ((float)($data[$field] ?? 0) < 0) {
                $this->setError($field, "{$label} không được âm.");
                return false;
            }
        }
        foreach (['lastServiceDate' => 'Ngày bảo trì gần nhất', 'nextDueDate' => 'Ngày tới hạn'] as $field => $label) {
            $value = (string)($data[$field] ?? '');
            if ($value !== '' && !$this->isDate($value)) {
                $this->setError($field, "{$label} không hợp lệ.");
                return false;
            }
        }

        return true;
    }

    public function typeChoices(): array
    {
        return ScheduleConst::TYPE_LABELS;
    }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
