<?php

declare(strict_types=1);

namespace Sales\Form\Contract;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Sales\Model\Contract\ContractConst;

class ContractStatusForm extends AppForm
{
    protected const string FORM_NAME = 'sales.contract.status';

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::intField('id', true));
        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'required'   => true,
            'enumValues' => array_keys(ContractConst::STATUS_LABELS),
        ]));
        $this->add(CommonFieldFilters::dynamicField('cancelReason', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 500,
        ]));
    }
}

