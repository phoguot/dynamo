# Dynamo Rental

Hệ thống quản lý và cho thuê máy phát điện: đội máy, báo giá, hợp đồng, đơn thuê, điều phối giao nhận, bảo trì, hóa đơn và công nợ.

Đã có bộ design + mockup, **khung ứng dụng Laminas** (`module/Application`: lớp nền
Controller/Service/Mapper/Model/Filter, guard vai trò + CSRF, phân trang, trang lỗi) và **cả 10
module nghiệp vụ M01–M10** (`module/User`, `Fleet`, `Crm`, `Sales`, `Rental`, `Dispatch`,
`Maintenance`, `Billing`, `Reporting`, `Platform`). Quy ước viết code:
[docs/code-standards/laminas-conventions.md](docs/code-standards/laminas-conventions.md).

Hệ thống **chỉ render HTML phía server** — không có tầng API — và **không chạy cron hay tiến
trình nền** nào: xem [ADR-0002](docs/adr/0002-lua-chon-stack.md) và [ADR-0005](docs/adr/0005-xu-ly-job-nen.md).

## Bắt đầu từ đâu

| Bạn muốn | Đọc |
|---|---|
| Hiểu sản phẩm làm gì, phạm vi tới đâu | [PRD.md](PRD.md) |
| Hiểu cách làm việc trong repo này | [CLAUDE.md](CLAUDE.md) |
| Hiểu kiến trúc và cách chia module | [docs/00-architecture.md](docs/00-architecture.md), [docs/01-modules.md](docs/01-modules.md) |
| Hiểu giao diện, xem mockup | [design/README.md](design/README.md) |
| Làm việc trên một module cụ thể | `modules/<Mxx-ten>/CLAUDE.md` |
| Tra thuật ngữ nghiệp vụ | [docs/02-glossary.md](docs/02-glossary.md) |

## Stack

PHP + Laminas MVC · MySQL 8 · PHTML + Bootstrap 5 · một codebase, không tách backend/frontend.
Chi tiết và hệ quả: [ADR-0002](docs/adr/0002-lua-chon-stack.md).

## Yêu cầu môi trường

- PHP 8.3+ với Composer (dùng typed class constants).
- MySQL 8 (InnoDB, utf8mb4).

Không cần Node.js: CSS/JS viết tay trong `public/`, không có bước build.

## Mockup

Mockup tĩnh nằm ở [design/html/index.html](design/html/index.html) kèm ba ảnh preview trong cùng thư mục.
Mở thẳng bằng trình duyệt, không cần công cụ render.

## Cài đặt

```bash
composer install
```

```bash
cp config/autoload/local.php.dist config/autoload/local.php
```

Sửa `config/autoload/local.php` theo thông tin MySQL trên máy bạn, rồi trỏ webserver (hoặc `php -S localhost:8080 -t public`) vào thư mục `public/`.

Tạo schema (chưa có công cụ migrate — hiện nạp thẳng file schema đầy đủ):

```bash
mysql -u root dynamo_rental < data/schema.sql
```

Nạp dữ liệu mẫu để chạy thử (dữ liệu giả, không phải dữ liệu khách hàng thật):

```bash
mysql -u root dynamo_rental < data/demo_seed.sql
```

## Chạy test

```bash
vendor/bin/phpunit
```

## Cấu hình cá nhân

Sao chép mẫu rồi chỉnh theo máy của bạn — hai file này không được commit:

```bash
cp .claude/settings.local.json.example .claude/settings.local.json
```

```bash
cp CLAUDE.local.md.example CLAUDE.local.md
```

## Deploy

Quy trình và điều kiện phát hành: [.claude/rules/deploy.md](.claude/rules/deploy.md) và skill [`phat-hanh`](.claude/skills/phat-hanh/SKILL.md).
