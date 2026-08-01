<?php

declare(strict_types=1);

namespace Reporting\Form\Report;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Reporting\Model\ReportingConst;

class ReceivablesSearchForm extends AppForm
{
    protected const string FORM_NAME = 'reporting.receivables.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('snapshotDate', ['type' => CommonFieldFilters::TYPE_TEXT, 'maxLength' => 10]));
        $this->add(CommonFieldFilters::intField('customerId'));
        $this->initInputPaging();
        $this->initSorting(ReportingConst::RECEIVABLES_SORT_DEFAULT, 'desc', array_keys(ReportingConst::RECEIVABLES_SORT_MAP));
    }

    protected function validateBusinessRules(): bool
    {
        $value = (string)($this->getData()['snapshotDate'] ?? '');
        if ($value === '') {
            return true;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $this->setError('snapshotDate', 'Ngày snapshot không hợp lệ.');
            return false;
        }

        return true;
    }
}
