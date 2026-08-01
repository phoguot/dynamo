<?php

declare(strict_types=1);

namespace Dispatch\Form\DispatchJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\DispatchJob\DispatchJobConst;

class DispatchJobSearchForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.job.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::dynamicField('jobType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(DispatchJobConst::TYPE_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('rentalOrderId'));
        $this->add(CommonFieldFilters::intField('generatorId'));
        $this->add(CommonFieldFilters::intField('vehicleId'));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(DispatchJobConst::STATUS_LABELS),
        ]));
        $this->initInputPaging();
        $this->initSorting(DispatchJobConst::SORT_DEFAULT, 'asc', array_keys(DispatchJobConst::SORT_MAP));
    }

    public function typeChoices(): array { return DispatchJobConst::TYPE_LABELS; }
    public function statusChoices(): array { return DispatchJobConst::STATUS_LABELS; }
}
