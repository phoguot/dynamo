<?php

declare(strict_types=1);

namespace Sales\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use Sales\Model\Quote\QuoteModel;
use Sales\Service\QuoteService;

class QuoteController extends BaseController
{
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(QuoteService::class);
        $model = $this->getViewModel();

        try {
            $model->setVariable('paginator', $service->searchQuotes($query));
            $model->setVariable('errors', []);
        } catch (ValidationException $e) {
            $model->setVariable('paginator', null);
            $model->setVariable('errors', $e->getErrors());
        }

        $model->setVariables([
            'searchForm' => $service->newSearchForm($query),
            'canEdit'    => $this->canEdit(),
        ]);

        return $model;
    }

    public function detailAction(): ViewModel
    {
        $service = $this->getContainerEntry(QuoteService::class);
        $quote = $service->getQuote((int)$this->params()->fromRoute('id', 0));

        $model = $this->getViewModel();
        $model->setVariables([
            'quote'        => $quote,
            'lines'        => $service->linesOf($quote),
            'nextStatuses' => $service->nextStatuses($quote),
            'statusForm'   => $service->newStatusForm(),
            'canEdit'      => $this->canEdit(),
            'errors'       => [],
        ]);

        return $model;
    }

    public function createAction(): mixed
    {
        $service = $this->getContainerEntry(QuoteService::class);

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, null);
        }

        return $this->formView($service, null);
    }

    public function editAction(): mixed
    {
        $service = $this->getContainerEntry(QuoteService::class);
        $quote = $service->getQuote((int)$this->params()->fromRoute('id', 0));

        if ($this->getRequest()->isPost()) {
            return $this->handleSave($service, $quote);
        }

        return $this->formView($service, $quote);
    }

    public function changestatusAction(): mixed
    {
        $service = $this->getContainerEntry(QuoteService::class);
        $id = (int)$this->params()->fromRoute('id', 0);
        $payload = $this->getAllPostParams();
        $payload['id'] = $id;

        try {
            $quote = $service->changeStatus($payload);
            $this->flashMessenger()->addSuccessMessage(sprintf(
                'Đã chuyển báo giá %s sang trạng thái "%s".',
                (string)$quote->getQuoteNo(),
                $quote->getStatusLabel()
            ));
        } catch (ValidationException $e) {
            $this->flashMessenger()->addErrorMessage(implode(' ', $e->getErrors()));
        }

        return $this->redirect()->toRoute('quotes', ['action' => 'detail', 'id' => $id]);
    }

    private function handleSave(QuoteService $service, ?QuoteModel $existing): mixed
    {
        $payload = $this->getAllPostParams();
        if ($existing !== null) {
            $payload['id'] = $existing->getId();
        }

        try {
            $saved = $service->saveQuote($payload);
        } catch (ValidationException $e) {
            return $this->formView($service, $existing, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Đã lưu báo giá %s.', (string)$saved->getQuoteNo()));

        return $this->redirect()->toRoute('quotes', ['action' => 'detail', 'id' => $saved->getId()]);
    }

    private function formView(QuoteService $service, ?QuoteModel $existing, array $values = [], array $errors = []): ViewModel
    {
        $model = $this->getViewModel();
        $model->setTemplate('sales/quote/form');
        $model->setVariables([
            'quote'    => $existing,
            'saveForm' => $service->newSaveForm($existing, $values),
            'errors'   => $errors,
        ]);

        return $model;
    }

    private function canEdit(): bool
    {
        return $this->isAllowedAction('create')
            || $this->isAllowedAction('edit')
            || $this->isAllowedAction('changestatus');
    }
}

