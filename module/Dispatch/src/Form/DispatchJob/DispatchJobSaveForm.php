<?php

declare(strict_types=1);

namespace Dispatch\Form\DispatchJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\DispatchJob\DispatchJobConst;
use Laminas\Validator\Regex;

class DispatchJobSaveForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.job.save';

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
                    'options'                => ['messages' => ['isEmpty' => 'Bạn chưa nhập số lệnh.']],
                ],
                [
                    'name'                   => Regex::class,
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'pattern'  => '/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/',
                        'messages' => [Regex::NOT_MATCH => 'Số lệnh chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu gạch chéo.'],
                    ],
                ],
            ],
        ]);
        $this->add(CommonFieldFilters::dynamicField('jobType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(DispatchJobConst::TYPE_LABELS),
        ]));
        foreach (['rentalOrderId', 'generatorId', 'newGeneratorId', 'siteId', 'vehicleId'] as $field) {
            $this->add(CommonFieldFilters::intField($field, in_array($field, ['rentalOrderId', 'generatorId'], true)));
        }
        $this->add(CommonFieldFilters::dynamicField('scheduledAt', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 19,
        ]));
        $this->add(CommonFieldFilters::dynamicField('priority', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(DispatchJobConst::PRIORITY_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $scheduledAt = (string)($data['scheduledAt'] ?? '');
        if ($scheduledAt !== '' && !$this->isDateTime($scheduledAt)) {
            $this->setError('scheduledAt', 'Lịch hẹn không hợp lệ.');
            return false;
        }
        if (($data['jobType'] ?? '') === DispatchJobConst::TYPE_SWAP && (int)($data['newGeneratorId'] ?? 0) <= 0) {
            $this->setError('newGeneratorId', 'Lệnh đổi máy cần nhập ID máy mới.');
            return false;
        }
        if ((int)($data['newGeneratorId'] ?? 0) > 0 && (int)$data['newGeneratorId'] === (int)($data['generatorId'] ?? 0)) {
            $this->setError('newGeneratorId', 'Máy mới phải khác máy đang thu hồi.');
            return false;
        }

        return true;
    }

    public function typeChoices(): array { return DispatchJobConst::TYPE_LABELS; }
    public function priorityChoices(): array { return DispatchJobConst::PRIORITY_LABELS; }

    private function isDateTime(string $value): bool
    {
        $value = str_replace('T', ' ', $value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            return false;
        }

        return checkdate((int)$m[2], (int)$m[3], (int)$m[1])
            && (int)$m[4] <= 23
            && (int)$m[5] <= 59
            && (int)($m[6] ?? 0) <= 59;
    }
}
