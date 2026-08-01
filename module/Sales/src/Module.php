<?php

declare(strict_types=1);

namespace Sales;

use Application\Factory\AppInvokableFactory;
use Sales\Model\Contract\ContractMapper;
use Sales\Model\Contract\ContractModel;
use Sales\Model\PriceList\PriceListMapper;
use Sales\Model\PriceList\PriceListModel;
use Sales\Model\PriceListItem\PriceListItemMapper;
use Sales\Model\PriceListItem\PriceListItemModel;
use Sales\Model\Quote\QuoteMapper;
use Sales\Model\Quote\QuoteModel;
use Sales\Model\QuoteLine\QuoteLineMapper;
use Sales\Model\QuoteLine\QuoteLineModel;
use Sales\Service\ContractService;
use Sales\Service\PriceListService;
use Sales\Service\QuoteService;

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
                PriceListModel::class     => PriceListModel::class,
                PriceListItemModel::class => PriceListItemModel::class,
                QuoteModel::class         => QuoteModel::class,
                QuoteLineModel::class     => QuoteLineModel::class,
                ContractModel::class      => ContractModel::class,
            ],
            'factories' => [
                PriceListMapper::class     => AppInvokableFactory::class,
                PriceListItemMapper::class => AppInvokableFactory::class,
                QuoteMapper::class         => AppInvokableFactory::class,
                QuoteLineMapper::class     => AppInvokableFactory::class,
                ContractMapper::class      => AppInvokableFactory::class,
                PriceListService::class    => AppInvokableFactory::class,
                QuoteService::class        => AppInvokableFactory::class,
                ContractService::class     => AppInvokableFactory::class,
            ],
        ];
    }
}

