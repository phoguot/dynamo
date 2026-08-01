<?php

declare(strict_types=1);

namespace UserTest\Form\AuditLog;

use ApplicationTest\Form\FormTestCase;
use User\Form\AuditLog\AuditLogSearchForm;
use User\Model\AuditLog\AuditLogModel;

/**
 * Luật lọc của màn hình đọc nhật ký kiểm toán.
 *
 * Form gửi bằng GET, chỉ đọc ⇒ KHÔNG cần CSRF. Whitelist hành động và cột sắp xếp là chốt
 * chặn ở tầng Form; luật ngày (định dạng, from <= to) nằm ở Service, không test ở đây.
 */
class AuditLogSearchFormTest extends FormTestCase
{
    /** @param array<string, mixed> $data */
    private function form(array $data = []): AuditLogSearchForm
    {
        $form = new AuditLogSearchForm($this->container);
        $form->setData($data);

        return $form;
    }

    public function test_khong_can_csrf_va_de_trong_van_hop_le(): void
    {
        self::assertTrue($this->form()->isValid());
    }

    public function test_hanh_dong_trong_whitelist_duoc_chap_nhan(): void
    {
        $form = $this->form(['action' => AuditLogModel::ACTION_PERMISSION_CHANGED]);

        self::assertTrue($form->isValid());
        self::assertSame(AuditLogModel::ACTION_PERMISSION_CHANGED, $form->getData()['action']);
    }

    public function test_hanh_dong_ngoai_whitelist_bi_chan(): void
    {
        // Giá trị lạ ⇒ form không hợp lệ (InArray), controller báo lỗi trên filter bar thay vì
        // để chuỗi lạ lọt xuống câu WHERE.
        $form = $this->form(['action' => 'xoa_sach_he_thong']);

        self::assertFalse($form->isValid());
        self::assertArrayHasKey('action', $form->getMessagesArr());
    }

    public function test_cot_sap_xep_hop_le_duoc_giu(): void
    {
        // Chốt chặn chống SQL injection cho cột sắp xếp nằm ở mapper (applySort tra SORT_MAP),
        // không phải ở form. Ở tầng form chỉ cần khóa sort hợp lệ đi qua đúng như đã gõ.
        foreach (array_keys(AuditLogModel::SORT_MAP) as $sortKey) {
            $form = $this->form(['sort' => $sortKey]);

            self::assertTrue($form->isValid());
            self::assertSame($sortKey, $form->getData()['sort'] ?? null);
        }
    }
}
