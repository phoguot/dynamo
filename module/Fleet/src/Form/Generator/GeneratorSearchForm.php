<?php

declare(strict_types=1);

namespace Fleet\Form\Generator;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use Fleet\Model\Generator\GeneratorConst;

/**
 * Form lọc trang danh sách máy phát điện.
 *
 * Gửi bằng GET và KHÔNG đổi dữ liệu ⇒ không cần CSRF. Bắt token ở đây chỉ làm hỏng
 * link chia sẻ và nút Back của trình duyệt.
 *
 * Mọi giá trị dùng để sắp xếp đều đi qua whitelist GeneratorConst::SORT_MAP.
 */
class GeneratorSearchForm extends AppForm
{
    protected const string FORM_NAME = 'fleet.generator.search';

    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('keyword', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => CommonFieldFilters::LEN_TITLE,
        ]));

        $this->add(CommonFieldFilters::dynamicField('status', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(GeneratorConst::STATUS_LABELS),
        ]));

        $this->add(CommonFieldFilters::dynamicField('fuelType', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(GeneratorConst::FUEL_LABELS),
        ]));

        $this->add(CommonFieldFilters::intField('capacityFrom'));
        $this->add(CommonFieldFilters::intField('capacityTo'));

        $this->initInputPaging();
        $this->initSorting(GeneratorConst::SORT_DEFAULT, 'asc', array_keys(GeneratorConst::SORT_MAP));
    }

    protected function validateBusinessRules(): bool
    {
        $data = $this->getData();
        $from = $data['capacityFrom'] ?? null;
        $to   = $data['capacityTo'] ?? null;

        if ($from !== null && $to !== null && $from !== '' && $to !== '' && (int)$from > (int)$to) {
            $this->setError('capacityTo', 'Công suất "đến" phải lớn hơn hoặc bằng công suất "từ".');
            return false;
        }

        return true;
    }

    /** @return array<string, string> */
    public function statusChoices(): array
    {
        return GeneratorConst::STATUS_LABELS;
    }

    /** @return array<string, string> */
    public function fuelChoices(): array
    {
        return GeneratorConst::FUEL_LABELS;
    }
}
