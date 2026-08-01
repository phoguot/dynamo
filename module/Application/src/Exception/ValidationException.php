<?php

declare(strict_types=1);

namespace Application\Exception;

use Application\Model\AppMessage;
use RuntimeException;

/**
 * Service ném khi Filter không hợp lệ, hoặc khi quy tắc nghiệp vụ từ chối dữ liệu
 * (trùng mã máy, ngày bắt đầu sau ngày kết thúc...).
 *
 * Khác AccessDenied/NotFound: Controller TỰ BẮT exception này để render lại đúng form
 * kèm dữ liệu người dùng vừa gõ — xem docs/code-standards/render-view.md mục PRG.
 */
class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors field => câu lỗi tiếng Việt */
    public function __construct(
        private readonly array $errors = [],
        string $message = AppMessage::INVALID_DATA,
    ) {
        parent::__construct($message);
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
