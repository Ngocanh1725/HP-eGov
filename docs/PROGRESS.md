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
- **Kế hoạch cho Phiên 3:**
  - Chạy **Phiên 3/16 – Lõi MVC (Core Framework)**: Xây dựng nền tảng PHP 8.2 thuần chuẩn MVC, Router, Database Master/Replica PDO, Request, Response, Controller, Model, View, Csrf, Session, Validator, Logger, ErrorHandler và test runner tự viết `tests/run.php`.

