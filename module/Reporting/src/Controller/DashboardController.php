<?php

declare(strict_types=1);

namespace Reporting\Controller;

use Application\Controller\BaseController;
use Laminas\View\Model\ViewModel;
use Reporting\Service\ReportingService;

class DashboardController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $service = $this->getContainerEntry(ReportingService::class);
        $model = $this->getViewModel();
        $model->setVariable('summary', $service->dashboardSummary());

        return $model;
    }
}
