<?php

declare(strict_types=1);

namespace Reporting\Form\Report;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Reporting\Model\ReportingConst;

class RevenueSearchForm extends AppForm
{
    protected const string FORM_NAME = 'reporting.revenue.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        foreach (['periodYear', 'periodMonth', 'customerId'] as $field) {
            $this->add(CommonFieldFilters::intField($field));
        }
        $this->initInputPaging();
        $this->initSorting(ReportingConst::REVENUE_SORT_DEFAULT, 'desc', array_keys(ReportingConst::REVENUE_SORT_MAP));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $year = (int)($data['periodYear'] ?? 0);
        $month = (int)($data['periodMonth'] ?? 0);
        if ($year !== 0 && ($year < 2000 || $year > 2100)) {
            $this->setError('periodYear', 'Năm báo cáo không hợp lệ.');
            return false;
        }
        if ($month !== 0 && ($month < 1 || $month > 12)) {
            $this->setError('periodMonth', 'Tháng báo cáo phải từ 1 đến 12.');
            return false;
        }

        return true;
    }
}
