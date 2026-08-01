<?php

declare(strict_types=1);

namespace User\Form\AuditLog;

use Application\Filter\CommonFieldFilters;
use Application\Form\AppForm;
use User\Model\AuditLog\AuditLogModel;

/**
 * Form lọc trang đọc nhật ký kiểm toán.
 *
 * Gửi bằng GET và KHÔNG đổi dữ liệu ⇒ không cần CSRF (bắt token ở đây chỉ làm hỏng link
 * chia sẻ và nút Back). Nhật ký là append-only: màn hình này chỉ đọc.
 *
 * Mọi giá trị dùng để sắp xếp đều đi qua whitelist AuditLogModel::SORT_MAP.
 */
class AuditLogSearchForm extends AppForm
{
    protected const string FORM_NAME = 'user.auditlog.search';

    protected const bool REQUIRE_CSRF = false;

    protected function initFields(): void
    {
        $this->add(CommonFieldFilters::dynamicField('action', [
            'type'       => CommonFieldFilters::TYPE_ENUM_STRING,
            'enumValues' => array_keys(AuditLogModel::ACTION_LABELS),
        ]));

        // Tên bảng đối tượng, ví dụ `usr_users`, `flt_generators`. Lọc theo tiền tố ở mapper.
        $this->add(CommonFieldFilters::dynamicField('objectType', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 64,
        ]));

        $this->add(CommonFieldFilters::dynamicField('userId', [
            'type' => CommonFieldFilters::TYPE_INT,
        ]));

        // Khoảng ngày (Y-m-d). Định dạng được kiểm ở Service để báo lỗi đẹp trên filter bar.
        $this->add(CommonFieldFilters::dynamicField('dateFrom', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 10,
        ]));

        $this->add(CommonFieldFilters::dynamicField('dateTo', [
            'type'      => CommonFieldFilters::TYPE_TEXT,
            'maxLength' => 10,
        ]));

        $this->initInputPaging();
        $this->initSorting(AuditLogModel::SORT_DEFAULT, 'desc', array_keys(AuditLogModel::SORT_MAP));
    }

    /** @return array<string, string> */
    public function actionChoices(): array
    {
        return AuditLogModel::ACTION_LABELS;
    }
}
