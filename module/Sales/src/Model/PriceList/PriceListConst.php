<?php

declare(strict_types=1);

namespace Sales\Model\PriceList;

use Application\Model\Constant\AppConstModel;

class PriceListConst extends AppConstModel
{
    public const array SORT_MAP = [
        'code'      => 'p.code',
        'name'      => 'p.name',
        'validFrom' => 'p.validFrom',
        'validTo'   => 'p.validTo',
        'isActive'  => 'p.isActive',
        'createdAt' => 'p.createdAt',
    ];

    public const string SORT_DEFAULT = 'validFrom';
}

