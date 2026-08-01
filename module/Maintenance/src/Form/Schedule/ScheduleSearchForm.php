<?php

declare(strict_types=1);

namespace Maintenance\Form\Schedule;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Maintenance\Model\Schedule\ScheduleConst;

class ScheduleSearchForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.schedule.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('generatorId'));
        $this->add(CommonFieldFilters::dynamicField('scheduleType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(ScheduleConst::TYPE_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('isActive'));
        foreach (['page', 'limit'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        $this->add(CommonFieldFilters::dynamicField('sort', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(ScheduleConst::SORT_MAP),
        ]));
        $this->add(CommonFieldFilters::dynamicField('dir', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => ['asc', 'desc'],
        ]));
    }

    public function typeChoices(): array
    {
        return ScheduleConst::TYPE_LABELS;
    }
}
