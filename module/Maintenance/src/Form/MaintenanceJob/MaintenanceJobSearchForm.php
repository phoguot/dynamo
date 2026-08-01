<?php

declare(strict_types=1);

namespace Maintenance\Form\MaintenanceJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;

class MaintenanceJobSearchForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.job.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 30,
        ]));
        foreach (['generatorId', 'scheduleId', 'assigneeId', 'page', 'limit'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        foreach ([
            'jobType'  => array_keys(MaintenanceJobConst::TYPE_LABELS),
            'priority' => array_keys(MaintenanceJobConst::PRIORITY_LABELS),
            'status'   => array_keys(MaintenanceJobConst::STATUS_LABELS),
            'sort'     => array_keys(MaintenanceJobConst::SORT_MAP),
            'dir'      => ['asc', 'desc'],
        ] as $field => $values) {
            $this->add(CommonFieldFilters::dynamicField($field, [
                'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
                'enumValues' => $values,
            ]));
        }
    }

    public function typeChoices(): array { return MaintenanceJobConst::TYPE_LABELS; }
    public function priorityChoices(): array { return MaintenanceJobConst::PRIORITY_LABELS; }
    public function statusChoices(): array { return MaintenanceJobConst::STATUS_LABELS; }
}
