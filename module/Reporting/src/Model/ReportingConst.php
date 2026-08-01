<?php

declare(strict_types=1);

namespace Reporting\Model;

use Application\Model\Constant\AppConstModel;

class ReportingConst extends AppConstModel
{
    public const array FLEET_SORT_MAP = [
        'reportDate'      => 'u.reportDate',
        'warehouseCode'   => 'u.warehouseCode',
        'totalGenerators' => 'u.totalGenerators',
        'rentedCount'     => 'u.rentedCount',
        'availableCount'  => 'u.availableCount',
        'utilizationRate' => 'u.utilizationRate',
        'computedAt'      => 'u.computedAt',
    ];

    public const string FLEET_SORT_DEFAULT = 'reportDate';

    public const array REVENUE_SORT_MAP = [
        'periodYear'        => 'r.periodYear',
        'periodMonth'       => 'r.periodMonth',
        'customerId'        => 'r.customerId',
        'invoicedAmount'    => 'r.invoicedAmount',
        'collectedAmount'   => 'r.collectedAmount',
        'outstandingAmount' => 'r.outstandingAmount',
        'overdueAmount'     => 'r.overdueAmount',
        'computedAt'        => 'r.computedAt',
    ];

    public const string REVENUE_SORT_DEFAULT = 'periodYear';

    public const array RECEIVABLES_SORT_MAP = [
        'snapshotDate' => 's.snapshotDate',
        'customerId'   => 's.customerId',
        'totalDebt'    => 's.totalDebt',
        'dsoDays'      => 's.dsoDays',
        'computedAt'   => 's.computedAt',
    ];

    public const string RECEIVABLES_SORT_DEFAULT = 'snapshotDate';

    public static function utilizationRate(int $rentedCount, int $activeGenerators): float
    {
        if ($activeGenerators <= 0) {
            return 0.0;
        }

        return round($rentedCount * 100 / $activeGenerators, 2);
    }
}
