<?php

declare(strict_types=1);

namespace User\Controller;

use Application\Controller\BaseController;
use Application\Exception\ValidationException;
use Laminas\View\Model\ViewModel;
use User\Service\AuditLogService;

/**
 * Màn hình đọc nhật ký kiểm toán toàn hệ thống (/audit-logs).
 *
 * Chỉ ĐỌC: bảng usr_audit_logs là append-only, không có action tạo/sửa/xóa ở đây. Quyền vào
 * màn hình do ACL M01 quyết định (resource `user:auditlog`).
 *
 * Controller mỏng: nhận request → gọi Service → đổ vào ViewModel.
 */
class AuditLogController extends BaseController
{
    /** Danh sách nhật ký, có lọc và phân trang. */
    public function indexAction(): ViewModel
    {
        $query = $this->getAllQueryParams();
        $service = $this->getContainerEntry(AuditLogService::class);

        $model = $this->getViewModel();

        try {
            $paginator = $service->searchLogs($query);
            $model->setVariables([
                'paginator'  => $paginator,
                'actorNames' => $service->actorNamesForPage($paginator),
                'errors'     => [],
            ]);
        } catch (ValidationException $e) {
            // Tham số lọc sai: vẫn hiển thị trang, báo lỗi ngay trên filter bar.
            $model->setVariables([
                'paginator'  => null,
                'actorNames' => [],
                'errors'     => $e->getErrors(),
            ]);
        }

        $model->setVariable('searchForm', $service->newSearchForm($query));

        return $model;
    }
}
