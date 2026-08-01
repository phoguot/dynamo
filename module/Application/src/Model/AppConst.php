<?php

declare(strict_types=1);

namespace Application\Model;

/**
 * Hằng số dùng chung toàn hệ thống.
 * Enum trạng thái nghiệp vụ KHÔNG đặt ở đây — xem contracts/enums.md và <Entity>Const của module sở hữu.
 */
class AppConst
{
    /** Múi giờ hiển thị. Dữ liệu luôn lưu UTC — xem CLAUDE.md mục "Quy ước code". */
    public const string TIMEZONE_DISPLAY = 'Asia/Ho_Chi_Minh';

    /** Đơn vị tiền tệ duy nhất của hệ thống. Lưu BIGINT, không thập phân. */
    public const string CURRENCY = 'VND';

    // --- Khóa session ---
    public const string SESSION_NAMESPACE = 'dynamo';
    public const string SESSION_KEY_USER  = 'identity';
    /** Bí mật gốc để dẫn xuất CSRF token theo từng form — không phải token gửi xuống client. */
    public const string SESSION_KEY_CSRF  = 'csrfSecret';

    // --- CSRF ---
    /** Tên input hidden mang token trong mọi form POST. */
    public const string FIELD_CSRF_TOKEN = 'csrfToken';
    /** Tên input hidden mang TÊN FORM, để server biết kiểm token theo form nào. */
    public const string FIELD_FORM_NAME  = 'formName';
    /** Dùng khi một form (hiếm) không khai tên riêng. */
    public const string CSRF_FORM_DEFAULT = 'default';
}
