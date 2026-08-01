<?php

declare(strict_types=1);

namespace Application\Exception;

use Application\Model\AppMessage;
use RuntimeException;

/**
 * Service ném khi người dùng đủ vai trò nhưng không được phép trên bản ghi cụ thể
 * (kỹ thuật viên xem việc của người khác, kinh doanh xem khách không phụ trách — BR12).
 * BaseController bắt và đổi thành trang lỗi 403.
 */
class AccessDeniedException extends RuntimeException
{
    public function __construct(string $message = AppMessage::NO_PERMISSION_ON_RECORD)
    {
        parent::__construct($message);
    }
}
