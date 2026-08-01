<?php

declare(strict_types=1);

namespace Dispatch\Form\Assignment;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Dispatch\Model\Assignment\AssignmentConst;

class AssignmentSaveForm extends AppForm
{
    protected const string FORM_NAME = 'dispatch.assignment.save';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('jobId', true));
        $this->add(CommonFieldFilters::intField('userId', true));
        $this->add(CommonFieldFilters::dynamicField('roleInJob', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(AssignmentConst::ROLE_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('isLead'));
    }

    public function roleChoices(): array
    {
        return AssignmentConst::ROLE_LABELS;
    }
}
