<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Exception\AccessDeniedException;
use Application\Exception\NotFoundException;
use Application\Model\AppConst;
use Application\Model\AppMessage;
use Application\Service\PermissionCheckerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;

/**
 * Controller cho trang HTML render phía server.
 *
 * onDispatch làm 3 việc trước khi gọi action:
 *   1. Hỏi ACL xem action hiện tại có public không.
 *   2. Nếu không public: bắt đăng nhập rồi hỏi ACL quyền action hiện tại.
 *   3. Kiểm CSRF cho mọi request đổi dữ liệu (POST/PUT/DELETE).
 * Sau đó bọc action trong try/catch để đổi AccessDenied/NotFound thành trang 403/404.
 *
 * Quyền theo VAI TRÒ và theo NGƯỜI kiểm ở đây; quyền theo PHẠM VI DỮ LIỆU (bản ghi của ai)
 * kiểm ở Service.
 */
abstract class BaseController extends AppController
{
    public function onDispatch(MvcEvent $e): mixed
    {
        $denied = $this->guard();
        if ($denied !== null) {
            return $denied;
        }

        try {
            return parent::onDispatch($e);
        } catch (AccessDeniedException $ex) {
            return $this->errorView('error/403', Response::STATUS_CODE_403, $ex->getMessage());
        } catch (NotFoundException $ex) {
            return $this->errorView('error/404', Response::STATUS_CODE_404, $ex->getMessage());
        }
    }

    /**
     * @return Response|ViewModel|null null nghĩa là được đi tiếp
     */
    private function guard(): Response|ViewModel|null
    {
        $auth  = $this->getAuthContext();
        $action = (string)$this->params()->fromRoute('action', 'index');

        if (!$this->isPublicAction($action)) {
            if (!$auth->isLoggedIn()) {
                return $this->redirect()->toUrl('/login?next=' . rawurlencode($this->currentPathAndQuery()));
            }

            if (!$this->isAllowedAction($action)) {
                return $this->errorView('error/403', Response::STATUS_CODE_403, AppMessage::COMMON_403);
            }
        }

        if (!$this->getRequest()->isGet() && !$this->isValidCsrf()) {
            return $this->errorView('error/403', Response::STATUS_CODE_403, AppMessage::CSRF_INVALID);
        }

        return null;
    }

    /**
     * Đường dẫn hiện tại dạng NỘI BỘ (`/users/detail/7?tab=lich-su`), không kèm scheme và host.
     *
     * Cố ý không dùng `getUriString()`: hàm đó trả về URL tuyệt đối, mà trang đăng nhập chỉ
     * chấp nhận đường dẫn nội bộ để chặn open redirect (User\Form\Auth\LoginForm::safeNextUrl).
     * Đưa URL tuyệt đối vào `next` sẽ khiến mọi lần đăng nhập đều rơi về trang chủ thay vì
     * quay lại đúng trang người dùng đang muốn vào.
     */
    private function currentPathAndQuery(): string
    {
        $uri = $this->getRequest()->getUri();
        $path = $uri->getPath() ?: '/';
        $query = $uri->getQuery();

        return $query ? $path . '?' . $query : $path;
    }

    /**
     * Hỏi M01 user xem người đang đăng nhập có quyền trên đúng màn hình + thao tác này không.
     *
     * Resource và privilege suy ra từ chính route đang chạy (`static::class` + tên action),
     * nên controller KHÔNG phải khai lại tên quyền — khai tay là cách chắc chắn để quyền và
     * route lệch nhau sau vài lần refactor.
     *
     * Không có PermissionCheckerInterface nghĩa là cấu hình quyền đang hỏng: từ chối thay vì cho qua.
     */
    protected function isAllowedAction(string $action): bool
    {
        $checker = $this->getContainerEntry(PermissionCheckerInterface::class);
        if (!$checker instanceof PermissionCheckerInterface) {
            return false;
        }

        return $checker->isAllowedAction(static::class, $action);
    }

    private function isPublicAction(string $action): bool
    {
        $checker = $this->getContainerEntry(PermissionCheckerInterface::class);
        if (!$checker instanceof PermissionCheckerInterface) {
            return false;
        }

        return $checker->isPublicAction(static::class, $action);
    }

    /**
     * CSRF kiểm theo TỪNG FORM: token phải khớp với tên form mà chính request khai báo.
     * Token của form khác không dùng lẫn được.
     *
     * Đây là lớp phòng thủ thứ nhất; AppForm::isValidCsrf() kiểm lại lần nữa khi validate.
     */
    private function isValidCsrf(): bool
    {
        $request  = $this->getRequest();
        $token    = $request->getPost(AppConst::FIELD_CSRF_TOKEN);
        $formName = $request->getPost(AppConst::FIELD_FORM_NAME);

        return $this->getAuthContext()->isValidCsrfToken(
            is_string($token) ? $token : null,
            is_string($formName) && $formName !== '' ? $formName : AppConst::CSRF_FORM_DEFAULT
        );
    }

    private function errorView(string $template, int $statusCode, string $message): ViewModel
    {
        $this->getResponse()->setStatusCode($statusCode);

        $model = new ViewModel(['message' => $message]);
        $model->setTemplate($template);
        return $model;
    }

    /**
     * Token CSRF của một form cụ thể.
     *
     * Thường KHÔNG cần gọi trực tiếp: view dựng form qua `partial/form/open`, partial đó tự
     * lấy token từ chính đối tượng Form. Chỉ dùng khi phải render token ngoài luồng form
     * (ví dụ nút POST đơn lẻ trên trang chi tiết).
     */
    protected function csrfToken(string $formName): string
    {
        return $this->getAuthContext()->getCsrfToken($formName);
    }
}
