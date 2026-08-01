<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Fixture;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;

/** Form tối thiểu, chỉ để test hành vi CSRF của lớp nền AppForm. */
class CsrfTestForm extends AppForm
{
    public const string NAME = 'test.csrf';

    protected const string FORM_NAME = self::NAME;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('name', [
            'type'     => CommonFieldFilters::TYPE_TEXT,
            'required' => true,
        ]));
    }
}
