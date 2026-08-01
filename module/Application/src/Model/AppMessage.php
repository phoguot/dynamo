<?php

declare(strict_types=1);

namespace Application\Model;

/**
 * Câu thông báo dùng chung. Tiếng Việt có dấu, câu hoàn chỉnh.
 * Thông báo riêng của nghiệp vụ đặt ở <Entity>Const của module sở hữu, không nhét vào đây.
 */
class AppMessage
{
    // --- Trang / phiên ---
    public const string COMMON_401          = 'Bạn chưa đăng nhập.';
    public const string COMMON_403          = 'Bạn không có quyền truy cập nội dung này.';
    public const string COMMON_404          = 'Không tìm thấy nội dung yêu cầu.';
    public const string COMMON_BACK_TO_HOME = 'Về trang chủ';
    public const string SYSTEM_ERROR        = 'Hệ thống đang gặp sự cố, vui lòng thử lại sau.';
    public const string CSRF_INVALID        = 'Phiên làm việc đã hết hạn, vui lòng tải lại trang và thử lại.';

    // --- Kết quả thao tác ---
    public const string ADD_SUCCESSFULLY    = 'Thêm dữ liệu thành công.';
    public const string SAVE_SUCCESSFULLY   = 'Lưu dữ liệu thành công.';
    public const string UPDATE_SUCCESSFULLY = 'Cập nhật dữ liệu thành công.';
    public const string DELETE_SUCCESSFULLY = 'Xóa dữ liệu thành công.';
    public const string CANCEL_SUCCESSFULLY = 'Hủy bản ghi thành công.';

    // --- Dữ liệu ---
    public const string NO_DATA        = 'Không tìm thấy dữ liệu.';
    public const string EXISTED_DATA   = 'Dữ liệu đã tồn tại.';
    public const string INVALID_DATA   = 'Dữ liệu không hợp lệ.';
    public const string NO_PERMISSION_ON_RECORD = 'Bạn không có quyền thao tác trên bản ghi này.';

    // --- Validate ---
    public const string VALIDATOR_REQUIRED   = 'Trường dữ liệu không được để trống.';
    public const string DATE_FORMAT_INVALID  = 'Định dạng ngày không hợp lệ.';
    public const string STATUS_INVALID       = 'Trạng thái không hợp lệ.';
    public const string AMOUNT_NEGATIVE      = 'Số tiền không được nhỏ hơn 0.';
    public const string FROM_DATE_AFTER_TO_DATE = 'Ngày bắt đầu không được lớn hơn ngày kết thúc.';

    // --- State machine ---
    public const string TRANSITION_NOT_ALLOWED = 'Không thể chuyển trạng thái này.';

    // --- File ---
    public const string NO_FILE_SELECT             = 'Bạn chưa chọn file.';
    public const string FILE_UPLOAD_INVALID_FORMAT = 'Định dạng file không hợp lệ.';
    public const string FILE_UPLOAD_FAILED         = 'Tải file lên không thành công.';
}
