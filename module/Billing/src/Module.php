<?php

declare(strict_types=1);

namespace Billing;

use Application\Factory\AppInvokableFactory;
use Billing\Model\CreditLimit\CreditLimitMapper;
use Billing\Model\CreditLimit\CreditLimitModel;
use Billing\Model\Deposit\DepositMapper;
use Billing\Model\Deposit\DepositModel;
use Billing\Model\Invoice\InvoiceMapper;
use Billing\Model\Invoice\InvoiceModel;
use Billing\Model\InvoiceLine\InvoiceLineMapper;
use Billing\Model\InvoiceLine\InvoiceLineModel;
use Billing\Model\Payment\PaymentMapper;
use Billing\Model\Payment\PaymentModel;
use Billing\Service\CreditLimitService;
use Billing\Service\DepositService;
use Billing\Service\InvoiceService;
use Billing\Service\PaymentService;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig(): array
    {
        return [
            'invokables' => [
                CreditLimitModel::class => CreditLimitModel::class,
                InvoiceModel::class     => InvoiceModel::class,
                InvoiceLineModel::class => InvoiceLineModel::class,
                PaymentModel::class     => PaymentModel::class,
                DepositModel::class     => DepositModel::class,
            ],
            'factories' => [
                CreditLimitMapper::class => AppInvokableFactory::class,
                InvoiceMapper::class     => AppInvokableFactory::class,
                InvoiceLineMapper::class => AppInvokableFactory::class,
                PaymentMapper::class     => AppInvokableFactory::class,
                DepositMapper::class     => AppInvokableFactory::class,
                CreditLimitService::class => AppInvokableFactory::class,
                InvoiceService::class     => AppInvokableFactory::class,
                PaymentService::class     => AppInvokableFactory::class,
                DepositService::class     => AppInvokableFactory::class,
            ],
        ];
    }
}
