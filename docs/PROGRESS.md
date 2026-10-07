# NHẬT KÝ TIẾN ĐỘ TRIỂN KHAI DỰ ÁN HP-eGov

> **Đề tài:** Phân tích, thiết kế và xây dựng Hệ thống thông tin quản lý hành chính & Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov)  
> **Cơ quan chủ quản:** Ủy ban nhân dân Thành phố Hải Phòng  
> **Đơn vị thẩm định / Giám sát:** Sở Thông tin và Truyền thông TP. Hải Phòng  
> **Tài liệu căn cứ gốc:** `Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf`  
> **Quy chuẩn mã nguồn & phát triển:** Tuân thủ bắt buộc `.agents/rules/hp-egov.md`  

---

## 1. BẢNG THEO DÕI TIẾN ĐỘ 17 PHIÊN TRIỂN KHAI

| Phiên | Tên phiên | Phân hệ / Nội dung chính | Trạng thái | Ngày hoàn thành | Ghi chú / File chính |
|:---:|---|---|:---:|:---:|---|
| **0** | **Thiết lập bối cảnh & Rule workspace** | Rule workspace, kiểm tra môi trường, khung thư mục, Git, tài liệu tiến độ | **Hoàn thành** | 2026-10-07 | `.agents/rules/hp-egov.md`, `PROGRESS.md`, `00_TRUY_VET_BAO_CAO.md` |
| **1** | Phân tích & Thiết kế hệ thống | Đặc tả 15 phân hệ, Use Case, State Machine, Sequence, ERD, 3 vùng mạng | *Chưa làm* | - | Kế hoạch: `docs/01_PHAN_TICH_THIET_KE.md` |
| **2** | Cơ sở dữ liệu (phpMyAdmin) & Dữ liệu mẫu | DDL InnoDB, trigger chặn DELETE, view, seed 15 quận/huyện HP, ~200 hồ sơ | *Chưa làm* | - | Kế hoạch: `database/schema.sql`, `seed.sql` |
| **3** | Lõi MVC (Core Framework) | PHP 8.2 pure, Router, Controller, Model, View, DB master/replica, CSRF, Test runner | *Chưa làm* | - | Kế hoạch: `app/Core/*`, `tests/run.php` |
| **4** | Design System & Layout giao diện | Navy `#0B2E59`, Be Vietnam Pro, Chart.js, Portal layout, Admin layout, UI Kit | *Chưa làm* | - | Kế hoạch: `public/assets/*`, `app/Views/layouts/*` |
| **5** | Xác thực & Phân quyền RBAC đa cấp | Guard cán bộ/công dân, SSO VNeID mock, 7 vai trò, ma trận quyền, Audit hash chain | *Chưa làm* | - | Kế hoạch: PH11, `app/Services/AuthService.php`, `AuditService.php` |
| **6** | Danh mục dùng chung (MDM) & Quy trình | MDM ĐVHC, TTHC quốc gia, BPMN-lite quy trình liên thông, E-Form designer JSON | *Chưa làm* | - | Kế hoạch: PH09, PH10, `app/Services/WorkflowEngine.php` |
| **7** | Cổng Dịch vụ công (Công dân) | Wizard nộp hồ sơ, Auto-fill VNeID, Kho số hóa công dân, tra cứu QR, SIPAS | *Chưa làm* | - | Kế hoạch: PH01, `app/Controllers/Portal/*` |
| **8** | Bộ phận Một cửa | Bàn tiếp nhận, quét QR CCCD, số hóa NĐ 107/2021, OCR mock, in Giấy hẹn A4 | *Chưa làm* | - | Kế hoạch: PH02, `app/Controllers/Admin/MotCuaController.php` |
| **9** | Workflow Engine · Thụ lý & Liên thông | Phân công, thẩm định, đối soát LGSP/C06, liên thông ngang/dọc đa cơ quan | *Chưa làm* | - | Kế hoạch: PH03, `WorkflowEngine.php`, `LienThongLogisticsTest.php` |
| **10** | Phê duyệt, Ký số & Văn thư số | OpenSSL demo, SmartCA mock, ký số hàng loạt, cấp số sổ phát hành, đóng dấu số | *Chưa làm* | - | Kế hoạch: PH04, PH05, `ChuKySoService.php`, `VanThuService.php` |
| **11** | SLA Engine & Thông báo đa kênh | SLA Xanh/Vàng/Đỏ (trừ ngày nghỉ), khóa quá hạn, Mẫu 04 xin lỗi, SMS/Zalo mock | *Chưa làm* | - | Kế hoạch: PH06, PH07, `cron/sla_scan.php`, `cron/notify_dispatch.php` |
| **12** | Dashboard chỉ đạo điều hành & Báo cáo | IOC Hải Phòng, KPI 4 chỉ tiêu, Heatmap, Bản đồ 15 quận/huyện, báo cáo TT 01 | *Chưa làm* | - | Kế hoạch: PH08, `app/Controllers/Admin/DashboardController.php` |
| **13** | Quản trị & Vận hành hệ thống | Sao lưu RPO ≤ 2h, RTO ≤ 4h, Health endpoint, sự cố BCP/DRP, đào tạo 4 nhóm | *Chưa làm* | - | Kế hoạch: PH12, PH13, PH15, `docs/05_QUY_CHE_VAN_HANH.md` |
| **14** | Chuyển đổi dữ liệu ETL, Tích hợp & API | ETL 4 bước (checksum SHA-256), REST API /api/v1, Webhook LGSP, tài liệu API | *Chưa làm* | - | Kế hoạch: PH14, `EtlService.php`, `docs/06_API.md` |
| **15** | Kiểm thử toàn diện & QA/QC | Unit, Integration liên thông, Security OWASP Top 10, k6/JMeter tải, UAT | *Chưa làm* | - | Kế hoạch: `tests/*`, `docs/07_KE_HOACH_KIEM_THU.md` |
| **16** | Clean code, Hardening & Bàn giao | Rà soát PSR-12, CSP, user MySQL tối thiểu, README, HDSD 7 vai trò, Git tag v1.0.0 | *Chưa làm* | - | Kế hoạch: `README.md`, `docs/10_HDSD_*`, `git tag v1.0.0` |

---

## 2. NHẬT KÝ CHI TIẾT TỪNG PHIÊN

### PHIÊN 0: THIẾT LẬP BỐI CẢNH & RULE WORKSPACE
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Đọc và phân tích toàn diện 17 trang tài liệu gốc `Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf`.
  2. Trích xuất nguyên văn toàn bộ nội dung Master Context thành rule workspace `.agents/rules/hp-egov.md` để Antigravity tự động áp dụng bắt buộc cho mọi phiên làm việc tiếp theo.
  3. Khởi tạo Git repository cho thư mục dự án `c:\xampp\htdocs\HP-eGov\`, cấu hình `.gitignore` chuẩn bảo mật (chặn rò rỉ `.env` và các file tạm trong `storage/`).
  4. Tạo toàn bộ khung thư mục theo chuẩn MVC thuần PHP 8.2 (37 thư mục và các file `.gitkeep` giữ nhánh).
  5. Kiểm tra chi tiết môi trường thực tế (PHP 8.2.12, extensions, trạng thái Apache mod_rewrite, AllowOverride, cổng MySQL 3306).
  6. Xây dựng tài liệu ma trận truy vết toàn diện `docs/00_TRUY_VET_BAO_CAO.md` kết nối từng mục báo cáo với 15 phân hệ và kế hoạch thực hiện.
  7. Thiết lập tài liệu theo dõi tiến độ `docs/PROGRESS.md`.
- **Tệp tin đã tạo:**
  - `.agents/rules/hp-egov.md`
  - `.gitignore`
  - `docs/PROGRESS.md`
  - `docs/00_TRUY_VET_BAO_CAO.md`
  - Hệ thống 37 thư mục khung chuẩn MVC
- **Tình trạng môi trường:**
  - PHP: `8.2.12` CLI (Đạt chuẩn). Các extension cần thiết (`pdo_mysql`, `openssl`, `curl`, `fileinfo`, `gd`, `mbstring`) đều đã bật.
  - Apache: `mod_rewrite` đã bật; `AllowOverride All` đã cấu hình cho `C:/xampp/htdocs`. Lưu ý: Cần nhấn **Start** Apache trên XAMPP Control Panel để phục vụ web.
  - MySQL: Cổng 3306 chưa mở (chưa khởi động service MySQL). Cần nhấn **Start** MySQL trên XAMPP Control Panel trước khi thực hiện Phiên 2.
- **Kế hoạch cho Phiên 1:**
  - Chạy **Phiên 1/16 – Phân tích & Thiết kế hệ thống** để tạo tài liệu đặc tả phân tích kiến trúc `docs/01_PHAN_TICH_THIET_KE.md` (Use Case, State Machine, Sequence, DFD, ERD, Sitemap, Ma trận quyền chi tiết).
