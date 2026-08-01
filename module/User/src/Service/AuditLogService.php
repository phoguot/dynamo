<?php

declare(strict_types=1);

namespace User\Service;

use Application\Exception\ValidationException;
use Application\Factory\AppServiceFactory;
use Application\Paginator\Paginator;
use Application\Paginator\PaginatorUtil;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\Http\Request;
use User\Form\AuditLog\AuditLogSearchForm;
use User\Model\AuditLog\AuditLogMapper;
use User\Model\AuditLog\AuditLogModel;
use User\Model\User\UserMapper;

/**
 * Ghi nhật ký kiểm toán.
 *
 * Mọi module đều gọi service này khi tạo/sửa/duyệt/hủy/chuyển trạng thái — danh sách bắt
 * buộc ở .claude/rules/security.md. Nhật ký là append-only.
 *
 * Nguyên tắc khi ghi: **không bao giờ để việc ghi log làm hỏng nghiệp vụ**. Ghi log lỗi thì
 * nuốt lỗi, vì mất một dòng nhật ký còn nhẹ hơn việc người dùng không lưu được đơn hàng.
 * Đổi lại, giá trị trước/sau phải được lọc sạch dữ liệu nhạy cảm TRƯỚC khi tới đây.
 */
class AuditLogService extends AppServiceFactory
{
    /**
     * Khóa không bao giờ được ghi vào nhật ký, dù người gọi có truyền vào.
     * Đây là lưới an toàn cuối; người gọi vẫn nên tự không truyền lên.
     */
    private const array REDACTED_KEYS = [
        'password', 'passwordConfirm', 'passwordHash', 'password_hash',
        'csrfToken', 'token', 'secret',
    ];

    private function mapper(): ?AuditLogMapper
    {
        return $this->getContainerEntry(AuditLogMapper::class);
    }

    // ------------------------------------------------------------------
    //  Đọc — màn hình nhật ký kiểm toán (/audit-logs)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $query tham số lọc hiện tại trên URL */
    public function newSearchForm(array $query = []): AuditLogSearchForm
    {
        $form = new AuditLogSearchForm($this->getContainer());
        $form->setData($query);

        return $form;
    }

    /**
     * Danh sách nhật ký toàn hệ thống, có lọc và phân trang. Chỉ đọc.
     *
     * @param array<string, mixed> $payload tham số thô từ request
     * @throws ValidationException khi tham số lọc sai (kể cả ngày sai định dạng)
     */
    public function searchLogs(array $payload = []): Paginator
    {
        $form = new AuditLogSearchForm($this->getContainer());
        $form->setData($payload);
        if (!$form->isValid()) {
            throw new ValidationException($form->getMessagesArr());
        }

        $formData = $form->getData();

        $criteria = (new AuditLogModel())
            ->setAction($this->nullIfBlank($formData['action'] ?? null))
            ->setObjectType($this->nullIfBlank($formData['objectType'] ?? null))
            ->setUserId($this->positiveIntOrNull($formData['userId'] ?? null));

        $dateFrom = $this->dateOrNull($formData['dateFrom'] ?? null, 'dateFrom');
        $dateTo   = $this->dateOrNull($formData['dateTo'] ?? null, 'dateTo');
        if ($dateFrom !== null && $dateTo !== null && strcmp($dateFrom, $dateTo) > 0) {
            throw new ValidationException(['dateTo' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.']);
        }

        $paging = PaginatorUtil::fromFormData($formData);
        $paging['sort'] = $formData['sort'] ?? AuditLogModel::SORT_DEFAULT;
        $paging['dir']  = $formData['dir'] ?? 'desc';

        return $this->mapper()->searchLogs($criteria, $paging, $dateFrom, $dateTo);
    }

    /**
     * Tên người thực hiện cho đúng trang đang xem — một truy vấn `IN (...)`, không JOIN
     * (repo không dùng JOIN). Trả map id => họ tên để view tra cứu.
     *
     * @return array<int, string>
     */
    public function actorNamesForPage(Paginator $paginator): array
    {
        $userMapper = $this->getContainerEntry(UserMapper::class);
        if (!$userMapper instanceof UserMapper) {
            return [];
        }

        $ids = [];
        foreach ($paginator->getCurrentItems() as $item) {
            if ($item instanceof AuditLogModel && $item->getUserId() !== null) {
                $ids[] = (int)$item->getUserId();
            }
        }

        return $userMapper->displayNamesByIds($ids);
    }

    /**
     * Ghi một dòng nhật ký.
     *
     * @param string $action     hằng số ACTION_* của AuditLogModel
     * @param string $objectType tên bảng của đối tượng, ví dụ `usr_users`
     * @param mixed  $before     giá trị trước (mảng hoặc null)
     * @param mixed  $after      giá trị sau (mảng hoặc null)
     */
    public function write(
        string $action,
        string $objectType,
        ?int $objectId,
        mixed $before = null,
        mixed $after = null,
        ?int $actorId = null
    ): void {
        $mapper = $this->mapper();
        if ($mapper === null) {
            return;
        }

        $item = (new AuditLogModel())
            ->setUserId($actorId ?? $this->currentUserId())
            ->setAction($action)
            ->setObjectType($objectType)
            ->setObjectId($objectId)
            ->setBeforeJson($this->encode($before))
            ->setAfterJson($this->encode($after))
            ->setIp($this->clientIp())
            ->setUserAgent($this->userAgent());

        try {
            $mapper->insertLog($item);
        } catch (\Throwable) {
            // Xem chú thích đầu class: nghiệp vụ quan trọng hơn một dòng nhật ký.
        }
    }

    private function encode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            $value = ['value' => $value];
        }

        foreach (self::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $value)) {
                $value[$key] = '***';
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: null;
    }

    private function request(): ?Request
    {
        $request = $this->getContainerEntry('Request');

        return $request instanceof Request ? $request : null;
    }

    private function clientIp(): ?string
    {
        // useProxy mặc định false: chỉ tin X-Forwarded-For sau khi đã chốt danh sách proxy
        // tin cậy ở tầng hạ tầng, nếu không thì bất kỳ ai cũng tự khai IP của mình được.
        $ip = (new RemoteAddress())->getIpAddress();

        return $ip !== '' ? $ip : null;
    }

    private function userAgent(): ?string
    {
        $header = $this->request()?->getHeader('User-Agent');
        if ($header === null || $header === false) {
            return null;
        }

        // Cột chỉ 255 ký tự; user agent dài hơn thì cắt, không để câu INSERT chết.
        return mb_substr((string)$header->getFieldValue(), 0, 255);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return ($value === null || $value === '') ? null : (string)$value;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int)$value;

        return $value > 0 ? $value : null;
    }

    /**
     * Ngày lọc hợp lệ (Y-m-d) hoặc null. Sai định dạng ⇒ báo lỗi ngay trên filter bar thay vì
     * lặng lẽ bỏ qua để người dùng tưởng đã lọc theo ngày.
     */
    private function dateOrNull(mixed $value, string $field): ?string
    {
        $value = $this->nullIfBlank($value);
        if ($value === null) {
            return null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            throw new ValidationException([$field => 'Ngày lọc phải theo định dạng năm-tháng-ngày, ví dụ 2026-07-31.']);
        }

        return $value;
    }
}
