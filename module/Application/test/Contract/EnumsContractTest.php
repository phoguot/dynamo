<?php

declare(strict_types=1);

namespace ApplicationTest\Contract;

use Billing\Model\Deposit\DepositConst;
use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\InvoiceLine\InvoiceLineConst;
use Billing\Model\Payment\PaymentConst;
use Crm\Model\Customer\CustomerConst;
use Crm\Model\Site\SiteConst;
use Dispatch\Model\Assignment\AssignmentConst;
use Dispatch\Model\DispatchJob\DispatchJobConst;
use Dispatch\Model\Vehicle\VehicleConst;
use Fleet\Model\Generator\GeneratorConst;
use Maintenance\Model\MaintenanceJob\MaintenanceJobConst;
use Maintenance\Model\PartUsed\PartUsedConst;
use Maintenance\Model\Schedule\ScheduleConst;
use PHPUnit\Framework\TestCase;
use Platform\Model\PlatformConst;
use Rental\Model\GeneratorOccupancy\GeneratorOccupancyConst;
use Rental\Model\RentalOrder\RentalOrderConst;
use Sales\Model\Contract\ContractConst;
use Sales\Model\PriceListItem\PriceListItemConst;
use Sales\Model\Quote\QuoteConst;
use User\Model\AuditLog\AuditLogModel;
use User\Model\Permission\PermissionConst;
use User\Model\User\UserConst;

class EnumsContractTest extends TestCase
{
    public function test_contract_enums_chua_du_cac_gia_tri_enum_chinh_trong_code(): void
    {
        $contract = file_get_contents(dirname(__DIR__, 4) . '/contracts/enums.md') ?: '';

        foreach ($this->enumValues() as $source => $values) {
            foreach ($values as $value) {
                self::assertStringContainsString(
                    "`{$value}`",
                    $contract,
                    "{$source} thiếu giá trị {$value} trong contracts/enums.md"
                );
            }
        }
    }

    /** @return array<string, list<string>> */
    private function enumValues(): array
    {
        return [
            AuditLogModel::class . '::ACTION_LABELS' => array_keys(AuditLogModel::ACTION_LABELS),
            UserConst::class . '::ROLE_LABELS' => array_keys(UserConst::ROLE_LABELS),
            UserConst::class . '::STATUS_LABELS' => array_keys(UserConst::STATUS_LABELS),
            PermissionConst::class . '::EFFECT_LABELS' => array_keys(PermissionConst::EFFECT_LABELS),
            GeneratorConst::class . '::STATUS_LABELS' => array_keys(GeneratorConst::STATUS_LABELS),
            GeneratorConst::class . '::FUEL_LABELS' => array_keys(GeneratorConst::FUEL_LABELS),
            CustomerConst::class . '::TYPE_LABELS' => array_keys(CustomerConst::TYPE_LABELS),
            CustomerConst::class . '::STATUS_LABELS' => array_keys(CustomerConst::STATUS_LABELS),
            SiteConst::class . '::STATUS_LABELS' => array_keys(SiteConst::STATUS_LABELS),
            QuoteConst::class . '::STATUS_LABELS' => array_keys(QuoteConst::STATUS_LABELS),
            ContractConst::class . '::STATUS_LABELS' => array_keys(ContractConst::STATUS_LABELS),
            ContractConst::class . '::BILLING_LABELS' => array_keys(ContractConst::BILLING_LABELS),
            PriceListItemConst::class . '::DURATION_LABELS' => array_keys(PriceListItemConst::DURATION_LABELS),
            RentalOrderConst::class . '::STATUS_LABELS' => array_keys(RentalOrderConst::STATUS_LABELS),
            GeneratorOccupancyConst::class . '::HOLD_LABELS' => array_keys(GeneratorOccupancyConst::HOLD_LABELS),
            VehicleConst::class . '::TYPE_LABELS' => array_keys(VehicleConst::TYPE_LABELS),
            VehicleConst::class . '::STATUS_LABELS' => array_keys(VehicleConst::STATUS_LABELS),
            DispatchJobConst::class . '::TYPE_LABELS' => array_keys(DispatchJobConst::TYPE_LABELS),
            DispatchJobConst::class . '::STATUS_LABELS' => array_keys(DispatchJobConst::STATUS_LABELS),
            DispatchJobConst::class . '::PRIORITY_LABELS' => array_keys(DispatchJobConst::PRIORITY_LABELS),
            DispatchJobConst::class . '::FEE_BEARER_LABELS' => array_keys(DispatchJobConst::FEE_BEARER_LABELS),
            AssignmentConst::class . '::ROLE_LABELS' => array_keys(AssignmentConst::ROLE_LABELS),
            ScheduleConst::class . '::TYPE_LABELS' => array_keys(ScheduleConst::TYPE_LABELS),
            MaintenanceJobConst::class . '::TYPE_LABELS' => array_keys(MaintenanceJobConst::TYPE_LABELS),
            MaintenanceJobConst::class . '::STATUS_LABELS' => array_keys(MaintenanceJobConst::STATUS_LABELS),
            MaintenanceJobConst::class . '::PRIORITY_LABELS' => array_keys(MaintenanceJobConst::PRIORITY_LABELS),
            PartUsedConst::class . '::UNIT_LABELS' => array_keys(PartUsedConst::UNIT_LABELS),
            InvoiceConst::class . '::STATUS_LABELS' => array_keys(InvoiceConst::STATUS_LABELS),
            InvoiceLineConst::class . '::TYPE_LABELS' => array_keys(InvoiceLineConst::TYPE_LABELS),
            InvoiceLineConst::class . '::UNIT_LABELS' => array_keys(InvoiceLineConst::UNIT_LABELS),
            PaymentConst::class . '::METHOD_LABELS' => array_keys(PaymentConst::METHOD_LABELS),
            PaymentConst::class . '::STATUS_LABELS' => array_keys(PaymentConst::STATUS_LABELS),
            DepositConst::class . '::STATUS_LABELS' => array_keys(DepositConst::STATUS_LABELS),
            PlatformConst::class . '::ATTACHMENT_KINDS' => PlatformConst::ATTACHMENT_KINDS,
            PlatformConst::class . '::NOTIFICATION_CHANNELS' => PlatformConst::NOTIFICATION_CHANNELS,
            PlatformConst::class . '::SETTING_TYPES' => PlatformConst::SETTING_TYPES,
            PlatformConst::class . '::OUTBOX_STATUSES' => PlatformConst::OUTBOX_STATUSES,
        ];
    }
}
