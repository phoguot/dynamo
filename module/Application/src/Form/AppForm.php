<?php

declare(strict_types=1);

namespace Application\Form;

use Application\Filter\AppFilter;
use Application\Model\AppConst;
use Application\Model\AppMessage;
use Application\Service\AuthContextService;

/**
 * Lớp nền cho MỌI form của hệ thống.
 *
 * Form là nơi duy nhất giữ: khai báo field, luật validate, CSRF token và dữ liệu để render
 * lại khi có lỗi. Controller không validate, không dựng markup form; Service nhận form đã
 * hợp lệ và chỉ lo nghiệp vụ.
 *
 * Vòng đời chuẩn trong Service:
 *
 *   $form = new GeneratorSaveForm($this->getContainer());
 *   $form->setData($payload);
 *   if (!$form->isValid()) {
 *       throw new ValidationException($form->getMessagesArr());
 *   }
 *   $formData = $form->getData();
 *
 * Form con BẮT BUỘC khai `FORM_NAME` — đó vừa là khóa dẫn xuất CSRF token, vừa là tên
 * dùng trong partial. Trùng tên giữa hai form nghĩa là token dùng lẫn được cho nhau.
 */
abstract class AppForm extends AppFilter
{
    /** Tên form, duy nhất toàn hệ thống. Form con BẮT BUỘC khai lại. */
    protected const string FORM_NAME = AppConst::CSRF_FORM_DEFAULT;

    /**
     * Form CHỈ ĐỌC (form lọc/tìm kiếm gửi bằng GET) không cần CSRF —
     * CSRF chỉ bảo vệ thao tác ĐỔI dữ liệu. Form con dạng tìm kiếm đặt `false`.
     */
    protected const bool REQUIRE_CSRF = true;

    /**
     * Dữ liệu thô đang gắn với form: thứ người dùng vừa gõ, hoặc giá trị bản ghi đang sửa.
     * Giữ nguyên để đổ lại vào form khi báo lỗi — người dùng không phải gõ lại từ đầu.
     */
    protected array $submittedData = [];

    public function __construct($container, $options = [])
    {
        parent::__construct($container, $options);

        $this->initFields();
    }

    /**
     * Khai báo toàn bộ field của form. Form con BẮT BUỘC cài đặt.
     * Dùng CommonFieldFilters cho các dạng field phổ biến, không tự viết lại.
     */
    abstract protected function initFields(): void;

    public function getFormName(): string
    {
        return static::FORM_NAME;
    }

    public function requiresCsrf(): bool
    {
        return static::REQUIRE_CSRF;
    }

    public function setData($data): static
    {
        $this->submittedData = is_array($data) ? $data : (array)$data;
        parent::setData($this->submittedData);

        return $this;
    }

    /**
     * Giá trị thô của một field, dùng để RENDER lại form sau khi báo lỗi.
     * KHÔNG dùng cho nghiệp vụ — nghiệp vụ luôn đọc `getData()` (đã qua filter + validate).
     */
    public function getSubmittedValue(string $field, mixed $default = null): mixed
    {
        $value = $this->submittedData[$field] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** @return array<string, mixed> */
    public function getSubmittedData(): array
    {
        return $this->submittedData;
    }

    /**
     * Đổ giá trị sẵn có (bản ghi đang sửa) vào form để render lần đầu.
     * Thứ người dùng vừa gõ luôn thắng giá trị cũ — không ghi đè công sức của họ.
     *
     * @param array<string, mixed> $values
     */
    public function fill(array $values): static
    {
        $this->submittedData = array_merge($values, $this->submittedData);

        return $this;
    }

    /**
     * Token CSRF của CHÍNH form này. Partial `partial/form/csrf` tự gọi hàm này.
     */
    public function getCsrfToken(): string
    {
        return $this->auth()->getCsrfToken($this->getFormName());
    }

    /**
     * Kiểm CSRF ngay trong form — lớp phòng thủ thứ hai, độc lập với guard ở BaseController.
     * Sai token là lỗi của trường `csrfToken`, không phải lỗi nghiệp vụ.
     */
    public function isValidCsrf(): bool
    {
        if (!$this->requiresCsrf()) {
            return true;
        }

        $token = $this->submittedData[AppConst::FIELD_CSRF_TOKEN] ?? null;

        return $this->auth()->isValidCsrfToken(
            is_string($token) ? $token : null,
            $this->getFormName()
        );
    }

    public function isValid($context = null): bool
    {
        if (!$this->isValidCsrf()) {
            $this->setError(AppConst::FIELD_CSRF_TOKEN, AppMessage::CSRF_INVALID);
            return false;
        }

        if (!parent::isValid($context)) {
            return false;
        }

        return $this->validateBusinessRules();
    }

    /**
     * Kiểm tra LIÊN TRƯỜNG của form (ngày từ ≤ ngày đến, đủ cặp tọa độ, tổng tiền khớp dòng).
     * Chạy sau khi từng field đã hợp lệ. Dùng `setError()` để gắn lỗi.
     *
     * Quy tắc cần TRUY VẤN DB (trùng mã, đủ hạn mức, state machine) KHÔNG đặt ở đây —
     * đó là việc của Service.
     */
    protected function validateBusinessRules(): bool
    {
        return true;
    }

    private function auth(): AuthContextService
    {
        return $this->getContainerEntry(AuthContextService::class);
    }
}
