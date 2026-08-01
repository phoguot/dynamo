<?php

declare(strict_types=1);

namespace Dispatch\Form\Vehicle;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\Vehicle\VehicleConst;

class VehicleStatusForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.vehicle.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(VehicleConst::STATUS_LABELS),
        ]));
    }
}
