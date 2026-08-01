<?php

declare(strict_types=1);

namespace Application\Controller;

class IndexController extends BaseController
{
    /** Trang chủ là welcome cho khách; người đã đăng nhập đi thẳng vào dashboard. */
    public function indexAction(): mixed
    {
        if ($this->getAuthContext()->isLoggedIn()) {
            return $this->redirect()->toRoute('dashboard');
        }

        $model = $this->getViewModel();
        $model->setVariables([
            'userName' => $this->getAuthContext()->getUserName(),
        ]);

        return $model;
    }
}
