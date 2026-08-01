<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Fixture;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

/** Form tìm kiếm gửi bằng GET — không đổi dữ liệu nên không cần CSRF. */
class ReadOnlyTestForm extends AppForm
{
    protected const string FORM_NAME = 'test.readonly';

    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('name', [
            'type' => CommonFieldFilters::TYPE_TEXT,
        ]));
    }
}
