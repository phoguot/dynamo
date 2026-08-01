<?php

declare(strict_types=1);

namespace BillingTest\Model\CreditLimit;

use Billing\Model\CreditLimit\CreditLimitConst;
use PHPUnit\Framework\TestCase;

class CreditLimitEventTest extends TestCase
{
    public function test_credit_limit_event_names_match_contract(): void
    {
        self::assertSame('billing.credit.exceeded', CreditLimitConst::EVENT_CREDIT_EXCEEDED);
        self::assertSame('billing.credit.cleared', CreditLimitConst::EVENT_CREDIT_CLEARED);
    }
}
