<?php

declare(strict_types=1);

namespace Billing\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Billing\Service\PaymentService;
use Laminas\View\Model\ViewModel;

class PaymentController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(PaymentService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchPayments($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canCreate'  => $this->isAllowedAction('create'),
            'canCancel'  => $this->isAllowedAction('cancel'),
        ]);

        return $model;
    }

    public function detailAction(): ViewModel
    {
        $service = $this->getContainerEntry(PaymentService::class);
        $model = $this->getViewModel();
        $model->setVariables([
            'payment'    => $service->getPayment((int)$this->params()->fromRoute('id', 0)),
            'cancelForm' => $service->newCancelForm(),
            'canCancel'  => $this->isAllowedAction('cancel'),
            'errors'     => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(PaymentService::class);
        if ($this->getRequest()->isPost()) {
            $payload = $this->getAllPostParams();
            try {
                $saved = $service->savePayment($payload);
            } catch (ValidationException $e) {
                return $this->formView($service, $payload, $e->getErrors());
            }

            $this->flashMessenger()->addSuccessMessage(sprintf('Đã ghi nhận phiếu thu %s.', (string)$saved->getPaymentNo()));

            return $this->redirect()->toRoute('payments', ['action' => 'detail', 'id' => $saved->getId()]);
        }

        return $this->formView($service);
    }

    public function cancelAction(): mixed
    {
        $service = $this->getContainerEntry(PaymentService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $payment = $service->cancelPayment($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa phiếu thu %s.', (string)$payment->getPaymentNo()));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('payments');
    }

    private function formView(PaymentService $service, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('billing/payment/form');
        $model->setVariables([
            'saveForm' => $service->newSaveForm(null, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
