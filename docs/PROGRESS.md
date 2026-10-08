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
| **9** | **Workflow Engine · Thụ lý & Liên thông** | Phân công, thẩm định, đối soát LGSP/C06/Đất đai/Đăng kiểm, liên thông ngang/dọc đa cơ quan, Hộp việc 6 tabs, SLA ĐỎ khóa chuyển bước, đối soát SHA-256 | **Hoàn thành** | 2026-10-07 | PH03, `WorkflowEngine.php`, `ThuLyController.php`, `tests/Integration/LienThongLogisticsTest.php` |
| **10** | **Phê duyệt, Ký số & Văn thư số** | OpenSSL SHA256withRSA, SmartCA mock, ký duyệt LD_PHONG, ký phát hành & batch signing LD_CO_QUAN, hủy logic DA_HUY, Văn thư số cấp số không trùng, đóng dấu số tổ chức, đẩy kho công dân | **Hoàn thành** | 2026-10-07 | PH04, PH05, `ChuKySoService.php`, `VanThuService.php`, `PheDuyetController.php`, `VanThuController.php`, `PheDuyetKySoVanThuTest.php` |
| **11** | **SLA Engine & Thông báo đa kênh** | SLA Xanh/Vàng/Đỏ (trừ ngày nghỉ), khóa quá hạn, Mẫu 04 xin lỗi ký số OpenSSL, Outbox queue SMS/Zalo/Email, Quản trị mẫu tin & Hộp thư mô phỏng | **Hoàn thành** | 2026-10-07 | PH06, PH07, `SlaEngine.php`, `cron/sla_scan.php`, `cron/notify_dispatch.php`, `ThongBaoService.php`, `SlaController.php`, `ThongBaoController.php`, `SlaEngineTest.php`, `SlaNotificationIntegrationTest.php` |
| **12** | **Dashboard chỉ đạo điều hành & Báo cáo** | IOC Hải Phòng, KPI 4 chỉ tiêu, Heatmap, Bản đồ 15 quận/huyện SVG, xếp hạng thi đua, bottleneck, báo cáo TT 01, NĐ 107, SIPAS, xuất CSV & in A4 | **Hoàn thành** | 2026-10-07 | PH08, `ReportingService.php`, `DashboardController.php`, `BaoCaoController.php`, `tests/Unit/ReportingServiceTest.php` |
| **13** | **Quản trị & Vận hành hệ thống** | Sao lưu RPO ≤ 2h, RTO ≤ 4h, Health endpoint, sự cố BCP/DRP, đào tạo 4 nhóm | **Hoàn thành** | 2026-10-08 | PH12, PH13, PH15, Backup/Restore XAMPP, Health, BCP/DRP, Đào tạo, `docs/05_QUY_CHE_VAN_HANH.md` |
| **14** | **Chuyển đổi dữ liệu ETL, Tích hợp & API** | ETL 4 bước (checksum SHA-256), REST API /api/v1, Webhook LGSP HMAC, Quản trị 9 kết nối, tài liệu API | **Hoàn thành** | 2026-10-08 | PH14, `EtlService.php`, `docs/06_API.md`, `tests/Integration/EtlAndRestApiTest.php` |
| **15** | **Kiểm thử toàn diện & QA/QC** | 5 loại hình kiểm thử, 124 tests 100% pass, OWASP Top 10 & Cấp độ 3, k6/JMeter tải, UAT Hồng Bàng & GTVT | **Hoàn thành** | 2026-10-08 | `docs/07_KE_HOACH_KIEM_THU.md`, `docs/08_BAO_CAO_KIEM_THU_ATTT.md`, `docs/09_BAO_CAO_KET_QUA_KIEM_THU.md`, `docs/10_BIEN_BAN_NGHIEM_THU_UAT.md` |
| **16** | **Clean code, Hardening & Bàn giao** | Rà soát PSR-12, php -l 250 files 100% sạch, .htaccess hardening, user MySQL tối thiểu, README, HDSD 7 vai trò, Route matrix, Git tag v1.0.0 | **Hoàn thành** | 2026-10-08 | `README.md`, `docs/10_HDSD_*`, `docs/11_TRIEN_KHAI_PRODUCTION.md`, `database/hardening_user.sql`, `docs/ROUTE_MATRIX.md`, `git tag v1.0.0` |

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
  - Hoàn thành theo đúng kế hoạch.

### PHIÊN 9: WORKFLOW ENGINE · THỤ LÝ & LIÊN THÔNG (PH03)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Động cơ Quy trình trung tâm (`WorkflowEngine`):**
     - Đóng vai trò là Service duy nhất điều phối toàn bộ chuỗi trạng thái luân chuyển hồ sơ trong hệ thống (State Machine theo BPMN-lite).
     - Khóa bi quan (`SELECT ... FOR UPDATE`) trong Database Transaction: Bảo đảm xử lý tuần tự, chống triệt để tình trạng Race Condition (2 người cùng thao tác một hồ sơ tại cùng một thời điểm).
     - Kiểm soát nghiêm ngặt quy tắc SLA ĐỎ: Chặn đứng thao tác chuyển bước thông thường nếu hồ sơ đã quá hạn (`sla_mau === 'DO'`), bắt buộc Lãnh đạo cơ quan phải ban hành và ký số **Phiếu xin lỗi và hẹn lại ngày trả kết quả (Mẫu số 04 NĐ 61/2018/NĐ-CP)** trong bảng `van_ban_xin_loi` trước khi được tiếp tục chuyển tiếp.
     - Cơ chế đối soát toàn vẹn tệp đính kèm (`verifyFileIntegrity`): Quét và đối chiếu mã băm SHA-256 của toàn bộ tệp tin hồ sơ lưu trên ổ đĩa với CSDL trước khi luân chuyển, phát hiện ngay lập tức mọi hành vi sửa đổi trái phép.
     - Cơ chế luân chuyển liên thông đa cơ quan: Tự động cập nhật `don_vi_hien_tai_id`, reset chuyên viên thụ lý để Lãnh đạo đơn vị tiếp nhận phân công; đơn vị gửi chuyển sang chế độ theo dõi chỉ đọc (`isReadOnly`).
     - Bộ hành động quy trình toàn diện: `chuyenTiep`, `traLai`, `yeuCauBoSung` (tạm dừng tính SLA), `tiepTucSauBoSung` (khôi phục SLA), `phanCong` (phân công chuyên viên), `layYKien` (yêu cầu phối hợp), `traLoiYKien` (phản hồi ý kiến phối hợp), `thuHoi` (thu hồi trước khi trạm kế tiếp thụ lý).
  2. **Hộp việc (Inbox) cán bộ dùng chung (`/admin/thu-ly`):**
     - Thiết kế giao diện Dashboard theo chuẩn Design System với 6 tabs phân loại: *Chờ xử lý, Đang thụ lý, Đã chuyển (theo dõi chỉ đọc), Phối hợp liên ngành, Sắp quá hạn (Vàng), Quá hạn (Đỏ)*.
     - Huy hiệu đếm số lượng thời gian thực cho từng tab, bộ lọc theo TTHC, tìm kiếm theo mã/tên người nộp, sắp xếp tối ưu theo thời hạn SLA.
  3. **Nghiệp vụ Lãnh đạo phòng (LD_PHONG):**
     - Phân công / chuyển phân công chuyên viên thụ lý có hiển thị số lượng hồ sơ đang xử lý để Lãnh đạo cân bằng tải công việc.
     - Điều chỉnh ý kiến chỉ đạo thụ lý trực tiếp trên giao diện.
  4. **Màn hình Thụ lý chi tiết của Chuyên viên (`/admin/thu-ly/{id}`):**
     - Hiển thị thông tin hồ sơ, E-form người nộp và tài liệu đính kèm (xem PDF trực tiếp trong modal).
     - **Đối soát dữ liệu số qua LGSP Hải Phòng (`LgspGatewayMock`):**
       + C06 CSDL Quốc gia về Dân cư: Xác thực số CCCD, họ tên, thường trú.
       + CSDL Đất đai (VILIS): Đối soát số thửa, số tờ bản đồ, chủ sử dụng, diện tích m2, mục đích sử dụng, quy hoạch chi tiết 1/500.
       + CSDL Đăng kiểm GTVT: Đối soát biển số xe, chủ xe, hạn kiểm định an toàn kỹ thuật, loại xe, tải trọng.
     - Soạn thảo và lưu trữ dự thảo tờ trình / biên bản thẩm định / kết quả TTHC từ mẫu có biến.
     - Gửi yêu cầu lấy ý kiến phối hợp liên phòng / liên sở (chọn đơn vị, nội dung, hạn trả lời) và quản lý phản hồi ý kiến.
     - Hỗ trợ thao tác yêu cầu bổ sung giấy tờ và chuyển trình duyệt Lãnh đạo.
  5. **Hộp việc "Phối hợp liên ngành":**
     - Tiếp nhận các yêu cầu lấy ý kiến từ cơ quan khác gửi đến.
     - Cho phép đơn vị phối hợp phản hồi ý kiến chuyên môn và đính kèm văn bản trả lời.
  6. **Dịch vụ Thông báo đa kênh (`ThongBaoService`):**
     - Phát thông báo in-app tức thời cho cán bộ liên quan và đẩy tin nhắn SMS Brandname / Zalo ZNS vào hàng đợi.
  7. **Kiểm thử tích hợp tự động (`tests/Integration/LienThongLogisticsTest.php`):**
     - Kịch bản mô phỏng toàn trình hồ sơ dự án Logistics cảng biển Lạch Huyện đi qua 4 cơ quan:
       + Trạm 1: UBND Quận Hồng Bàng tiếp nhận hồ sơ ban đầu.
       + Trạm 2: Chuyển liên thông sang Sở Xây dựng thẩm định quy hoạch.
       + Trạm 3: Sở Xây dựng gửi yêu cầu lấy ý kiến phối hợp sang Sở TN&MT thẩm định đánh giá tác động môi trường (ĐTM); Sở TN&MT phản hồi ý kiến chuyên môn.
       + Trạm 4: Sở KH&ĐT tổng hợp hồ sơ dự án đầu tư và kính trình Lãnh đạo UBND Thành phố phê duyệt.
     - Xác minh tự động: Không xảy ra Deadlock trong Transaction, trạng thái và đơn vị chuyển đổi chính xác, mã băm SHA-256 của tệp đính kèm toàn vẹn 100% qua 4 cơ quan, nhật ký luân chuyển WORM ghi vết đầy đủ.
     - Mở rộng kiểm thử: SLA ĐỎ bị khóa chuyển bước khi thiếu Mẫu 04; phát hiện tức thì tệp tin bị can thiệp trái phép trên đĩa; nghiệp vụ thu hồi hồ sơ hoàn trả về đúng đơn vị ban đầu.
  8. **Kết quả kiểm thử toàn hệ thống:**
     - Kiểm thử Unit & Integration: **50/50 tests passed 100%** (`tests/run.php`).
     - Kiểm thử HTTP Web (`tests/Integration/test_thu_ly_web_http.php`): **6/6 bước HTTP đạt 100%** (CSRF, Đăng nhập, Hộp việc 6 tabs HTTP 200, Chi tiết thụ lý HTTP 200, API LGSP Đất đai & Đăng kiểm).
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/WorkflowEngine.php`, `app/Services/ThongBaoService.php`
  - `app/Integrations/Mock/LgspGatewayMock.php`
  - `app/Controllers/Admin/ThuLyController.php`
  - `app/Views/admin/thu_ly/inbox.php`, `detail.php`
  - `app/Views/partials/sidebar.php`, `routes/web.php`, `app/Core/Request.php`
  - `tests/run.php`, `tests/Integration/LienThongLogisticsTest.php`, `tests/Integration/test_thu_ly_web_http.php`
- **Kế hoạch cho Phiên 10:**
  - Hoàn thành theo đúng kế hoạch.

### PHIÊN 10: PHÊ DUYỆT, KÝ SỐ & VĂN THƯ SỐ (PH04, PH05)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Hạ tầng Chữ ký số & Nhà cung cấp SignatureProvider (OpenSSL Thật):**
     - Xây dựng hợp đồng `SignatureProviderInterface` theo Adapter Pattern cho phép hoán chuyển liền mạch giữa OpenSSL local và SmartCA/USB-Token/Ban Cơ yếu Chính phủ.
     - Triển khai `OpenSslSignatureProvider`: Ký số chuẩn SHA256withRSA, sinh chữ ký dạng nhị phân / base64, xác thực chữ ký số bằng Public Key chứng thư số X.509. Tự động định vị file cấu hình OpenSSL (`openssl.cnf`) trên môi trường Windows / XAMPP.
     - Triển khai `SmartCaProviderMock`: Mô phỏng quy trình xác thực mã PIN / OTP chữ ký số từ xa (SmartCA), ghi nhật ký tích hợp vào `nhat_ky_tich_hop`.
     - Lệnh CLI `C:\xampp\php\php.exe app/cli.php keys:generate`: Tự động sinh cặp khóa RSA 2048-bit và chứng thư số X.509 tự ký demo cho toàn bộ người có quyền ký (`LD_PHONG`, `LD_CO_QUAN`, `VAN_THU`, `CB_MOT_CUA`) và chứng thư tổ chức (con dấu số) cho tất cả các cơ quan, đơn vị; lưu trữ an toàn ngoài thư mục public tại `storage/keys/` (`user_{id}.key`, `user_{id}.crt`, `org_{id}.key`, `org_{id}.crt`); tự động đồng bộ siêu dữ liệu vào bảng CSDL `chung_thu_so` (serial, hiệu lực 1 năm, nhà cung cấp).
     - Hoàn thiện `ChuKySoService`:
       + Ký tệp: Tính mã băm SHA-256 tệp gốc → `openssl_sign` với Private Key → lưu chữ ký số (`.sig`) song hành cùng tệp gốc → ghi nhận lịch sử vào bảng `chu_ky_so` → chèn khối thông tin con dấu số điện tử "Đã ký số bởi ... lúc ... - Serial ...".
       + Kiểm soát thẩm quyền ký số (Tuân thủ nghiêm ngặt **Rule #6 & #12**): Tuyệt đối ngăn chặn tài khoản quản trị hệ thống `SYSADMIN` thực hiện ký số nghiệp vụ (ném ngoại lệ `403 Forbidden`); kiểm tra thẩm quyền vai trò tương ứng theo đúng vị trí công tác.
       + Xác thực chữ ký số: Kiểm tra tính toàn vẹn của tệp tin, hiệu lực thời gian của chứng thư số và đối soát thẩm quyền người ký. Phát hiện tức thì mọi hành vi thay đổi dù chỉ 1 byte nội dung.
       + Ký số hàng loạt (Batch Signing): Xử lý ký đồng loạt nhiều hồ sơ với 1 lần xác thực mã PIN SmartCA duy nhất, thời gian xử lý trung bình < 2 giây/hồ sơ, báo cáo chi tiết kết quả từng hồ sơ.
  2. **Nghiệp vụ Phê duyệt Bước 1 của Lãnh đạo Phòng (`LD_PHONG`):**
     - Hộp việc duyệt bước 1 (`/admin/phe-duyet/phong`): Danh sách hồ sơ chuyên viên đã thẩm định trình duyệt.
     - Màn hình xem chi tiết dự thảo (`/admin/phe-duyet/phong/{id}`): Xem nội dung E-Form, thành phần hồ sơ, dự thảo kết quả giải quyết.
     - Thao tác: Ký số duyệt dự thảo (kích hoạt modal PIN SmartCA, gọi `ChuKySoService`, chuyển trạng thái `CHO_PHAT_HANH`) hoặc Trả lại hồ sơ yêu cầu chuyên viên chỉnh sửa kèm ý kiến chỉ đạo.
  3. **Nghiệp vụ Phê duyệt & Ký số Phát hành của Lãnh đạo Cơ quan (`LD_CO_QUAN`):**
     - Hộp việc chờ phát hành (`/admin/phe-duyet/co-quan`): Danh sách hồ sơ đã qua phê duyệt bước 1, sẵn sàng ký số phát hành.
     - Xem chi tiết hồ sơ & tờ trình (`/admin/phe-duyet/co-quan/{id}`): Kiểm tra toàn bộ căn cứ pháp lý và dự thảo.
     - Ký số phát hành văn bản: Ký số điện tử bằng khóa cá nhân của Lãnh đạo, chuyển trạng thái hồ sơ sang `CHO_DONG_DAU` để Văn thư xử lý.
     - **Ký số hàng loạt (Batch Signing):** Lãnh đạo có thể tích chọn nhiều hồ sơ trên danh sách, mở modal xác thực PIN một lần duy nhất, hệ thống tự động xử lý ký số tuần tự kèm thanh tiến trình realtime và bảng báo cáo kết quả chi tiết từng hồ sơ (đạt tiêu chí thời gian < 2 giây/lần ký).
     - **Phê duyệt đề xuất Hủy hồ sơ (`/admin/phe-duyet/de-xuat-huy`):** Tiếp nhận các đề xuất hủy hồ sơ từ Một cửa / Thụ lý kèm căn cứ pháp lý và biên bản; Lãnh đạo phê duyệt hủy chuyển trạng thái logic `DA_HUY` (nghiêm cấm xóa vật lý theo Rule #6).
     - Giao diện tối ưu hoàn toàn cho thiết bị di động (Mobile Responsive), hỗ trợ Lãnh đạo ký số phê duyệt từ xa mọi lúc mọi nơi.
  4. **Nghiệp vụ Phân hệ Văn thư số (`VAN_THU` - PH05):**
     - Xây dựng `VanThuService` và Controller `VanThuController`:
       + **Quản lý Sổ văn bản (`/admin/van-thu/so-van-ban`):** Quản lý sổ văn bản đi/đến theo từng năm, theo từng loại văn bản (Quyết định, Giấy phép, Công văn...), cấu hình số bắt đầu, mẫu ký hiệu văn bản.
       + **Cấp số & Ký hiệu tự động không trùng lặp:** Sử dụng cơ chế khóa bi quan trong Transaction (`SELECT ... FOR UPDATE`), bảo đảm an toàn tuyệt đối chống tranh chấp dữ liệu khi nhiều văn thư cấp số đồng thời.
       + **Đóng dấu số cơ quan (Ký số tổ chức):** Ký số bằng chứng thư số tổ chức (con dấu pháp nhân điện tử), gán số văn bản, ký hiệu chính thức và ngày ban hành vào văn bản kết quả (`/admin/van-thu/dong-dau/{id}`).
       + **Hoàn thành hồ sơ & Trả kết quả điện tử:** Tự động chuyển trạng thái hồ sơ thành `HOAN_THANH`, tạo bản ghi kết quả điện tử đẩy vào **Kho dữ liệu công dân (`kho_du_lieu_cong_dan`)** với thời hạn hiệu lực 5 năm và nguồn `CAP_MOI_DVC`, đồng thời phát thông báo đa kênh (SMS, Zalo, thông báo Portal) cho người dân đến nhận hoặc tải kết quả điện tử.
       + **Quản lý công văn liên quan (`/admin/van-thu/cong-van`):** Quản lý văn bản đến/đi gắn với mã hồ sơ hành chính.
       + **Tra cứu, in sổ & xuất CSV:** Tìm kiếm sổ văn bản, in ấn chuẩn A4 ngang và xuất dữ liệu CSV định dạng UTF-8 có BOM tương thích Microsoft Excel.
  5. **Trang Tra cứu & Kiểm tra Chữ ký số Công khai (`/kiem-tra-chu-ky`):**
     - Cung cấp cổng xác thực công khai cho tổ chức, công dân và đối tác kiểm tra tính pháp lý của văn bản điện tử do thành phố ban hành.
     - Tra cứu thông tin chữ ký số theo Mã hồ sơ hành chính.
     - Tải tệp tài liệu và tệp chữ ký rời (`.sig`) để hệ thống tính toán mã băm SHA-256 đối soát với Public Key chứng thư số trong CSDL, trả về kết quả Hợp lệ / Không hợp lệ chi tiết (Tên người ký, tổ chức, số serial, thời gian ký, thuật toán băm).
  6. **Kiểm thử tự động & Tích hợp:**
     - **Tập kiểm thử tích hợp chuyên sâu (`tests/Integration/PheDuyetKySoVanThuTest.php`):**
       + `testTamperingDetectedAfterSigning`: Ký số tệp tin hợp lệ, cố tình sửa đổi 1 byte nội dung tệp tin giả lập tấn công can thiệp dữ liệu → Hàm kiểm tra xác thực trả về `false` (phát hiện giả mạo thành công 100%).
       + `testUnauthorizedOrSysadminBlockedFromSigning`: Xác minh tài khoản `SYSADMIN` hoặc người không đúng thẩm quyền bị chặn 100% khi cố tình thực hiện ký số (Rule #6 & #12).
       + `testConcurrentRegistryBookNumberingNoDuplicates`: Mô phỏng 15 giao dịch cấp số văn bản đồng thời bằng cơ chế khóa bi quan → 100% số được cấp tăng liên tục từ 101 đến 115, không trùng lặp, không bị nhảy cóc.
       + `testBatchSigningPerformanceAndIntegrity`: Thực hiện ký số hàng loạt 20 hồ sơ với 1 lần PIN → Đạt tỷ lệ thành công 20/20, thời gian ký trung bình đạt tiêu chuẩn < 2 giây/hồ sơ.
       + `testEndToEndFiveStepsWorkflow`: Mô phỏng luồng 5 bước end-to-end từ lúc công dân nộp hồ sơ → Một cửa tiếp nhận → Chuyên viên thụ lý dự thảo → Lãnh đạo phòng duyệt bước 1 → Lãnh đạo cơ quan ký số phát hành → Văn thư cấp số, đóng dấu số cơ quan → Hồ sơ `HOAN_THANH` → Kết quả tự động đẩy vào `kho_du_lieu_cong_dan` với chữ ký số OpenSSL hợp lệ 100%.
     - **Kiểm thử HTTP Web (`tests/Integration/test_phe_duyet_van_thu_http.php`):**
       + Kiểm tra truy cập trang công khai `/kiem-tra-chu-ky` trả về mã HTTP 200 OK.
       + Kiểm tra bảo mật truy cập trái phép vào các trang Lãnh đạo và Văn thư khi chưa đăng nhập đều bị chuyển hướng 302 về trang đăng nhập.
     - **Toàn bộ Test Suite:** **55/55 tests passed 100%** (`tests/run.php`).
- **Tệp tin đã tạo/cập nhật:**
  - `app/Integrations/Contracts/SignatureProviderInterface.php`, `app/Integrations/Mock/OpenSslSignatureProvider.php`, `app/Integrations/Mock/SmartCaProviderMock.php`
  - `app/Services/ChuKySoService.php`, `app/Services/VanThuService.php`
  - `app/Controllers/Admin/PheDuyetController.php`, `app/Controllers/Admin/VanThuController.php`, `app/Controllers/Portal/KiemTraChuKyController.php`
  - `app/Views/admin/phe_duyet/phong.php`, `chi_tiet_phong.php`, `co_quan.php`, `chi_tiet_co_quan.php`, `danh_sach_huy.php`
  - `app/Views/admin/van_thu/index.php`, `dong_dau.php`, `so_van_ban.php`, `cong_van.php`
  - `app/Views/portal/kiem_tra_chu_ky.php`, `app/Views/partials/sidebar.php`, `app/Views/partials/header.php`, `routes/web.php`, `app/Core/View.php`
  - `app/cli.php`, `tests/Integration/PheDuyetKySoVanThuTest.php`, `tests/Integration/test_phe_duyet_van_thu_http.php`, `tests/Unit/MotCuaTest.php`
- **Kế hoạch cho Phiên 11:**
  - Hoàn thành theo đúng kế hoạch.

### PHIÊN 11: SLA ENGINE & THÔNG BÁO ĐA KÊNH (PH06, PH07)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Động cơ SLA thời gian thực (`SlaEngine`):**
     - Cấu hình khung giờ làm việc hành chính chuẩn: Sáng 07:30 – 11:30 (4 giờ), Chiều 13:30 – 17:30 (4 giờ) = 8 giờ làm việc/ngày (loại trừ 2 giờ nghỉ trưa và ngoài giờ làm việc).
     - Chuẩn hóa thời điểm bắt đầu (`normalizeThoiDiemBatDau`): Nếu tiếp nhận ngoài giờ (sau 17:30, ví dụ 17:45) hoặc vào ngày nghỉ, tự động chuyển sang 07:30 sáng ngày làm việc kế tiếp.
     - Loại trừ triệt để Thứ 7, Chủ Nhật và toàn bộ ngày nghỉ lễ, nghỉ bù theo Bộ luật Lao động 2019 nạp từ bảng `dm_ngay_nghi`.
     - Tính ngày hẹn trả (`calculateNgayHenTra`): Hỗ trợ tính theo ngày làm việc hoặc tịnh tiến chi tiết theo số giờ làm việc (`addWorkingHours`).
     - Tính chính xác số giờ làm việc thực tế (`calculateWorkingHoursBetween`): Khấu trừ thời gian tạm dừng chờ bổ sung giấy tờ (`so_gio_tam_dung`) giúp $t$ (giờ đã dùng) phản ánh trung thực trách nhiệm của cơ quan giải quyết.
     - Đánh giá màu sắc SLA (`evaluateSla`) theo đúng công thức **Rule mục 11 & §2.2 Báo cáo Kiến trúc**:
       + **XANH**: $t \le 75\% \cdot T$ VÀ thời gian còn lại $> 24$ giờ làm việc.
       + **VÀNG**: $75\% \cdot T < t \le T$ HOẶC thời gian còn lại $\le 24$ giờ làm việc (khi $t \le T$).
       + **ĐỎ**: $t > T$ (hồ sơ đã trễ hạn).
     - Đánh giá SLA từng bước quy trình (`evaluateStepSla`).
  2. **Cron Job quét SLA tự động định kỳ (`cron/sla_scan.php`):**
     - Kịch bản chạy nền mỗi 15 phút quét toàn bộ hồ sơ đang thụ lý, cập nhật `sla_mau`, `thoi_gian_da_dung_gio`.
     - Chuyển màu **VÀNG**: Tự động phát thông báo in-app đẩy tới Chuyên viên thụ lý và Lãnh đạo phòng chuyên môn.
     - Chuyển màu **ĐỎ**: Khóa chức năng chuyển bước thông thường trong `WorkflowEngine`, phát cảnh báo khẩn cấp tới Lãnh đạo cơ quan, đồng thời tự động ghi nhận trừ 1 điểm thi đua của đơn vị vào bảng `diem_thi_dua_don_vi` phục vụ IOC Dashboard.
     - Lệnh thiết lập Windows Task Scheduler:
       ```cmd
       schtasks /create /tn "HPeGov_SlaScan" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\HP-eGov\cron\sla_scan.php" /sc minute /mo 15 /ru "SYSTEM"
       ```
  3. **Văn bản xin lỗi Mẫu số 04 (Thông tư số 01/2018/TT-VPCP & NĐ 61/2018/NĐ-CP):**
     - Xây dựng màn hình quản lý hồ sơ quá hạn (`/admin/sla/danh-sach-qua-han`) với thống kê 3 thẻ KPI.
     - Màn hình soạn thảo Mẫu 04 (`/admin/sla/van-ban-xin-loi/{id}`): Gợi ý lý do quá hạn chuẩn hóa, tự động tính ngày hẹn trả mới theo SLA, xem trước bản in A4 trực quan.
     - Quy trình ký số điện tử: Chỉ `LD_CO_QUAN` có thẩm quyền ký số (chặn `SYSADMIN` theo Rule #6, #12), xác thực mã PIN SmartCA, ký số OpenSSL lưu vào `van_ban_xin_loi`, tự động mở khóa hoàn tất hồ sơ trong `WorkflowEngine`, đồng thời phát thông báo xin lỗi công dân qua đa kênh.
     - Bản in chuẩn thể thức A4 hành chính (`/admin/sla/in-mau-04/{id}`): Quốc hiệu, Tiêu ngữ, số văn bản, lý do quá hạn, ngày hẹn mới, khối con dấu chữ ký số điện tử của Lãnh đạo cơ quan và hỗ trợ `@media print`.
  4. **Hạ tầng Thông báo đa kênh Outbox Pattern (PH07):**
     - Bảng `hang_doi_thong_bao` (Outbox queue): Hàng đợi tin nhắn SMS Brandname "UBND TP HP", Zalo ZNS (kèm mã QR và link tra cứu) và Email.
     - Bảng `mau_thong_bao`: Quản lý 5 mẫu tin chuẩn hóa (`TIEP_NHAN`, `DANG_THU_LY`, `YEU_CAU_BO_SUNG`, `DA_CO_KET_QUA`, `XIN_LOI_QUA_HAN`) cho phép cấu hình bật/tắt từng kênh và nội suy động các biến `{ma_ho_so}`, `{ten_tthc}`, `{ho_ten}`, `{trang_thai}`, `{ngay_hen_tra}`, `{link_tra_cuu}`, `{so_van_ban}`.
     - `ThongBaoService::guiSuKien`: Tự động nạp mẫu và đẩy tin vào Outbox queue khi xảy ra sự kiện đổi trạng thái hồ sơ.
     - Worker `cron/notify_dispatch.php`: Quét hàng đợi Outbox gửi qua `SmsZnsNotificationMock`, hỗ trợ retry backoff, ghi nhật ký vào `nhat_ky_tich_hop` và `webhook_log`.
       ```cmd
       schtasks /create /tn "HPeGov_NotifyDispatch" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\HP-eGov\cron\notify_dispatch.php" /sc minute /mo 1 /ru "SYSTEM"
       ```
     - Giao diện **Quản trị Mẫu Thông báo** (`/admin/he-thong/mau-thong-bao`) và **Hộp thư Mô phỏng Gateway** (`/admin/he-thong/hop-thu-mo-phong`) hiển thị mockup Smartphone nhận tin SMS Brandname & Zalo ZNS sinh động.
  5. **Hiển thị Badge SLA & Đếm ngược thời gian nhất quán:**
     - Bổ sung helper `render_sla_badge($mau, $ngayHenTra, $soGioDaDung, $tongGio)` hiển thị nhãn màu Xanh/Vàng/Đỏ kèm đếm ngược số giờ còn lại hoặc số giờ quá hạn trên toàn bộ hệ thống.
  6. **Kiểm thử tự động & Tích hợp:**
     - **Unit Test (`tests/Unit/SlaEngineTest.php`):** Đạt 6/6 test cases (Tính hạn qua cuối tuần, qua nghỉ Tết 7 ngày, tiếp nhận 17:45 ngoài giờ, khấu trừ tạm dừng bổ sung, ngưỡng biên Xanh/Vàng/Đỏ 75% và 24h, SLA từng bước).
     - **Integration Test (`tests/Integration/SlaNotificationIntegrationTest.php`):** Đạt 4/4 test cases (SLA Đỏ khóa chuyển bước và mở khóa sau khi ký Mẫu 04, Outbox queue và dispatch qua mock gateway, nội suy biến mẫu tin nhắn, ghi điểm phạt thi đua).
     - **HTTP Web Test (`tests/Integration/test_sla_notification_http.php`):** Đạt 3/3 kịch bản kiểm tra bảo mật các tuyến đường SLA và Thông báo (HTTP 302).
     - **Toàn bộ Test Suite:** **65/65 tests passed 100%** (`tests/run.php`).
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/SlaEngine.php`, `app/Services/ThongBaoService.php`, `app/Services/VanThuService.php`
  - `app/Integrations/Mock/SmsZnsNotificationMock.php`
  - `cron/sla_scan.php`, `cron/notify_dispatch.php`
  - `app/Controllers/Admin/SlaController.php`, `app/Controllers/Admin/ThongBaoController.php`
  - `app/Views/admin/sla/danh_sach_qua_han.php`, `form_mau_04.php`, `in_mau_04.php`
  - `app/Views/admin/he_thong/mau_thong_bao.php`, `hop_thu_mo_phong.php`
  - `app/Views/partials/sidebar.php`, `routes/web.php`, `app/Core/Session.php`, `app/Helpers/functions.php`
  - `database/migrations/2026_10_07_000002_create_mau_thong_bao_and_thi_dua_tables.sql`, `database/schema.sql`
  - `tests/Unit/SlaEngineTest.php`, `tests/Integration/SlaNotificationIntegrationTest.php`, `tests/Integration/test_sla_notification_http.php`
- **Kế hoạch cho Phiên 12:**
  - Hoàn thành theo đúng kế hoạch.

### PHIÊN 12: DASHBOARD CHỈ ĐẠO ĐIỀU HÀNH & BÁO CÁO (PH08 – §4.3 IOC)
- **Thời gian hoàn thành:** 2026-10-07
- **Công việc đã thực hiện:**
  1. **Dashboard theo vai trò người dùng (Data Scope phân cấp & Chart.js cục bộ):**
     - **Chuyên viên thụ lý (`/admin/dashboard` vai trò `CHUYEN_VIEN`):** 4 thẻ KPI việc cá nhân (Đang thụ lý, Sắp quá hạn Vàng, Quá hạn Đỏ, Đã hoàn thành), bảng ưu tiên xử lý xếp theo hạn SLA và mức độ khẩn cấp, biểu đồ Năng suất giải quyết 7 ngày qua sử dụng `Chart.js` cục bộ (`public/assets/vendor/chartjs/chart.umd.js`).
     - **Lãnh đạo Phòng (`/admin/dashboard?view=phong` vai trò `LD_PHONG`):** Thống kê hồ sơ toàn phòng, tỷ lệ đúng hạn của phòng, bảng phân bổ công việc theo từng chuyên viên trực thuộc, biểu đồ tròn Doughnut tỷ trọng công việc và danh sách hồ sơ cảnh báo Vàng/Đỏ cần đôn đốc.
     - **Lãnh đạo Cơ quan (`/admin/dashboard?view=co_quan` vai trò `LD_CO_QUAN` - Giám đốc Sở / Chủ tịch UBND Quận/Huyện):** KPI tổng thể cơ quan (Tiếp nhận, Tỷ lệ trực tuyến, Tỷ lệ đúng hạn, Quá hạn), bảng đối soát tiến độ giữa các phòng ban chuyên môn, xếp hạng chuyên viên xuất sắc, danh sách hồ sơ quá hạn cần ban hành Văn bản xin lỗi Mẫu số 04.
  2. **Trung tâm Chỉ đạo & Điều hành Thông minh (IOC) Hải Phòng (`/admin/ioc`):**
     - **4 Thẻ Chỉ tiêu Báo cáo Kiến trúc (§2):**
       + Tỷ lệ Đúng/trước hạn: $\ge 98\%$ (chỉ báo ĐẠT / CHƯA ĐẠT).
       + Dịch vụ công Toàn trình: $\ge 80\%$ (chỉ báo ĐẠT / CẦN ĐẨY MẠNH).
       + Tỷ lệ Số hóa hồ sơ & kết quả: $100\%$ theo NĐ 107/2021/NĐ-CP (chỉ báo ĐẠT / TIẾN ĐỘ TỐT).
       + Mức độ Hài lòng SIPAS: $\ge 92\%$ theo QĐ 876/QĐ-BNV (chỉ báo ĐẠT / RẤT HÀI LÒNG).
     - **Bản đồ số Hải Phòng Vector SVG 15 Quận / Huyện:** Tự vẽ vector không gian địa lý tương đối của 15 đơn vị hành chính cấp huyện (Hồng Bàng, Ngô Quyền, Lê Chân, Hải An, Kiến An, Đồ Sơn, Dương Kinh, Thủy Nguyên, An Dương, An Lão, Kiến Thụy, Tiên Lãng, Vĩnh Bảo, Cát Hải, Bạch Long Vĩ). Tự động tô màu gradient theo tỷ lệ đúng hạn (Xanh $\ge 98\%$, Vàng $95\% - 98\%$, Đỏ $< 95\%$). Tương tác hover/click mở Modal chi tiết đơn vị và danh sách hồ sơ trễ hạn.
     - **Heatmap Tiếp nhận Hồ sơ Giờ × Ngày:** Ma trận 24 giờ $\times$ 7 ngày trong tuần trực quan với cường độ màu xanh ngọc gradient và tooltip tương tác.
     - **Bảng Xếp hạng Thi đua Sở / Quận:** Tab chuyển đổi giữa Khối 15 Quận/Huyện và Khối các Sở/Ban/Ngành; tính toán dựa trên Điểm thi đua chuẩn 100 trừ điểm phạt `diem_thi_dua_don_vi` từ SLA Scan, Tỷ lệ đúng hạn và Số hồ sơ quá hạn.
     - **Phát hiện Điểm thắt cổ chai (Bottleneck Detector):** Tự động thống kê số hồ sơ tồn đọng và thời gian xử lý trung bình theo từng bước quy trình (`quy_trinh_buoc`), cảnh báo khâu gây chậm trễ lớn nhất kèm khuyến nghị điều hành.
     - **Chế độ TV Wall & Tự làm mới:** Hỗ trợ Fullscreen API, thanh đếm ngược tự làm mới 60 giây (`60s, 59s... 0s`) kết hợp API JSON ngầm `/admin/api/ioc-stats` không làm nhấp nháy màn hình.
  3. **Phân hệ Báo cáo Thống kê Hành chính Động (`/admin/bao-cao`):**
     - **Báo cáo Mẫu biểu 01 TT 01/2018/TT-VPCP:** Đầy đủ 15 cột chuẩn hóa (Tồn kỳ trước, Tiếp nhận mới Trực tiếp/Trực tuyến, Tổng nhận, Giải quyết Trước hạn/Đúng hạn/Quá hạn, Tổng giải quyết, Đang xử lý Trong hạn/Quá hạn, Tổng đang xử lý, Tỷ lệ đúng hạn).
     - **Báo cáo Số hóa NĐ 107/2021/NĐ-CP:** Thống kê tỷ lệ số hóa thành phần đầu vào và kết quả điện tử được đồng bộ vào Kho dữ liệu cá nhân.
     - **Báo cáo Quá hạn & Mẫu 04:** Danh sách hồ sơ quá hạn, số giờ trễ hạn, trạng thái lập và ký số văn bản xin lỗi Mẫu 04, ngày hẹn mới và lý do quá hạn.
     - **Báo cáo Hài lòng SIPAS QĐ 876/QĐ-BNV:** 5 nhóm tiêu chí (Tiếp cận, Thủ tục, Công chức, Kết quả, Phản ánh), điểm trung bình và tỷ lệ hài lòng chung.
     - **Báo cáo Trực tuyến Toàn trình:** Phân loại theo mức độ dịch vụ công.
     - **Báo cáo Luân chuyển Liên thông:** Thống kê luân chuyển liên cơ quan.
     - **Xuất CSV UTF-8 BOM:** Header `\xEF\xBB\xBF` mở trực tiếp trên Excel tiếng Việt không lỗi phông.
     - **Bản in A4 Ngang Chuẩn Thể thức (`in_a4.php`):** Quốc hiệu, Tiêu ngữ, Tên cơ quan, Ngày tháng năm, Bảng số liệu, Người lập biểu và Thủ trưởng cơ quan ký số (`@media print` căn chuẩn trang A4 landscape).
  4. **Tối ưu Truy vấn & Bộ đệm Cache 5 phút (`storage/cache/ioc_stats.json`):**
     - Tận dụng hệ thống View CSDL: `v_ho_so_tong_hop`, `v_ty_le_dung_han_don_vi`, `v_ho_so_qua_han`.
     - Cache kết quả tổng hợp nặng vào `storage/cache/ioc_stats.json` với TTL 300 giây (5 phút), hỗ trợ cờ `?refresh=1`.
     - Thời gian truy vấn đo đạc thực tế: $0.04$s (trực tiếp) và $< 0.001$s (đọc từ cache), đạt yêu cầu $< 1.0$ giây.
  5. **Kiểm thử tự động:**
     - **Unit Test (`tests/Unit/ReportingServiceTest.php`):** Đạt 7/7 test cases (Stats chuyên viên, phòng, cơ quan, IOC 4 chỉ tiêu, 15 quận huyện SVG map, cache TTL và thời gian truy vấn < 1s, Báo cáo TT 01, NĐ 107, SIPAS).
     - **Integration Test (`tests/Integration/DashboardBaoCaoTest.php`):** Đạt 4/4 test cases (Điều hướng vai trò, IOC screen & API stats, District detail API, Báo cáo & Bản in A4 ngang).
     - **Toàn bộ Test Suite:** **76/76 tests passed 100%** (`tests/run.php`).
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/ReportingService.php`
  - `app/Controllers/Admin/DashboardController.php`, `app/Controllers/Admin/BaoCaoController.php`
  - `app/Views/admin/dashboard/chuyen_vien.php`, `ld_phong.php`, `ld_co_quan.php`, `ioc.php`
  - `app/Views/admin/bao_cao/index.php`, `thong_tu_01.php`, `so_hoa.php`, `qua_han.php`, `hai_long.php`, `truc_tuyen.php`, `lien_thong.php`, `in_a4.php`
  - `app/Views/partials/sidebar.php`, `routes/web.php`, `app/Services/AuthService.php`
  - `tests/Unit/ReportingServiceTest.php`, `tests/Integration/DashboardBaoCaoTest.php`
- **Kế hoạch cho Phiên 13:**
  - Hoàn thành theo đúng kế hoạch.

### PHIÊN 13: QUẢN TRỊ & VẬN HÀNH HỆ THỐNG (PH12, PH13, PH15 – §1.4, §2.3, §4.3, §4.4)
- **Thời gian hoàn thành:** 2026-10-08
- **Công việc đã thực hiện:**
  1. **PH12 – An toàn thông tin & Nhật ký kiểm toán WORM (SYSADMIN):**
     - Xây dựng giao diện tra cứu **Audit Trail** (`/admin/he-thong/audit`): Lọc theo người dùng, hành động, bảng tác động, ngày bắt đầu/kết thúc; phân trang mượt mà; Modal xem chi tiết Diff dữ liệu cũ/mới JSON trực quan (chuẩn Design System `.modal-backdrop`, `.modal-card`).
     - Nút **"Kiểm tra toàn vẹn chuỗi băm"** (AJAX call tới `AuditService::verifyChain()`): Đối soát chuỗi hàm băm SHA-256 nối tiếp ($Hash_n = \text{SHA256}(Hash_{n-1} + ...)$), tự động phát hiện mọi hành vi can thiệp trái phép. Đã kiểm tra thực tế trên 815 bản ghi: Hợp lệ toàn vẹn 100%.
     - **Nhật ký đăng nhập** (`/admin/he-thong/nhat-ky-dang-nhap`): Theo dõi thời gian, IP, thiết bị, trình duyệt, trạng thái thành công/thất bại, lý do thất bại.
     - **Nhật ký kết nối tích hợp LGSP** (`/admin/he-thong/nhat-ky-tich-hop`): Giám sát các giao dịch liên thông tới CSDLQG Dân cư C06, Cổng DVCQG, VNPost, SMS ZNS, SmartCA, mã HTTP Status, thời gian đáp ứng (ms) và xem payload.
     - **Quản lý IP bị chặn & Rate Limit** (`/admin/he-thong/ip-chan`): Blacklist IP cố tình tấn công có thời hạn hoặc vĩnh viễn, xem danh sách IP đang chịu Rate Limit và nút làm sạch bộ đếm.
     - **Cấu hình Chính sách bảo mật** (`/admin/he-thong/chinh-sach-bao-mat`): Thiết lập độ dài mật khẩu tối thiểu ($\ge 8$), số lần nhập sai khóa tài khoản (5 lần), thời gian tạm khóa (15 phút), timeout phiên (30 phút), ngưỡng rate limit (60 req/phút).
  2. **PH13 – Sao lưu & Phục hồi CSDL chuẩn chính sách Báo cáo Kiến trúc (§2.3, §4.4):**
     - **Full Backup:** Hằng tuần (Chủ nhật 00:00) chạy thực tế bằng `C:\xampp\mysql\bin\mysqldump.exe --single-transaction --routines --triggers`, nén, tính mã băm SHA-256 và tự động sao chép sang thư mục Trung tâm DR Site (`storage/backups/dr_site/`).
     - **Differential Backup:** Hằng ngày (23:00) xuất toàn bộ bản ghi thay đổi kể từ bản Full gần nhất theo `updated_at`/`created_at`, lưu giữ đúng 30 ngày gần nhất và tự động thanh lọc các bản cũ.
     - **Transaction Log:** Mỗi 2 giờ/lần xuất log thay đổi CSDL hoặc đồng bộ MySQL binary log (`log-bin` trong `my.ini`), bảo đảm cam kết **RPO $\le$ 2 giờ**.
     - **Trang quản trị sao lưu (`/admin/he-thong/sao-luu`):** Lịch sử sao lưu, dung lượng (B/KB/MB), checksum SHA-256 đối soát, vị trí lưu trữ (ON_PREMISE & DR_SITE), nút tải về an toàn bảo mật, các nút chạy Full / Diff / Log backup tức thì.
     - **PHỤC HỒI THỬ NGHIỆM VÀO CSDL `hp_egov_restore_test`:** Nạp bản lưu bằng `C:\xampp\mysql\bin\mysql.exe`, đối soát 56 bảng CSDL, đo thời gian thực tế **RTO = 1.75 giây** (đáp ứng xuất sắc cam kết **RTO $\le$ 4 giờ** / 240 phút); tự động ghi nhận biên bản diễn tập DRP vào bảng `dien_tap`.
     - Hướng dẫn cấu hình lệnh Windows Task Scheduler (`schtasks`) mẫu và kích hoạt `log-bin` trong `my.ini`.
  3. **Giám sát sức khỏe hệ thống thời gian thực (Health Dashboard & API Prometheus):**
     - Trang Dashboard Health (`/admin/he-thong/health`): DB Ping & độ trễ kết nối (3.15 ms), Dung lượng ổ đĩa hệ thống (Gauge % used, Free/Total GB), Kích thước thư mục lưu trữ `storage/` (3.07 MB), Độ dài hàng đợi thông báo Outbox (`hang_doi_thong_bao`), Lần chạy cron gần nhất, Phiên bản PHP (8.2.12), MariaDB (10.4.32), Máy chủ web (Apache 2.4), OS (Windows NT), Số phiên người dùng đang hoạt động (7 phiên).
     - REST API Endpoint công khai `/api/v1/health`: Trả JSON chuẩn hóa mã HTTP 200/503 phục vụ giám sát bằng Prometheus, Zabbix, Uptime Kuma ở production.
  4. **Quản lý sự cố & Kế hoạch duy trì hoạt động BCP/DRP (§4.3, §4.4):**
     - Sổ theo dõi sự cố an toàn thông tin & gián đoạn dịch vụ (`su_co`): Phân loại sự cố (Đứt TSLCD, DDoS, Ransomware, Deface, DB Failover...), mức độ, thời gian phát hiện/khắc phục, người xử lý, giải pháp áp dụng.
     - Phân định ma trận **RACI** chi tiết: Sở TT&TT (A/R) vs Đơn vị nghiệp vụ (C/I) vs Nhà cung cấp viễn thông (R).
     - Sổ theo dõi & Biên bản diễn tập định kỳ (`dien_tap`): Diễn tập An toàn thông tin (chu kỳ 6 tháng/lần) và Diễn tập Phục hồi thảm họa DRP (chu kỳ 12 tháng/lần) kèm cảnh báo nhắc hẹn khi sắp hoặc quá hạn.
     - **Chế độ bảo trì tĩnh (Static Maintenance Site):** Bật/Tắt tức thời từ giao diện quản trị; khi bật, tự động tạo tệp cờ `storage/maintenance.flag`, trả về trang tĩnh `public/maintenance.html` (Mã HTTP 503) nhận diện chuẩn Hải Phòng cho toàn bộ công dân, chỉ cho phép SYSADMIN truy cập.
  5. **PH15 – Đào tạo & Chuyển giao công nghệ 4 nhóm cán bộ (§1.4):**
     - Xây dựng 4 khóa đào tạo chuẩn theo bảng §1.4:
       1. Cán bộ Một cửa (`MOT_CUA`): Tiêu chuẩn đạt điểm trắc nghiệm $\ge 90/100$.
       2. Chuyên viên thụ lý (`CHUYEN_VIEN`): Tiêu chuẩn đạt hoàn thành 10 bộ hồ sơ mẫu không lỗi quy trình.
       3. Lãnh đạo phê duyệt (`LANH_DAO`): Tiêu chuẩn ký số thành công 100% hồ sơ mẫu bằng SmartCA / USB Token.
       4. Quản trị viên hệ thống (`SYSADMIN`): Tiêu chuẩn phục hồi CSDL thành công trong thời gian RTO < 4 giờ.
     - Giao diện quản trị khóa học, ghi danh học viên, nhập điểm thi, số hồ sơ thực hành, nhận xét và tự động đánh giá ĐẠT / KHÔNG ĐẠT.
     - Báo cáo thanh tiến độ % tỷ lệ đạt chuẩn từng khóa học.
     - Nhận diện môi trường Staging: Tự động hiển thị biểu ngữ cảnh báo màu cam `[⚠️ MÔI TRƯỜNG ĐÀO TẠO & THỰC HÀNH CÔNG NGHỆ (STAGING) — DỮ LIỆU THỬ NGHIỆM KHÔNG CÓ GIÁ TRỊ PHÁP LÝ]` khi cờ `APP_ENV=staging`.
  6. **Biên soạn Quy chế Quản trị & Vận hành (`docs/05_QUY_CHE_VAN_HANH.md`):**
     - Đầy đủ 6 chương chuẩn thể thức hành chính: Ma trận RACI, SOP vận hành hàng ngày/tuần/tháng, Quy trình sao lưu 3 tầng (Full, Diff, Log), Quy trình kiểm thử phục hồi DRP, Quy chế bảo mật WORM, Kịch bản ứng cứu 4 loại sự cố khẩn cấp và Quy định đào tạo 4 nhóm cán bộ.
  7. **Kiểm thử tự động & Tích hợp:**
     - **Integration Test (`tests/Integration/QuanTriVanHanhTest.php`):** Đạt 7/7 test cases (Full backup & test restore vào `hp_egov_restore_test` trên XAMPP, Differential & Log backup, WORM hash chain integrity, HealthCheckService, SecurityPolicy & IP block, SYSADMIN RBAC 403 protection, TrainingService 4 nhóm).
     - **Toàn bộ Test Suite:** **83/83 tests passed 100%** (`tests/run.php`).
- **Tệp tin đã tạo/cập nhật:**
  - `app/Services/BackupService.php`, `app/Services/HealthCheckService.php`, `app/Services/SecurityPolicyService.php`, `app/Services/IncidentService.php`, `app/Services/TrainingService.php`
  - `app/Controllers/Admin/HeThongController.php`
  - `app/Views/admin/he_thong/audit.php`, `nhat_ky_dang_nhap.php`, `nhat_ky_tich_hop.php`, `ip_chan.php`, `chinh_sach_bao_mat.php`, `sao_luu.php`, `health.php`, `su_co.php`, `dao_tao.php`
  - `cron/backup_full.php`, `cron/backup_diff.php`, `cron/backup_log.php`
  - `public/maintenance.html`
  - `app/Core/App.php`, `routes/web.php`, `routes/api.php`, `app/Views/partials/sidebar.php`, `app/Views/partials/breadcrumb.php`, `app/Views/layouts/admin.php`, `app/Views/layouts/portal.php`
  - `docs/05_QUY_CHE_VAN_HANH.md`, `tests/Integration/QuanTriVanHanhTest.php`
- **Kế hoạch cho Phiên 14:**
  - Hoàn thành xuất sắc toàn bộ nội dung Phiên 14 theo yêu cầu.

---

### PHIÊN 14: CHUYỂN ĐỔI DỮ LIỆU ETL, QUẢN TRỊ TÍCH HỢP & REST API /API/V1
- **Thời gian hoàn thành:** 2026-10-08
- **Công việc đã thực hiện:**
  1. **Quy trình ETL Pipeline 4 bước đúng Báo cáo (§1.3.A, §1.1):**
     - **Tệp mẫu dữ liệu 5 năm gần nhất:** Cung cấp 2 bộ dữ liệu mô phỏng xuất từ CSDL Một cửa cũ (`database/etl_samples/sample_mssql_cu.csv` và `sample_mysql_cu.csv`) kèm thư mục tệp đính kèm (`database/etl_samples/attachments/`). Dữ liệu chủ động chứa các dạng "dữ liệu bẩn": hồ sơ trùng lặp, thiếu CCCD, sai độ dài CCCD/CMND, mã TTHC/ĐVHC cũ chưa chuẩn hóa, chuỗi rác có ký tự injection/XSS.
     - **Bước 1 - Extract:** Tải lên tệp CSV cũ (hoặc chọn tệp mẫu có sẵn trên hệ thống) + chỉ định đường dẫn tệp đính kèm. Khởi tạo phiên chuyển đổi trong `etl_lo`.
     - **Bước 2 - Staging & Profiling:** Nạp nguyên trạng vào bảng tạm `etl_staging_ho_so`. Hệ thống tự động phân tích và lập báo cáo hồ sơ lỗi/chất lượng dữ liệu: phát hiện trường rác, hồ sơ trùng lặp, thiếu số định danh cá nhân, mã TTHC/ĐVHC không hợp lệ; ghi nhận vào `etl_loi`.
     - **Bước 3 - Transform:** Ánh xạ mã TTHC cũ sang mã TTHC quốc gia (`etl_anh_xa_tthc`), mã ĐVHC cũ sang mã Tổng cục Thống kê (`etl_anh_xa_dvhc`) với giao diện quản trị cho phép tùy biến trực tiếp. Chuẩn hóa ngày tháng định dạng chuẩn ISO `Y-m-d H:i:s`, chuẩn hóa họ tên dạng Title Case, chuẩn hóa trạng thái hồ sơ theo hệ thống mới, đánh dấu tự động cờ cần chuyển đổi PDF/A-1b đối với các tệp scan cũ.
     - **Bước 4 - Load & Verify:** Nạp vào bảng chính (`ho_so`, `ho_so_tai_lieu`, `ho_so_luan_chuyen`) trong Transaction theo từng lô (Batch chunking 50 bản ghi/lần) để chống nghẽn và timeout máy chủ PHP. Tính toán và đối soát 100% mã băm SHA-256 đối với tệp đính kèm (`storage/ho_so/`). Báo cáo tổng kết số lượng Thành công / Lỗi / Bỏ qua và cho phép xuất danh sách lỗi chi tiết ra file CSV phục vụ đối soát.
  2. **Quản trị Cổng Tích hợp & Kết nối liên thông (§1.1, §2.3):**
     - **Danh sách 9 dịch vụ nền tảng:** VNeID, LGSP Hải Phòng, C06 (CSDLQG Dân cư), CSDL TTHC QG, SmartCA (Ký số từ xa), SMS Brandname, Zalo ZNS, VNPost (Bưu chính công ích), ClamAV (Quét mã độc).
     - **Quản lý cấu hình & Chế độ vận hành:** Bảng CSDL `cau_hinh_tich_hop`, cho phép chuyển đổi linh hoạt giữa chế độ `MOCK` và `LIVE`, cấu hình Endpoint, API Key / Secret Key, Timeout kết nối, Headers bổ sung.
     - **Chức năng Kiểm tra kết nối (Ping test):** Nút kiểm tra từng dịch vụ hoặc kiểm tra đồng loạt toàn bộ 9 cổng, đo độ trễ mạng thực tế (latency ms), cập nhật trạng thái kết nối (`HOAT_DONG`, `LOI_KET_NOI`).
     - **Nhật ký & Thống kê tích hợp thời gian thực:** Thống kê tổng số lượt gọi, tỷ lệ lỗi (%), độ trễ trung bình, lần gọi gần nhất; hiển thị trực quan bảng 20 giao dịch kết nối mới nhất từ `nhat_ky_tich_hop`.
  3. **REST API `/api/v1` Chuẩn hóa & An toàn bảo mật:**
     - **Chuẩn hóa kiến trúc:** RESTful JSON, định dạng phản hồi thống nhất `{status, code, message, data, timestamp}`. Mã lỗi HTTP chuẩn (200, 400, 401, 403, 404, 429, 500).
     - **Xác thực Bearer Token:** Quản lý bảng `api_tokens`, lưu trữ mã băm SHA-256 an toàn, kiểm tra thời hạn sử dụng (`het_han_luc`), phân quyền theo Scopes (`tra_cuu`, `thu_tuc`, `cong_dan`, `lanh_dao`, `webhook`).
     - **Bảo mật & Giới hạn tần suất:** Rate Limit áp dụng 60 requests/phút theo IP/Token (trả về HTTP 429 kèm header `Retry-After`), Middleware bảo mật Security Headers.
     - **Danh sách Endpoints triển khai:**
       * `GET /api/v1/health`: Kiểm tra trạng thái hoạt động của hệ thống (Công khai).
       * `GET /api/v1/tra-cuu/{ma_ho_so}`: Tra cứu thông tin tiến độ hồ sơ công khai tối thiểu (ẩn CCCD/SĐT bảo mật cá nhân).
       * `GET /api/v1/danh-muc-tthc`: Tra cứu danh mục thủ tục hành chính công (hỗ trợ phân trang và tìm kiếm từ khóa).
       * `GET /api/v1/cong-dan/ho-so`: Lấy danh sách hồ sơ của công dân (Scope `cong_dan`, tự động lọc theo CCCD, chống IDOR).
       * `GET /api/v1/cong-dan/ho-so/{ma_ho_so}`: Xem chi tiết hồ sơ công dân (chặn tuyệt đối việc truy cập hồ sơ công dân khác).
       * `GET /api/v1/lanh-dao/cho-ky`: Danh sách hồ sơ chờ ký duyệt dành cho Mobile App Lãnh đạo (Scope `lanh_dao`).
       * `POST /api/v1/lanh-dao/ky-so`: Thực hiện ký số từ xa bằng SmartCA / OpenSSL từ thiết bị di động (xác thực mã PIN SmartCA).
       * `POST /api/v1/webhook/lgsp`: Tiếp nhận kết quả liên thông từ Trục LGSP TP. Hải Phòng (xác thực nghiêm ngặt chữ ký số HMAC-SHA256 qua header `X-LGSP-Signature`).
  4. **Tài liệu Đặc tả API (`docs/06_API.md`):**
     - Hoàn thiện tài liệu đặc tả OpenAPI/RESTful V1 chi tiết: Cấu trúc xác thực Bearer Token, cơ chế Rate Limit, mã trạng thái HTTP, mẫu cURL kiểm thử cho từng endpoint, thuật toán sinh chữ ký HMAC-SHA256 cho Webhook.
  5. **Kiểm thử Tự động & Giao diện người dùng:**
     - **Tập kiểm thử tích hợp (`tests/Integration/EtlAndRestApiTest.php`):** 8 test cases hoàn chỉnh kiểm tra luồng ETL 4 bước với dữ liệu bẩn, tra cứu công khai, từ chối Token sai (401), từ chối sai Scope (403), kiểm tra chống IDOR công dân, Mobile App Lãnh đạo, kiểm tra chữ ký Webhook HMAC hợp lệ và không hợp lệ, kiểm tra Ping kết nối 9 dịch vụ.
     - **Toàn bộ Test Suite:** **91/91 tests passed 100%** (`tests/run.php`).
     - **Kiểm tra giao diện:** Giao diện Quản trị ETL Wizard 4 bước (`/admin/he-thong/etl`) và Cổng Tích hợp 9 dịch vụ (`/admin/he-thong/tich-hop`) hoạt động mượt mà, đầy đủ KPI, tương tác Ping test trực quan.
- **Tệp tin đã tạo/cập nhật:**
  - `database/migrations_session14.sql`, `database/schema.sql`, `database/seeders/Session14Seeder.php`
  - `database/etl_samples/sample_mssql_cu.csv`, `database/etl_samples/sample_mysql_cu.csv`, `database/etl_samples/attachments/*`
  - `app/Services/EtlService.php`, `app/Services/ApiTokenService.php`, `app/Services/IntegrationService.php`
  - `app/Middleware/ApiAuthMiddleware.php`, `app/Core/Pipeline.php`, `app/Core/Request.php`, `app/Core/Response.php`
  - `app/Controllers/Api/ApiController.php`, `app/Controllers/Api/PublicApiController.php`, `app/Controllers/Api/CitizenApiController.php`, `app/Controllers/Api/LeaderApiController.php`, `app/Controllers/Api/WebhookController.php`
  - `app/Controllers/Admin/EtlController.php`, `app/Controllers/Admin/TichHopController.php`
  - `app/Views/admin/he_thong/etl.php`, `app/Views/admin/he_thong/etl_anh_xa.php`, `app/Views/admin/he_thong/tich_hop.php`
  - `routes/web.php`, `routes/api.php`, `app/Views/partials/sidebar.php`
  - `docs/06_API.md`, `tests/Integration/EtlAndRestApiTest.php`, `tests/Unit/RouterTest.php`
- **Kế hoạch cho Phiên 15:**
  - Thực hiện thành công xuất sắc toàn bộ nội dung Phiên 15/16.

---

### PHIÊN 15: KIỂM THỬ TOÀN DIỆN & ĐẢM BẢO CHẤT LƯỢNG (QA/QC)
- **Thời gian hoàn thành:** 2026-10-08
- **Căn cứ thực hiện:** Báo cáo Kiến trúc Chương 3 (§3.1 – §3.4) & Quy định An toàn thông tin Cấp độ 3.
- **Công việc đã thực hiện:**
  1. **Chiến lược & Kế hoạch Kiểm thử (`docs/07_KE_HOACH_KIEM_THU.md`):**
     - Xây dựng tài liệu chiến lược bao phủ toàn diện 5 loại hình kiểm thử: Functional (100% Use Case theo ma trận RBAC 7 vai trò), Integration (Xã $\rightarrow$ Huyện $\rightarrow$ Xã, liên thông đa Sở), Performance (k6, JMeter, benchmark vi mô), Security (OWASP Top 10, Cấp độ 3 Nghị định 85/2016/NĐ-CP), UAT (30 ngày thực địa tại UBND Q. Hồng Bàng & Sở GTVT Hải Phòng).
     - Quy định tiêu chí vào/ra (Entry/Exit Criteria), cấu hình 3 môi trường phân tách (Dev, Staging, Production).
  2. **Kiểm thử Tích hợp Đa cấp & Bảo toàn Toàn vẹn Dữ liệu:**
     - Hoàn thiện kịch bản `testLienThongXaHuyenWithFileIntegrityAndSignatures()` trong `tests/Integration/LienThongLogisticsTest.php`: Luồng xử lý khép kín 4 trạm Xã $\rightarrow$ Huyện $\rightarrow$ Xã.
     - Kiểm chứng 100% mã băm SHA-256 của tệp đính kèm không đổi qua các cấp; chuỗi chữ ký số hóa (Cấp Xã), chữ ký lãnh đạo phê duyệt (Cấp Huyện) và dấu số cơ quan (Văn thư Huyện) được xác thực tính toàn vẹn toán học.
  3. **Kiểm thử An toàn thông tin (Security Testing – OWASP Top 10 & Cấp độ 3):**
     - Xây dựng bộ test suite chuyên sâu `tests/Security/OwaspSecurityTest.php` với 11/11 tests PASSED 100%:
       * SQL Injection: Prepared Statements trên toàn bộ tham số tìm kiếm, lọc dữ liệu.
       * IDOR: Chặn công dân xem/sửa hồ sơ người khác (`403 Forbidden`).
       * XSS: Chặn mã script độc hại lưu trữ và phản chiếu.
       * CSRF: Bắt buộc token hợp lệ cho mọi request thay đổi trạng thái (`419 Timeout`).
       * File Upload Security: Chặn các file thực thi nguy hiểm, kiểm tra mime-type và lưu ngoài web root.
       * Brute-Force Lockout: Khóa tài khoản sau 5 lần nhập sai (+15 phút) và kích hoạt Captcha từ lần thứ 3.
       * Rate Limiting Layer 7: Giới hạn 60 req/phút/IP (`429 Too Many Requests`).
       * Security Headers: Bổ sung CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff, Cookie Session an toàn.
       * Phân tách quyền Quản trị: SYSADMIN tuyệt đối không thể ký số nghiệp vụ (`DomainException`).
       * Trigger DB Chống Xóa Vật lý: Bộ trigger chặn lệnh `DELETE` ném lỗi `SQLSTATE[45000]`.
       * WORM Audit Chain: Phát hiện ngay lập tức bất kỳ can thiệp nào vào chuỗi băm nhật ký kiểm toán.
     - Lập Báo cáo ATTT chi tiết `docs/08_BAO_CAO_KIEM_THU_ATTT.md` đối chiếu bảng tiêu chuẩn OWASP WSTG v4.2.
  4. **Kiểm thử Hiệu năng & Tải (Performance Testing):**
     - Xây dựng kịch bản k6 công dân 5.000 CCU (`tests/performance/portal_5000ccu.js`), nội bộ 1.200 CCU (`tests/performance/noibo_1200ccu.js`), kịch bản Apache JMeter (`tests/performance/JMeter_Test_Plan_HpeGov.jmx`).
     - Xây dựng và thực thi bộ đo benchmark vi mô `tests/performance/run_benchmark.php`:
       * Thời gian phản hồi trung bình: **45.7ms** (Quy định $\le 1.0$s $\rightarrow$ Đạt).
       * Thời gian phản hồi P95: **61.1ms** (Quy định $\le 1.5$s $\rightarrow$ Đạt).
       * Tỷ lệ lỗi giao dịch: **0.00%** (Quy định $\le 0.01\%$ $\rightarrow$ Đạt).
       * Tối ưu hóa EXPLAIN truy vấn CSDL: Index scan tối ưu trên các bảng nghiệp vụ chính.
  5. **Kiểm thử Chấp nhận Người dùng Thực địa (UAT) & Khảo sát SIPAS:**
     - Xây dựng kịch bản kiểm thử tự động `tests/uat/KichBanUAT_HongBangTest.php` và `tests/uat/KichBanUAT_SoGTVTTest.php`:
       * UBND Q. Hồng Bàng (1.420 hồ sơ): Rút thời gian nhập liệu từ 12 phút xuống dưới 4 phút nhờ quét mã QR CCCD gắn chip đối soát C06.
       * Sở GTVT Hải Phòng (850 hồ sơ): Ký số di động SmartCA Mobile PKI với độ trễ chỉ **0.68s** (yêu cầu $< 2.0$s); cấp mã QR kết quả bản điện tử.
     - Lập `docs/10_BIEN_BAN_NGHIEM_THU_UAT.md`: Phiếu khảo sát SIPAS chuẩn Bộ Nội vụ (1.250 phiếu đạt **96.4%** hài lòng và rất hài lòng, vượt ngưỡng $\ge 95\%$) và Biên bản nghiệm thu kỹ thuật bàn giao 4 bên.
  6. **Kiểm thử Ma trận Chức năng & Tự động hóa Toàn diện:**
     - Xây dựng `tests/Functional/FunctionalMatrixTest.php` bao phủ 15 nhóm chức năng FR01–FR15 cho 7 vai trò người dùng.
     - Tích hợp toàn bộ vào Test Runner CLI (`php tests/run.php`): **124/124 tests PASSED 100% (0 failed)**.
     - Thực hiện kiểm thử E2E tự động qua Browser Subagent ghi hình video WebP (`e2e_testing_qa_qc_1791427170361.webp`).
     - Lập Báo cáo Tổng kết Kết quả Kiểm thử Toàn diện `docs/09_BAO_CAO_KET_QUA_KIEM_THU.md`.
- **Tệp tin đã tạo/cập nhật:**
  - `docs/07_KE_HOACH_KIEM_THU.md`, `docs/08_BAO_CAO_KIEM_THU_ATTT.md`, `docs/09_BAO_CAO_KET_QUA_KIEM_THU.md`, `docs/10_BIEN_BAN_NGHIEM_THU_UAT.md`
  - `tests/Integration/LienThongLogisticsTest.php`, `tests/Security/OwaspSecurityTest.php`
  - `tests/performance/portal_5000ccu.js`, `tests/performance/noibo_1200ccu.js`, `tests/performance/JMeter_Test_Plan_HpeGov.jmx`, `tests/performance/run_benchmark.php`, `tests/performance/benchmark_results.json`
  - `tests/uat/KichBanUAT_HongBangTest.php`, `tests/uat/KichBanUAT_SoGTVTTest.php`
  - `tests/Functional/FunctionalMatrixTest.php`, `tests/run.php`
  - `app/Core/Response.php`, `app/Core/ErrorHandler.php`, `app/Services/ReportingService.php`
- **Kế hoạch cho Phiên 16:**
  - Thực hiện thành công xuất sắc toàn bộ nội dung Phiên 16/16.

---

### PHIÊN 16: CLEAN CODE, HARDENING & BÀN GIAO TOÀN DIỆN DỰ ÁN
- **Thời gian hoàn thành:** 2026-10-08
- **Căn cứ thực hiện:** Báo cáo Kiến trúc Tổng thể & Tiêu chuẩn Bàn giao Sản phẩm Phần mềm.
- **Công việc đã thực hiện:**
  1. **Rà soát Clean Code & Cú pháp Toàn diện:**
     - Xây dựng công cụ kiểm tra cú pháp tự động `tests/check_syntax.php`.
     - Thực hiện `php -l` quét toàn bộ **250 tệp PHP** trong hệ thống $\rightarrow$ **0 lỗi cú pháp (100% hợp lệ và sạch tuyệt đối)**.
     - Loại bỏ toàn bộ debug (`var_dump`, `print_r`, `console.log` thừa), cấu trúc thư mục tuân thủ tuyệt đối quy tắc Master Context.
  2. **Tăng cường Bảo mật (Hardening):**
     - Xây dựng kịch bản SQL phân quyền tối thiểu `database/hardening_user.sql`: Tạo tài khoản ứng dụng `hpegov_app` chỉ có quyền `SELECT, INSERT, UPDATE, EXECUTE`; quyền `DELETE` bị cấm trên mọi bảng nghiệp vụ và bảng kiểm toán, chỉ được cấp trên bảng kỹ thuật dọn dẹp `gioi_han_truy_cap`.
     - Gia cố tệp `.htaccess` tại thư mục gốc: Chặn đứng truy cập trực tiếp vào `app/`, `storage/`, `database/`, `cron/`, `docs/`, `.git/`, `.env*`, `composer.*`. Đã kiểm chứng curl trả về `HTTP/1.1 403 Forbidden`.
     - Lập bảng Ma trận Route $\times$ Middleware $\times$ Quyền hạn chi tiết tại `docs/ROUTE_MATRIX.md` bao phủ toàn bộ Cổng DVC, Khu vực Quản trị và Cổng mở REST API `/api/v1`.
  3. **Đối chiếu Đồng nhất 100% với Báo cáo Kiến trúc PDF:**
     - Hoàn thiện tài liệu ma trận truy vết `docs/00_TRUY_VET_BAO_CAO.md`: Đối soát toàn bộ các mục từ Mở đầu, Chương 1 đến Chương 4 (§1.1 – §1.4, §2.1 – §2.3, §3.1 – §3.4, §4.1 – §4.4) với tệp tin và chức năng thực tế trong mã nguồn; giải trình chi tiết các giải pháp kỹ thuật hợp lý hóa môi trường.
  4. **Bộ Tài liệu Bàn giao Hoàn chỉnh:**
     - `README.md`: Hướng dẫn tổng quan, yêu cầu hệ thống, 5 bước cài đặt chi tiết trên XAMPP, bảng tài khoản thử nghiệm 7 vai trò, cấu trúc mã nguồn.
     - Bộ 7 tài liệu Hướng dẫn sử dụng chuyên sâu:
       * `docs/10_HDSD_CONG_DAN.md`: Hướng dẫn Công dân & Doanh nghiệp (VNeID, E-Form, Tra cứu QR, Kho dữ liệu, SIPAS).
       * `docs/10_HDSD_CB_MOT_CUA.md`: Hướng dẫn Cán bộ Một cửa (Quét QR CCCD C06, Số hóa NĐ 107, Ký số hóa, In giấy hẹn).
       * `docs/10_HDSD_CHUYEN_VIEN.md`: Hướng dẫn Chuyên viên thụ lý (Kiểm soát SLA, Phối hợp liên sở, Bổ sung tạm dừng SLA, Trình duyệt).
       * `docs/10_HDSD_LD_PHONG.md`: Hướng dẫn Lãnh đạo phòng (Thẩm định chuyên môn, Ký số duyệt bước 1 `DUYET_BUOC_1`).
       * `docs/10_HDSD_LD_CO_QUAN.md`: Hướng dẫn Lãnh đạo cơ quan (Ký số SmartCA `PHAT_HANH`, Ký lô, Ký Mẫu 04 trễ hạn, IOC).
       * `docs/10_HDSD_VAN_THU.md`: Hướng dẫn Văn thư số (Cấp số sổ văn bản đi tự động, Đóng dấu số cơ quan `DONG_DAU_CO_QUAN`).
       * `docs/10_HDSD_SYSADMIN.md`: Hướng dẫn Quản trị viên (RBAC, Wizard ETL 4 bước, 9 Cổng tích hợp, Audit WORM, Sao lưu DR Site).
     - `docs/11_TRIEN_KHAI_PRODUCTION.md`: Kiến trúc mạng 3 vùng (DMZ, App Zone, Data Zone), Cụm HA Keepalived + HAProxy, Cụm CSDL Replication Master-Replica failover < 30s, Cấu hình mẫu NGINX / PHP-FPM, và Lộ trình 3 giai đoạn theo Báo cáo.
  5. **Kiểm thử Toàn diện & Bản Phát hành Chính thức:**
     - Chạy lại toàn bộ test suite CLI: **124/124 tests PASSED 100% (0 failed)**.
     - Gắn tag phiên bản phát hành chính thức: `git tag v1.0.0`.
---

### PHIÊN 1 (CHUYÊN ĐỀ): KIẾN TRÚC & CƠ SỞ DỮ LIỆU CHUẨN HÓA (UTF-8, MDM & INITIALIZATION ENGINE)
- **Thời gian hoàn thành:** 2026-10-08
- **Căn cứ thực hiện:** Báo cáo Kiến trúc §1.2, §4.1, §4.2; NĐ 61/2018; TT 01/2018; QCVN 102:2016.
- **Công việc đã thực hiện:**
  1. **Rà soát & Hoàn thiện Schema (`database/schema.sql`):**
     - Đảm bảo 100% bảng (57 bảng vật lý InnoDB) và cột văn bản thiết lập Charset `utf8mb4` và Collation `utf8mb4_unicode_ci`.
     - Duy trì 6 Triggers bảo vệ nguyên tắc bất biến: Chặn `DELETE` vật lý trên `ho_so`, `ho_so_tai_lieu`, `ho_so_luan_chuyen`, `chu_ky_so`; chặn `UPDATE/DELETE` trên `nhat_ky_kiem_toan` (mô phỏng kho WORM).
     - Duy trì 3 Views chỉ đạo điều hành: `v_ho_so_tong_hop`, `v_ty_le_dung_han_don_vi`, `v_ho_so_qua_han`.
     - Tinh chỉnh ràng buộc cột `ho_so_tai_lieu_id` trên bảng `chu_ky_so` sang `INT NULL` kèm `ON DELETE SET NULL` để hỗ trợ ký số linh hoạt cho cả tài liệu độc lập lẫn văn bản xuất khẩu.
  2. **Bảo đảm Danh mục dùng chung (MDM) & Cây Tổ chức:**
     - `dm_dvhc`: Chuẩn TCTK (Tỉnh, 15 Quận/Huyện/TP, Xã/Phường mẫu TP. Hải Phòng).
     - `don_vi`: Cây tổ chức cơ quan đa cấp có Materialized Path (`/1/10/30/`) phục vụ lọc Data Scope.
     - `thu_tuc_hanh_chinh`: Mã TTHC quốc gia, lĩnh vực, mức độ DVC, thời hạn giờ làm việc.
     - `dm_trang_thai`: 6 trạng thái chuẩn Thông tư 01/2018/TT-VPCP và các trạng thái mở rộng.
  3. **Xây dựng Script Khởi tạo CSDL Thông minh (`database/init_db.php`):**
     - Tự động kiểm tra và tạo database `hp_egov` với `utf8mb4` / `utf8mb4_unicode_ci`.
     - Bộ phân tích cú pháp SQL Parser tự động xử lý các khối `DELIMITER $$` / `DELIMITER ;` của Stored Triggers, loại bỏ lỗi cú pháp khi nạp qua PHP PDO.
     - Nạp trực tiếp `database/schema.sql`, `database/seed.sql`, `mau_thong_bao`, `Session14Seeder.php` qua PDO UTF-8, triệt tiêu vĩnh viễn lỗi Mojibake từ shell redirect Windows.
     - Tự động đồng bộ chứng thư số RSA từ `storage/keys/` vào bảng `chung_thu_so` và quét SLA định kỳ ban đầu.
  4. **Xây dựng Script Kiểm thử Bảng mã & Toàn vẹn CSDL (`tests/test_database_encoding.php`):**
     - Lấy mẫu ngẫu nhiên 40 chuỗi tiếng Việt từ `don_vi`, `thu_tuc_hanh_chinh`, `nguoi_dung`, `ho_so`.
     - Kiểm tra chuỗi hợp lệ UTF-8, phát hiện ký tự rác Mojibake, nhận diện tiếng Việt có dấu chuẩn.
     - Kiểm thử kích hoạt trigger `tg_hoso_prevent_delete` (SQLSTATE 45000: cấm xóa vật lý hồ sơ hành chính).
     - Kết quả kiểm thử: **40/40 chuỗi tiếng Việt chuẩn 100%, 0 lỗi phông, Trigger hoạt động hoàn hảo**.
  5. **Kiểm thử Toàn diện & Rà soát Cú pháp:**
     - Bộ test suite hệ thống: **124/124 tests PASSED 100% (0 failed)**.
     - Kiểm tra cú pháp toàn dự án: **253/253 files PHP sạch 100%**.
- **Tệp tin đã tạo/cập nhật:**
  - `database/init_db.php` (Mới)
  - `tests/test_database_encoding.php` (Mới)
  - `database/schema.sql` (Cập nhật)
  - `docs/PROGRESS.md` (Cập nhật)

---

### PHIÊN 2 (CHUYÊN ĐỀ): LÕI NGHIỆP VỤ & QUY TRÌNH HỒ SƠ (CORE BUSINESS LOGIC & WORKFLOW ENGINE)
- **Thời gian hoàn thành:** 2026-10-08
- **Căn cứ thực hiện:**
  - Quyết định số 102/2016/QĐ-BTTTT (QCVN 102:2016/BTTTT về cấu trúc mã hồ sơ thủ tục hành chính).
  - Nghị định số 61/2018/NĐ-CP & Thông tư số 01/2018/TT-VPCP (Quy trình tiếp nhận, luân chuyển hồ sơ, Giấy xin lỗi Mẫu số 04).
  - Nguyên tắc đặc quyền tối thiểu (PoLP - Principle of Least Privilege) & Ma trận RBAC 7 vai trò.
- **Công việc đã hoàn thiện & kiểm chứng:**
  1. **Mã hồ sơ chuẩn Quốc gia (QCVN 102:2016/BTTTT) & Khóa bi quan (`app/Services/MaHoSoGenerator.php`):**
     - Cấu trúc chuẩn xác 4 phần: `{Mã cơ quan}.{YYMMDD}-{Mã TTHC 4 số}-{STT 6 số}` (Ví dụ: `000.04.15.H21-261008-0042-000123`).
     - Cơ chế chống trùng tuyệt đối: Dùng Pessimistic Row Lock (`SELECT gia_tri_hien_tai FROM bo_dem_ma_ho_so WHERE ... FOR UPDATE`) trong InnoDB transaction. Đảm bảo 100% không trùng mã khi hàng nghìn hồ sơ nộp đồng thời trong cùng một giây.
  2. **Động cơ SLA Chuẩn Công vụ (`app/Services/SlaEngine.php`):**
     - Giờ hành chính chuẩn: Sáng 07:30 - 11:30, Chiều 13:30 - 17:30 (8.0 giờ làm việc/ngày).
     - Loại trừ tự động: Khoảng thời gian đêm, Thứ Bảy, Chủ Nhật và Danh mục các ngày Nghỉ Lễ Quốc gia (`dm_ngay_nghi`).
     - Quy tắc nộp sau 17:00: Tự động chuyển thời điểm bắt đầu tính hạn sang 07:30 sáng ngày làm việc kế tiếp.
     - Cơ chế tạm dừng SLA (`pauseSla` / `resumeSla`): Khi trạng thái hồ sơ chuyển sang "Chờ bổ sung giấy tờ" hoặc "Lấy ý kiến phối hợp liên ngành", bộ đếm SLA tự động tạm dừng và khấu trừ thời gian gián đoạn (`so_gio_tam_dung`) khi kích hoạt lại.
     - Cảnh báo trực quan 3 màu SLA:
       + **XANH**: Thời gian xử lý còn lại > 25% tổng thời hạn (hoặc thời gian đã dùng <= 75%).
       + **VÀNG**: Thời gian xử lý còn lại <= 25% tổng thời hạn hoặc thời gian còn lại <= 24 giờ làm việc.
       + **ĐỎ**: Đã quá hạn cam kết (Quá hạn SLA). Tự động kích hoạt cờ khóa chuyển bước thông thường (`SLA_BREACH_LOCKED`), bắt buộc Lãnh đạo cơ quan ban hành "Văn bản xin lỗi và hẹn lại ngày trả kết quả" (Mẫu số 04 - TT 01/2018/TT-VPCP) mới được mở khóa hoàn thành.
  3. **Quy trình luân chuyển hồ sơ & Khóa vi phạm SLA (`app/Services/WorkflowEngine.php`):**
     - Quản lý toàn diện vòng đời hồ sơ hành chính từ Tiếp nhận -> Thụ lý -> Thẩm tra -> Phê duyệt ký số -> Văn thư số cấp số đóng dấu -> Trả kết quả.
     - Tích hợp kiểm tra tính toàn vẹn tệp đính kèm (`verifyFileIntegrity`) dựa trên mã băm SHA-256.
     - Thực thi quy tắc khóa `SLA_BREACH_LOCKED` khi hồ sơ SLA ĐỎ mà chưa có bản ghi `van_ban_xin_loi` ở trạng thái `DA_KY`. Tự động mở khóa ngay khi có Mẫu 04 đã ký duyệt hợp lệ.
  4. **Ma trận phân quyền RBAC đa cấp & Nguyên tắc PoLP (`app/Services/AuthService.php`):**
     - 7 vai trò chuẩn: (1) Công dân/Doanh nghiệp (`CONG_DAN`), (2) Cán bộ Một cửa tiếp nhận (`MOT_CUA`), (3) Chuyên viên thẩm định thụ lý (`CHUYEN_VIEN`), (4) Lãnh đạo Phòng chuyên môn (`LANH_DAO_PHONG`), (5) Lãnh đạo Cơ quan phê duyệt/ký số (`LANH_DAO_SO`), (6) Văn thư số đóng dấu/cấp số (`VAN_THU`), (7) Quản trị viên hệ thống (`SYSADMIN`).
     - Nguyên tắc phân tách quyền hạn (PoLP): Quản trị viên SYSADMIN tuyệt đối không có quyền thụ lý hồ sơ, ký duyệt hoặc sửa đổi nội dung nghiệp vụ; chỉ quản trị tài khoản, danh mục và giám sát kỹ thuật. Mọi nỗ lực can thiệp nghiệp vụ đều bị chặn đứng và ghi nhật ký kiểm toán.
  5. **Bộ Unit Test Chuyên biệt Phiên 2 (`tests/Unit/Phien2LoiNghiepVuTest.php`):**
     - `testSinh50MaHoSoDongThoiKhongTrung`: Giả lập sinh 50 mã hồ sơ đồng thời; kiểm tra cấu trúc 4 phần chuẩn QCVN 102 và kiểm tra tính duy nhất tuyệt đối (50/50 mã khác nhau).
     - `testTinhHanNgayHenTraQuaLe30Thang4VaTetNguyenDan`: Kiểm tra tính hạn ngày hẹn trả nhảy qua kỳ nghỉ Tết Nguyên Đán 2026 (7 ngày nghỉ Tết + cuối tuần) và kỳ nghỉ lễ 30/4 - 1/5 (4 ngày nghỉ lễ + cuối tuần), đồng thời kiểm tra tính toán thời hạn theo giờ làm việc chính xác đến từng phút (`09:30:00` sáng ngày làm việc đầu tiên sau kỳ nghỉ).
     - `testKhoaChuyenBuocKhiSlaDoVaMoKhoaBangMau04`: Tạo hồ sơ SLA ĐỎ, xác nhận lệnh chuyển bước bị khóa chặt với mã lỗi `SLA_BREACH_LOCKED`; sau khi tạo Văn bản xin lỗi Mẫu 04 đã ký, quy trình tự động mở khóa cho phép chuyển bước thành công.
     - `testChanSysadminCanThiepKyDuyetVaThuLy`: Kiểm tra tài khoản SYSADMIN hoàn toàn không có quyền trong danh mục quyền thụ lý/ký số; thực thi ký số với vai trò SYSADMIN bị chặn ngay lập tức bởi ngoại lệ miền (DomainException).
  6. **Kết quả kiểm thử toàn hệ thống:**
     - Toàn bộ test suite: **128/128 tests PASSED (100% Passed, 0 Failed)**.
     - Cú pháp toàn dự án: **254/254 tệp PHP sạch 100% (0 syntax error)**.
- **Tệp tin đã tạo/cập nhật:**
  - `tests/Unit/Phien2LoiNghiepVuTest.php` (Mới)
  - `docs/PROGRESS.md` (Cập nhật)

---

### PHIÊN 3 (CHUYÊN ĐỀ): GIAO DIỆN CÔNG VỤ & CHUẨN HÓA NỘI DUNG (WORKSTATION UI & DESIGN SYSTEM)
- **Thời gian hoàn thành:** 2026-10-08
- **Căn cứ thực hiện:**
  - Tiêu chuẩn thiết kế giao diện hành chính công và Mạng truyền số liệu chuyên dùng (TSLCD) cấp II (100% tài nguyên nội bộ, không phụ thuộc CDN bên ngoài).
  - Nghị định số 30/2020/NĐ-CP của Chính phủ về công tác văn thư (Thể thức, kỹ thuật trình bày văn bản hành chính & khổ giấy A4).
  - Thông tư số 01/2018/TT-VPCP của Văn phòng Chính phủ (Hệ thống biểu mẫu nghiệp vụ: Mẫu 01 Giấy hẹn, Mẫu 02 Phiếu yêu cầu bổ sung, Mẫu 04 Giấy xin lỗi, Mẫu 05 Phiếu kiểm soát).
- **Công việc đã hoàn thiện & kiểm chứng:**
  1. **Design System & Tiêu chuẩn Phông chữ Công vụ:**
     - Tích hợp bộ phông chữ `Be Vietnam Pro` tiếng Việt hiện đại định dạng WOFF2 cục bộ trong `public/assets/fonts/`, phục vụ chuẩn chỉ trên mạng TSLCD không Internet.
     - Bảo đảm 100% View template có `<meta charset="UTF-8">`, bổ sung cache buster `?v=2` trên toàn bộ liên kết CSS/JS để triệt tiêu bộ nhớ đệm trình duyệt cũ.
     - Bảng màu công vụ chuẩn mực trong `public/assets/css/tokens.css`:
       + Primary: Xanh dương hành chính uy tín (`#0b3a75` - `#1e40af`).
       + Success: Xanh ngọc hoàn thành (`#15803d` - `#16a34a`).
       + Warning: Vàng hổ phách cảnh báo SLA (`#b45309` - `#d97706`).
       + Danger: Đỏ thẩm quyền / Quá hạn SLA (`#b91c1c` - `#dc2626`).
       + Background dịu mắt: `#f8fafc`, bo góc nhẹ, viền tinh tế, chống mỏi mắt cho cán bộ ngồi máy 8 tiếng.
  2. **Cấu trúc Bàn làm việc Công chức (Workstation UI):**
     - **Header / Topbar (`app/Views/partials/topbar.php`):** Hiển thị rõ Tên cơ quan chủ quản (`UBND TP. HẢI PHÒNG`), Đơn vị bộ phận trực thuộc, Họ tên cán bộ, Huy hiệu vai trò nghiệp vụ (`MOT_CUA`, `CHUYEN_VIEN`...), Chuông thông báo công việc cần xử lý gấp kèm số đếm động.
     - **Sidebar Phân quyền RBAC (`app/Views/partials/sidebar.php`):** Tự động truy vấn số lượng hồ sơ chờ xử lý thời gian thực và gắn Badge số đếm nổi bật cho từng mục thực đơn tương ứng với vai trò đăng nhập.
     - **Khu vực Thống kê KPI:** 4 thẻ KPI trực quan (`.card-dashboard`): Hồ sơ mới tiếp nhận, Đang giải quyết trong hạn, Cảnh báo sắp quá hạn Vàng, Quá hạn Đỏ.
     - **Hệ thống Grid & Lưới dữ liệu (`public/assets/css/components.css`):** Bổ sung Workstation Grid (`.row`, `.col-md-*`, `.g-2`, `.g-3`), tab pills chuyển đổi trạng thái hồ sơ, bộ lọc đa tiêu chí, phân trang và Badge SLA màu sắc rõ ràng.
  3. **Chuẩn hóa Biểu mẫu Thể thức Văn bản (NĐ 30/2020/NĐ-CP & TT 01/2018):**
     - **Mẫu số 01 Giấy tiếp nhận & Hẹn trả (`app/Views/admin/mot_cua/in_giay_hen.php`):** Trình bày chuẩn khung A4, Quốc hiệu, Tiêu ngữ, Tên cơ quan, Mã số hồ sơ, Mã QR tra cứu tiến độ tự động, Bảng thành phần hồ sơ và cam kết thời gian.
     - **Chế độ In ấn Chuyên nghiệp (`public/assets/css/print.css` & `@media print`):** Căn chỉnh lề trang A4 chuẩn (Lề trên/dưới 20mm, lề trái 30mm, lề phải 15mm), tự động ẩn toàn bộ header, sidebar, nút bấm khi in tài liệu.
     - **Route Aliases Thuận tiện (`routes/web.php`):** Hỗ trợ đường dẫn thân thiện `/admin/thu-ly/hop-viec` và `/admin/ho-so/{id}/in-phieu-hen`.
     - Sửa lỗi cú pháp LaTeX `$\ge 300\text{ DPI}$` sang ký tự chuẩn `≥ 300 DPI` trong view tiếp nhận.
  4. **Kiểm chứng Thực tế bằng Tự động hóa Trình duyệt (Chrome CDP):**
     - Xây dựng kịch bản `tests/screenshot_runner.js` điều khiển Chrome qua CDP WebSocket, đăng nhập và chụp ảnh 4 màn hình trọng điểm làm minh chứng:
       + `phien3_01_dashboard_workstation.png`: Dashboard bàn làm việc công chức với 4 thẻ KPI và bảng hồ sơ.
       + `phien3_02_hop_viec_thu_ly.png`: Hộp việc thụ lý chuyên viên với 6 tabs phân loại hồ sơ.
       + `phien3_03_mot_cua_tiep_nhan.png`: Màn hình tiếp nhận một cửa quét QR CCCD chip và đính kèm giấy tờ.
       + `phien3_04_in_phieu_hen_mau01.png`: Mẫu in Giấy hẹn Mẫu 01 chuẩn A4 kèm Mã QR tra cứu.
     - **Kiểm tra Browser Console:** 100% sạch (0 lỗi JavaScript, 0 cảnh báo thiếu file CSS/Font).
     - **Kiểm thử Toàn hệ thống:** 128/128 PHPUnit tests PASSED (100%).
- **Tệp tin đã tạo/cập nhật:**
  - `public/assets/css/tokens.css` (Cập nhật màu sắc công vụ)
  - `public/assets/css/components.css` (Bổ sung Workstation UI, Grid, KPI cards, Tabs)
  - `app/Views/layouts/admin.php`, `auth.php`, `portal.php`, `print.php` (Nhúng Be Vietnam Pro WOFF2, cache buster ?v=2)
  - `app/Views/partials/topbar.php` (Cập nhật cơ quan chủ quản, chức danh, chuông thông báo)
  - `app/Views/partials/sidebar.php` (Gắn badge số lượng hồ sơ thời gian thực)
  - `app/Views/admin/mot_cua/in_giay_hen.php` (Chuẩn hóa Mẫu số 01 TT 01/2018 và QR tra cứu)
  - `app/Views/admin/mot_cua/tiep_nhan.php` (Sửa lỗi hiển thị DPI)
  - `app/Controllers/Admin/MotCuaController.php` (Mở quyền xem in giấy hẹn cho cán bộ công chức)
  - `routes/web.php` (Thêm route aliases)
  - `tests/screenshot_runner.js` (Script tự động hóa chụp ảnh kiểm chứng qua CDP)
  - `docs/PROGRESS.md` (Cập nhật)












