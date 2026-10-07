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
| **1** | **Phân tích & Thiết kế hệ thống** | Đặc tả 15 phân hệ, Use Case, State Machine, Sequence, ERD, 3 vùng mạng | **Hoàn thành** | 2026-10-07 | `docs/01_PHAN_TICH_THIET_KE.md` |
| **2** | **Cơ sở dữ liệu (phpMyAdmin) & Dữ liệu mẫu** | DDL InnoDB, trigger chặn DELETE, view, seed 15 quận/huyện HP, ~200 hồ sơ | **Hoàn thành** | 2026-10-07 | `database/schema.sql`, `database/seed.sql`, `docs/02_THIET_KE_CSDL.md` |
| **3** | **Lõi MVC (Core Framework)** | PHP 8.2 pure, Router, Controller, Model, View, DB master/replica, CSRF, Test runner | **Hoàn thành** | 2026-10-07 | `app/Core/*`, `tests/run.php`, `public/index.php` |
| **4** | **Design System & Layout giao diện** | Navy `#0B2E59`, Be Vietnam Pro, Chart.js, Portal layout, Admin layout, UI Kit | **Hoàn thành** | 2026-10-07 | `public/assets/*`, `layouts/*`, `ui_kit.php` |
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

### PHIÊN 1: PHÂN TÍCH & THIẾT KẾ HỆ THỐNG
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Xây dựng tài liệu phân tích thiết kế toàn diện `docs/01_PHAN_TICH_THIET_KE.md` chuẩn hóa đầy đủ 12 nội dung cốt lõi và mục giả định kỹ thuật.
  2. Đặc tả chi tiết 15 phân hệ nghiệp vụ (PH01 – PH15) với mã yêu cầu chức năng `FRxx.yy`, phân quyền vai trò và độ ưu tiên.
  3. Xây dựng các sơ đồ Mermaid chuyên sâu: Sơ đồ Mindmap phân hệ, Use Case tổng quát và chi tiết, Sơ đồ hoạt động luồng 5 bước (§2.1), Sơ đồ máy trạng thái (State Machine) 9 trạng thái chuẩn Thông tư 01/2018/TT-VPCP.
  4. Thiết lập 3 sơ đồ tuần tự (Sequence Diagram) quan trọng:
     - (a) Luồng nộp trực tuyến qua VNeID OIDC + Auto-fill E-Form;
     - (b) Luồng liên thông dự án Logistics cảng biển: Q. Hồng Bàng → Sở XD → Sở TN&MT → Sở KH&ĐT → UBND Thành phố;
     - (c) Luồng quá hạn SLA, kích hoạt khóa chuyển bước và lập Văn bản xin lỗi Mẫu số 04.
  5. Thiết lập sơ đồ luồng dữ liệu DFD Mức 0 và Mức 1; Sơ đồ thực thể liên kết ERD mức khái niệm kết nối toàn diện các bảng dữ liệu.
  6. Mô hình hóa kiến trúc 3 vùng mạng an toàn Đạt chuẩn Cấp độ 3 (Edge WAF → DMZ → Nội bộ TSLCD cấp II → CSDL Master/Replica & WORM log) đúng sơ đồ Trang 4 Báo cáo; ánh xạ chuẩn sang kiến trúc phần mềm PHP MVC.
  7. Lập Sitemap chi tiết cho Cổng DVC và Khu vực Quản trị theo vai trò; thiết lập Ma trận phân quyền chi tiết (Matrix of Permissions).
- **Tệp tin đã tạo:**
  - `docs/01_PHAN_TICH_THIET_KE.md`
- **Kế hoạch cho phiên tiếp theo:** Chuyển sang Phiên 2 thực hiện cơ sở dữ liệu.

### PHIÊN 2: CƠ SỞ DỮ LIỆU (phpMyAdmin) & DỮ LIỆU MẪU HẢI PHÒNG
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Xây dựng mã nguồn CSDL `database/schema.sql`:
     - Khởi tạo Database `hp_egov` (charset `utf8mb4`, collate `utf8mb4_unicode_ci`).
     - Thiết kế đầy đủ 50 bảng chuẩn hóa InnoDB thuộc 9 nhóm nghiệp vụ: Tổ chức & Định danh, Danh mục dùng chung, Quy trình & E-Form, Hồ sơ Một cửa, Ký số & Văn thư, Thông báo & Tích hợp, Quản trị hệ thống & Bảo mật (BCP/DRP/Audit chain), ETL chuyển đổi dữ liệu, Đào tạo bồi dưỡng.
     - 100% các bảng và cột đều có chú thích `COMMENT` bằng tiếng Việt nghiệp vụ chuẩn hóa.
     - Thiết lập hệ thống khóa ngoại (`FOREIGN KEY`) toàn vẹn và chỉ mục đánh số/tra cứu tối ưu (`INDEX`, `FULLTEXT`).
  2. Xây dựng 6 Trigger bảo mật và toàn vẹn dữ liệu bất biến:
     - Chặn triệt để thao tác xóa vật lý `DELETE` trên các bảng nghiệp vụ cốt lõi: `ho_so`, `ho_so_tai_lieu`, `ho_so_luan_chuyen`, `chu_ky_so` (kích hoạt lỗi `SQLSTATE '45000'`).
     - Chặn triệt để thao tác `UPDATE` và `DELETE` trên bảng `nhat_ky_kiem_toan` (đảm bảo nguyên tắc WORM Append-Only chống chối bỏ).
     - Đã kiểm thử kích hoạt trigger bằng lệnh `DELETE FROM ho_so` trong MySQL: trigger bắt giữ thành công và trả về lỗi `ERROR 1644 (45000): [HP-eGov Bảo mật] Nghiêm cấm xóa vật lý hồ sơ hành chính! Phải sử dụng cơ chế hủy logic`.
  3. Xây dựng 3 Views báo cáo phục vụ điều hành IOC và thống kê theo Thông tư 01/2018/TT-VPCP:
     - `v_ho_so_tong_hop`: Quy đổi 9 trạng thái nghiệp vụ về 6 nhóm chỉ tiêu chuẩn hóa quốc gia.
     - `v_ty_le_dung_han_don_vi`: Tính toán tự động tỷ lệ giải quyết đúng/trước hạn của từng cơ quan, phòng ban (đối chiếu KPI $\ge 98\%$).
     - `v_ho_so_qua_han`: Thống kê danh sách hồ sơ quá hạn SLA, cảnh báo kích hoạt Văn bản xin lỗi Mẫu 04.
  4. Xây dựng kịch bản nạp dữ liệu thực tế Hải Phòng `database/seed.sql`:
     - Nạp ĐVHC chuẩn: TP Hải Phòng (31) và đầy đủ 15 quận/huyện/thành phố theo báo cáo (Hồng Bàng 303, Ngô Quyền 304, Lê Chân 305, Hải An 306, Kiến An 307, Đồ Sơn 308, Dương Kinh 309, TP. Thủy Nguyên 311, Q. An Dương 312, H. An Lão 313, Kiến Thụy 314, Tiên Lãng 315, Vĩnh Bảo 316, Cát Hải 317, Bạch Long Vĩ 318), kèm các phường/xã mẫu với cơ chế hiệu lực theo thời gian (`hieu_luc_tu`, `hieu_luc_den`).
     - Nạp cây đơn vị hành chính: UBND Thành phố, Văn phòng UBND TP, các Sở (KH&ĐT, XD, TN&MT, GTVT, TT&TT), 15 UBND quận/huyện, các Bộ phận Một cửa và phòng ban trực thuộc; mã định danh chuẩn QCVN 102 (`000.04.15.H21...`).
     - Nạp 7 vai trò chuẩn, 34 mã quyền chi tiết, ma trận phân quyền vai trò - quyền theo §4.1.
     - Tạo danh sách tài khoản cán bộ mẫu với mật khẩu Bcrypt an toàn (`Hpegov@2026`).
     - Nạp 12 thủ tục hành chính mẫu trọng điểm (khai sinh, chứng thực, GPXD, biến động đất đai, cấp đổi GPLX, phù hiệu xe tải cảng biển, liên thông dự án logistics cảng biển...) kèm thành phần hồ sơ, cấu trúc E-Form JSON và các bước quy trình chuẩn xác.
     - Nạp danh mục ngày nghỉ lễ/Tết theo Bộ luật Lao động 2019 và tham số cấu hình hệ thống (giờ làm việc, tỷ lệ cảnh báo SLA 75%, rate limit...).
     - Nạp 205 hồ sơ hành chính mẫu phân bổ trên 15 đơn vị trong 6 tháng, đủ 9 trạng thái và 3 màu cảnh báo SLA (Xanh, Vàng, Đỏ), kèm luân chuyển, ý kiến phối hợp liên thông, nhật ký kiểm toán băm chuỗi SHA-256, khảo sát SIPAS và văn bản xin lỗi Mẫu số 04.
  5. Xây dựng tài liệu thiết kế CSDL toàn diện `docs/02_THIET_KE_CSDL.md` gồm sơ đồ ERD Mermaid chi tiết, từ điển dữ liệu 50 bảng, cơ chế triggers và views báo cáo.
  6. Import thử nghiệm thành công 100% vào MariaDB/MySQL 10.4 qua CLI (tương thích hoàn toàn với phpMyAdmin).
- **Tệp tin đã tạo:**
  - `database/schema.sql`
  - `database/seed.sql`
  - `docs/02_THIET_KE_CSDL.md`
- **Kế hoạch cho phiên tiếp theo:** Chuyển sang Phiên 3 thực hiện Lõi MVC Framework.

### PHIÊN 3: LÕI MVC (CORE FRAMEWORK)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Xây dựng nền tảng điều hướng và khởi động:
     - Root `.htaccess` điều hướng toàn bộ request vào `public/`, chặn truy cập trực tiếp file môi trường và tài liệu nhạy cảm.
     - `public/.htaccess` thiết lập RewriteEngine chuyển hướng tất cả URL ảo về `public/index.php` (Front Controller duy nhất), chặn `Options -Indexes`.
     - `public/index.php` khởi tạo hằng số `HPEGOV_START`, nạp bootstrap và thực thi `App::run()`.
     - `app/bootstrap.php` nạp Autoloader PSR-4 thuần cho namespace `App\` $\rightarrow$ `app/`, nạp helper functions và khởi động ứng dụng.
  2. Xây dựng hệ thống cấu hình và môi trường:
     - `app/Core/Env.php`: Đọc file `.env`, parse kiểu dữ liệu tự động (`true`, `false`, `null`, `string`, cắt bỏ comment cuối dòng).
     - `app/Core/Config.php`: Truy xuất cấu hình đa cấp theo cú pháp dot-notation (`Config::get('app.url')`), cache trong bộ nhớ.
     - `.env.example` & `.env`: Cấu hình đầy đủ các biến môi trường (`APP_*`, `DB_*`, `DB_READ_*`, `INTEGRATION_MODE`, `SESSION_*`, `RATE_LIMIT_*`).
     - Tệp cấu hình: `app/Config/app.php`, `database.php`, `session.php`, `security.php`, `sla.php`.
  3. Xây dựng tầng HTTP Foundation & Routing:
     - `app/Core/Request.php`: Đóng gói input, hỗ trợ Method Spoofing (`_method` và header `X-HTTP-Method-Override`), chuẩn hóa đường dẫn tương đối (tự động loại bỏ prefix thư mục con XAMPP `/HP-eGov`), xử lý an toàn JSON payload, file uploads, IP và AJAX detection.
     - `app/Core/Response.php`: Đóng gói phản hồi HTTP (HTML, JSON utf-8, Redirect, Tải file an toàn chống Path Traversal).
     - `app/Core/Router.php`: Đăng ký route `GET`, `POST`, `PUT`, `DELETE`, hỗ trợ tham số động có regex `{id:\d+}`, Route Group với `prefix` và `middleware`, đặt tên route và hàm đảo ngược URL `route()`.
  4. Xây dựng tầng Cơ sở MVC:
     - `app/Core/Controller.php`: Cung cấp hàm tiện ích `view()`, `json()`, `redirect()`, `authorize()`.
     - `app/Core/View.php`: Render giao diện thuần PHP hỗ trợ Layouts kế thừa, Section bắt khối và Partial tái sử dụng.
     - `app/Core/Model.php`: Prepared statement PDO 100%, query builder (`where`, `whereIn`, `orderBy`, `limit`, `paginate`), Mass Assignment whitelist `$fillable`. **Đặc biệt: Chặn tuyệt đối xóa vật lý (`delete()` ném LogicException), chỉ hỗ trợ `softDelete()`**.
     - `app/Core/Paginator.php`: Quản lý phân trang và sinh liên kết HTML chuẩn giao diện.
  5. Xây dựng tầng CSDL PDO Master/Replica:
     - `app/Core/Database.php`: Quản lý kết nối PDO Singleton với `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_EMULATE_PREPARES => false`, hỗ trợ tách kết nối Write (Master) và Read (Replica), cơ chế Transaction an toàn `transaction(callable)`.
  6. Xây dựng hệ thống Bảo mật, Phiên & Tiện ích:
     - `app/Core/Session.php`: Cấu hình Cookie an toàn (HttpOnly, SameSite, Secure khi HTTPS), kiểm soát Idle Timeout 30 phút, xoay vòng session ID và cơ chế Flash message.
     - `app/Core/Csrf.php`: Sinh token ngẫu nhiên 32-byte bằng `random_bytes`, kiểm tra qua `hash_equals`, sinh thẻ ẩn form HTML.
     - `app/Core/Validator.php`: Tập luật kiểm định đầy đủ quy chuẩn Việt Nam (`required`, `email`, `cccd` 12 số, `mst` 10/13 số, `phone` VN, `date`, `in`, `max`, `min`, `unique` CSDL...) với thông báo lỗi tiếng Việt thân thiện.
     - `app/Core/Logger.php`: Ghi nhật ký theo ngày chuẩn PSR-3 tại `storage/logs/hp-egov-YYYY-MM-DD.log`.
     - `app/Core/ErrorHandler.php`: Bắt Exception & Error toàn cục, hiển thị trang lỗi thân thiện (403, 404, 419, 429, 500), ẩn chi tiết ở môi trường production.
  7. Xây dựng Middleware Pipeline:
     - `app/Core/MiddlewareInterface.php` & `app/Core/Pipeline.php` (kiến trúc củ hành Onion).
     - 6 Middlewares: `SecurityHeadersMiddleware`, `CsrfMiddleware`, `RateLimitMiddleware` (kiểm tra bảng `gioi_han_truy_cap` $\le 60$ req/phút và `ip_chan`), `AuthMiddleware`, `GuestMiddleware`, `PermissionMiddleware`.
  8. Hệ thống Enums & Helpers:
     - 7 Enums chuẩn hóa: `TrangThaiHoSo` (9 trạng thái, màu sắc, quy về 6 nhóm chuẩn TT01), `VaiTro` (7 vai trò cán bộ/công dân), `MauSla` (Xanh/Vàng/Đỏ), `KenhNop`, `LoaiBuoc`, `LoaiTaiLieu`, `KenhThongBao`.
     - `app/Helpers/functions.php`: Cung cấp `e()`, `url()`, `asset()`, `route()`, `csrf_field()`, `csrf_token()`, `method_field()`, `old()`, `errors()`, `flash()`, `date_vn()`, `auth()`, `env()`, `config()`.
  9. Kiểm thử tự động & Trang chủ kiểm tra luồng:
     - `tests/run.php`: Test Runner CLI độc lập hiển thị màu ANSI, hỗ trợ assertions.
     - Bộ 5 Unit Tests: `RouterTest.php`, `ValidatorTest.php`, `CsrfTest.php`, `EnumsTest.php`, `ModelTest.php`. Chạy thành công 19/19 tests (100% passed).
     - Kiểm tra cú pháp PHP: `php -l` đạt 50/50 file không có bất kỳ lỗi cú pháp nào.
     - Kiểm tra thực tế trên Web Server Apache XAMPP: URL `http://localhost/HP-eGov/` trả về HTTP 200 trang chủ với giao diện chuẩn; URL ảo lỗi trả về HTTP 404 với giao diện thông báo lỗi trang nhã; API `/api/v1/health` trả về JSON hợp lệ kèm đầy đủ các Security Headers và RateLimit Headers.
- **Tệp tin đã tạo:**
  - `.htaccess`, `public/.htaccess`, `public/index.php`, `app/bootstrap.php`
  - `.env.example`, `.env`, `app/Config/*.php` (5 file)
  - `app/Core/*.php` (17 file)
  - `app/Middleware/*.php` (6 file)
  - `app/Enums/*.php` (7 file)
  - `app/Helpers/functions.php`
  - `app/Controllers/Portal/HomeController.php`, `app/Views/portal/home.php`
  - `routes/web.php`, `routes/api.php`
  - `tests/run.php`, `tests/Unit/*.php` (5 test suites)
- **Kế hoạch cho phiên tiếp theo:** Chuyển sang Phiên 4 thực hiện Design System & Layout giao diện.

### PHIÊN 4: DESIGN SYSTEM & LAYOUT GIAO DIỆN
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Xây dựng bộ Design System CSS hoàn chỉnh trong `public/assets/css/`:
     - `tokens.css`: Thiết lập toàn bộ biến CSS bản sắc Hải Phòng (Navy `#0B2E59`, Đỏ cờ `#C8102E`, Vàng kim `#D4A017`, Nền `#F4F6FA`, SLA Xanh `#16A34A`, Vàng `#F59E0B`, Đỏ `#DC2626`, 9 màu trạng thái Thông tư 01). Tích hợp Chế độ tối (Dark Mode) cho phân hệ cán bộ qua thuộc tính `[data-theme="dark"]`.
     - `base.css`: CSS Reset hiện đại, tích hợp Font Be Vietnam Pro, thang kích thước chữ hành chính chuẩn và hệ thống utility classes linh hoạt.
     - `components.css`: Phát triển đầy đủ các thành phần UI tái sử dụng: Buttons (Primary, Accent, Gold, Outline, Icon), Forms (Floating Labels, Validation feedback), Data Tables (sắp xếp cột, tìm kiếm tức thì), KPI Cards (chỉ số, tỷ lệ tăng trưởng, icon box), Status Badges (9 trạng thái Thông tư 01/2018), SLA Badges (Xanh, Vàng, Đỏ có micro-animation nhấp nháy báo động), Stepper 5 bước quy trình (§2.1), Workflow Timeline (chuỗi luân chuyển), Modal Dialogs, Toast Notifications (4 trạng thái), Notification Dropdown, Tabs, Breadcrumbs, Empty States, Skeleton Loading Shimmer và Drag-and-drop File Upload có thanh tiến trình.
     - `layout.css`: Định dạng khung bố cục Cổng DVC và Phân hệ Cán bộ, Sidebar thu gọn (Collapsible), Topbar đơn vị làm việc và menu responsive thích ứng hoàn hảo cho cả Desktop 1366px và Mobile 390px.
     - `print.css`: Thể thức văn bản hành chính theo Nghị định 30/2020/NĐ-CP (khổ A4, Quốc hiệu, Tiêu ngữ, số ký hiệu, nơi nhận, bảng ký tên và dấu số, tự động ẩn giao diện web khi in `@media print`).
  2. Tải và lưu trữ cục bộ 100% tài nguyên Font & Thư viện JS Vendor:
     - Tải 12 tệp WOFF2 Font Be Vietnam Pro phục vụ On-Premise nội bộ.
     - Tải `Chart.js 4.4.1` UMD bundle vào `public/assets/vendor/chartjs/chart.umd.js`.
     - Tải `qrcode-generator 1.4.4` vào `public/assets/vendor/qrcode/qrcode.min.js`.
     - Tải `html5-qrcode 2.3.8` vào `public/assets/vendor/html5-qrcode/html5-qrcode.min.js`.
     - Xây dựng tài liệu danh mục thư viện, phiên bản và bản quyền tại `docs/THU_VIEN.md` (cam kết 100% không dùng CDN internet, an toàn trên mạng TSLCD cấp II).
  3. Xây dựng 4 Master Layouts và hệ thống Partials:
     - `app/Views/layouts/portal.php`: Layout Cổng DVC công dân, Header có Quốc huy & Biểu trưng Hải Phòng cách điệu (tự vẽ bằng pure SVG, không bản quyền), thanh menu điều hướng 6 mục, Footer cơ quan chủ quản.
     - `app/Views/layouts/admin.php`: Layout Bàn làm việc cán bộ, Sidebar thu gọn, Topbar hiển thị node đơn vị hiện tại, chuông thông báo, nút Dark Mode, avatar cán bộ.
     - `app/Views/layouts/auth.php`: Layout đăng nhập chuyên biệt cho Cán bộ và Công dân (VNeID).
     - `app/Views/layouts/print.php`: Layout xem trước và in ấn A4 trực tiếp qua trình duyệt.
     - Hệ thống 8 Partials: `header.php`, `footer.php`, `sidebar.php`, `topbar.php`, `breadcrumb.php`, `flash.php`, `sla_badge.php`, `status_badge.php`, `stepper.php`, `timeline.php`.
  4. Xây dựng `public/assets/js/app.js` module hóa ES6:
     - Tuân thủ tuyệt đối CSP Cấp độ 3 (không dùng bất kỳ inline script nào).
     - Cung cấp Fetch wrapper tự động gắn `X-CSRF-TOKEN`, Toast Manager, Modal/Confirm Dialog, Dark Mode switch lưu localStorage, Collapsible Sidebar, Client-side table sorter/search và Dropzone file upload.
  5. Xây dựng trang Thư viện giao diện UI Kit:
     - Tuyến đường `/admin/ui-kit` (Controller `UiKitController`, View `app/Views/admin/ui_kit.php`).
     - Trình diễn thực tế đầy đủ 10 nhóm thành phần giao diện kèm Biểu đồ Doughnut Chart.js và Mã QR động.
  6. Kiểm tra trực quan bằng Browser Subagent:
     - Đã kiểm tra tự động trên trình duyệt thật ở cả 2 độ phân giải chuẩn: Desktop 1366x768 và Mobile 375x812/390x844.
     - Không có lỗi JavaScript Console nào xảy ra.
     - Chụp ảnh màn hình trực quan và lưu trữ vào `docs/screenshots/`: `ui_kit_desktop_top.png`, `ui_kit_desktop_middle.png`, `ui_kit_modal.png`, `ui_kit_mobile_top.png`, `ui_kit_mobile_middle.png`.
- **Tệp tin đã tạo/cập nhật:**
  - `public/assets/css/tokens.css`, `base.css`, `components.css`, `layout.css`, `print.css`
  - `public/assets/fonts/be-vietnam-pro.css`, 12 tệp `font_*.woff2`
  - `public/assets/vendor/chartjs/chart.umd.js`, `qrcode/qrcode.min.js`, `html5-qrcode/html5-qrcode.min.js`
  - `docs/THU_VIEN.md`, `docs/screenshots/*` (5 tệp ảnh)
  - `public/assets/js/app.js`, `public/assets/js/ui-kit.js`
  - `app/Views/layouts/portal.php`, `admin.php`, `auth.php`, `print.php`
  - `app/Views/partials/*` (10 file)
  - `app/Controllers/Admin/UiKitController.php`, `app/Views/admin/ui_kit.php`
  - `app/Views/portal/home.php`, `routes/web.php`
- **Kế hoạch cho Phiên 5:**
  - Chạy **Phiên 5/16 – Xác thực & Phân quyền RBAC đa cấp**: Xây dựng Guard cán bộ & công dân, SSO VNeID mock, đăng nhập mật khẩu Bcrypt + Captcha (GD), chống Brute-force (khóa 15 phút sau 5 lần sai), phân quyền 7 vai trò bám sát ma trận quyền §4.1, kiểm soát phạm vi dữ liệu Data Scope theo cây đơn vị `don_vi.path`, Audit log chuỗi băm WORM SHA-256 (`nhat_ky_kiem_toan`).



