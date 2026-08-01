<?php

declare(strict_types=1);

namespace Maintenance\Form\MaintenanceJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Laminas\Validator\Regex;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;

class MaintenanceJobSaveForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.job.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        $this->add([
            'name'       => 'jobNo',
            'required'   => true,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StringToUpper'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập số phiếu.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số phiếu chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.'],
                    ],
                ],
            ],
        ]);
        $this->add(CommonFieldFilters::intField('generatorId', true));
        $this->add(CommonFieldFilters::intField('scheduleId'));
        $this->add(CommonFieldFilters::dynamicField('jobType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(MaintenanceJobConst::TYPE_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('priority', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(MaintenanceJobConst::PRIORITY_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('triggerReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 255,
        ]));
        $this->add(CommonFieldFilters::dynamicField('triggerHourMeter', ['type' => CommonFieldFilters::TYPE_FLOAT]));
        $this->add(CommonFieldFilters::dynamicField('idempotencyKey', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 120,
        ]));
        $this->add(CommonFieldFilters::dynamicField('scheduledDate', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 10,
        ]));
        $this->add(CommonFieldFilters::intField('assigneeId'));
        $this->add(CommonFieldFilters::intField('laborCost'));
        $this->add(CommonFieldFilters::dynamicField('findings', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 5000,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['generatorId'] ?? 0) <= 0) {
            $this->setError('generatorId', 'Bạn chưa nhập ID máy.');
            return false;
        }
        if ((float)($data['triggerHourMeter'] ?? 0) < 0) {
            $this->setError('triggerHourMeter', 'Giờ máy kích hoạt không được âm.');
            return false;
        }
        if ((int)($data['laborCost'] ?? 0) < 0) {
            $this->setError('laborCost', 'Chi phí công thợ không được âm.');
            return false;
        }
        $scheduledDate = (string)($data['scheduledDate'] ?? '');
        if ($scheduledDate !== '' && !$this->isDate($scheduledDate)) {
            $this->setError('scheduledDate', 'Ngày lên lịch không hợp lệ.');
            return false;
        }

        return true;
    }

    public function typeChoices(): array { return MaintenanceJobConst::TYPE_LABELS; }
    public function priorityChoices(): array { return MaintenanceJobConst::PRIORITY_LABELS; }

    private function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
    }
}
