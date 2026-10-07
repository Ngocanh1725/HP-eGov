# BỘ PROMPT XÂY DỰNG HỆ THỐNG HP-eGov THEO PHIÊN (dùng trên Antigravity)

> **Đề tài:** Phân tích, thiết kế và xây dựng Hệ thống thông tin quản lý hành chính & Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov)
> **Tài liệu gốc (nguồn chuẩn duy nhất):** `Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf` (17 trang, 4 chương)
> **Công nghệ:** PHP 8.2 thuần, mô hình MVC tự xây (không dùng framework) · MySQL/MariaDB quản trị bằng phpMyAdmin (XAMPP) · HTML5 + Vanilla CSS + Vanilla JS
> **Môi trường đã kiểm tra:** PHP 8.2.12 (có `pdo_mysql`, `openssl`, `gd`, `fileinfo`, `mbstring`, `curl`; **không có** `zip`, `intl`, `sodium`) · Git 2.53 · **chưa có Composer** → hệ thống phải chạy được mà không cần thư viện ngoài.

---

## MỤC LỤC

- [PHẦN I – Hướng dẫn sử dụng bộ prompt](#phần-i--hướng-dẫn-sử-dụng-bộ-prompt)
- [PHẦN II – Ma trận truy vết Báo cáo → Phân hệ → Phiên](#phần-ii--ma-trận-truy-vết-báo-cáo--phân-hệ--phiên)
- [PHẦN III – MASTER CONTEXT (lưu thành rule của workspace)](#phần-iii--master-context)
- [PHẦN IV – Prompt từng phiên (Phiên 0 → Phiên 16)](#phần-iv--prompt-từng-phiên)
- [PHẦN V – Prompt tiện ích (sửa lỗi, tiếp tục phiên, rà soát đồng nhất)](#phần-v--prompt-tiện-ích)
- [PHẦN VI – Các điểm đã chuẩn hóa so với báo cáo](#phần-vi--các-điểm-đã-chuẩn-hóa-so-với-báo-cáo)

---

## PHẦN I – HƯỚNG DẪN SỬ DỤNG BỘ PROMPT

1. **Mỗi phiên = 1 cuộc hội thoại mới** trên Antigravity. Không gộp nhiều phiên vào một hội thoại (tránh tràn ngữ cảnh làm agent "quên" quy ước).
2. **Phiên 0 chạy đầu tiên, chỉ một lần**: agent tạo file rule `.agents/rules/hp-egov.md` từ PHẦN III. Từ đó, Antigravity **tự nạp** bối cảnh này vào mọi phiên sau → đảm bảo đồng nhất với báo cáo.
3. **Chạy tuần tự** từ Phiên 1 → 16. Mỗi phiên phụ thuộc kết quả phiên trước.
4. **Nên bật `/plan`** ở đầu mỗi phiên lớn (3, 5, 7–11) để agent lập kế hoạch, bạn duyệt rồi mới code.
5. **Cuối mỗi phiên**, agent bắt buộc cập nhật `docs/PROGRESS.md` (nhật ký phiên) và commit Git. Phiên sau đọc file này để nối tiếp.
6. **Kiểm tra trước khi sang phiên mới**: mở `http://localhost/HP-eGov/`, đăng nhập bằng tài khoản mẫu, kiểm tra "Tiêu chí hoàn thành" của phiên.
7. Nếu phiên bị ngắt giữa chừng → dùng **Prompt P-01 (Tiếp tục phiên)** ở PHẦN V.

```
Phiên 0  Thiết lập bối cảnh & rule               Phiên 9  Workflow engine – Thụ lý & Liên thông
Phiên 1  Phân tích & Thiết kế (Chương 1)         Phiên 10 Phê duyệt – Ký số – Văn thư số
Phiên 2  Cơ sở dữ liệu phpMyAdmin + dữ liệu mẫu  Phiên 11 SLA Engine & Thông báo đa kênh
Phiên 3  Lõi MVC (Core framework)                Phiên 12 Dashboard chỉ đạo điều hành & Báo cáo
Phiên 4  Design System & Layout giao diện        Phiên 13 Quản trị & Vận hành (Chương 2.3, 4)
Phiên 5  Xác thực & Phân quyền RBAC              Phiên 14 Chuyển đổi dữ liệu ETL, Tích hợp & API
Phiên 6  Danh mục dùng chung (MDM) & Quy trình   Phiên 15 Kiểm thử toàn diện (Chương 3)
Phiên 7  Cổng Dịch vụ công (công dân)            Phiên 16 Clean code, Hardening & Bàn giao
Phiên 8  Bộ phận Một cửa (Tiếp nhận – Trả KQ)
```

---

## PHẦN II – MA TRẬN TRUY VẾT BÁO CÁO → PHÂN HỆ → PHIÊN

| Mục báo cáo | Nội dung | Phân hệ | Phiên |
|---|---|---|---|
| Mở đầu §1–2 | Liên thông dọc/ngang, vòng đời hồ sơ, chỉ tiêu SLA (≥98%, ≥80%, 100%, ≥92%) | PH03, PH08 | 1, 9, 12 |
| 1.1 | TSLCD, LGSP (ESB) | PH14 (Adapter LGSP) | 14 |
| 1.2 | Kiến trúc 3 vùng mạng, HA, Master/Replica | Tài liệu triển khai + `Database` read/write split | 3, 16 |
| 1.3.A | ETL: Extract → Staging → Transform → Load & Verify (SHA-256) | PH14 | 14 |
| 1.3.B | SSO VNeID (OIDC), Auto-fill E-Form, xác thực C06 qua LGSP | PH01, PH05-Auth, PH03 | 5, 7, 9 |
| 1.4 | Đào tạo 4 nhóm đối tượng; số hóa NĐ 107/2021 | PH15, PH02 | 8, 13 |
| 2.1 | Quy trình 5 bước; mã hồ sơ QCVN 102:2016 | PH02, PH03, PH04, PH05 | 8, 9, 10 |
| 2.2 | SLA Xanh/Vàng/Đỏ, khóa chuyển bước, Văn bản xin lỗi Mẫu 04 (TT 01/2018), SMS/Zalo ZNS | PH06, PH07 | 11 |
| 2.3 | RACI, Backup Full/Differential/Log, RPO ≤ 2h, RTO ≤ 4h | PH13 | 13 |
| 3.1–3.4 | Kiểm thử chức năng, liên thông, hiệu năng, bảo mật OWASP, UAT | tests/ + docs | 15 |
| 4.1 | RBAC đa cấp: Tài khoản → Đơn vị (Node) → Vai trò → Ma trận quyền; không xóa vật lý | PH11 | 2, 5 |
| 4.2 | MDM: TTHC quốc gia, ĐVHC (TCTK), chức danh, trạng thái TT 01/2018 | PH09 | 6 |
| 4.3 | Audit Trail, giám sát hiệu năng, IOC heatmap | PH12, PH08, PH13 | 12, 13 |
| 4.4 | BCP/DRP, kịch bản sự cố, diễn tập 6/12 tháng | PH13 | 13 |
| Kết luận | Lộ trình 3 giai đoạn, thí điểm Hồng Bàng – Sở GTVT – Sở KH&ĐT | Dữ liệu mẫu + UAT | 2, 15 |

---

## PHẦN III – MASTER CONTEXT

> Nội dung trong khung dưới đây sẽ được agent ghi vào `.agents/rules/hp-egov.md` ở Phiên 0. **Không sửa tùy tiện** – mọi thay đổi quy ước phải sửa tại đây và ở file rule.

````markdown
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
````

---

## PHẦN IV – PROMPT TỪNG PHIÊN

> Sao chép **nguyên khối code** của mỗi phiên vào một hội thoại mới trên Antigravity.

---

### PHIÊN 0 – THIẾT LẬP BỐI CẢNH & RULE WORKSPACE

```text
[HP-eGov | PHIÊN 0/16 – THIẾT LẬP BỐI CẢNH]

Bạn là Chuyên gia cao cấp MIS & Kiến trúc sư giải pháp Chính quyền điện tử, kiêm Senior PHP Engineer.

Nhiệm vụ:
1. Đọc toàn bộ file "Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf" ở thư mục gốc (trích văn bản bằng Python pymupdf nếu cần).
2. Đọc file docs/PROMPT_XAY_DUNG_HP-eGov_THEO_PHIEN.md, lấy NGUYÊN VĂN nội dung trong khung "PHẦN III – MASTER CONTEXT"
   và ghi thành file `.agents/rules/hp-egov.md` (rule workspace, tự nạp cho mọi phiên sau).
3. Kiểm tra môi trường: phiên bản PHP (C:\xampp\php\php.exe -v), extension, MySQL đang chạy, mod_rewrite của Apache bật,
   `AllowOverride All` cho htdocs. Báo cáo những gì thiếu kèm hướng dẫn bật.
4. Khởi tạo Git: `git init`, tạo `.gitignore` (.env, storage/uploads/*, storage/logs/*, storage/backups/*, storage/keys/*, storage/cache/*,
   giữ lại .gitkeep).
5. Tạo khung thư mục đúng mục 4 của Master Context (chỉ thư mục + .gitkeep, chưa code).
6. Tạo `docs/PROGRESS.md` với bảng 17 phiên (trạng thái: Chưa làm / Đang làm / Hoàn thành) và mục "Nhật ký phiên".
7. Tạo `docs/00_TRUY_VET_BAO_CAO.md`: ma trận truy vết từng mục của báo cáo (Mở đầu, 1.1 → 4.4, Kết luận) → phân hệ → phiên → file dự kiến.
   Đánh dấu rõ hạng mục nào HIỆN THỰC BẰNG CODE, hạng mục nào MÔ PHỎNG (mock), hạng mục nào CHỈ TÀI LIỆU (hạ tầng HA, 3 vùng mạng, TSLCD).

Ràng buộc: KHÔNG viết code ứng dụng trong phiên này.
Tiêu chí hoàn thành: có file rule, khung thư mục, PROGRESS.md, 00_TRUY_VET_BAO_CAO.md, commit đầu tiên "Phiên 0: Khởi tạo dự án".
```

---

### PHIÊN 1 – PHÂN TÍCH & THIẾT KẾ HỆ THỐNG (Chương 1)

```text
[HP-eGov | PHIÊN 1/16 – PHÂN TÍCH & THIẾT KẾ]

Đọc .agents/rules/hp-egov.md, docs/PROGRESS.md, docs/00_TRUY_VET_BAO_CAO.md và báo cáo PDF (Mở đầu, Chương 1, 2.1, 4.1, 4.2).

Tạo tài liệu `docs/01_PHAN_TICH_THIET_KE.md` (tiếng Việt, dùng Mermaid cho sơ đồ) gồm:
1. Bối cảnh & phạm vi: liên thông dọc (UBND TP ↔ Quận/Huyện ↔ Xã/Phường), liên thông ngang (Sở KH&ĐT, Sở XD, Sở TN&MT, Sở GTVT);
   15 quận/huyện Hải Phòng; chỉ tiêu: đúng/trước hạn ≥ 98%, toàn trình ≥ 80%, số hóa 100%, SIPAS ≥ 92%.
2. Tác nhân & vai trò (7 vai trò) + ma trận CRUD đúng §4.1.
3. Danh sách 15 phân hệ (PH01–PH15) và danh mục chức năng chi tiết từng phân hệ (mã chức năng FRxx.yy, mô tả, vai trò, độ ưu tiên):
   PH01 Cổng DVC công dân · PH02 Một cửa · PH03 Thụ lý & Liên thông · PH04 Phê duyệt & Ký số · PH05 Văn thư số
   PH06 Giám sát SLA · PH07 Thông báo đa kênh · PH08 Dashboard & Báo cáo · PH09 Danh mục dùng chung (MDM)
   PH10 Quản trị quy trình · PH11 Người dùng & Phân quyền · PH12 An toàn thông tin & Nhật ký
   PH13 Vận hành (sao lưu, giám sát, sự cố BCP/DRP) · PH14 Chuyển đổi dữ liệu ETL & Tích hợp · PH15 Đào tạo & Chuyển giao.
4. Yêu cầu phi chức năng: hiệu năng (≤1.0s TB, P95 ≤1.5s, lỗi ≤0.01%), an toàn Cấp độ 3, RPO ≤ 2h, RTO ≤ 4h, khả dụng, khả năng mở rộng.
5. Sơ đồ Use Case tổng quát & theo từng vai trò.
6. Sơ đồ hoạt động quy trình 5 bước; sơ đồ trạng thái hồ sơ (state machine, 9 trạng thái ở Master Context mục 8).
7. Sơ đồ tuần tự (sequence): (a) Nộp trực tuyến qua VNeID + auto-fill; (b) Liên thông dự án logistics cảng biển:
   UBND Q.Hồng Bàng → Sở XD (thẩm định quy hoạch) → Sở TN&MT (ĐTM) → Sở KH&ĐT tổng hợp → Lãnh đạo UBND TP ký số → trả kết quả;
   (c) Quá hạn → Văn bản xin lỗi Mẫu 04.
8. DFD mức 0 và mức 1.
9. ERD mức khái niệm (các nhóm: Tổ chức & Định danh, Danh mục dùng chung, Quy trình, Hồ sơ, Ký số & Văn thư, Thông báo, Hệ thống & Kiểm toán, ETL, Đào tạo).
10. Kiến trúc: sơ đồ 3 vùng mạng (Edge CDN/WAF → DMZ → Nghiệp vụ nội bộ → CSDL) đúng hình trang 4 báo cáo;
    ánh xạ sang kiến trúc phần mềm MVC (Front Controller → Middleware → Controller → Service → Model → DB; Integrations Adapter).
11. Sitemap đầy đủ (Portal + Admin theo vai trò) và danh sách route dự kiến.
12. Ma trận quyền chi tiết: danh sách mã quyền (`hoso.tiep_nhan`, `hoso.thu_ly`, `kyso.duyet_buoc1`, `kyso.phat_hanh`, `vanthu.dong_dau`,
    `admin.nguoi_dung`, …) × 7 vai trò.

Ràng buộc: KHÔNG code. Mọi thuật ngữ, con số, chỉ tiêu phải khớp báo cáo; nếu báo cáo mơ hồ → ghi vào mục "Giả định thiết kế".
Tiêu chí hoàn thành: tài liệu đầy đủ 12 mục, sơ đồ Mermaid hiển thị được; cập nhật PROGRESS.md; commit.
```

---

### PHIÊN 2 – CƠ SỞ DỮ LIỆU (phpMyAdmin) & DỮ LIỆU MẪU HẢI PHÒNG

```text
[HP-eGov | PHIÊN 2/16 – CƠ SỞ DỮ LIỆU]

Đọc rule, PROGRESS.md, docs/01_PHAN_TICH_THIET_KE.md.

1. Viết `database/schema.sql` (import trực tiếp bằng phpMyAdmin, MariaDB 10.4+): CREATE DATABASE hp_egov utf8mb4_unicode_ci;
   InnoDB, khóa ngoại, chỉ mục hợp lý, COMMENT tiếng Việt cho mọi bảng/cột. Tối thiểu các nhóm bảng:
   - Tổ chức & Định danh: dm_dvhc (mã TCTK, cấp TINH/HUYEN/XA, loại, parent_id, hieu_luc_tu, hieu_luc_den), don_vi (cây node, path,
     ma_dinh_danh QCVN 102, loai UBND_TP/SO_NGANH/UBND_HUYEN/UBND_XA/PHONG_BAN/BO_PHAN_MOT_CUA), chuc_danh, nguoi_dung (cán bộ),
     cong_dan (cá nhân/doanh nghiệp: so_cccd, ma_so_thue, vneid_sub, thông tin định danh), vai_tro, quyen, vai_tro_quyen,
     nguoi_dung_vai_tro (kèm don_vi_id phạm vi).
   - Danh mục dùng chung: dm_linh_vuc, thu_tuc_hanh_chinh (ma_tthc quốc gia, ma_rut_gon 4 số, cấp thực hiện, mức độ MOT_PHAN/TOAN_TRINH,
     thoi_han_ngay, phí/lệ phí, căn cứ pháp lý, trạng thái công bố CONG_BO/SUA_DOI/BAI_BO, ngay_dong_bo), thanh_phan_ho_so,
     bieu_mau_eform (cấu trúc JSON), dm_trang_thai, dm_ngay_nghi, cau_hinh_he_thong (key-value có kiểu & mô tả).
   - Quy trình: quy_trinh (phiên bản), quy_trinh_buoc (loai_buoc, vai_tro_xu_ly, don_vi_xu_ly_id, thoi_han_gio, cho_phep_tra_lai),
     quy_trinh_chuyen_tiep (tu_buoc, den_buoc, hanh_dong, dieu_kien).
   - Hồ sơ: ho_so (đủ cột theo rule mục 8–11, du_lieu_eform JSON, sla_mau, thoi_gian_da_dung_gio, so_gio_tam_dung, hinh_thuc_nhan_kq,
     các cột hủy logic), bo_dem_ma_ho_so, ho_so_tai_lieu (ten_goc, ten_luu UUID, mime, kich_thuoc, sha256, loai DAU_VAO/SO_HOA/DU_THAO/KET_QUA),
     ho_so_luan_chuyen, ho_so_y_kien_phoi_hop, ho_so_bo_sung, van_ban_xin_loi, ket_qua_giai_quyet, danh_gia_hai_long, kho_du_lieu_cong_dan.
   - Ký số & Văn thư: chung_thu_so, chu_ky_so (loại CA_NHAN/TO_CHUC/SO_HOA, nhà cung cấp, hash trước/sau, kết quả xác thực),
     so_van_ban, van_ban_den_di.
   - Thông báo: thong_bao (in-app), hang_doi_thong_bao (outbox SMS/ZALO/EMAIL: trạng thái, số lần thử, phản hồi), webhook_log.
   - Hệ thống: nhat_ky_kiem_toan (hash chain), nhat_ky_dang_nhap, nhat_ky_tich_hop, gioi_han_truy_cap, ip_chan, sao_luu,
     su_co (BCP/DRP), dien_tap, phien_ban_cau_hinh.
   - ETL: etl_lo, etl_staging_ho_so, etl_loi.
   - Đào tạo: dao_tao_khoa, dao_tao_hoc_vien, dao_tao_ket_qua (ngưỡng đạt theo nhóm, ví dụ ≥ 90/100 cho cán bộ Một cửa).
2. Trigger: chặn DELETE trên ho_so, ho_so_tai_lieu, ho_so_luan_chuyen, chu_ky_so; chặn UPDATE/DELETE trên nhat_ky_kiem_toan (SIGNAL SQLSTATE '45000').
3. View báo cáo: v_ho_so_tong_hop (trạng thái quy về 6 nhóm chuẩn), v_ty_le_dung_han_don_vi, v_ho_so_qua_han.
4. Viết `database/seed.sql` dữ liệu mẫu thực tế Hải Phòng:
   - dm_dvhc: TP Hải Phòng (31) và 15 đơn vị cấp huyện theo báo cáo: Q.Hồng Bàng(303), Ngô Quyền(304), Lê Chân(305), Hải An(306),
     Kiến An(307), Đồ Sơn(308), Dương Kinh(309), TP Thủy Nguyên(311), Q.An Dương(312), H.An Lão(313), Kiến Thụy(314), Tiên Lãng(315),
     Vĩnh Bảo(316), Cát Hải(317), Bạch Long Vĩ(318) (đối chiếu lại bảng mã TCTK); mỗi quận/huyện vài phường/xã mẫu (Hồng Bàng đầy đủ hơn).
     Cấu trúc phải hỗ trợ hiệu lực theo thời gian để chuyển đổi sang mô hình ĐVHC mới (không sửa đè dữ liệu cũ).
   - don_vi: UBND TP, VP UBND TP, Sở KH&ĐT, Sở XD, Sở TN&MT, Sở GTVT, Sở TT&TT, UBND 15 quận/huyện, một số UBND phường; mỗi cơ quan có
     Bộ phận Một cửa + 1–2 phòng chuyên môn; mã định danh theo QCVN 102 dùng tham số mã tỉnh.
   - 7 vai trò, toàn bộ mã quyền, ma trận vai_tro_quyen đúng §4.1; tài khoản mẫu theo rule mục 15 (password_hash bcrypt của `Hpegov@2026`).
   - ≥ 12 TTHC mẫu (mã minh họa, ghi chú "đồng bộ lại từ CSDL TTHC QG"): Đăng ký khai sinh (xã), Chứng thực bản sao (xã),
     Cấp GPXD nhà ở riêng lẻ (huyện), Đăng ký biến động đất đai (huyện), Cấp đổi GPLX (Sở GTVT), Cấp phù hiệu xe container/xe tải (Sở GTVT),
     Quyết định chủ trương đầu tư dự án logistics cảng biển (liên thông Hồng Bàng → Sở XD → Sở TN&MT → Sở KH&ĐT → UBND TP), …
     kèm thành phần hồ sơ, biểu mẫu E-form JSON, quy trình + bước + chuyển tiếp với tổng thời hạn khớp thời hạn TTHC.
   - dm_ngay_nghi theo Bộ luật Lao động 2019 cho năm hiện hành (ngày âm lịch ghi chú "cần xác minh theo thông báo hằng năm"),
     cau_hinh_he_thong (giờ làm việc 07:30–11:30, 13:30–17:30; ngưỡng SLA 75%/24h; rate limit 60; ma_tinh_qcvn102=H21…).
   - ~200 hồ sơ mẫu phân bố đủ trạng thái, đủ 3 màu SLA, nhiều đơn vị, trải 6 tháng gần nhất (phục vụ Dashboard).
5. Viết `docs/02_THIET_KE_CSDL.md`: ERD Mermaid chi tiết, từ điển dữ liệu (bảng, cột, kiểu, ràng buộc, ý nghĩa), chiến lược chỉ mục.
6. Import thử vào MySQL bằng `C:\xampp\mysql\bin\mysql.exe` (hoặc hướng dẫn import phpMyAdmin) và xác nhận không lỗi; thử DELETE ho_so phải bị chặn.

Tiêu chí hoàn thành: schema.sql + seed.sql import sạch; trigger hoạt động; tài liệu CSDL; PROGRESS.md; commit.
```

---

### PHIÊN 3 – LÕI MVC (CORE FRAMEWORK)

```text
[HP-eGov | PHIÊN 3/16 – LÕI MVC]

Đọc rule, PROGRESS.md. Dùng /plan trước khi code.

Xây dựng lõi MVC thuần PHP 8.2 trong app/Core (clean code, strict_types, có PHPDoc):
1. `.htaccess` gốc + `public/.htaccess` + `public/index.php` (front controller duy nhất); `app/bootstrap.php`; autoloader PSR-4 (App\ → app/).
2. `Env` (đọc .env), `Config` (dot-notation), `.env.example` đầy đủ (APP_ENV, APP_URL, DB_*, DB_READ_HOST cho replica, INTEGRATION_MODE, SESSION_LIFETIME...).
3. `Router`: GET/POST/PUT/DELETE (method spoofing `_method`), tham số động `{id:\d+}`, nhóm route với prefix + middleware, đặt tên route + hàm `route()`.
4. `Request` (input đã lọc, file, json, ip, isAjax), `Response` (html, json, redirect, download an toàn, status code).
5. `Controller` cơ sở (view, json, redirect, authorize), `View` (layout + section + partial, auto-escape helper), `Model` cơ sở
   (find, where, paginate, insert, update, softDelete; KHÔNG có hàm delete vật lý cho bảng nghiệp vụ; whitelist cột fillable).
6. `Database`: PDO singleton, ERRMODE_EXCEPTION, EMULATE_PREPARES=false, transaction helper `transaction(callable)`,
   tách kết nối write/read (mô phỏng Master/Replica §1.2 – nếu không cấu hình replica thì dùng chung).
7. `Session` an toàn (cookie flags theo rule mục 13, timeout, flash), `Csrf`, `Validator` (required, email, cccd 12 số, mst, phone VN,
   date, in, max, file_mime, file_size, unique…; thông báo lỗi tiếng Việt), `Logger` (PSR-3 đơn giản, file theo ngày), `ErrorHandler`
   (trang 403/404/419/429/500 thân thiện; ẩn chi tiết ở production), `Paginator`.
8. Middleware pipeline: SecurityHeaders, Csrf, RateLimit (bảng gioi_han_truy_cap), Auth, Guest, Permission (khung, hoàn thiện ở Phiên 5).
9. `app/Enums`: TrangThaiHoSo (label, màu, nhóm chuẩn TT01), VaiTro, MauSla, KenhNop, LoaiBuoc, LoaiTaiLieu, KenhThongBao.
10. `app/Helpers/functions.php`: e(), url(), asset(), route(), csrf_field(), method_field(), old(), errors(), flash(), date_vn(), auth().
11. `tests/run.php` – test runner tối giản (assertEquals, assertTrue, assertThrows, báo cáo màu trên CLI) + test cho Router, Validator, Csrf, Enums.
12. Trang chủ tạm "HP-eGov đang hoạt động" để kiểm tra luồng.

Tiêu chí hoàn thành: http://localhost/HP-eGov/ hiển thị; route lỗi trả 404 đẹp; `php tests/run.php` xanh; php -l không lỗi; PROGRESS.md; commit.
```

---

### PHIÊN 4 – DESIGN SYSTEM & LAYOUT GIAO DIỆN

```text
[HP-eGov | PHIÊN 4/16 – DESIGN SYSTEM & LAYOUT]

Đọc rule (mục 14), PROGRESS.md.

1. `public/assets/css/`: tokens.css (biến màu, font, spacing, radius, shadow, z-index, dark mode), base.css, components.css, layout.css, print.css.
   Tải Be Vietnam Pro (woff2), Chart.js, thư viện QR, html5-qrcode về `public/assets/vendor` (ghi rõ phiên bản & giấy phép trong docs/THU_VIEN.md).
2. Thành phần UI tái sử dụng (partials + CSS + JS thuần): nút, form (floating label, thông báo lỗi), bảng dữ liệu (sắp xếp, lọc, phân trang),
   thẻ KPI, badge trạng thái (9 trạng thái), badge SLA (Xanh/Vàng/Đỏ có nhấp nháy nhẹ khi Đỏ), stepper 5 bước, timeline luân chuyển,
   modal xác nhận, toast, dropdown thông báo, tabs, breadcrumb, empty state, skeleton loading, upload kéo-thả có thanh tiến trình.
3. Layout:
   - `portal.php`: header có quốc huy cách điệu (tự vẽ SVG, KHÔNG dùng ảnh bản quyền) + tên "Cổng Dịch vụ công TP. Hải Phòng",
     menu (Trang chủ, Thủ tục hành chính, Nộp hồ sơ, Tra cứu, Đánh giá, Hỗ trợ), footer thông tin đơn vị chủ quản.
   - `admin.php`: sidebar thu gọn được, menu sinh động theo quyền (đọc từ cấu hình menu + quyền), topbar (đơn vị đang làm việc,
     chuông thông báo, chuyển chế độ tối, hồ sơ cá nhân), vùng nội dung có breadcrumb.
   - `auth.php` (đăng nhập cán bộ / công dân), `print.php` (A4, thể thức văn bản hành chính: Quốc hiệu, tiêu ngữ, số ký hiệu, nơi nhận).
4. `assets/js/app.js` module hóa (ES6, không inline script để tuân thủ CSP): fetch wrapper tự gắn CSRF, toast, modal, confirm, table, upload.
5. Trang mẫu "UI Kit" nội bộ `/admin/ui-kit` (chỉ APP_ENV=development) trình diễn toàn bộ thành phần.
6. Kiểm tra trực quan bằng trình duyệt ở 1366px và 390px; chụp ảnh màn hình đưa vào docs.

Tiêu chí hoàn thành: giao diện cao cấp, nhất quán, responsive, không lỗi console, không inline JS; PROGRESS.md; commit.
```

---

### PHIÊN 5 – XÁC THỰC & PHÂN QUYỀN RBAC ĐA CẤP (§4.1, §1.3.B)

```text
[HP-eGov | PHIÊN 5/16 – XÁC THỰC & PHÂN QUYỀN RBAC]

Đọc rule (mục 6, 13), PROGRESS.md, docs/01 (ma trận quyền). Dùng /plan.

1. Hai cổng xác thực tách biệt (guard): `cán bộ` (/admin/dang-nhap) và `công dân` (/dang-nhap).
   - Cán bộ: username/mật khẩu, chính sách mật khẩu, khóa 15 phút sau 5 lần sai, captcha GD sau 3 lần, bắt đổi mật khẩu lần đầu,
     ghi nhat_ky_dang_nhap, chọn đơn vị làm việc nếu kiêm nhiệm nhiều node.
   - Công dân: (a) Đăng nhập VNeID mô phỏng theo luồng OIDC Authorization Code (trang "VNeID Sandbox" giả lập: nhập CCCD/quét QR → consent
     → callback có state/nonce chống CSRF) → nhận thuộc tính: Số CCCD, Họ tên, Ngày sinh, Giới tính, Quê quán, Địa chỉ thường trú → tạo/cập nhật cong_dan;
     (b) Tài khoản doanh nghiệp bằng mã số thuế + mật khẩu.
2. `Auth` service: user hiện tại, vai trò, quyền (cache trong session, làm mới khi đổi quyền), đơn vị hiện tại, hàm can('hoso.thu_ly').
3. Middleware Permission (`->middleware('can:hoso.tiep_nhan')`) và DataScope: mọi truy vấn hồ sơ phía cán bộ tự lọc theo phạm vi vai trò
   (đơn vị / phòng / được phân công / toàn cơ quan / toàn TP); công dân chỉ thấy hồ sơ của mình. Truy cập trái phép → 403 + ghi audit.
4. PH11 – Quản trị người dùng & phân quyền (SYSADMIN): CRUD tài khoản cán bộ (không xóa vật lý: khóa/vô hiệu), gán nhiều vai trò theo đơn vị,
   reset mật khẩu, mở khóa, danh sách công dân (chỉ xem/khóa), CRUD vai trò, giao diện MA TRẬN QUYỀN dạng lưới checkbox (vai trò × quyền),
   chặn cứng: không cho gán `hoso.*`/`kyso.*` cho SYSADMIN; mọi thay đổi ghi audit (dữ liệu cũ/mới).
5. AuditService (dùng chung toàn hệ thống): ghi nhat_ky_kiem_toan với hash chain; hàm verifyChain() phát hiện bản ghi bị sửa.
6. Hồ sơ cá nhân cán bộ: đổi mật khẩu, xem lịch sử đăng nhập.
7. Test: đăng nhập đúng/sai/khóa; IDOR (công dân A truy cập hồ sơ công dân B → 403); SYSADMIN truy cập chức năng nghiệp vụ → 403; verifyChain.

Tiêu chí hoàn thành: đăng nhập được bằng mọi tài khoản mẫu, menu hiển thị đúng theo vai trò; test xanh; PROGRESS.md; commit.
```

---

### PHIÊN 6 – DANH MỤC DÙNG CHUNG (MDM) & QUẢN TRỊ QUY TRÌNH (§4.2)

```text
[HP-eGov | PHIÊN 6/16 – MDM & QUY TRÌNH]

Đọc rule, PROGRESS.md.

A. PH09 – Quản trị danh mục dùng chung (quyền admin.danh_muc), mỗi danh mục có: danh sách (tìm kiếm, lọc, phân trang), thêm, sửa,
   ngừng hiệu lực (không xóa vật lý nếu đã phát sinh dữ liệu), xuất CSV, nhập CSV có kiểm tra lỗi từng dòng, lịch sử thay đổi:
   1. Đơn vị hành chính (cây 3 cấp, hiệu lực từ/đến, hỗ trợ sáp nhập/chia tách: chọn đơn vị cũ → đơn vị mới, giữ lịch sử).
   2. Cây tổ chức đơn vị/phòng ban (kéo-thả hoặc chọn cha), mã định danh QCVN 102 tự gợi ý.
   3. Chức danh, Lĩnh vực.
   4. Thủ tục hành chính: thông tin đầy đủ, thành phần hồ sơ, phí/lệ phí, căn cứ pháp lý, mức độ DVC, gắn quy trình;
      nút "Đồng bộ CSDL TTHC Quốc gia" gọi adapter CsdlTthcQuocGia (Mock trả danh sách công bố mới/sửa đổi/bãi bỏ) → màn hình so sánh khác biệt → áp dụng.
   5. Trình thiết kế E-Form: định nghĩa trường bằng giao diện (text, số, ngày, select, CCCD, địa chỉ ĐVHC 3 cấp, bảng lặp, file) → lưu JSON;
      đánh dấu trường auto-fill từ VNeID (cccd, ho_ten, ngay_sinh, gioi_tinh, que_quan, dia_chi_thuong_tru); xem trước.
   6. Trạng thái xử lý (chỉ xem – theo TT 01/2018), Ngày nghỉ lễ (lịch năm, nhân bản sang năm sau), Giờ làm việc, Tham số hệ thống
      (có kiểu dữ liệu, kiểm tra hợp lệ, lưu lịch sử phiên bản cấu hình).
B. PH10 – Quản trị quy trình (BPMN-lite, quyền admin.quy_trinh):
   - Danh sách quy trình theo TTHC, tạo phiên bản mới (hồ sơ đang chạy giữ phiên bản cũ).
   - Thiết kế bước: loại bước (TIEP_NHAN, PHAN_CONG, THU_LY, LAY_Y_KIEN, THAM_TRA, PHE_DUYET, DONG_DAU, TRA_KET_QUA), vai trò xử lý,
     đơn vị xử lý (cùng đơn vị hoặc đơn vị liên thông khác – liên Sở/liên cấp), thời hạn giờ, cho phép trả lại.
   - Chuyển tiếp: hành động (Chuyển, Trả lại, Yêu cầu bổ sung, Từ chối), điều kiện.
   - Hiển thị sơ đồ quy trình trực quan (SVG/Mermaid sinh tự động), kiểm tra hợp lệ: có điểm đầu/cuối, không bước mồ côi,
     tổng thời hạn các bước = thời hạn TTHC (cảnh báo nếu lệch).
C. Viết test cho kiểm tra hợp lệ quy trình và tính tổng thời hạn.

Tiêu chí hoàn thành: SYSADMIN quản trị trọn vẹn danh mục & quy trình; quy trình liên thông logistics hiển thị đúng 5 đơn vị; PROGRESS.md; commit.
```

---

### PHIÊN 7 – PH01 CỔNG DỊCH VỤ CÔNG (CÔNG DÂN / DOANH NGHIỆP)

```text
[HP-eGov | PHIÊN 7/16 – CỔNG DỊCH VỤ CÔNG]

Đọc rule (mục 10, 12, 13), PROGRESS.md. Dùng /plan.

1. Trang chủ cổng: banner, ô tìm kiếm TTHC, thống kê công khai (hồ sơ tiếp nhận, tỷ lệ đúng hạn, mức hài lòng), TTHC phổ biến, tin thông báo.
2. Danh mục TTHC: lọc theo lĩnh vực / cấp thực hiện / cơ quan / mức độ DVC; trang chi tiết TTHC (trình tự, thành phần, thời hạn, phí, căn cứ, biểu mẫu).
3. Nộp hồ sơ trực tuyến (yêu cầu đăng nhập VNeID/doanh nghiệp) – wizard 4 bước:
   (1) Chọn TTHC & cơ quan tiếp nhận (ĐVHC 3 cấp) → (2) E-Form động render từ JSON, AUTO-FILL từ VNeID (trường auto-fill bị khóa sửa)
   → (3) Đính kèm thành phần hồ sơ: chọn lại giấy tờ từ "Kho dữ liệu điện tử cá nhân" (không cần nộp lại) hoặc tải mới (bảo mật upload đúng rule mục 13,
   quét MalwareScanner mock, SHA-256) → (4) Xem lại, chọn hình thức nhận kết quả (trực tiếp / VNPost / điện tử), ký số E-form (mock) → Nộp.
   Kết quả: tạo ho_so trạng thái CHO_TIEP_NHAN, cấp mã hồ sơ qua MaHoSoGenerator (QCVN 102, chống trùng đồng thời), hiển thị mã + QR tra cứu,
   phát sự kiện thông báo (SMS/Zalo mock).
4. Hồ sơ của tôi: danh sách, lọc trạng thái, chi tiết (stepper 5 bước, timeline luân chuyển công khai – ẩn ý kiến nội bộ), tải kết quả điện tử.
5. Bổ sung hồ sơ khi trạng thái CHO_BO_SUNG: xem nội dung yêu cầu, chỉ sửa trường/tệp được yêu cầu, nộp bổ sung → quay lại DANG_XU_LY.
6. Tra cứu công khai theo mã hồ sơ hoặc quét QR (không cần đăng nhập, chỉ hiện thông tin tối thiểu, có rate limit & captcha).
7. Kho dữ liệu điện tử cá nhân/tổ chức: danh sách giấy tờ đã số hóa & kết quả đã cấp, hiệu lực, tái sử dụng.
8. Đánh giá mức độ hài lòng sau khi nhận kết quả (theo tiêu chí SIPAS: tiếp cận dịch vụ, thủ tục, công chức, kết quả, tiếp nhận phản ánh) – 1 lần/hồ sơ.
9. Trung tâm thông báo của công dân; trang Hỗ trợ/Hỏi đáp.
10. Kiểm tra: công dân A không xem được hồ sơ B (403); upload file .php/.exe đổi đuôi bị chặn; nộp đồng thời 20 request không trùng mã.

Tiêu chí hoàn thành: luồng nộp trực tuyến trọn vẹn trên trình duyệt (ghi video bằng browser subagent); PROGRESS.md; commit.
```

---

### PHIÊN 8 – PH02 BỘ PHẬN MỘT CỬA (TIẾP NHẬN – SỐ HÓA – TRẢ KẾT QUẢ)

```text
[HP-eGov | PHIÊN 8/16 – BỘ PHẬN MỘT CỬA]

Đọc rule (mục 9, 10), PROGRESS.md, báo cáo §1.4 (quy trình số hóa NĐ 107/2021) và §2.1 Bước 1, Bước 5.

Vai trò CB_MOT_CUA, dữ liệu lọc theo đơn vị:
1. Bàn làm việc Một cửa: thẻ đếm (chờ tiếp nhận trực tuyến, tiếp nhận hôm nay, chờ trả kết quả, sắp quá hạn), danh sách việc cần làm.
2. Tiếp nhận trực tiếp: quét QR CCCD gắn chip bằng webcam (html5-qrcode) hoặc dán chuỗi QR định dạng `CCCD|CMND|Họ tên|ddMMyyyy|Giới tính|Địa chỉ|ddMMyyyy`
   → tự điền thông tin; tra cứu/đối chiếu C06 qua LgspGateway mock; chọn TTHC → E-form → checklist thành phần hồ sơ.
3. Số hóa đầu vào theo NĐ 107/2021 (4 bước như báo cáo): kiểm tra pháp lý (checkbox xác nhận bản chính) → tải file scan (≥300 DPI khuyến nghị,
   lưu PDF) → OCR mô phỏng (trích CCCD/tên/số thửa/biển số từ tên file hoặc nhập tay có gợi ý) → ký số số hóa bằng chứng thư cá nhân (ChuKySoService)
   → đồng bộ vào kho_du_lieu_cong_dan.
4. Tiếp nhận → sinh mã hồ sơ QCVN 102, tính ngày hẹn trả theo SlaEngine (nếu SlaEngine chưa có, tạo phiên bản tối thiểu ở đây và hoàn thiện ở Phiên 11),
   chuyển MOI_TIEP_NHAN → WorkflowEngine chuyển sang bước kế tiếp; in Giấy tiếp nhận & hẹn trả kết quả (mẫu print A4, có QR tra cứu).
5. Xử lý hồ sơ trực tuyến CHO_TIEP_NHAN: xem, kiểm tra, (a) Tiếp nhận, (b) Yêu cầu bổ sung (nêu rõ nội dung, một lần duy nhất theo quy định),
   (c) Từ chối tiếp nhận (lý do bắt buộc) → thông báo công dân.
6. Trả kết quả: danh sách hồ sơ HOAN_THANH, trả trực tiếp (xác nhận người nhận, ký nhận) hoặc gửi VNPost (BuuChinhGateway mock, mã vận đơn)
   → DA_TRA_KET_QUA; hồ sơ trễ phải đính kèm Văn bản xin lỗi (liên kết Phiên 11).
7. Đề xuất hủy hồ sơ (nhập căn cứ pháp lý + biên bản giải trình) → chờ LD_CO_QUAN duyệt (chức năng duyệt làm ở Phiên 10).
8. Sổ theo dõi hồ sơ (Mẫu sổ theo TT 01/2018) – lọc, xuất CSV, in.
9. Test: tiếp nhận 2 hồ sơ cùng lúc không trùng mã; thời gian tạo hồ sơ mới nhanh nhờ QR (mục tiêu báo cáo: 12 phút → 4 phút).

Tiêu chí hoàn thành: luồng tiếp nhận trực tiếp + số hóa + in biên nhận + trả kết quả hoạt động; PROGRESS.md; commit.
```

---

### PHIÊN 9 – WORKFLOW ENGINE · THỤ LÝ & LIÊN THÔNG ĐIỆN TỬ (PH03)

```text
[HP-eGov | PHIÊN 9/16 – WORKFLOW ENGINE, THỤ LÝ & LIÊN THÔNG]

Đọc rule (mục 8, 9), PROGRESS.md, docs/01 (state machine, sequence liên thông). Dùng /plan.

1. `WorkflowEngine` (Service trung tâm, mọi chuyển bước phải qua đây):
   - Đọc định nghĩa quy_trinh/buoc/chuyen_tiep theo phiên bản của hồ sơ; kiểm tra: vai trò & đơn vị người thao tác, trạng thái hợp lệ,
     SLA (ĐỎ → chặn chuyển bước thông thường, trả lỗi nghiệp vụ rõ ràng), khóa lạc quan/bi quan chống 2 người xử lý cùng lúc.
   - Thực hiện trong transaction: cập nhật ho_so, ghi ho_so_luan_chuyen (từ/đến bước, người, đơn vị, ý kiến, hạn bước), audit, phát sự kiện
     (ThongBaoService – in-app ngay; SMS/Zalo đưa vào hàng đợi).
   - Hành động: chuyenTiep, traLai, yeuCauBoSung (tạm dừng SLA), tiepTucSauBoSung, layYKien, phanCong, thuHoi (trước khi người nhận mở).
   - Liên thông: khi bước kế tiếp thuộc đơn vị khác (liên Sở / liên cấp) → chuyển `don_vi_hien_tai_id`, hồ sơ xuất hiện ở hộp việc đơn vị nhận,
     đơn vị gửi vẫn theo dõi được (chỉ đọc); kiểm tra toàn vẹn tệp đính kèm (so SHA-256) và chữ ký số tại mỗi trạm chuyển.
2. Hộp việc (Inbox) dùng chung cho cán bộ: tab Chờ xử lý / Đang xử lý / Đã chuyển / Phối hợp / Sắp quá hạn / Quá hạn; lọc, tìm, sắp xếp theo SLA.
3. LD_PHONG: phân công/chuyển phân công chuyên viên (gợi ý theo khối lượng đang xử lý), điều chỉnh ý kiến thụ lý.
4. CHUYEN_VIEN – màn hình thụ lý: thông tin hồ sơ, E-form, tài liệu (xem PDF trực tiếp), timeline; đối soát dữ liệu qua LgspGateway mock
   (xác thực cư trú C06, thông tin đất đai/đăng kiểm mô phỏng) hiển thị kết quả khớp/không khớp; tạo tờ trình/biên bản thẩm định;
   gửi lấy ý kiến phối hợp liên phòng/liên sở (đơn vị, nội dung, hạn trả lời) và nhận phản hồi; dự thảo kết quả (giấy phép/quyết định/văn bản trả lời)
   từ mẫu có biến; yêu cầu bổ sung; trình duyệt.
5. Đơn vị được hỏi ý kiến: hộp "Phối hợp" – trả lời ý kiến, đính kèm văn bản.
6. Kịch bản kiểm thử tích hợp tự động `tests/Integration/LienThongLogisticsTest.php`: hồ sơ dự án logistics cảng biển nộp tại UBND Q.Hồng Bàng
   → Sở XD thẩm định quy hoạch → lấy ý kiến Sở TN&MT (ĐTM) → Sở KH&ĐT tổng hợp → trình Lãnh đạo UBND TP; xác minh không deadlock, không mất trạng thái,
   hash tệp không đổi qua mỗi trạm.

Tiêu chí hoàn thành: hồ sơ đi trọn từ B1 đến B4 qua nhiều đơn vị trên giao diện; test tích hợp xanh; PROGRESS.md; commit.
```

---

### PHIÊN 10 – PHÊ DUYỆT, KÝ SỐ & VĂN THƯ SỐ (PH04, PH05)

```text
[HP-eGov | PHIÊN 10/16 – PHÊ DUYỆT, KÝ SỐ & VĂN THƯ SỐ]

Đọc rule (mục 6, 12), PROGRESS.md, báo cáo §1.4 nhóm 3, §2.1 Bước 4–5.

1. `ChuKySoService` + `SignatureProvider` (Mock dùng OpenSSL thật):
   - Lệnh CLI `C:\xampp\php\php.exe app/cli.php keys:generate` tạo cặp khóa + chứng thư tự ký demo cho từng người có quyền ký
     và chứng thư tổ chức (con dấu số) cho từng cơ quan, lưu `storage/keys/` (ngoài public), ghi chung_thu_so (serial, hiệu lực, nhà cung cấp SMARTCA/USB_TOKEN/BCY).
   - Ký: tính SHA-256 tệp → openssl_sign → lưu chữ ký (.sig) cạnh tệp gốc + bản in kết quả (HTML A4) có khối "Đã ký số bởi … lúc … – Serial …" → ghi chu_ky_so.
   - Xác thực: verify chữ ký, kiểm tra chứng thư còn hiệu lực & đúng thẩm quyền người ký; trang "Kiểm tra chữ ký số" hiển thị kết quả hợp lệ/không hợp lệ.
   - Mô phỏng xác nhận OTP/PIN SmartCA (modal nhập PIN); SYSADMIN tuyệt đối không ký được.
2. LD_PHONG: danh sách chờ duyệt bước 1 → xem dự thảo → Ký số duyệt / Trả lại kèm ý kiến.
3. LD_CO_QUAN: danh sách chờ phát hành → Ý kiến chỉ đạo / Ký số phát hành / Trả lại; KÝ SỐ HÀNG LOẠT (Batch Signing – chọn nhiều hồ sơ, 1 lần nhập PIN,
   báo cáo kết quả từng hồ sơ); duyệt đề xuất hủy hồ sơ (căn cứ pháp lý + biên bản) → DA_HUY logic.
   Giao diện ký số tối ưu di động (mục tiêu báo cáo: độ trễ < 2 giây/lần ký).
4. VAN_THU – Văn thư số: quản lý sổ văn bản (sổ phát hành theo năm, theo loại), cấp số & ký hiệu tự động không trùng (khóa transaction),
   đóng dấu số cơ quan (ký số tổ chức) → ho_so HOAN_THANH → tự động đẩy kết quả vào kho_du_lieu_cong_dan + thông báo công dân;
   quản lý công văn đến/đi gắn với hồ sơ; tra cứu, in sổ, xuất CSV.
5. Test: ký → sửa 1 byte tệp → verify thất bại; người không đúng thẩm quyền ký → bị chặn; cấp số đồng thời không trùng; batch 20 hồ sơ.

Tiêu chí hoàn thành: hồ sơ đi trọn 5 bước từ nộp đến công dân nhận kết quả điện tử có chữ ký số kiểm tra được; PROGRESS.md; commit.
```

---

### PHIÊN 11 – SLA ENGINE & THÔNG BÁO ĐA KÊNH (PH06, PH07 – §2.2)

```text
[HP-eGov | PHIÊN 11/16 – SLA ENGINE & THÔNG BÁO ĐA KÊNH]

Đọc rule (mục 11), PROGRESS.md, báo cáo §2.2.

1. `SlaEngine` (hoàn thiện, có unit test đầy đủ):
   - Tính ngày/giờ hẹn trả từ thời điểm tiếp nhận + thời hạn (ngày làm việc → giờ làm việc), trừ T7/CN, ngày lễ (dm_ngay_nghi), ngoài giờ hành chính;
     tiếp nhận sau giờ làm việc tính từ đầu giờ làm việc kế tiếp.
   - Tính t (giờ đã dùng) trừ khoảng tạm dừng chờ bổ sung; tính màu XANH/VÀNG/ĐỎ đúng công thức rule mục 11; hạn từng bước & toàn hồ sơ.
   - Unit test: qua cuối tuần, qua Tết, tiếp nhận 17:45, tạm dừng bổ sung, ngưỡng biên 75%/100%/còn 24h.
2. `cron/sla_scan.php` (chạy mỗi 15 phút bằng Windows Task Scheduler – viết sẵn lệnh `schtasks` trong docs): cập nhật sla_mau, thoi_gian_da_dung_gio;
   chuyển VÀNG → push alert (in-app realtime bằng polling/SSE nhẹ) cho chuyên viên & LD_PHONG; chuyển ĐỎ → khóa chuyển bước, thông báo LD_CO_QUAN,
   ghi điểm thi đua đơn vị.
3. Văn bản xin lỗi Mẫu số 04 (TT 01/2018/TT-VPCP): LD_CO_QUAN lập (lý do quá hạn, ngày hẹn mới), ký số, in đúng thể thức; sau khi có văn bản
   mới mở khóa hoàn tất hồ sơ; gửi công dân.
4. `ThongBaoService` + hàng đợi `hang_doi_thong_bao` (Outbox pattern) + `cron/notify_dispatch.php`: mỗi lần đổi trạng thái
   (Tiếp nhận → Đang thụ lý → Yêu cầu bổ sung → Đã có kết quả) sinh thông báo theo mẫu tin cấu hình được:
   SMS Brandname "UBND TP HP", Zalo ZNS (Trung tâm Thông tin và Dịch vụ hành chính công Hải Phòng, kèm link QR tra cứu), email; retry có backoff,
   ghi webhook_log. Gateway Mock lưu tin vào trang "Hộp thư mô phỏng" (/admin/he-thong/hop-thu-mo-phong) để minh họa.
5. Quản trị mẫu thông báo (biến {ma_ho_so}, {ten_tthc}, {trang_thai}, {link_tra_cuu}…), bật/tắt kênh theo loại sự kiện.
6. Badge SLA và đếm ngược thời gian còn lại hiển thị nhất quán ở mọi danh sách hồ sơ.

Tiêu chí hoàn thành: unit test SlaEngine xanh 100%; hồ sơ quá hạn bị khóa đúng và mở khóa sau Mẫu 04; tin nhắn mô phỏng xuất hiện; PROGRESS.md; commit.
```

---

### PHIÊN 12 – DASHBOARD CHỈ ĐẠO ĐIỀU HÀNH & BÁO CÁO (PH08 – §4.3 IOC)

```text
[HP-eGov | PHIÊN 12/16 – DASHBOARD & BÁO CÁO]

Đọc rule, PROGRESS.md, báo cáo (Mở đầu §2 chỉ tiêu, §2.2, §4.3).

1. Dashboard theo vai trò (dữ liệu lọc theo phạm vi, Chart.js cục bộ):
   - Chuyên viên/LD_PHONG: việc của tôi/của phòng, sắp quá hạn, năng suất.
   - LD_CO_QUAN (Giám đốc Sở/Chủ tịch UBND Quận): KPI cơ quan, hồ sơ quá hạn theo phòng, xếp hạng chuyên viên.
   - Lãnh đạo UBND TP (node UBND_TP) – màn hình IOC: 4 thẻ KPI so với chỉ tiêu báo cáo (đúng/trước hạn ≥ 98%, toàn trình ≥ 80%, số hóa 100%,
     hài lòng ≥ 92%) có màu đạt/chưa đạt; heatmap số hồ sơ tiếp nhận theo giờ × ngày; bản đồ số Hải Phòng (SVG tự vẽ đơn giản 15 quận/huyện)
     tô màu theo tỷ lệ quá hạn; bảng xếp hạng thi đua Sở/Quận; phát hiện điểm thắt cổ chai (bước có thời gian tồn đọng trung bình cao nhất);
     chế độ trình chiếu toàn màn hình, tự làm mới 60 giây.
2. Báo cáo (lọc kỳ: tháng/quý/năm/tùy chọn; đơn vị; lĩnh vực; xuất CSV UTF-8 BOM & bản in A4):
   - Báo cáo tình hình giải quyết TTHC theo mẫu TT 01/2018 (tồn kỳ trước, tiếp nhận mới, đã giải quyết đúng hạn/quá hạn, đang giải quyết…).
   - Báo cáo số hóa hồ sơ & kết quả (NĐ 107/2021), báo cáo hồ sơ quá hạn kèm Mẫu 04, báo cáo mức độ hài lòng,
     báo cáo hồ sơ trực tuyến toàn trình/một phần, báo cáo liên thông giữa các đơn vị.
3. Tối ưu truy vấn: dùng view/chỉ mục, cache kết quả tổng hợp 5 phút (storage/cache), đo thời gian truy vấn < 1 giây với dữ liệu mẫu.

Tiêu chí hoàn thành: dashboard đẹp, số liệu khớp truy vấn kiểm chứng; báo cáo xuất được; PROGRESS.md; commit.
```

---

### PHIÊN 13 – QUẢN TRỊ & VẬN HÀNH HỆ THỐNG (PH12, PH13, PH15 – §2.3, §4.3, §4.4, §1.4)

```text
[HP-eGov | PHIÊN 13/16 – QUẢN TRỊ & VẬN HÀNH]

Đọc rule, PROGRESS.md, báo cáo §1.4, §2.3, §4.3, §4.4.

1. PH12 – An toàn thông tin & Nhật ký (SYSADMIN):
   - Tra cứu Audit Trail (lọc người dùng, hành động, đối tượng, thời gian; xem diff dữ liệu cũ/mới); nút "Kiểm tra toàn vẹn chuỗi băm".
   - Nhật ký đăng nhập, nhật ký tích hợp, danh sách IP bị chặn/rate-limit, cấu hình chính sách bảo mật (độ dài mật khẩu, số lần sai, timeout phiên).
2. PH13 – Sao lưu & phục hồi đúng chính sách báo cáo (dùng C:\xampp\mysql\bin\mysqldump.exe, mysqlbinlog):
   - Full Backup 1 lần/tuần 00:00 Chủ nhật (mysqldump --single-transaction + nén + SHA-256, sao chép sang thư mục "DR Site" cấu hình được).
   - Differential 1 lần/ngày 23:00 (xuất bản ghi thay đổi kể từ bản Full gần nhất theo updated_at, giữ 30 ngày).
   - Transaction Log 2 giờ/lần (hướng dẫn bật log-bin trong my.ini; sao chép binlog) → bảo đảm RPO ≤ 2 giờ.
   - Trang quản trị sao lưu: lịch sử, dung lượng, checksum, tải về (có kiểm tra quyền), PHỤC HỒI thử vào CSDL `hp_egov_restore_test`
     và đo thời gian (ghi nhận so với RTO ≤ 4 giờ); lệnh `schtasks` mẫu trong docs.
3. Giám sát sức khỏe hệ thống: trang Health (DB ping & độ trễ, dung lượng ổ đĩa, kích thước storage, độ dài hàng đợi thông báo, lần chạy cron gần nhất,
   phiên bản PHP/MySQL, số phiên đang hoạt động), endpoint `/api/v1/health` (JSON, dùng cho Prometheus/Zabbix ở production).
4. Quản lý sự cố & BCP/DRP: ghi nhận sự cố (loại: đứt TSLCD, DDoS, Ransomware, Deface, sự cố CSDL…), mức độ, thời gian phát hiện/khắc phục,
   người xử lý, RACI (Sở TT&TT vs đơn vị nghiệp vụ); kế hoạch & biên bản diễn tập (an toàn thông tin 6 tháng/lần, chuyển đổi thảm họa 12 tháng/lần)
   có nhắc lịch; chế độ bảo trì (Static Maintenance Site) bật/tắt từ quản trị – trả trang thông báo tĩnh cho công dân.
5. PH15 – Đào tạo & chuyển giao: khóa đào tạo theo 4 nhóm đối tượng (Một cửa, Chuyên viên, Lãnh đạo ký số, SysAdmin) với nội dung & phương thức đánh giá
   đúng bảng §1.4; ghi danh học viên, nhập điểm/kết quả thực hành (Một cửa ≥ 90/100; chuyên viên hoàn thành 10 hồ sơ mẫu không lỗi;
   lãnh đạo ký số thành công; SysAdmin restore trong RTO < 4h), báo cáo tỷ lệ đạt; môi trường Staging = cờ APP_ENV=staging có banner nhận diện.
6. Viết `docs/05_QUY_CHE_VAN_HANH.md`: RACI, SOP vận hành hằng ngày/tuần/tháng, quy trình sao lưu – phục hồi, kịch bản ứng cứu sự cố.

Tiêu chí hoàn thành: sao lưu/phục hồi chạy thật trên XAMPP; trang Health, sự cố, đào tạo hoạt động; PROGRESS.md; commit.
```

---

### PHIÊN 14 – CHUYỂN ĐỔI DỮ LIỆU ETL, TÍCH HỢP & REST API (PH14 – §1.1, §1.3)

```text
[HP-eGov | PHIÊN 14/16 – ETL, TÍCH HỢP & API]

Đọc rule (mục 12), PROGRESS.md, báo cáo §1.1, §1.3.

1. ETL Pipeline 4 bước đúng báo cáo (giao diện wizard + chạy theo lô, xử lý từng phần để không timeout):
   - Extract: tải lên tệp CSV xuất từ hệ thống Một cửa cũ (cung cấp 2 tệp mẫu `database/etl_samples/` mô phỏng MS SQL Server & MySQL cũ, 5 năm gần nhất,
     có dữ liệu bẩn) + thư mục tệp đính kèm.
   - Staging & Profiling: nạp nguyên trạng vào etl_staging_ho_so; báo cáo trường rác, hồ sơ trùng, thiếu CCCD/CMND, mã TTHC/ĐVHC không hợp lệ.
   - Transform: ánh xạ mã TTHC cũ → mã TTHC quốc gia, mã ĐVHC cũ → mã TCTK (bảng ánh xạ chỉnh được), chuẩn hóa ngày, họ tên, trạng thái; đánh dấu tệp cần chuyển PDF/A-1b.
   - Load & Verify: nạp vào bảng chính trong transaction theo lô, tính & đối soát SHA-256 100% tệp đính kèm; báo cáo kết quả (thành công/lỗi/bỏ qua), xuất danh sách lỗi.
2. Quản trị tích hợp: danh sách kết nối (VNeID, LGSP, C06, CSDL TTHC QG, SmartCA, SMS, Zalo ZNS, VNPost, ClamAV) – endpoint, chế độ mock/live,
   nút "Kiểm tra kết nối", thống kê nhat_ky_tich_hop (số lượt, tỷ lệ lỗi, độ trễ trung bình).
3. REST API `/api/v1` (JSON, xác thực Bearer token có hạn, rate limit, chuẩn lỗi thống nhất, tài liệu `docs/06_API.md`):
   tra cứu hồ sơ theo mã (công khai tối thiểu), danh mục TTHC, hồ sơ của công dân (token công dân), danh sách chờ ký & ký số cho Mobile App lãnh đạo,
   webhook nhận kết quả từ LGSP (xác thực chữ ký HMAC), health.
4. Test: ETL với dữ liệu bẩn; API sai token → 401, sai quyền → 403, vượt rate → 429; webhook sai HMAC → từ chối.

Tiêu chí hoàn thành: chạy trọn ETL với tệp mẫu, có báo cáo đối soát; API hoạt động theo tài liệu; PROGRESS.md; commit.
```

---

### PHIÊN 15 – KIỂM THỬ TOÀN DIỆN (Chương 3)

```text
[HP-eGov | PHIÊN 15/16 – KIỂM THỬ & QA/QC]

Đọc rule, PROGRESS.md, báo cáo Chương 3. Dùng /plan.

1. `docs/07_KE_HOACH_KIEM_THU.md`: chiến lược 5 loại kiểm thử đúng §3.1 (Functional – 100% use case theo ma trận RBAC; Integration – Xã → Huyện → Sở → VP UBND TP;
   Performance; Security – OWASP Top 10, Cấp độ 3; UAT – Q.Hồng Bàng & Sở GTVT), môi trường, tiêu chí vào/ra.
2. Functional: bộ test case (mã TC, tiền điều kiện, bước, dữ liệu, kết quả mong đợi) cho mọi chức năng FRxx.yy; tự động hóa phần lõi trong tests/;
   dùng browser subagent chạy các luồng E2E chính cho từng vai trò và ghi video.
3. Integration: hoàn thiện LienThongLogisticsTest + luồng Xã → Huyện; kiểm tra toàn vẹn tệp & chữ ký qua từng trạm.
4. Security (tests/Security): SQLi trên mọi tham số tìm kiếm/lọc; IDOR đổi `hoso_id` từ công dân A sang B → 403; XSS lưu trữ & phản chiếu;
   CSRF thiếu/sai token → 419; upload đổi đuôi/mime giả/file > 10MB; brute-force đăng nhập → khóa; rate limit 60 req/phút/IP → 429;
   kiểm tra header bảo mật & cookie flags; SYSADMIN không truy cập được nghiệp vụ/ký số; thử DELETE vật lý ho_so → bị trigger chặn; verify audit chain.
   Lập `docs/08_BAO_CAO_KIEM_THU_ATTT.md` đối chiếu OWASP WSTG.
5. Performance: script k6 (`tests/performance/portal_5000ccu.js`, `noibo_1200ccu.js`) và kế hoạch JMeter theo kịch bản báo cáo (5.000 CCU công dân,
   1.200 CCU cán bộ, file 2–10MB, ký số hàng loạt); chạy bản thu nhỏ trên máy dev, ghi số liệu thực đo (Response TB, P95, Error rate) so với ngưỡng
   (≤1.0s, ≤1.5s, ≤0.01%); tối ưu truy vấn chậm (EXPLAIN, chỉ mục) nếu vượt ngưỡng. Ghi rõ giới hạn môi trường XAMPP so với Staging thật.
6. UAT: kịch bản UAT 30 ngày cho Bộ phận Một cửa Q.Hồng Bàng (đất đai, hộ tịch, GPXD) và Sở GTVT (GPLX, phù hiệu xe container); phiếu khảo sát
   người dùng; biểu mẫu biên bản nghiệm thu.
7. Sửa toàn bộ lỗi phát hiện; `php tests/run.php` xanh 100%; `docs/09_BAO_CAO_KET_QUA_KIEM_THU.md` (tổng hợp số test, đạt/không đạt, lỗi đã sửa, tồn đọng).

Tiêu chí hoàn thành: toàn bộ test xanh, báo cáo kiểm thử đầy đủ; PROGRESS.md; commit.
```

---

### PHIÊN 16 – CLEAN CODE, HARDENING & BÀN GIAO

```text
[HP-eGov | PHIÊN 16/16 – CLEAN CODE, HARDENING & BÀN GIAO]

Đọc rule, PROGRESS.md, toàn bộ docs/.

1. Rà soát clean code toàn dự án: đúng cấu trúc thư mục & quy ước đặt tên (rule mục 4–5); controller mỏng; loại bỏ code trùng lặp, code chết, file thừa,
   comment rác, debug; thay magic string bằng Enum; hàm > 40 dòng tách nhỏ; PHPDoc tiếng Việt đầy đủ; php -l toàn bộ; đồng nhất định dạng PSR-12.
2. Rà soát CSS/JS: gom biến dùng chung, xóa class không dùng, không inline style/script, kiểm tra console sạch trên mọi trang.
3. Hardening: APP_ENV=production ẩn lỗi; tạo user MySQL `hpegov_app` quyền tối thiểu (SELECT/INSERT/UPDATE/EXECUTE; DELETE chỉ trên bảng kỹ thuật như
   gioi_han_truy_cap); kiểm tra mọi route có middleware phù hợp (in bảng route × middleware × quyền); .htaccess chặn truy cập app/, storage/, database/, .env.
4. Đối chiếu đồng nhất cuối cùng với báo cáo PDF: cập nhật `docs/00_TRUY_VET_BAO_CAO.md` – từng mục báo cáo → file/chức năng đã hiện thực → trạng thái
   (Đã làm / Mô phỏng / Tài liệu); liệt kê sai khác (nếu có) và lý do.
5. Tài liệu bàn giao:
   - `README.md`: giới thiệu, yêu cầu, cài đặt trên XAMPP từng bước (import schema.sql & seed.sql qua phpMyAdmin, cấu hình .env, tạo khóa demo, cron), tài khoản mẫu.
   - `docs/10_HDSD_<VAI_TRO>.md` cho 7 vai trò (có ảnh chụp màn hình).
   - `docs/11_TRIEN_KHAI_PRODUCTION.md`: kiến trúc 3 vùng mạng (Edge CDN/WAF – DMZ – Nội bộ TSLCD – CSDL), HA (HAProxy/NGINX/Keepalived),
     MySQL replication Master–Replica (failover < 30 giây), cấu hình mẫu NGINX/PHP-FPM, CSP ở reverse proxy, lộ trình 3 giai đoạn của báo cáo.
6. Chạy lại toàn bộ test + kiểm thử E2E nhanh bằng trình duyệt cho 7 vai trò; tạo bản phát hành `git tag v1.0.0`.

Tiêu chí hoàn thành: dự án sạch, chạy ổn định, tài liệu đầy đủ, đồng nhất với báo cáo; PROGRESS.md đánh dấu hoàn thành 17/17 phiên; commit & tag.
```

---

## PHẦN V – PROMPT TIỆN ÍCH

### P-01 · Tiếp tục phiên bị gián đoạn
```text
[HP-eGov | TIẾP TỤC PHIÊN X]
Đọc .agents/rules/hp-egov.md, docs/PROGRESS.md và `git log -n 10 --stat`. Xác định các hạng mục của Phiên X đã xong/chưa xong
so với prompt gốc trong docs/PROMPT_XAY_DUNG_HP-eGov_THEO_PHIEN.md. Liệt kê ngắn gọn, sau đó tiếp tục làm các hạng mục còn lại.
Không viết lại phần đã hoàn thành.
```

### P-02 · Sửa lỗi
```text
[HP-eGov | SỬA LỖI]
Lỗi: <mô tả, URL, tài khoản, thao tác, thông báo lỗi/ảnh chụp>.
Hãy: (1) tái hiện lỗi, (2) tìm nguyên nhân gốc (đọc storage/logs), (3) sửa tối thiểu đúng kiến trúc MVC & quy ước rule,
(4) viết test hồi quy, (5) chạy php -l và tests/run.php, (6) ghi vào mục "Lỗi đã sửa" trong PROGRESS.md, commit.
```

### P-03 · Rà soát đồng nhất với báo cáo
```text
[HP-eGov | RÀ SOÁT ĐỒNG NHẤT]
Đối chiếu mã nguồn hiện tại với báo cáo PDF và .agents/rules/hp-egov.md theo: 7 vai trò & ma trận CRUD, 9 trạng thái, quy trình 5 bước,
định dạng mã hồ sơ QCVN 102, công thức SLA Xanh/Vàng/Đỏ, Mẫu 04, chính sách upload, rate limit 60/phút, Backup/RPO/RTO, nguyên tắc không xóa vật lý.
Xuất bảng: Hạng mục | Báo cáo yêu cầu | Hiện trạng code | Đạt/Không đạt | Đề xuất sửa. Chưa sửa code, chờ tôi duyệt.
```

### P-04 · Thêm thủ tục hành chính / quy trình mới
```text
[HP-eGov | THÊM TTHC]
Thêm TTHC "<tên>" (cấp <xã/huyện/sở>, thời hạn <n> ngày làm việc, thành phần hồ sơ: <...>, liên thông: <đơn vị...>).
Tạo migration seed (TTHC, thành phần, E-form JSON, quy trình + bước + chuyển tiếp, tổng thời hạn khớp), không sửa code lõi.
Kiểm tra bằng cách nộp thử 1 hồ sơ và chạy đến khi trả kết quả.
```

---

## PHẦN VI – CÁC ĐIỂM ĐÃ CHUẨN HÓA SO VỚI BÁO CÁO

Bộ prompt giữ nguyên toàn bộ số liệu và quy định của báo cáo. Có 6 điểm được làm rõ để lập trình được, cần bạn xác nhận:

| # | Nội dung trong báo cáo | Cách xử lý trong prompt | Lý do |
|---|---|---|---|
| 1 | CSDL lõi ghi "PostgreSQL/Oracle HA" (trang 4) | Dùng **MySQL/MariaDB qua phpMyAdmin**; class `Database` tách kết nối đọc/ghi để mô phỏng Master/Replica | Theo yêu cầu của bạn; sơ đồ trang 4 đã có cổng 3306 |
| 2 | SLA: "Xanh (t ≥ 75%·T)" mâu thuẫn với "Vàng (75%·T < t ≤ 100%·T)" | Định nghĩa **t = thời gian đã dùng** → Xanh: **t ≤ 75%·T** | Nếu giữ "≥" thì hai khoảng chồng lên nhau |
| 3 | Công thức mã hồ sơ ghi `{Năm_Tiếp_Nhận}`, nhưng ví dụ là `260115` | Theo ví dụ: `yyMMdd`; STT tăng theo (đơn vị, năm) | Ví dụ trong báo cáo cụ thể hơn |
| 4 | Báo cáo nêu 6 trạng thái theo TT 01/2018, đồng thời có `status = CANCELLED` | Giữ 6 trạng thái chuẩn, thêm 3 trạng thái mở rộng (`CHO_TIEP_NHAN`, `TU_CHOI`, `DA_HUY`); báo cáo thống kê quy về 6 nhóm chuẩn | Cần cho luồng nộp trực tuyến và hủy logic |
| 5 | Mô hình 15 quận/huyện (3 cấp chính quyền) | Giữ đúng báo cáo; bảng ĐVHC có hiệu lực từ/đến để chuyển sang mô hình 2 cấp sau sắp xếp năm 2025 mà không phải sửa code | Báo cáo §4.2 yêu cầu chuẩn bị cho sự thay đổi ĐVHC |
| 6 | VNeID, LGSP/C06, SmartCA, SMS, Zalo ZNS, VNPost, ClamAV, HA, 3 vùng mạng | Tích hợp ngoài: **Adapter mô phỏng** (ký số chạy OpenSSL thật). Hạ tầng (HA, WAF, TSLCD): **tài liệu triển khai** | Không có kết nối thật trên XAMPP; đổi sang `INTEGRATION_MODE=live` khi có kết nối |
