<?php

declare(strict_types=1);

namespace Rental\Form\RentalOrder;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Rental\Model\RentalOrder\RentalOrderConst;

class RentalOrderSearchForm extends AppForm
{
    protected const string FORM_NAME = 'rental.order.search';
    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));
        $this->add(CommonFieldFilters::intField('customerId'));
        $this->add(CommonFieldFilters::intField('generatorId'));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(RentalOrderConst::STATUS_LABELS),
        ]));
        $this->initInputPaging();
        $this->initSorting(RentalOrderConst::SORT_DEFAULT, 'desc', array_keys(RentalOrderConst::SORT_MAP));
    }

    public function statusChoices(): array
    {
        return RentalOrderConst::STATUS_LABELS;
    }
}

