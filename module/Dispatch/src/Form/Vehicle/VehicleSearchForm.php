<?php

declare(strict_types=1);

namespace Dispatch\Form\Vehicle;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\Vehicle\VehicleConst;

class VehicleSearchForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.vehicle.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::dynamicField('vehicleType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(VehicleConst::TYPE_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(VehicleConst::STATUS_LABELS),
        ]));
        $this->initInputPaging();
        $this->initSorting(VehicleConst::SORT_DEFAULT, 'asc', array_keys(VehicleConst::SORT_MAP));
    }

    public function typeChoices(): array
    {
        return VehicleConst::TYPE_LABELS;
    }

    public function statusChoices(): array
    {
        return VehicleConst::STATUS_LABELS;
    }
}
