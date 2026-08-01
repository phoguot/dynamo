<?php

declare(strict_types=1);

namespace Application\Service;

/**
 * Cổng hỏi quyền của tầng nền.
 *
 * `BaseController` cần biết "người đang đăng nhập có được vào màn hình này không", nhưng
 * câu trả lời nằm ở M01 user (bảng `usr_user_permissions` + `UserAcl`). Tầng nền KHÔNG
 * được import class của module nghiệp vụ (.claude/rules/module-boundaries.md), nên hợp đồng
 * đặt ở đây và M01 đăng ký hiện thực vào container dưới đúng tên interface này.
 *
 * Mọi quyết định public/đăng nhập/quyền action đều đi qua interface này để không lặp role
 * trong từng controller.
 */
interface PermissionCheckerInterface
{
    /**
     * Người đang đăng nhập có được chạy action này của controller này không.
     *
     * Tham số cố ý là TÊN CLASS chứ không phải chuỗi quyền: tầng nền không biết — và không
     * cần biết — quy ước đặt tên resource của M01. Việc đổi `Fleet\Controller\GeneratorController`
     * thành `fleet:generator` là chuyện nội bộ của M01.
     *
     * @param string $controllerClass FQCN của controller đang xử lý request
     * @param string $action          tên action lấy từ route, ví dụ `index`, `changestatus`
     */
    public function isAllowedAction(string $controllerClass, string $action): bool;

    /**
     * Action này có cho người chưa đăng nhập chạy không.
     *
     * Dùng cho login/trang chủ/trang công khai. Các action không khai public phải đăng nhập
     * rồi mới được xét quyền bằng isAllowedAction().
     */
    public function isPublicAction(string $controllerClass, string $action): bool;
}
