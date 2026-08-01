<?php

declare(strict_types=1);

namespace Application\Exception;

use Application\Model\AppMessage;
use RuntimeException;

/**
 * Service ném khi bản ghi không tồn tại (hoặc không thuộc phạm vi dữ liệu của người dùng
 * — trả 404 thay vì 403 để không lộ sự tồn tại của bản ghi).
 */
class NotFoundException extends RuntimeException
{
    public function __construct(string $message = AppMessage::NO_DATA)
    {
        parent::__construct($message);
    }
}
