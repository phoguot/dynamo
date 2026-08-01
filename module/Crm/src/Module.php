<?php

declare(strict_types=1);

namespace Crm;

use Application\Factory\AppInvokableFactory;
use Crm\Listener\BillingCreditEventHandler;
use Crm\Model\Contact\ContactMapper;
use Crm\Model\Contact\ContactModel;
use Crm\Model\Customer\CustomerMapper;
use Crm\Model\Customer\CustomerModel;
use Crm\Model\Site\SiteMapper;
use Crm\Model\Site\SiteModel;
use Crm\Service\CustomerService;
use Crm\Service\SiteService;
use Crm\Service\ContactService;

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
                CustomerModel::class => CustomerModel::class,
                SiteModel::class     => SiteModel::class,
                ContactModel::class  => ContactModel::class,
            ],
            'factories'  => [
                BillingCreditEventHandler::class => AppInvokableFactory::class,
                CustomerMapper::class            => AppInvokableFactory::class,
                SiteMapper::class                => AppInvokableFactory::class,
                ContactMapper::class             => AppInvokableFactory::class,
                CustomerService::class           => AppInvokableFactory::class,
                SiteService::class               => AppInvokableFactory::class,
                ContactService::class            => AppInvokableFactory::class,
            ],
        ];
    }
}
