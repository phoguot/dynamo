<?php

declare(strict_types=1);

namespace User\Form\Auth;

use Application\Form\AppForm;

/**
 * Form CSRF-only cho thao tác đăng xuất.
 */
class LogoutForm extends AppForm
{
    protected const string FORM_NAME = 'user.auth.logout';

    protected function initFields(): void
    {
    }
}
