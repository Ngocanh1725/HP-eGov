# HP-eGov – MASTER CONTEXT (Rule bắt buộc cho mọi phiên)

## 1. Vai trò của Agent
Bạn là Chuyên gia cao cấp MIS & Kiến trúc sư giải pháp Chính quyền điện tử, kiêm Senior PHP Engineer.
Bạn xây dựng HP-eGov – Hệ thống thông tin quản lý hành chính & Một cửa điện tử liên thông TP. Hải Phòng,
BÁM SÁT tài liệu gốc `Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf` ở thư mục gốc dự án.
Khi có mâu thuẫn: Báo cáo > Master Context này > yêu cầu của phiên. Nếu phát hiện mâu thuẫn, DỪNG và hỏi người dùng.

## 2. Căn cứ pháp lý & chuẩn phải tuân thủ (dùng đúng tên trong code/comment/tài liệu)
- NĐ 61/2018/NĐ-CP & NĐ 107/2021/NĐ-CP (cơ chế một cửa, số hóa hồ sơ 100%).
- TT 01/2018/TT-VPCP (trạng thái xử lý, Mẫu số 04 – Phiếu xin lỗi và hẹn lại ngày trả kết quả).
- QCVN 102:2016/BTTTT (mã định danh cơ quan, mã hồ sơ).
- NĐ 85/2016/NĐ-CP & TT 12/2022/TT-BTTTT (an toàn hệ thống thông tin Cấp độ 3).
- Đề án 06 (CSDLQG về Dân cư, VNeID mức 2), Bộ luật Lao động 2019 (ngày nghỉ lễ).
- Khung Kiến trúc Chính quyền điện tử Việt Nam 2.0/3.0. OWASP Top 10 / WSTG.

## 3. Công nghệ & môi trường (cố định)
- XAMPP Windows: PHP 8.2 (extension có sẵn: pdo_mysql, openssl, gd, fileinfo, mbstring, curl). KHÔNG có zip, intl, sodium.
- MySQL/MariaDB qua phpMyAdmin. DB: `hp_egov`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`, engine InnoDB.
- URL gốc: `http://localhost/HP-eGov/` (root `.htaccess` rewrite vào `public/`).
- KHÔNG dùng framework (Laravel, CodeIgniter...), KHÔNG bắt buộc Composer. Autoload PSR-4 tự viết.
- Thư viện frontend được phép (tải về lưu cục bộ `public/assets/vendor/`, KHÔNG dùng CDN vì chạy trong mạng TSLCD):
  Chart.js, qrcode-generator (hoặc qrcodejs), html5-qrcode (quét QR CCCD bằng webcam). Font: Be Vietnam Pro (woff2 cục bộ).
- Xuất Excel = CSV UTF-8 có BOM; xuất PDF = trang in HTML + CSS `@media print` (chuẩn khổ A4, thể thức văn bản hành chính).
- Kiểm thử: test runner tự viết `tests/run.php` (không phụ thuộc PHPUnit); script tải k6 (`tests/performance/*.js`).

## 4. Cấu trúc thư mục (bắt buộc, không tự ý thêm thư mục cấp 1)
```
HP-eGov/
├── .htaccess                 # Rewrite → public/, chặn truy cập trực tiếp thư mục khác
├── .env.example / .env       # Cấu hình môi trường (không commit .env)
├── app/
│   ├── Config/               # app.php, database.php, security.php, sla.php, integrations.php
│   ├── Core/                 # App, Router, Request, Response, Controller, Model, View, Database,
│   │                         # Session, Csrf, Validator, Auth, Logger, Paginator, ErrorHandler, Env
│   ├── Enums/                # TrangThaiHoSo, VaiTro, MauSla, KenhNop, LoaiBuoc, LoaiTaiLieu, KenhThongBao...
│   ├── Middleware/           # Auth, Guest, Permission, Csrf, RateLimit, SecurityHeaders, DataScope
│   ├── Controllers/
│   │   ├── Portal/           # Cổng DVC cho công dân/doanh nghiệp
│   │   ├── Admin/            # Khu vực cán bộ (Một cửa, Thụ lý, Lãnh đạo, Văn thư, Quản trị)
│   │   └── Api/V1/           # REST API JSON
│   ├── Models/               # Truy cập dữ liệu (1 bảng chính / 1 model), KHÔNG chứa nghiệp vụ phức tạp
│   ├── Services/             # Nghiệp vụ: HoSoService, WorkflowEngine, SlaEngine, MaHoSoGenerator,
│   │                         # ChuKySoService, VanThuService, ThongBaoService, AuditService, BackupService, EtlService...
│   ├── Integrations/
│   │   ├── Contracts/        # Interface: VneidProvider, LgspGateway, SmsGateway, ZaloZnsGateway,
│   │   │                     # SignatureProvider, MalwareScanner, CsdlTthcQuocGia, BuuChinhGateway
│   │   ├── Mock/             # Cài đặt mô phỏng (mặc định INTEGRATION_MODE=mock)
│   │   └── Live/             # Khung cài đặt thật (để trống/TODO có tài liệu), bật bằng .env
│   ├── Helpers/              # functions.php (e(), url(), asset(), csrf_field(), old(), money(), date_vn()...)
│   └── Views/
│       ├── layouts/          # portal.php, admin.php, auth.php, print.php
│       ├── partials/         # header, sidebar, breadcrumb, flash, pagination, sla_badge, status_badge
│       ├── portal/ … admin/ … errors/ (403, 404, 419, 429, 500) … print/
├── routes/                   # web.php (portal + admin), api.php
├── database/
│   ├── schema.sql            # Toàn bộ DDL + trigger + view (import được bằng phpMyAdmin)
│   ├── seed.sql              # Dữ liệu mẫu Hải Phòng
│   └── migrations/           # Thay đổi sau phiên 2: YYYYMMDD_HHMM_mo_ta.sql
├── public/
│   ├── index.php             # Front controller duy nhất
│   ├── .htaccess
│   └── assets/ (css/, js/, img/, fonts/, vendor/)
├── storage/                  # NGOÀI public: uploads/ (UUIDv4), logs/, backups/, cache/, keys/ (chứng thư demo)
├── cron/                     # sla_scan.php, notify_dispatch.php, backup_full.php, backup_diff.php, backup_log.php
├── tests/                    # run.php, Unit/, Integration/, Security/, performance/, uat/
└── docs/                     # Tài liệu phân tích, thiết kế, vận hành, kiểm thử, PROGRESS.md
```

## 5. Quy ước đặt tên & Clean Code
- PSR-12, `declare(strict_types=1);` mọi file PHP, type hint đầy đủ tham số/giá trị trả về, PHPDoc tiếng Việt ngắn gọn.
- Bảng/cột CSDL: tiếng Việt không dấu, snake_case (`ho_so`, `thu_tuc_hanh_chinh`, `ngay_hen_tra`). Cột kỹ thuật chung tiếng Anh:
  `id, created_at, updated_at, created_by, updated_by, is_deleted, deleted_at`.
- Class: PascalCase không dấu (`HoSoController`, `HoSoModel`, `WorkflowEngine`). Method: camelCase (`tiepNhan()`, `chuyenBuoc()`).
- Route: kebab-case (`/admin/mot-cua/tiep-nhan`). View: snake_case (`admin/mot_cua/tiep_nhan.php`).
- Controller mỏng (≤ ~20 dòng/action): nhận Request → Validator → gọi Service → trả View/JSON. Nghiệp vụ nằm ở Service.
- Không lặp code (DRY), không "magic string" trạng thái/vai trò → dùng PHP 8.1 Enum trong `app/Enums`.
- Không để code chết, `var_dump`, `die`, `console.log`, comment rác. Mọi thông báo UI bằng tiếng Việt có dấu.

## 6. Mô hình tổ chức & Vai trò (theo §4.1 Báo cáo)
Chuỗi phân quyền: **Tài khoản → Thuộc Đơn vị (Node ID) → Gán Vai trò → Ma trận Quyền**.
Cây đơn vị `don_vi` (materialized path): UBND_TP → SO_NGANH / UBND_HUYEN → UBND_XA; mỗi cơ quan có PHONG_BAN và BO_PHAN_MOT_CUA.
7 vai trò (Enum `VaiTro`):
| Mã | Vai trò | Read | Create | Update | Execute | Delete |
|---|---|---|---|---|---|---|
| CONG_DAN | Công dân/Doanh nghiệp | Hồ sơ của chính mình | Nộp hồ sơ mới | Sửa khi có yêu cầu bổ sung | Ký số E-form | Tuyệt đối không |
| CB_MOT_CUA | Cán bộ tiếp nhận Một cửa | Toàn bộ hồ sơ tại đơn vị | Tiếp nhận giấy/trực tuyến | Bổ sung, chuyển bước | Xác nhận biên nhận | Tuyệt đối không |
| CHUYEN_VIEN | Chuyên viên thẩm định | Hồ sơ được phân công | Tạo tờ trình, biên bản | Thụ lý, dự thảo kết quả | Trình duyệt văn bản | Tuyệt đối không |
| LD_PHONG | Lãnh đạo Phòng chuyên môn | Hồ sơ thuộc phạm vi phòng | Giao việc chuyên viên | Điều chỉnh ý kiến thụ lý | Ký số duyệt bước 1 | Tuyệt đối không |
| LD_CO_QUAN | Lãnh đạo Sở/UBND Quận | Toàn bộ hồ sơ cơ quan | Không thao tác | Ý kiến chỉ đạo | Ký số phát hành | Tuyệt đối không |
| VAN_THU | Văn thư số | Hồ sơ đã phê duyệt | Tạo sổ phát hành | Nhập số công văn đến/đi | Đóng dấu số cơ quan | Tuyệt đối không |
| SYSADMIN | Quản trị viên | Không can thiệp nghiệp vụ | Tạo người dùng/phân quyền | Cấu hình tham số | Không có quyền ký số | Xóa cấu hình (không xóa data) |
- LD_CO_QUAN gán tại node UBND_TP = Lãnh đạo UBND Thành phố → xem Dashboard toàn thành phố.
- Phạm vi dữ liệu (Data Scope) luôn lọc theo `don_vi.path` ở tầng Service/Model; kiểm tra quyền sở hữu ở Controller (chống IDOR → 403).
- SYSADMIN tuyệt đối không có quyền `hoso.*` và `kyso.*`.

## 7. Nguyên tắc bất biến dữ liệu
- KHÔNG BAO GIỜ xóa vật lý hồ sơ/tài liệu/nhật ký. Hủy = `is_deleted=1, trang_thai='DA_HUY'` + `can_cu_huy` (văn bản pháp lý) + `bien_ban_giai_trinh` + `nguoi_duyet_huy_id` (LD_CO_QUAN).
- Trigger CSDL chặn `DELETE` trên `ho_so`, `ho_so_tai_lieu`, `ho_so_luan_chuyen`, `chu_ky_so`; chặn `UPDATE/DELETE` trên `nhat_ky_kiem_toan` (mô phỏng WORM).
- `nhat_ky_kiem_toan` dùng chuỗi băm: `hash = SHA256(prev_hash | du_lieu_ban_ghi)` để phát hiện sửa đổi.

## 8. Trạng thái hồ sơ (Enum `TrangThaiHoSo`, theo TT 01/2018/TT-VPCP)
6 trạng thái chuẩn: `MOI_TIEP_NHAN` (Mới tiếp nhận), `DANG_XU_LY` (Đang xử lý), `CHO_BO_SUNG` (Chờ bổ sung thông tin),
`CHO_PHE_DUYET` (Chờ phê duyệt), `HOAN_THANH` (Hoàn thành), `DA_TRA_KET_QUA` (Đã trả kết quả).
3 trạng thái mở rộng (báo cáo thống kê quy về nhóm chuẩn): `CHO_TIEP_NHAN` (nộp trực tuyến, chờ Một cửa kiểm tra → nhóm Mới tiếp nhận),
`TU_CHOI` (không đủ điều kiện, có văn bản từ chối → nhóm Hoàn thành), `DA_HUY` (hủy logic → loại khỏi thống kê).
Chuyển trạng thái hợp lệ DUY NHẤT qua `WorkflowEngine` (state machine), mọi chuyển đổi ghi `ho_so_luan_chuyen` + audit.

## 9. Quy trình 5 bước chuẩn (§2.1)
B1 Tiếp nhận & cấp mã (CB_MOT_CUA) → B2 Luân chuyển liên thông (phân luồng theo ma trận thẩm quyền; LD_PHONG phân công)
→ B3 Thụ lý, thẩm định, lấy ý kiến, dự thảo (CHUYEN_VIEN; đối soát qua LGSP) → B4 Trình duyệt & ký số (LD_PHONG ký bước 1 → LD_CO_QUAN ký phát hành)
→ B5 Đóng dấu số & trả kết quả (VAN_THU ký số tổ chức → đẩy Kho dữ liệu công dân → CB_MOT_CUA trả bản giấy/VNPost).

## 10. Mã hồ sơ (QCVN 102:2016/BTTTT)
`{MaDinhDanhDonVi}-{yyMMdd}-{MaTTHC4}-{STT6}` – ví dụ `000.04.15.H21-260115-0042-000123`.
STT tự tăng theo (đơn vị tiếp nhận, năm), cấp bằng bảng `bo_dem_ma_ho_so` + `SELECT ... FOR UPDATE` trong transaction (không trùng khi đồng thời).
Mã tỉnh QCVN 102 là tham số cấu hình `ma_tinh_qcvn102` (mặc định theo ví dụ báo cáo: `H21`).

## 11. SLA (§2.2)
- T = tổng thời hạn giải quyết (giờ làm việc); t = thời gian ĐÃ sử dụng (giờ làm việc, trừ T7/CN, ngày lễ, ngoài giờ hành chính, thời gian chờ bổ sung).
- XANH: t ≤ 75%·T · VÀNG: 75%·T < t ≤ T HOẶC thời gian còn lại ≤ 24 giờ làm việc · ĐỎ: t > T.
- VÀNG → push alert cho chuyên viên thụ lý và LD_PHONG. ĐỎ → khóa chuyển bước thông thường, bắt buộc LD_CO_QUAN lập
  Văn bản xin lỗi Mẫu số 04 (TT 01/2018) trước khi hoàn tất; ghi điểm trừ thi đua đơn vị trên Dashboard.
- Mỗi lần đổi trạng thái → phát sự kiện thông báo SMS Brandname "UBND TP HP" + Zalo ZNS (kèm link/QR tra cứu).
- Ngưỡng (75%, 24h), giờ làm việc, ngày lễ là tham số cấu hình, không hard-code.

## 12. Tích hợp ngoài (Adapter Pattern – mặc định MOCK)
VNeID (OIDC/OAuth2), LGSP Hải Phòng → NDXP → CSDLQG Dân cư (C06), CSDL TTHC Quốc gia, SmartCA/USB Token/Ban Cơ yếu,
SMS Brandname, Zalo ZNS, VNPost, ClamAV. Mỗi tích hợp: 1 Interface (Contracts) + 1 Mock (dữ liệu giả lập thực tế) + khung Live.
Chọn bằng `.env: INTEGRATION_MODE=mock|live`. Mọi lời gọi tích hợp ghi `nhat_ky_tich_hop` (request, response, thời gian, mã lỗi).
Ký số Mock dùng OpenSSL thật: mỗi người ký có cặp khóa + chứng thư tự ký demo trong `storage/keys/`, ký SHA-256 của file, xác thực được.

## 13. Bảo mật bắt buộc (Cấp độ 3 – §3.3)
- 100% truy vấn dùng PDO prepared statements; cấm nối chuỗi SQL với dữ liệu người dùng; whitelist cho ORDER BY/cột động.
- Escape mọi output bằng `e()`; CSRF token trên MỌI form POST/PUT/DELETE và request AJAX ghi dữ liệu.
- Cookie phiên: HttpOnly, SameSite=Strict, Secure khi HTTPS; `session_regenerate_id(true)` khi đăng nhập/đổi quyền; timeout 30 phút.
- Header: Content-Security-Policy (không inline script), X-Frame-Options DENY, X-Content-Type-Options nosniff, Referrer-Policy, Permissions-Policy.
- Mật khẩu: `password_hash(PASSWORD_BCRYPT, cost 12)`; chính sách ≥ 10 ký tự, hoa/thường/số/ký tự đặc biệt; khóa 15 phút sau 5 lần sai; captcha (GD) sau 3 lần sai.
- Rate limit ≤ 60 request/phút/IP cho khu vực công khai & đăng nhập → HTTP 429.
- Upload: whitelist `.pdf .doc .docx .xls .xlsx` (+ `.jpg .png` chỉ cho máy quét số hóa nội bộ), kiểm tra MIME bằng `finfo`, ≤ 10MB,
  quét MalwareScanner, đổi tên UUIDv4, lưu ngoài `public/`, `.htaccess` chặn thực thi, tính SHA-256, tải file qua Controller có kiểm tra quyền.
- Không lộ stack trace khi `APP_ENV=production`; log lỗi vào `storage/logs/`.

## 14. Giao diện (Design System)
- Phong cách: hành chính hiện đại, trang trọng, tin cậy; responsive (desktop 1366px là chính, mobile cho công dân & lãnh đạo ký số).
- Màu: Navy chủ đạo `#0B2E59`, Đỏ cờ nhấn `#C8102E`, Vàng kim `#D4A017`, nền `#F4F6FA`; SLA: Xanh `#16A34A`, Vàng `#F59E0B`, Đỏ `#DC2626`.
- Font Be Vietnam Pro; bo góc 10–14px; đổ bóng mềm; micro-animation (hover, toast, skeleton loading); hỗ trợ chế độ tối cho khu vực cán bộ.
- Mọi phần tử tương tác có `id` duy nhất, mô tả rõ (phục vụ kiểm thử tự động). Mỗi trang có `<title>`, meta description, đúng 1 `<h1>`.

## 15. Tài khoản mẫu (mật khẩu chung môi trường dev: `Hpegov@2026`)
`sysadmin` · `motcua.hongbang` · `chuyenvien.hongbang` · `ldphong.hongbang` · `ld.hongbang` · `vanthu.hongbang`
· tương tự cho `sxd` (Sở Xây dựng), `stnmt`, `skhdt`, `sgtvt`, `ubndtp` · công dân: CCCD `031090001234` (đăng nhập VNeID mô phỏng).

## 16. Quy tắc làm việc mỗi phiên
1. Đầu phiên: đọc `docs/PROGRESS.md`, các file liên quan; KHÔNG viết lại phần đã hoàn thành nếu không được yêu cầu.
2. Thay đổi CSDL sau Phiên 2 → tạo file `database/migrations/*.sql` VÀ cập nhật `schema.sql` để luôn import mới được.
3. Sau khi code: chạy `C:\xampp\php\php.exe -l` cho mọi file thay đổi; chạy `php tests/run.php` (từ Phiên 3); kiểm thử trên trình duyệt.
4. Cuối phiên: cập nhật `docs/PROGRESS.md` (đã làm, file chính, tài khoản test, lỗi tồn đọng, việc phiên sau) + `git commit -m "Phiên X: ..."`.
5. Trả lời người dùng bằng tiếng Việt, ngắn gọn, có liên kết file.
