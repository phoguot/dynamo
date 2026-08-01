<?php

declare(strict_types=1);

namespace Billing\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Billing\Model\Invoice\InvoiceConst;
use Billing\Model\Invoice\InvoiceModel;
use Billing\Service\InvoiceService;
use Laminas\View\Model\ViewModel;

class InvoiceController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(InvoiceService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchInvoices($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canCreate'  => $this->isAllowedAction('create'),
            'canEdit'    => $this->isAllowedAction('edit'),
        ]);

        return $model;
    }

    public function detailAction(): ViewModel
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        $invoice = $service->getInvoice((int)$this->params()->fromRoute('id', 0));
        $model = $this->getViewModel();
        $model->setVariables([
            'invoice'         => $invoice,
            'lines'           => $service->linesOf($invoice),
            'nextStatuses'    => $service->nextStatuses($invoice),
            'statusForm'      => $service->newStatusForm(),
            'lineForm'        => $service->newLineForm($invoice),
            'canEdit'         => $this->isAllowedAction('edit'),
            'canChangeStatus' => $this->isAllowedAction('changestatus'),
            'canAddLine'      => $this->isAllowedAction('addline'),
            'canDeleteLine'   => $this->isAllowedAction('deleteline'),
            'errors'          => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        $invoice = $service->getInvoice((int)$this->params()->fromRoute('id', 0));
        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $invoice);
        }

        return $this->formView($service, $invoice);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $invoice = $service->changeStatus($payload);
            if (($payload['status'] ?? null) === InvoiceConst::STATUS_CANCELLED) {
                $this->flashMessenger()->addSuccessMessage(sprintf('Đã xóa hóa đơn %s.', (string)$invoice->getInvoiceNo()));

                return $this->redirect()->toRoute('invoices');
            }
            $this->flashMessenger()->addSuccessMessage(sprintf('Đã chuyển hóa đơn %s sang "%s".', (string)$invoice->getInvoiceNo(), $invoice->getStatusLabel()));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('invoices', ['action' => 'detail', 'id' => $id]);
    }

    public function addlineAction(): mixed
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['invoiceId'] = $id;

        try {
            $service->saveLine($payload);
            $this->flashMessenger()->addSuccessMessage('Đã thêm dòng hóa đơn.');
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('invoices', ['action' => 'detail', 'id' => $id]);
    }

    public function deletelineAction(): mixed
    {
        $service = $this->getContainerEntry(InvoiceService::class);
        $invoiceId = (int)$this->params()->fromRoute('id', 0);
        $lineId = (int)($this->getAllPostParams()['lineId'] ?? 0);

        try {
            $service->deleteLine($lineId);
            $this->flashMessenger()->addSuccessMessage('Đã xóa dòng hóa đơn.');
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('invoices', ['action' => 'detail', 'id' => $invoiceId]);
    }

    private function handleSave(InvoiceService $service, ?InvoiceModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveInvoice($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu hóa đơn %s.', (string)$saved->getInvoiceNo()));

        return $this->redirect()->toRoute('invoices', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(InvoiceService $service, ?InvoiceModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('billing/invoice/form');
        $model->setVariables([
            'invoice'  => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }
}
