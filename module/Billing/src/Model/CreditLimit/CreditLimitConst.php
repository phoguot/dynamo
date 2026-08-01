<?php

declare(strict_types=1);

namespace Billing\Model\CreditLimit;

use Application\Model\Constant\AppConstModel;

class CreditLimitConst extends AppConstModel
{
    public const string EVENT_CREDIT_EXCEEDED = 'billing.credit.exceeded';
    public const string EVENT_CREDIT_CLEARED = 'billing.credit.cleared';
}
