<?php

declare(strict_types=1);

namespace User\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use User\Service\AuthService;

/**
 * Trang đăng nhập / đăng xuất.
 *
 * Controller mỏng: nhận request → gọi Service → trả ViewModel. Không validate, không SQL.
 */
class AuthController extends BaseController
{
    /** Trang đăng nhập, và xử lý POST của chính nó. */
    public function loginAction(): mixed
    {
        $auth = $this->getAuthContext();

        // Đã đăng nhập rồi mà vào lại /login thì đưa thẳng về dashboard vận hành.
        if ($auth->isLoggedIn() && !$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('dashboard');
        }

        $service = $this->getContainerEntry(AuthService::class);

        if (!$this->getRequest()->isPost()) {
            return $this->loginView($service);
        }

        $payload = $this->getAllPostParams();
        $payload['next'] ??= (string)$this->params()->fromQuery('next', '');

        try {
            $user = $service->login($payload);
        } catch (ValidationException $e) {
            return $this->loginView($service, $payload, $e->getErrors());
        }

        $this->flashMessenger()->addSuccessMessage(sprintf(
            'Xin chào %s.',
            (string)$user->getFullName()
        ));

        return $this->redirect()->toUrl($service->newLoginForm($payload)->safeNextUrl('/dashboard'));
    }

    /**
     * Đăng xuất.
     *
     * Chỉ nhận POST: đăng xuất là thao tác ĐỔI trạng thái, để ở GET thì bất kỳ thẻ `<img>`
     * nào trên trang khác cũng đá được người dùng ra ngoài.
     */
    public function logoutAction(): mixed
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toUrl('/');
        }

        $service = $this->getContainerEntry(AuthService::class);
        $service->logout();

        return $this->redirect()->toRoute('login');
    }

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $errors
     */
    private function loginView(AuthService $service, array $values = [], array $errors = []): ViewModel
    {
        $values['next'] ??= (string)$this->params()->fromQuery('next', '');

        $model = $this->getViewModel();
        $model->setTemplate('user/auth/login');
        // Trang đăng nhập không dùng layout có sidebar/menu của người đã đăng nhập.
        $model->setVariables([
            'loginForm' => $service->newLoginForm($values),
            'errors'    => $errors,
        ]);

        return $model;
    }
}
