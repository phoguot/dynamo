<?php

declare(strict_types=1);

namespace Reporting\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Reporting\Service\ReportingService;

class ReportController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(ReportingService::class);
        $model = $this->getViewModel();

        foreach ([
            'fleetPaginator' => 'searchFleetUtilization',
            'revenuePaginator' => 'searchRevenue',
            'receivablesPaginator' => 'searchReceivables',
        ] as $variable => $method) {
            try {
                $model->setVariable($variable, $service->$method($query));
                $model->setVariable($variable . 'Errors', []);
            } catch (ValidationException $e) {
                $model->setVariable($variable, null);
                $model->setVariable($variable . 'Errors', $e->getErrors());
            }
        }

        $model->setVariables([
            'fleetForm'       => $service->newFleetForm($query),
            'revenueForm'     => $service->newRevenueForm($query),
            'receivablesForm' => $service->newReceivablesForm($query),
        ]);

        return $model;
    }
}
