<?php

declare(strict_types=1);

namespace Reporting\Form\Report;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Reporting\Model\ReportingConst;

class FleetUtilizationSearchForm extends AppForm
{
    protected const string FORM_NAME = 'reporting.fleet_utilization.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('reportDate', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 10]));
        $this->add(CommonFieldFilters::dynamicField('warehouseCode', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 32]));
        $this->initInputPaging();
        $this->initSorting(ReportingConst::FLEET_SORT_DEFAULT, 'desc', array_keys(ReportingConst::FLEET_SORT_MAP));
    }

    protected function validateBusinessRules(): bool
    {
        return $this->validateDate('reportDate', 'Ngày báo cáo');
    }

    private function validateDate(string $field, string $label): bool
    {
        $value = (string)($this->getData()[$field] ?? '');
        if ($value === '') {
            return true;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $this->setError($field, "{$label} không hợp lệ.");
            return false;
        }

        return true;
    }
}
