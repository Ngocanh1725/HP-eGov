# HỆ THỐNG THÔNG TIN QUẢN LÝ HÀNH CHÍNH & MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MariaDB%2010.4%2B%20%7C%20MySQL%208.0-orange.svg)](https://mariadb.org)
[![Security](https://img.shields.io/badge/Security-Level%203%20%7C%20OWASP%20WSTG%20v4.2-green.svg)](docs/08_BAO_CAO_KIEM_THU_ATTT.md)
[![Tests](https://img.shields.io/badge/Tests-124%2F124%20Passed%20(100%25)-brightgreen.svg)](tests/)
[![Architecture](https://img.shields.io/badge/Architecture-3--Tier%20%7C%20BPMN--Lite-purple.svg)](docs/01_PHAN_TICH_THIET_KE.md)

---

## 🏛️ 1. GIỚI THIỆU TỔNG QUAN

**HP-eGov** là Hệ thống Một cửa điện tử và Cổng Dịch vụ công liên thông đa cấp toàn trình dành riêng cho thành phố Hải Phòng. Hệ thống được thiết kế và triển khai bám sát **Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov**, kết nối liên thông dọc từ **UBND Thành phố $\leftrightarrow$ 15 Quận/Huyện/Thành phố Thủy Nguyên $\leftrightarrow$ Xã/Phường/Thị trấn**, đồng thời luân chuyển liên thông ngang giữa các Sở ban ngành chủ chốt (Sở GTVT, Sở KH&ĐT, Sở Xây dựng, Sở TN&MT).

### Các phân hệ cốt lõi tiêu biểu:
1. **Cổng DVC Trực tuyến Toàn trình:** Tích hợp VNeID Mức 2, nộp hồ sơ E-Form, đối soát danh tính C06, thanh toán trực tuyến, tra cứu tiến độ bằng mã QCVN 102 và mã QR camera không cần đăng nhập.
2. **Bộ phận Một cửa Điện tử:** Quét mã QR CCCD gắn chip tự động bóc tách và đối soát C06 qua LGSP; số hóa tài liệu đầu vào theo Nghị định 107/2021/NĐ-CP kèm ký số OpenSSL RSA-SHA256.
3. **Quy trình Luân chuyển BPMN-lite:** Tự động điều hướng hồ sơ theo cây đơn vị và ma trận thẩm quyền, hỗ trợ lấy ý kiến phối hợp liên phòng / liên sở.
4. **Phê duyệt Ký số & Văn thư số:** Lãnh đạo ký số phê duyệt (SmartCA Cloud PKI / USB Token), Văn thư cấp số tự động từ Sổ văn bản điện tử và đóng dấu số cơ quan (Ban Cơ yếu).
5. **Động cơ Giám sát SLA Thời gian thực:** Tính thời hạn trừ ngày nghỉ/lễ tết/ngoài giờ hành chính, chỉ báo 3 màu (Xanh, Vàng, Đỏ); khóa chuyển bước khi quá hạn và bắt buộc lập Văn bản xin lỗi Mẫu số 04 (TT 01/2018/TT-VPCP).
6. **Trung tâm Điều hành Thông minh (IOC) Hải Phòng:** Dashboard thời gian thực, Heatmap giờ tiếp nhận, biểu đồ phân tích nghẽn luồng (Bottleneck), xếp hạng thi đua 15 quận/huyện và các sở.
7. **An toàn Thông tin Cấp độ 3 & WORM Audit Trail:** Chống SQLi, IDOR, XSS, CSRF; Rate limit Layer 7 (60 req/phút); Trigger CSDL chặn tuyệt đối xóa vật lý hồ sơ; Chuỗi băm bất biến phát hiện can thiệp CSDL.
8. **ETL Pipeline Dữ liệu Lịch sử (5 năm):** Chuyển đổi dữ liệu từ hệ thống cũ theo quy trình 4 bước (Extract, Staging, Transform, Load) kèm đối soát toàn vẹn SHA-256 tệp tin.

---

## 💻 2. YÊU CẦU MÔI TRƯỜNG HỆ THỐNG

- **Máy chủ Web:** Apache 2.4+ (bật `mod_rewrite`, `mod_headers`) hoặc NGINX 1.20+.
- **Phiên bản PHP:** PHP 8.2 trở lên (yêu cầu các extension: `pdo_mysql`, `openssl`, `mbstring`, `curl`, `json`, `fileinfo`).
- **Cơ sở dữ liệu:** MariaDB 10.4.32+ hoặc MySQL 8.0+ (hỗ trợ `JSON`, `utf8mb4_unicode_ci`, Stored Triggers).
- **Môi trường phát triển khuyến nghị:** XAMPP for Windows (C:\xampp) với PHP 8.2 và MySQL đang chạy.

---

## 🚀 3. HƯỚNG DẪN CÀI ĐẶT TỪNG BƯỚC TRÊN XAMPP

### Bước 1: Sao chép mã nguồn vào thư mục Web
Đặt toàn bộ thư mục dự án vào thư mục `htdocs` của XAMPP:
```text
C:\xampp\htdocs\HP-eGov\
```

### Bước 2: Thiết lập Cơ sở dữ liệu CSDL `hp_egov`
1. Mở **phpMyAdmin** tại `http://localhost/phpmyadmin/` hoặc dùng command line MariaDB.
2. Tạo CSDL mới tên `hp_egov` với bảng mã `utf8mb4_unicode_ci`:
   ```sql
   CREATE DATABASE `hp_egov` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Nhập (Import) lần lượt 2 tệp CSDL:
   - Tệp cấu trúc bảng & Trigger: `database/schema.sql`
   - Tệp dữ liệu mẫu ban đầu: `database/seed.sql`
   - *(Tùy chọn)* Cập nhật bổ sung phiên 14: `database/migrations_session14.sql`
4. *(Tùy chọn Hardening)* Tạo người dùng an toàn tối thiểu: Chạy file `database/hardening_user.sql`.

### Bước 3: Cấu hình biến môi trường `.env`
Sao chép tệp mẫu cấu hình và chỉnh sửa tham số:
```cmd
copy .env.example .env
```
Nội dung file `.env` chuẩn môi trường XAMPP:
```ini
APP_NAME="HP-eGov Hải Phòng"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/HP-eGov

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hp_egov
DB_USERNAME=root
DB_PASSWORD=

INTEGRATION_MODE=MOCK
RATE_LIMIT_PER_MINUTE=60
SESSION_LIFETIME=1800
```

### Bước 4: Khởi tạo cặp khóa số và thư mục lưu trữ
Mở Command Prompt / Powershell tại thư mục dự án và chạy lệnh khởi tạo cặp khóa OpenSSL:
```cmd
php -r "require 'app/Core/Autoloader.php'; App\Core\Autoloader::register('.'); App\Core\App::boot(); App\Services\ChuKySoService::generateAllSystemKeys();"
```
Hệ thống sẽ tự động tạo các khóa RSA 2048-bit và chứng thư số X.509 tại `storage/keys/` cho tất cả cán bộ có quyền ký và con dấu số các cơ quan.

### Bước 5: Thiết lập Tác vụ định kỳ Cron Jobs (Nền tảng Windows Task Scheduler)
Để hệ thống tự động quét SLA, đẩy thông báo Outbox và sao lưu CSDL:
- **Quét SLA định kỳ (5 phút/lần):**
  ```cmd
  php C:\xampp\htdocs\HP-eGov\cron\sla_scan.php
  ```
- **Xử lý hàng đợi tin nhắn SMS/Zalo (1 phút/lần):**
  ```cmd
  php C:\xampp\htdocs\HP-eGov\cron\notify_dispatch.php
  ```
- **Sao lưu hàng ngày (23:00):**
  ```cmd
  php C:\xampp\htdocs\HP-eGov\cron\backup_diff.php
  ```

---

## 🔑 4. DANH SÁCH TÀI KHOẢN MẪU KIỂM THỬ (7 VAI TRÒ)

Mật khẩu mặc định cho toàn bộ tài khoản cán bộ nội bộ là: **`Hpegov@2026`**

| Vai trò RBAC | Tên đăng nhập | Họ và tên | Đơn vị công tác | Quyền hạn chính |
| :--- | :--- | :--- | :--- | :--- |
| **SYSADMIN** | `sysadmin` | Nguyễn Hoàng Nam | Trung tâm CNTT & TT (Sở TT&TT) | Quản trị hệ thống, RBAC, ETL, Sao lưu, ATTT |
| **CB_MOT_CUA** | `motcua.hongbang` | Nguyễn Thị Mai | Bộ phận Một cửa - UBND Q. Hồng Bàng | Tiếp nhận, Quét QR CCCD C06, Ký số hóa đầu vào |
| **CHUYEN_VIEN** | `chuyenvien.hongbang` | Trần Văn Hùng | Phòng TN&MT - UBND Q. Hồng Bàng | Thụ lý hồ sơ, lấy ý kiến phối hợp liên phòng |
| **LD_PHONG** | `ldphong.hongbang` | Phạm Đức Minh | Trưởng phòng TN&MT Q. Hồng Bàng | Thẩm định hồ sơ, ký số duyệt bước 1 |
| **LD_CO_QUAN** | `ldcoquan.hongbang` | Lê Ngọc Tân | Phó Chủ tịch UBND Q. Hồng Bàng | Phê duyệt kết quả, Ký số SmartCA, Ký Mẫu 04 |
| **VAN_THU** | `vanthu.hongbang` | Hoàng Thu Thủy | Văn phòng HĐND & UBND Q. Hồng Bàng | Cấp số sổ văn bản, Đóng dấu số cơ quan |
| **CONG_DAN** | *Đăng nhập VNeID* | Nguyễn Văn An | Phường Hoàng Văn Thụ, Q. Hồng Bàng | Nộp DVC, tra cứu QR, thanh toán, kho dữ liệu |

---

## 🧪 5. KIỂM THỬ HỆ THỐNG (TEST RUNNER)

Hệ thống tích hợp sẵn Test Runner CLI độc lập không phụ thuộc Composer:
```cmd
php tests/run.php
```
Kết quả kiểm thử đạt **124/124 bài test thành công 100%**:
- **Unit Tests:** `tests/Unit/` (56 tests)
- **Integration Tests:** `tests/Integration/` (27 tests)
- **Security Tests:** `tests/Security/` (11 tests bao phủ OWASP Top 10 & Cấp độ 3)
- **Functional Matrix:** `tests/Functional/` (15 tests FR01–FR15 cho 7 vai trò)
- **UAT Tests:** `tests/uat/` (6 tests mô phỏng Hồng Bàng & Sở GTVT)

---

## 📂 6. CẤU TRÚC THƯ MỤC DỰ ÁN

```text
HP-eGov/
├── app/
│   ├── Config/            # Các tệp cấu hình hệ thống (app, database, security, session, sla)
│   ├── Controllers/       # Tầng điều khiển (Admin, Portal, Api)
│   ├── Core/              # Framework lõi siêu nhẹ (App, Database, Router, Model, Request, Response)
│   ├── Enums/             # Định nghĩa Enum chuẩn (TrangThaiHoSo, VaiTro, MauSla, KenhNop...)
│   ├── Helpers/           # Hàm tiện ích dùng chung (functions.php)
│   ├── Integrations/      # Các Adapter kết nối (Mock & Gateway: LGSP, C06, VNeID, SmartCA)
│   ├── Middleware/        # Pipeline lọc HTTP (SecurityHeaders, Csrf, RateLimit, Auth, Permission)
│   ├── Models/            # Tầng thực thể dữ liệu Active Record
│   ├── Services/          # Tầng xử lý nghiệp vụ cốt lõi (Workflow, Sla, ChuKySo, Etl, Audit, Mdm...)
│   └── Views/             # Tầng giao diện người dùng (Admin, Portal, Partials, Layouts)
├── cron/                  # Các kịch bản chạy nền tự động (SLA scan, notify dispatch, backup)
├── database/              # CSDL, Schema, Dữ liệu ban đầu, Mẫu ETL, Script Hardening
├── docs/                  # Hệ thống tài liệu phân tích, kiến trúc, API, kiểm thử, HDSD 7 vai trò
├── public/                # Thư mục Web Root công khai duy nhất (index.php, assets, vendor)
├── storage/               # Lưu trữ khóa bí mật (keys/), tài liệu đính kèm (uploads/), nhật ký (logs/)
└── tests/                 # Toàn bộ bộ kiểm thử tự động (Unit, Integration, Security, Functional, UAT)
```

---

## 📚 7. DANH MỤC TÀI LIỆU KỸ THUẬT BÀN GIAO

Toàn bộ tài liệu chi tiết được lưu trữ trong thư mục [`docs/`](docs/):
1. [`docs/00_TRUY_VET_BAO_CAO.md`](docs/00_TRUY_VET_BAO_CAO.md): Ma trận đối soát 100% mục Báo cáo Kiến trúc PDF.
2. [`docs/01_PHAN_TICH_THIET_KE.md`](docs/01_PHAN_TICH_THIET_KE.md): Đặc tả phân tích thiết kế hệ thống & BPMN-lite.
3. [`docs/02_THIET_KE_CSDL.md`](docs/02_THIET_KE_CSDL.md): Thiết kế CSDL quan hệ chuẩn hóa 42 bảng.
4. [`docs/05_QUY_CHE_VAN_HANH.md`](docs/05_QUY_CHE_VAN_HANH.md): Quy chế vận hành, bảo trì, RACI và diễn tập sự cố DRP.
5. [`docs/06_API.md`](docs/06_API.md): Đặc tả RESTful API `/api/v1` & Webhook LGSP.
6. [`docs/07_KE_HOACH_KIEM_THU.md`](docs/07_KE_HOACH_KIEM_THU.md): Kế hoạch chiến lược 5 loại hình kiểm thử.
7. [`docs/08_BAO_CAO_KIEM_THU_ATTT.md`](docs/08_BAO_CAO_KIEM_THU_ATTT.md): Báo cáo an toàn thông tin OWASP WSTG v4.2 & Cấp độ 3.
8. [`docs/09_BAO_CAO_KET_QUA_KIEM_THU.md`](docs/09_BAO_CAO_KET_QUA_KIEM_THU.md): Báo cáo tổng kết nghiệm thu chất lượng kiểm thử.
9. [`docs/10_BIEN_BAN_NGHIEM_THU_UAT.md`](docs/10_BIEN_BAN_NGHIEM_THU_UAT.md): Biên bản nghiệm thu kỹ thuật UAT thực địa 4 bên & Phiếu SIPAS.
10. [`docs/10_HDSD_<VAI_TRO>.md`](docs/): Hướng dẫn sử dụng chi tiết cho 7 vai trò người dùng.
11. [`docs/11_TRIEN_KHAI_PRODUCTION.md`](docs/11_TRIEN_KHAI_PRODUCTION.md): Hướng dẫn kiến trúc 3 vùng mạng, HA, Replication và lộ trình triển khai Production.
