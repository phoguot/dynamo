<?php

declare(strict_types=1);

namespace SalesTest\Model;

use PHPUnit\Framework\TestCase;
use Sales\Model\Contract\ContractConst;
use Sales\Model\PriceListItem\PriceListItemConst;
use Sales\Model\Quote\QuoteConst;

class SalesStateMachineTest extends TestCase
{
    public function test_quote_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(QuoteConst::canTransit(QuoteConst::STATUS_DRAFT, QuoteConst::STATUS_PENDING));
        self::assertTrue(QuoteConst::canTransit(QuoteConst::STATUS_PENDING, QuoteConst::STATUS_APPROVED));
        self::assertTrue(QuoteConst::canTransit(QuoteConst::STATUS_PENDING, QuoteConst::STATUS_REJECTED));
        self::assertTrue(QuoteConst::canTransit(QuoteConst::STATUS_APPROVED, QuoteConst::STATUS_EXPIRED));
    }

    public function test_quote_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(QuoteConst::canTransit(QuoteConst::STATUS_DRAFT, QuoteConst::STATUS_APPROVED));
        self::assertFalse(QuoteConst::canTransit(QuoteConst::STATUS_REJECTED, QuoteConst::STATUS_PENDING));
        self::assertFalse(QuoteConst::canTransit(QuoteConst::STATUS_EXPIRED, QuoteConst::STATUS_APPROVED));
    }

    public function test_contract_state_machine_allows_expected_transitions(): void
    {
        self::assertTrue(ContractConst::canTransit(ContractConst::STATUS_DRAFT, ContractConst::STATUS_SIGNED));
        self::assertTrue(ContractConst::canTransit(ContractConst::STATUS_SIGNED, ContractConst::STATUS_ACTIVE));
        self::assertTrue(ContractConst::canTransit(ContractConst::STATUS_ACTIVE, ContractConst::STATUS_CLOSED));
        self::assertTrue(ContractConst::canTransit(ContractConst::STATUS_ACTIVE, ContractConst::STATUS_CANCELLED));
    }

    public function test_contract_state_machine_blocks_invalid_transitions(): void
    {
        self::assertFalse(ContractConst::canTransit(ContractConst::STATUS_DRAFT, ContractConst::STATUS_ACTIVE));
        self::assertFalse(ContractConst::canTransit(ContractConst::STATUS_CLOSED, ContractConst::STATUS_CANCELLED));
        self::assertFalse(ContractConst::canTransit(ContractConst::STATUS_CANCELLED, ContractConst::STATUS_SIGNED));
    }

    public function test_duration_tier_matches_schema(): void
    {
        self::assertTrue(PriceListItemConst::isValidDurationTier('ngay'));
        self::assertTrue(PriceListItemConst::isValidDurationTier('tuan'));
        self::assertTrue(PriceListItemConst::isValidDurationTier('thang'));
        self::assertFalse(PriceListItemConst::isValidDurationTier('quy'));
    }
}

