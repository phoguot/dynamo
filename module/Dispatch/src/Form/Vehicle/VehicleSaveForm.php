<?php

declare(strict_types=1);

namespace Dispatch\Form\Vehicle;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\Vehicle\VehicleConst;
use Laminas\Validator\Regex;

class VehicleSaveForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.vehicle.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id'));
        foreach (['code' => 'mã xe', 'plateNumber' => 'biển số'] as $field => $label) {
            $this->add([
                'name'       => $field,
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
                        'options'                => ['messages' => ['isEmpty' => "Bạn chưa nhập {$label}."]],
                    ],
                    [
                        'name'                   => Regex::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'pattern'  => '/^[A-Z0-9][A-Z0-9\-\.]{1,29}$/',
                            'messages' => [Regex::NOT_MATCH => "{$label} chỉ gồm chữ in hoa, số, dấu gạch ngang hoặc dấu chấm."],
                        ],
                    ],
                ],
            ]);
        }
        $this->add(CommonFieldFilters::dynamicField('vehicleType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(VehicleConst::TYPE_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('capacityKg'));
        $this->add(CommonFieldFilters::intField('driverId'));
        $this->add(CommonFieldFilters::dynamicField('note', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 255,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['capacityKg'] ?? 0) < 0) {
            $this->setError('capacityKg', 'Tải trọng không được âm.');
            return false;
        }

        return true;
    }

    public function typeChoices(): array
    {
        return VehicleConst::TYPE_LABELS;
    }
}
