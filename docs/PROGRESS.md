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
| **5** | **Xác thực & Phân quyền RBAC đa cấp** | Guard cán bộ/công dân, SSO VNeID mock, 7 vai trò, ma trận quyền, Audit hash chain | **Hoàn thành** | 2026-10-07 | PH11, `AuthService.php`, `AuditService.php`, `VNeID Sandbox` |
| **6** | **Danh mục dùng chung (MDM) & Quy trình** | MDM ĐVHC 3 cấp, cây tổ chức QCVN 102, TTHC quốc gia mock diff, E-Form designer JSON, BPMN-lite quy trình | **Hoàn thành** | 2026-10-07 | PH09, PH10, `WorkflowEngine.php`, `MdmService.php`, `tests/Unit/WorkflowValidationTest.php` |
| **7** | **Cổng Dịch vụ công (Công dân)** | Wizard 4 bước, Auto-fill VNeID, Kho số hóa công dân, QCVN 102 chống trùng, QR webcam, SIPAS | **Hoàn thành** | 2026-10-07 | PH01, `NopHoSoController.php`, `MaHoSoGenerator.php`, `FileUploadSecurityService.php`, `PortalDvcTest.php` |
| **8** | **Bộ phận Một cửa (Cán bộ - PH02)** | Bàn làm việc 4 thẻ KPI, quét QR CCCD chip C06, số hóa 4 bước NĐ 107/2021, ký số OpenSSL, SlaEngine trừ ngày nghỉ, in Giấy hẹn Mẫu 01, Mẫu 02 bổ sung, Mẫu 03 từ chối, trả kết quả VNPost, Sổ theo dõi Mẫu 06 A4 ngang | **Hoàn thành** | 2026-10-07 | PH02, `MotCuaController.php`, `SlaEngine.php`, `SoHoaService.php`, `ChuKySoService.php`, `tests/Unit/MotCuaTest.php` |
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
  - Chạy **Phiên 5/16 – Xác thực & Phân quyền RBAC đa cấp**: Đã hoàn thành toàn diện các yêu cầu.

### PHIÊN 5: XÁC THỰC & PHÂN QUYỀN RBAC ĐA CẤP
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. Xây dựng dịch vụ nhật ký kiểm toán WORM `app/Services/AuditService.php`:
     - Ghi nhật ký bảo mật với chuỗi băm WORM SHA-256: `hash = SHA256(prev_hash | data)`.
     - Hàm `verifyChain(?int $limit, int $offset)` kiểm tra tính liên tục của chuỗi và tính toàn vẹn của từng bản ghi, phát hiện chính xác ID bản ghi bị giả mạo.
     - Đã kiểm thử xác thực 100% hợp lệ trên 50+ bản ghi hiện có.
  2. Xây dựng dịch vụ Captcha chống Brute-force `app/Services/CaptchaService.php` & `CaptchaController.php`:
     - Sử dụng thư viện PHP GD cục bộ sinh ảnh PNG ngẫu nhiên với đường kẻ và chấm nhiễu chống OCR.
     - Cơ chế một lần (Single-use token) tự động xóa sau khi kiểm tra.
  3. Xây dựng lõi xác thực 2 cổng `app/Services/AuthService.php` & Facade `app/Core/Auth.php`:
     - Guard Cán bộ (`/admin/dang-nhap`): Bcrypt hash, khóa 15 phút sau 5 lần sai liên tiếp, bắt buộc Captcha sau 3 lần sai, bắt đổi mật khẩu lần đầu (`yeu_cau_doi_mk = 1`), ghi `nhat_ky_dang_nhap`, chọn node đơn vị công tác và hỗ trợ chuyển đổi đơn vị kiêm nhiệm (`switchDonVi()`).
     - Guard Công dân (`/dang-nhap`):
       + (a) VNeID Sandbox mô phỏng OIDC Authorization Code Flow (CSRF `state`, `nonce`, Consent Screen, trích xuất CCCD, Họ tên, Ngày sinh, Giới tính, Quê quán, Thường trú từ C06; tự động tạo/cập nhật `cong_dan`).
       - (b) Đăng nhập Doanh nghiệp qua Mã số thuế + mật khẩu.
     - Caching session quyền hạn, vai trò, đơn vị làm việc; hỗ trợ `can()`, `hasRole()`, `currentDonVi()`, `donViList()`, `refresh()`.
  4. Hệ thống Middleware phân quyền:
     - `AuthMiddleware`: Bảo vệ route theo guard (`auth:can_bo` hoặc `auth:cong_dan`), kiểm tra cờ bắt buộc đổi mật khẩu lần đầu.
     - `PermissionMiddleware`: Hỗ trợ alias `can:quyen.ma` (ví dụ `can:admin.nguoi_dung`). Xử lý chặn truy cập trái phép trả về 403 Forbidden và ghi nhật ký kiểm toán.
     - `DataScopeMiddleware` & `DataScopeService`: Ràng buộc phạm vi truy cập hồ sơ (Công dân chỉ xem hồ sơ của mình chống IDOR; Cán bộ một cửa theo đơn vị; Chuyên viên theo phân công; Lãnh đạo theo phòng/cơ quan/toàn TP; **SYSADMIN tuyệt đối không được can thiệp hồ sơ nghiệp vụ**).
  5. Phân hệ Quản trị PH11 (SYSADMIN):
     - `app/Controllers/Admin/UserController.php`: Quản lý danh sách cán bộ, mở khóa tài khoản bị khóa do nhập sai, bật/tắt kích hoạt (không xóa vật lý), reset mật khẩu về `Hpegov@2026`, danh sách công dân/doanh nghiệp.
     - `app/Controllers/Admin/RoleController.php`: Quản lý Ma trận phân quyền RBAC dạng lưới checkbox (7 vai trò $\times$ các nhóm quyền). **Chặn cứng bất biến**: Tự động loại bỏ và cảnh báo nếu có thao tác cấp quyền `hoso.*`, `kyso.*`, `thu_ly.*` cho vai trò `SYSADMIN`.
     - `app/Controllers/Admin/ProfileController.php`: Xem hồ sơ công chức, đổi mật khẩu cá nhân, xem lịch sử 20 lần đăng nhập gần nhất từ `nhat_ky_dang_nhap`.
  6. Giao diện người dùng:
     - `app/Views/admin/auth/login.php`: Đăng nhập cán bộ trang trọng, hiển thị Captcha khi sai $\ge 3$ lần.
     - `app/Views/admin/auth/force_change_password.php`: Bắt đổi mật khẩu lần đầu chuẩn chính sách $\ge 10$ ký tự.
     - `app/Views/portal/auth/login.php`: Cổng đăng nhập công dân 2 tab (VNeID nổi bật và Doanh nghiệp MST).
     - `app/Views/portal/auth/vneid_sandbox.php`: Giả lập VNeID C06 Bộ Công an có QR, chọn công dân mẫu nhanh và màn hình ủy quyền chia sẻ dữ liệu (Consent).
     - `app/Views/admin/users/index.php`, `citizens.php`, `admin/roles/matrix.php`, `admin/profile/index.php`.
     - Cập nhật header, sidebar, topbar hiển thị vai trò động và dropdown chuyển đổi đơn vị kiêm nhiệm.
  7. Kiểm thử toàn diện:
     - `tests/Unit/AuditTest.php`: Kiểm tra băm WORM, ghi log và `verifyChain()`.
     - `tests/Unit/AuthTest.php`: Đăng nhập đúng, kiểm tra đếm sai và khóa 15 phút, đăng nhập VNeID mock.
     - `tests/Unit/DataScopeTest.php`: Chặn cứng SYSADMIN không có quyền nghiệp vụ, chống IDOR công dân.
     - Chạy `tests/run.php`: **28/28 tests passed (100% xanh)**.
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/AuditService.php`, `AuthService.php`, `CaptchaService.php`, `DataScopeService.php`
  - `app/Core/Auth.php`, `app/Core/Database.php`, `app/Core/Session.php`, `app/Core/Pipeline.php`, `app/Core/Controller.php`
  - `app/Helpers/functions.php`
  - `app/Controllers/Admin/AuthController.php`, `UserController.php`, `RoleController.php`, `ProfileController.php`
  - `app/Controllers/Portal/AuthController.php`, `app/Controllers/CaptchaController.php`
  - `app/Middleware/AuthMiddleware.php`, `PermissionMiddleware.php`, `DataScopeMiddleware.php`
  - `app/Views/admin/auth/login.php`, `force_change_password.php`, `portal/auth/login.php`, `vneid_sandbox.php`
  - `app/Views/admin/users/index.php`, `citizens.php`, `admin/roles/matrix.php`, `admin/profile/index.php`
  - `app/Views/partials/header.php`, `sidebar.php`, `topbar.php`, `routes/web.php`
  - `tests/Unit/AuditTest.php`, `AuthTest.php`, `DataScopeTest.php`, `tests/run.php`
### PHIÊN 6: DANH MỤC DÙNG CHUNG (MDM - PH09) & QUẢN TRỊ QUY TRÌNH BPMN-LITE (PH10)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **A. Phân hệ PH09 – Quản trị Danh mục dùng chung (Quyền `admin.danh_muc`):**
     - **Đơn vị hành chính (`dm_dvhc`):** Cây 3 cấp (Tỉnh/Thành phố → Quận/Huyện → Xã/Phường), quản lý hiệu lực (`hieu_luc_tu`, `hieu_luc_den`), nghiệp vụ Sáp nhập ĐVHC chuyển giao địa bàn và lưu vết lịch sử thay đổi `AuditService`.
     - **Cây tổ chức Đơn vị / Phòng ban (`don_vi`):** Phân cấp trực thuộc (UBND TP → Sở ngành → Phòng ban → Bộ phận Một cửa), thuật toán tự động gợi ý Mã định danh điện tử cơ quan nhà nước theo chuẩn QCVN 102:2016/BTTTT (`000.xx.yy.H21`).
     - **Chức danh (`chuc_danh`) & Lĩnh vực (`dm_linh_vuc`):** Quản trị danh mục, ngừng hiệu lực an toàn (không xóa vật lý khi đã có liên kết khóa ngoại), xuất CSV UTF-8 kèm BOM.
     - **Thủ tục hành chính (`thu_tuc_hanh_chinh`):** Đầy đủ thông tin, thời hạn giải quyết, căn cứ pháp lý, mức độ Một phần/Toàn trình, phí/lệ phí, liên kết E-Form và quy trình.
     - **Tích hợp Đồng bộ CSDL TTHC Quốc gia:** Adapter Pattern `CsdlTthcQuocGiaInterface` & `CsdlTthcQuocGiaMock`, giao diện màn hình đối chiếu So sánh Khác biệt (Diff) chia 3 nhóm: Công bố mới, Cập nhật/Sửa đổi (So sánh Cũ vs Mới), và Bãi bỏ/Hết hiệu lực; chức năng xác nhận áp dụng cập nhật vào CSDL Hải Phòng.
     - **Trình thiết kế Biểu mẫu điện tử (E-Form Designer):** Giao diện tương tác trực quan cho phép định nghĩa các trường dữ liệu hành chính (Text, Số, Ngày tháng, Lựa chọn Select, Số CCCD 12 số, Địa chỉ ĐVHC 3 cấp, Bảng lặp Repeater, Tệp File đính kèm), đánh dấu trường tự động điền (Auto-fill) từ VNeID C06 (`ho_ten`, `so_cccd`, `ngay_sinh`, `gioi_tinh`, `que_quan`, `dia_chi_thuong_tru`), tính năng Xem trước biểu mẫu trực tiếp (Live Preview modal) và lưu trữ cấu trúc JSON theo phiên bản (`bieu_mau_eform`).
     - **Ngày nghỉ lễ & Lịch làm việc (`dm_ngay_nghi`):** Lịch năm phục vụ tính toán SLA chính xác (loại trừ thứ 7, CN và ngày nghỉ lễ); chức năng Nhân bản toàn bộ ngày nghỉ lễ sang năm tiếp theo.
     - **Tham số hệ thống (`cau_hinh_he_thong`):** Quản lý giờ làm việc sáng/chiều, kiểm tra hợp lệ kiểu dữ liệu (integer, json, string) và tự động ghi vết lịch sử phiên bản cấu hình (`phien_ban_cau_hinh`).
     - **Trạng thái xử lý:** Danh mục 9 trạng thái chuẩn theo Thông tư 01/2018/TT-VPCP (chế độ chỉ xem Read-only).
     - **Dịch vụ MDM chung (`MdmService`):** Xuất tệp CSV định dạng chuẩn UTF-8 có Byte Order Mark (`\xEF\xBB\xBF`) tương thích 100% với Microsoft Excel không bị lỗi font tiếng Việt; đọc và kiểm tra lỗi từng dòng CSV (Row-by-row Validator).
  2. **B. Phân hệ PH10 – Quản trị Quy trình xử lý (BPMN-lite, Quyền `admin.quy_trinh`):**
     - Quản trị danh sách quy trình theo TTHC, hỗ trợ cơ chế Quản lý phiên bản (Tạo phiên bản mới sao chép toàn bộ các bước và chuyển tiếp, các hồ sơ đang xử lý giữ nguyên phiên bản cũ an toàn).
     - Thiết kế bước (`quy_trinh_buoc`): Loại bước theo chuẩn 8 nhóm (`TIEP_NHAN`, `PHAN_CONG`, `THU_LY`, `LAY_Y_KIEN`, `THAM_TRA`, `PHE_DUYET`, `DONG_DAU`, `TRA_KET_QUA`), vai trò xử lý, đơn vị xử lý (nội bộ hoặc liên Sở / liên cấp), thời hạn giờ, cấu hình cho phép trả lại hồ sơ.
     - Luồng chuyển tiếp (`quy_trinh_chuyen_tiep`): Hành động (`CHUYEN`, `TRA_LAI`, `YEU_CAU_BO_SUNG`, `TU_CHOI`) và điều kiện luân chuyển.
     - **Động cơ Quy trình (`WorkflowEngine`):**
       + Trình thẩm định quy trình (`validateWorkflow`): Tự động phát hiện lỗi thiếu bước bắt đầu `TIEP_NHAN`, thiếu bước kết thúc `TRA_KET_QUA`, phát hiện các bước mồ côi (Unreachable không có luồng đến hoặc Dead-end không có luồng đi).
       + Bộ kiểm tra SLA: Tính tổng thời hạn các bước, so sánh đối chiếu với thời hạn quy định của TTHC, phát ra cảnh báo khi bị lệch thời hạn cam kết.
       + Sinh sơ đồ Mermaid BPMN-lite tự động (`generateMermaidDiagram`): Tự động kết xuất đồ thị trực quan với các hình khối và mũi tên hành động.
     - **Quy trình Mẫu Liên thông Logistics Cảng biển Hải Phòng:** Hiển thị chính xác chuỗi phối hợp liên thông qua đúng 5 cơ quan đơn vị:
       1. Bộ phận Một cửa - UBND Quận Hồng Bàng (`MC_HB`)
       2. Phòng Quản lý Quy hoạch & Kiến trúc - Sở Xây dựng (`QLQH_SXD`)
       3. Phòng Đánh giá Tác động Môi trường - Sở TN&MT (`DTM_STNMT`)
       4. Phòng Thẩm định Dự án Đầu tư - Sở KH&ĐT (`TEDA_SKHDT`)
       5. Lãnh đạo & Văn phòng Ủy ban nhân dân Thành phố Hải Phòng (`UBND_TP`).
  3. **C. Kiểm thử đơn vị & Tích hợp:**
     - Xây dựng `tests/Unit/WorkflowValidationTest.php` với 6 test cases: Thẩm định quy trình chuẩn, Tính tổng thời hạn SLA, Phát hiện thiếu đầu/cuối, Sinh Mermaid, Đồng bộ Adapter Mock CSDL Quốc gia, Xuất CSV UTF-8 BOM.
     - Toàn bộ test suite: **34/34 tests passed 100%**.
- **Tệp tin đã tạo/cập nhật:**
  - `app/Integrations/Contracts/CsdlTthcQuocGiaInterface.php`, `app/Integrations/Mock/CsdlTthcQuocGiaMock.php`
  - `app/Services/MdmService.php`, `app/Services/WorkflowEngine.php`
  - `app/Controllers/Admin/DvhcController.php`, `DonViController.php`, `CategoryController.php`, `TthcController.php`, `EformController.php`, `ConfigController.php`, `WorkflowController.php`
  - `app/Views/admin/dvhc/index.php`, `admin/don_vi/index.php`, `admin/categories/linh_vuc.php`, `chuc_danh.php`
  - `app/Views/admin/tthc/index.php`, `sync_preview.php`, `admin/eform/designer.php`
  - `app/Views/admin/config/holidays.php`, `parameters.php`, `statuses.php`
  - `app/Views/admin/workflow/index.php`, `detail.php`
  - `app/Views/partials/sidebar.php`, `routes/web.php`, `app/Core/Request.php`, `app/Core/Router.php`
  - `tests/Unit/WorkflowValidationTest.php`
- **Kế hoạch cho Phiên 7:**
  - Đã thực hiện và hoàn thành xuất sắc toàn diện các mục tiêu của Phiên 7.

### PHIÊN 7: CỔNG DỊCH VỤ CÔNG TRỰC TUYẾN (CÔNG DÂN - PH01)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Trang chủ Cổng DVC Hải Phòng (`/`):**
     - Banner nhận diện thành phố Cảng biển thông minh, ô tìm kiếm TTHC tức thời.
     - 4 Thẻ KPI công khai: Tổng hồ sơ tiếp nhận năm (182.450), Tỷ lệ giải quyết đúng/trước hạn (98.6%), Mức độ hài lòng người dân SIPAS (96.5%), Tỷ lệ DVC Toàn trình (84.2%).
     - Danh sách 6 DVC phổ biến nhất và Tin tức / Thông báo chỉ đạo hành chính.
  2. **Danh mục & Chi tiết TTHC (`/dvc/thu-tuc`, `/dvc/thu-tuc/{ma}`):**
     - Bộ lọc đa chiều: Lĩnh vực, Cấp thực hiện (Tỉnh, Huyện, Xã), Cơ quan thụ lý, Mức độ DVC (Một phần / Toàn trình).
     - Trang chi tiết TTHC: Trình tự thực hiện, Thành phần hồ sơ yêu cầu, Thời hạn giải quyết, Phí/lệ phí, Căn cứ pháp lý, Tải biểu mẫu và nút điều hướng "Nộp hồ sơ trực tuyến".
  3. **Nộp hồ sơ trực tuyến - Wizard 4 bước chuẩn hóa (`/dvc/nop-ho-so/{tthc_id}`):**
     - Yêu cầu xác thực tài khoản qua **VNeID OIDC Sandbox** hoặc Doanh nghiệp (MST).
     - **Bước 1 (Chọn cơ quan tiếp nhận):** Chọn đơn vị tiếp nhận trong cây tổ chức ĐVHC 3 cấp TP. Hải Phòng.
     - **Bước 2 (E-Form động):** Render linh hoạt từ schema JSON (hỗ trợ cả chuẩn `id`/`name`, `vneid_field`). Tự động Auto-fill thông tin từ VNeID (`ho_ten`, `so_cccd`, `ngay_sinh`, `gioi_tinh`, `dia_chi_thuong_tru`) và **khóa cứng không cho sửa** (Readonly badge) để bảo đảm tính pháp lý với C06 Bộ Công an.
     - **Bước 3 (Đính kèm hồ sơ):** Hỗ trợ 2 phương thức:
       + (a) Chọn giấy tờ đã số hóa từ **Kho dữ liệu điện tử cá nhân** theo Nghị định 107/2021/NĐ-CP (tái sử dụng, không cần nộp lại).
       + (b) Tải tệp mới: Kiểm tra bảo mật chặt chẽ qua `FileUploadSecurityService` (chặn `.php`, `.exe`, webshell, MIME type sniffing bằng `finfo`, giới hạn 10MB, sinh UUIDv4, lưu trữ an toàn ngoài web root, băm mã SHA-256).
     - **Bước 4 (Xem lại, Nhận kết quả & Ký số):** Chọn hình thức nhận kết quả (Điện tử có ký số cơ quan / Trực tiếp tại Một cửa / Chuyển phát bưu chính công ích VNPost). Ký số E-Form (`EformSignerMock` mô phỏng RSA-SHA256 kèm thông tin chứng thư số SmartCA).
     - **Cấp mã hồ sơ:** `MaHoSoGenerator` sinh mã chuẩn QCVN 102:2016/BTTTT dạng `{DonVi}-{yyMMdd}-{Ma4}-{STT6}` (ví dụ: `000.05.00.H21.MC-261007-0042-000010`). Cơ chế chống trùng lặp đồng thời (Concurrency-safe) qua Transaction và khóa hàng `SELECT ... FOR UPDATE` trên bảng `bo_dem_ma_ho_so`.
     - **Thông báo đa kênh:** Phát sự kiện mock SMS Brandname "UBND TP HP" và Zalo ZNS kèm ghi `nhat_ky_tich_hop` và bảng `thong_bao`.
  4. **Hồ sơ của tôi (`/cong-dan/ho-so`, `/cong-dan/ho-so/{ma_ho_so}`):**
     - Danh sách hồ sơ cá nhân, phân nhóm trạng thái (Chờ tiếp nhận, Đang xử lý, Chờ bổ sung, Hoàn thành...).
     - Giao diện chi tiết: Stepper 5 bước theo Thông tư 01/2018/TT-VPCP, Timeline luân chuyển công khai (tự động ẩn các ý kiến/thẩm định nội bộ của cán bộ công chức), tải giấy tờ đính kèm.
     - Bảo mật chống IDOR: Ngăn chặn tuyệt đối công dân A xem trộm hồ sơ của công dân B (`403 Forbidden`).
  5. **Bổ sung hồ sơ (`/cong-dan/ho-so/{ma_ho_so}/bo-sung`):**
     - Khi trạng thái là `CHO_BO_SUNG`: Hiển thị nội dung công chức yêu cầu bổ sung, chỉ cho phép sửa/tải lại các tệp được chỉ định, nộp lại đưa trạng thái về `DANG_XU_LY`.
  6. **Kho dữ liệu điện tử cá nhân / tổ chức (`/cong-dan/kho-du-lieu`):**
     - Danh mục giấy tờ đã số hóa, trạng thái hiệu lực, mã SHA-256 đối soát toàn vẹn.
  7. **Khảo sát hài lòng SIPAS (`/cong-dan/ho-so/{ma_ho_so}/danh-gia-sipas`):**
     - Đánh giá chuẩn 5 tiêu chí theo Quyết định 876/QĐ-BNV (Tiếp cận dịch vụ, Thủ tục hành chính, Tinh thần công chức, Kết quả dịch vụ, Tiếp nhận phản ánh) kèm thang điểm 1-5 sao. Giới hạn 1 lần đánh giá duy nhất cho mỗi hồ sơ hoàn thành.
  8. **Tra cứu công khai (`/tra-cuu`):**
     - Tra cứu theo mã hồ sơ hoặc quét QR trực tiếp qua webcam (`html5-qrcode`).
     - Không yêu cầu đăng nhập, chỉ trả về thông tin tối thiểu đảm bảo an toàn danh tính, có Rate Limiting (10 lượt/phút) và Captcha bảo vệ.
  9. **Kiểm thử tự động & Bằng chứng thực tế:**
     - Kiểm thử đơn vị (`tests/Unit/PortalDvcTest.php`): 5 test cases chuyên sâu (IDOR 403, Chặn upload mã độc, Sinh mã đồng thời 20 request không trùng lặp, Ký số mock SHA-256, SMS/ZNS notification log).
     - Kiểm thử E2E HTTP (`tests/E2E/portal_flow_test.php`): Đạt 10/10 bước luồng nộp hồ sơ thực tế.
     - **Browser Subagent:** Thực thi trọn vẹn kịch bản nộp trực tuyến trên trình duyệt (VNeID SSO -> Wizard 4 bước -> Cấp mã QCVN 102 -> Stepper 5 bước -> Tra cứu công khai). Video ghi lại quá trình: `portal_dvc_submission_1791349260177.webp`.
     - Tổng số test hệ thống: **39/39 tests passed 100%**.
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/MaHoSoGenerator.php`, `FileUploadSecurityService.php`
  - `app/Integrations/Mock/EformSignerMock.php`, `SmsZnsNotificationMock.php`
  - `app/Controllers/Portal/HomeController.php`, `DvcController.php`, `NopHoSoController.php`, `HoSoCaNhanController.php`, `TraCuuController.php`, `NotificationPortalController.php`, `HelpController.php`
  - `app/Views/portal/home.php`, `dvc/index.php`, `dvc/detail.php`, `nop_ho_so/wizard.php`, `nop_ho_so/success.php`, `ho_so/index.php`, `ho_so/detail.php`, `ho_so/bo_sung.php`, `ho_so/kho_du_lieu.php`, `ho_so/sipas.php`, `tra_cuu/index.php`, `thong_bao/index.php`, `tro_giup/index.php`
  - `database/migrations/2026_10_07_000001_create_khao_sat_sipas_table.sql`, `database/schema.sql`
  - `routes/web.php`, `app/Core/Request.php`
  - `tests/Unit/PortalDvcTest.php`, `tests/E2E/portal_flow_test.php`
- **Kế hoạch cho Phiên 8:**
  - Thực hiện **Phiên 8/16 – Phân hệ Một cửa điện tử (Cán bộ - PH02)**: Bàn tiếp nhận Một cửa trực tiếp và trực tuyến, tra cứu/quét QR CCCD, số hóa hồ sơ theo Nghị định 107/2021/NĐ-CP, kiểm tra thành phần hồ sơ, OCR mock bóc tách thông tin, in Giấy tiếp nhận & Hẹn trả kết quả (Mẫu số 01 Thông tư 01/2018/TT-VPCP chuẩn A4), in Phiếu từ chối / Phiếu yêu cầu bổ sung (Mẫu số 02, 03).

### PHIÊN 8: BỘ PHẬN MỘT CỬA (CÁN BỘ - PH02)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Bàn làm việc Một cửa (`/admin/mot-cua`):**
     - Thiết kế giao diện Dashboard theo chuẩn Design System Hải Phòng: 4 thẻ KPI đếm thời gian thực lọc theo đơn vị công tác của cán bộ (`Chờ tiếp nhận trực tuyến`, `Tiếp nhận hôm nay`, `Chờ trả kết quả`, `Sắp quá hạn / Quá hạn`).
     - Danh sách việc cần làm (To-do list): Ưu tiên hồ sơ nộp trực tuyến mới gửi đến và hồ sơ đã có kết quả sẵn sàng trả cho công dân.
  2. **Tiếp nhận trực tiếp & Quét QR CCCD gắn chip (`/admin/mot-cua/tiep-nhan`):**
     - Tích hợp 2 phương thức nhập dữ liệu công dân: (a) Quét trực tiếp bằng Camera qua thư viện `html5-qrcode`; (b) Dán chuỗi QR chuẩn C06 Bộ Công an (`CCCD|CMND|Họ tên|ddMMyyyy|Giới tính|Địa chỉ|ddMMyyyy`) qua máy quét barcode hoặc bàn phím.
     - Bộ phân tích Regex chuẩn bóc tách tự động 7 trường thông tin (Số CCCD, CMND cũ, Họ tên, Ngày sinh, Giới tính, Thường trú, Ngày cấp) $\rightarrow$ Tự điền Form tiếp nhận trong **< 3 giây** (giảm thời gian tạo hồ sơ từ 12 phút xuống < 4 phút đúng mục tiêu báo cáo).
     - Tích hợp Trục liên thông LGSP & C06 (`LgspGatewayMock::verifyCitizen`): Đối soát tức thời với CSDL Quốc gia về Dân cư, trả về mã đối soát `LGSP-C06-...` và lưu `nhat_ky_tich_hop`.
     - Tự động nạp động danh mục thành phần hồ sơ giấy tờ tương ứng với TTHC được chọn.
  3. **Quy trình Số hóa đầu vào 4 bước theo Nghị định 107/2021/NĐ-CP (`SoHoaService`):**
     - **Bước 1 (Kiểm tra pháp lý):** Cán bộ Một cửa đối chiếu bản chính / bản sao có chứng thực, tích chọn xác nhận bắt buộc.
     - **Bước 2 (Tải file quét):** Tiếp nhận file scan PDF $\ge 300\text{ DPI}$, kiểm tra bảo mật mã độc qua `FileUploadSecurityService`, tính mã băm SHA-256.
     - **Bước 3 (OCR mô phỏng):** Tự động bóc tách thông tin định danh (CCCD, Họ tên, Ngày sinh, Thửa đất, Biển số...) với độ tin cậy $\ge 95\%$.
     - **Bước 4 (Ký số số hóa & Đồng bộ):** Cán bộ Một cửa ký số điện tử OpenSSL bằng chứng thư cá nhân (`ChuKySoService`), lưu vào bảng `chu_ky_so` (`loai_chu_ky = 'SO_HOA'`) và tự động đồng bộ vào `kho_du_lieu_cong_dan` với nguồn cấp `'SO_HOA_MOT_CUA'`, thời hạn hiệu lực 5 năm.
  4. **Cấp mã QCVN 102 & Động cơ SLA loại trừ ngày nghỉ (`SlaEngine`):**
     - Sinh mã định danh chống trùng qua `MaHoSoGenerator::generate()` với cơ chế khóa hàng Transaction.
     - Tính ngày hẹn trả kết quả qua `SlaEngine::calculateNgayHenTra()`: Loại trừ Thứ Bảy, Chủ Nhật và toàn bộ ngày nghỉ lễ theo `dm_ngay_nghi`, chốt giờ hẹn lúc 17:00:00.
     - Tự động chuyển bước quy trình qua `WorkflowEngine::transition()`, ghi nhật ký `ho_so_luan_chuyen` và `AuditService` chuỗi băm WORM.
  5. **In ấn Giấy tiếp nhận và hẹn trả kết quả (Mẫu số 01 TT 01/2018/TT-VPCP):**
     - Giao diện in ấn A4 chuẩn quy thức văn bản hành chính Việt Nam (`/admin/mot-cua/in-giay-hen/{id}`).
     - Mã QR Code SVG nhúng trực tiếp phục vụ công dân tra cứu nhanh tiến độ giải quyết trên điện thoại di động.
  6. **Xử lý hồ sơ trực tuyến chờ tiếp nhận (`/admin/mot-cua/truc-tuyen`):**
     - Màn hình thẩm tra hồ sơ trực tuyến: Xem thông tin người nộp, E-Form nộp, chứng thư số công dân, giấy tờ đính kèm.
     - 3 hành động nghiệp vụ theo quy định:
       + (a) **Tiếp nhận:** Cấp mã QCVN 102, tính ngày hẹn trả SLA, chuyển `MOI_TIEP_NHAN` sang phòng ban thụ lý.
       + (b) **Yêu cầu bổ sung (Mẫu số 02 TT 01/2018):** Nêu rõ nội dung thiếu sót, tuân thủ nguyên tắc **CHỈ ĐƯỢC YÊU CẦU BỔ SUNG 01 LẦN DUY NHẤT** theo khoản 2 Điều 17 NĐ 61/2018/NĐ-CP $\rightarrow$ Chuyển `CHO_BO_SUNG`, in Phiếu yêu cầu bổ sung Mẫu 02 A4, gửi SMS/Zalo.
       + (c) **Từ chối tiếp nhận (Mẫu số 03 TT 01/2018):** Bắt buộc nêu căn cứ pháp lý lý do từ chối $\rightarrow$ Chuyển `TU_CHOI`, in Phiếu từ chối Mẫu 03 A4, thông báo công dân.
  7. **Quản lý Trả kết quả TTHC & Bưu chính VNPost (`/admin/mot-cua/tra-ket-qua`):**
     - Danh sách hồ sơ trạng thái `HOAN_THANH` chờ bàn giao.
     - Hai hình thức bàn giao:
       + **Trực tiếp tại Một cửa:** Xác nhận thông tin người nhận (Họ tên, CCCD, SĐT, giấy ủy quyền), lưu người nhận và thời điểm trả.
       + **Chuyển phát bưu chính:** Tích hợp `BuuChinhGatewayMock::createShipment`, sinh mã vận đơn bưu chính công ích chuẩn `HP{yyMMdd}{6_so}VN`, ghi nhật ký tích hợp.
     - **Cảnh báo trễ hạn:** Nếu hồ sơ hoàn thành sau hạn cam kết, hệ thống cảnh báo đỏ và bắt buộc xuất kèm **Mẫu số 04 - Giấy xin lỗi và hẹn lại ngày trả kết quả**.
     - **Đề xuất hủy hồ sơ:** Nhập căn cứ pháp lý và biên bản giải trình, chuyển trạng thái `CHO_DUYET_HUY` chờ Lãnh đạo cơ quan phê duyệt (Phiên 10).
  8. **Sổ theo dõi hồ sơ TTHC (Mẫu số 06 TT 01/2018/TT-VPCP):**
     - Đường dẫn: `/admin/mot-cua/so-theo-doi`.
     - Bộ lọc đa tiêu chí: Từ ngày, Đến ngày, Thủ tục hành chính, Trạng thái hồ sơ.
     - Bảng 11 cột chuẩn hóa theo Thông tư 01/2018/TT-VPCP.
     - Hỗ trợ xuất file CSV định dạng UTF-8 có BOM (tương thích mở trực tiếp trên Microsoft Excel không bị lỗi font tiếng Việt).
     - Định dạng in ấn chuẩn A4 ngang Landscape (`@media print`).
  9. **Kiểm thử tự động & Tích hợp:**
     - **Unit Test (`tests/Unit/MotCuaTest.php`):** 7/7 test cases đạt: Sinh mã QCVN 102 chống trùng, C06 qua LGSP, tính hạn SLA loại trừ cuối tuần/nghỉ lễ, đánh giá màu SLA (XANH/VANG/DO), quy trình số hóa NĐ 107/2021 & ký số OpenSSL, VNPost sinh mã vận đơn, State machine chuyển tiếp quy trình.
     - **Tích hợp HTTP (`tests/Integration/test_motcua_http.php`):** Đạt 8/8 kịch bản truy cập HTTP thực tế trên máy chủ Apache local.
     - **Toàn bộ Test Suite:** **46/46 tests passed 100%**.
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/SlaEngine.php`, `app/Services/SoHoaService.php`, `app/Services/ChuKySoService.php`
  - `app/Integrations/Mock/LgspGatewayMock.php`, `app/Integrations/Mock/BuuChinhGatewayMock.php`
  - `app/Controllers/Admin/MotCuaController.php`
  - `app/Views/admin/mot_cua/dashboard.php`, `tiep_nhan.php`, `in_giay_hen.php`, `ho_so_truc_tuyen.php`, `chi_tiet_truc_tuyen.php`, `tra_ket_qua.php`, `in_phieu_bo_sung.php`, `in_phieu_tu_choi.php`, `so_theo_doi.php`
  - `app/Views/partials/sidebar.php`, `routes/web.php`
  - `tests/Unit/MotCuaTest.php`, `tests/Integration/test_motcua_http.php`
- **Kế hoạch cho Phiên 9:**
  - Thực hiện **Phiên 9/16 – Phân hệ Thụ lý & Liên thông (PH03)**: Bàn làm việc Chuyên viên thụ lý, phân công thụ lý (thủ công / tự động vòng tròn cân bằng tải), thẩm định hồ sơ, phối hợp liên thông ngang (Sở KH&ĐT ↔ Sở XD ↔ Sở TN&MT) và liên thông dọc (UBND Quận/Huyện ↔ UBND Xã/Phường), biên bản thẩm định, dự thảo kết quả TTHC.


