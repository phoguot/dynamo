<?php

declare(strict_types=1);

namespace Maintenance\Form\MaintenanceJob;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;

class MaintenanceJobStatusForm extends AppForm
{
    protected const string FORM_NAME = 'maintenance.job.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(MaintenanceJobConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::intField('laborCost'));
        $this->add(CommonFieldFilters::dynamicField('findings', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 5000,
        ]));
        $this->add(CommonFieldFilters::dynamicField('cancelReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        if ((int)($data['laborCost'] ?? 0) < 0) {
            $this->setError('laborCost', 'Chi phí công thợ không được âm.');
            return false;
        }

        return true;
    }
}
