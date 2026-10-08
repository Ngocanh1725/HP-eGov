# MA TRẬN ĐIỀU HƯỚNG, MIDDLEWARE & PHÂN QUYỀN TRUY CẬP (ROUTE MATRIX)
## HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)
**Căn cứ kiểm soát an ninh:** OWASP WSTG v4.2 & Nghị định 85/2016/NĐ-CP (Cấp độ 3)

---

### I. CỔNG DỊCH VỤ CÔNG & CÔNG DÂN (PORTAL ZONE)

| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | `home` | `HomeController@index` | `security_headers` | Public (Tất cả) |
| `GET` | `/ping` | `ping` | `HomeController@ping` | `security_headers` | Public |
| `GET` | `/captcha` | `captcha` | `CaptchaController@render` | `security_headers` | Public |
| `GET` | `/dvc/thu-tuc` | `portal.dvc` | `DvcController@index` | `security_headers` | Public |
| `GET` | `/dvc/thu-tuc/{ma}` | `portal.dvc.detail` | `DvcController@detail` | `security_headers` | Public |
| `GET` | `/tra-cuu` | `portal.tra_cuu` | `TraCuuController@index` | `security_headers`, `rate_limit` | Public (Mã / QR Camera) |
| `GET` | `/kiem-tra-chu-ky` | `portal.kiem_tra_chu_ky` | `KiemTraChuKyController@index` | `security_headers` | Public |
| `POST` | `/kiem-tra-chu-ky/verify-file` | `portal.kiem_tra_chu_ky.verify_file` | `KiemTraChuKyController@verifyFile` | `security_headers`, `csrf` | Public (Tải file đối soát) |
| `GET` | `/tro-giup` | `portal.help` | `HelpController@index` | `security_headers` | Public |
| `GET` | `/dang-nhap` | `portal.login` | `PortalAuthController@showLogin` | `security_headers` | Public / Chưa đăng nhập |
| `POST` | `/dang-nhap-doanh-nghiep` | `portal.login_doanh_nghiep` | `PortalAuthController@loginDoanhNghiep` | `security_headers`, `csrf` | Doanh nghiệp MST |
| `GET` | `/dang-xuat` | `portal.logout` | `PortalAuthController@logout` | `security_headers` | Đang đăng nhập |
| `GET` | `/auth/vneid/login` | `portal.vneid_login` | `PortalAuthController@vneidLogin` | `security_headers` | SSO VNeID Sandbox |
| `GET` | `/auth/vneid/sandbox` | `portal.vneid_sandbox` | `PortalAuthController@vneidSandbox` | `security_headers` | Giả lập định danh Mức 2 |
| `POST` | `/auth/vneid/consent` | `portal.vneid_consent` | `PortalAuthController@vneidConsent` | `security_headers`, `csrf` | Cấp quyền chia sẻ C06 |
| `GET` | `/auth/vneid/callback` | `portal.vneid_callback` | `PortalAuthController@vneidCallback` | `security_headers` | Nhận mã code & Token |
| `GET` | `/dvc/nop-ho-so/{tthc_id}` | `portal.nop_ho_so` | `NopHoSoController@wizard` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Đã đăng nhập) |
| `POST` | `/dvc/nop-ho-so/{tthc_id}` | `portal.nop_ho_so.submit` | `NopHoSoController@submit` | `security_headers`, `auth:cong_dan`, `csrf` | `CONG_DAN` |
| `GET` | `/dvc/nop-ho-so/thanh-cong/{ma}` | `portal.nop_ho_so.success` | `NopHoSoController@success` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Chủ hồ sơ) |
| `GET` | `/cong-dan/ho-so` | `portal.ho_so` | `HoSoCaNhanController@index` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |
| `GET` | `/cong-dan/ho-so/{ma}` | `portal.ho_so.detail` | `HoSoCaNhanController@show` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Anti-IDOR) |
| `GET` | `/cong-dan/ho-so/{ma}/bo-sung` | `portal.ho_so.bo_sung` | `HoSoCaNhanController@boSungForm` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Anti-IDOR) |
| `POST` | `/cong-dan/ho-so/{ma}/bo-sung` | `portal.ho_so.bo_sung.submit` | `HoSoCaNhanController@submitBoSung` | `security_headers`, `auth:cong_dan`, `csrf` | `CONG_DAN` (Anti-IDOR) |
| `GET` | `/cong-dan/kho-du-lieu` | `portal.kho_du_lieu` | `HoSoCaNhanController@khoDuLieu` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |
| `GET` | `/cong-dan/ho-so/{ma}/danh-gia-sipas` | `portal.sipas` | `HoSoCaNhanController@sipasForm` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |
| `POST` | `/cong-dan/ho-so/{ma}/danh-gia-sipas` | `portal.sipas.submit` | `HoSoCaNhanController@submitSipas` | `security_headers`, `auth:cong_dan`, `csrf` | `CONG_DAN` |
| `GET` | `/cong-dan/thong-bao` | `portal.notifications` | `NotificationPortalController@index` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |

---

### II. HỆ THỐNG MỘT CỬA NỘI BỘ & QUẢN TRỊ (ADMIN ZONE)

| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn bắt buộc |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/dang-nhap` | `admin.login` | `AdminAuthController@showLogin` | `security_headers`, `guest:can_bo` | Cán bộ chưa đăng nhập |
| `POST` | `/admin/dang-nhap` | `admin.login.submit` | `AdminAuthController@login` | `security_headers`, `guest:can_bo`, `csrf`, `rate_limit` | Chống Brute-force & Lockout |
| `GET` | `/admin/dang-xuat` | `admin.logout` | `AdminAuthController@logout` | `security_headers` | Cán bộ nội bộ |
| `GET` | `/admin/dashboard` | `admin.dashboard` | `DashboardController@index` | `security_headers`, `auth:can_bo` | 6 vai trò cán bộ |
| `GET` | `/admin/dashboard/ioc` | `admin.dashboard.ioc` | `DashboardController@ioc` | `security_headers`, `auth:can_bo`, `permission:bao_cao.view` | `LD_CO_QUAN`, `SYSADMIN` |
| `GET` | `/admin/mot-cua/tiep-nhan` | `admin.mot_cua.tiep_nhan` | `MotCuaController@create` | `security_headers`, `auth:can_bo`, `permission:mot_cua.tiep_nhan` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/tiep-nhan` | `admin.mot_cua.store` | `MotCuaController@store` | `security_headers`, `auth:can_bo`, `csrf`, `permission:mot_cua.tiep_nhan` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/api/verify-cccd` | `admin.mot_cua.api.verify_cccd` | `MotCuaController@verifyCccdApi` | `security_headers`, `auth:can_bo`, `csrf`, `permission:mot_cua.tiep_nhan` | `CB_MOT_CUA` (C06 LGSP) |
| `GET` | `/admin/mot-cua/in-giay-hen/{id}` | `admin.mot_cua.in_giay_hen` | `MotCuaController@inGiayHen` | `security_headers`, `auth:can_bo`, `permission:mot_cua.tiep_nhan` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/tra-ket-qua` | `admin.mot_cua.tra_ket_qua` | `MotCuaController@danhSachTraKetQua` | `security_headers`, `auth:can_bo`, `permission:mot_cua.tra_kq` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/tra-ket-qua/{id}` | `admin.mot_cua.xac_nhan_tra` | `MotCuaController@xacNhanTraKetQua` | `security_headers`, `auth:can_bo`, `csrf`, `permission:mot_cua.tra_kq` | `CB_MOT_CUA` |
| `GET` | `/admin/thu-ly` | `admin.thu_ly.index` | `ThuLyController@index` | `security_headers`, `auth:can_bo`, `permission:thu_ly.view` | `CHUYEN_VIEN` |
| `GET` | `/admin/thu-ly/chi-tiet/{id}` | `admin.thu_ly.detail` | `ThuLyController@show` | `security_headers`, `auth:can_bo`, `permission:thu_ly.view` | `CHUYEN_VIEN` (Data Scope) |
| `POST` | `/admin/thu-ly/chuyen-buoc` | `admin.thu_ly.chuyen_buoc` | `ThuLyController@chuyenBuoc` | `security_headers`, `auth:can_bo`, `csrf`, `permission:thu_ly.edit` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/lay-y-kien` | `admin.thu_ly.lay_y_kien` | `ThuLyController@layYKien` | `security_headers`, `auth:can_bo`, `csrf`, `permission:thu_ly.edit` | `CHUYEN_VIEN` (Liên thông) |
| `POST` | `/admin/thu-ly/tra-loi-y-kien` | `admin.thu_ly.tra_loi_y_kien` | `ThuLyController@traLoiYKien` | `security_headers`, `auth:can_bo`, `csrf`, `permission:thu_ly.edit` | `CHUYEN_VIEN` (Phối hợp) |
| `GET` | `/admin/phe-duyet` | `admin.phe_duyet.index` | `PheDuyetController@index` | `security_headers`, `auth:can_bo`, `permission:phe_duyet.view` | `LD_PHONG`, `LD_CO_QUAN` |
| `POST` | `/admin/phe-duyet/ky-so-buoc-1` | `admin.phe_duyet.ky_buoc_1` | `PheDuyetController@kyDuyetBuoc1` | `security_headers`, `auth:can_bo`, `csrf`, `permission:kyso.duyet_buoc_1` | `LD_PHONG` (SYSADMIN bị cấm) |
| `POST` | `/admin/phe-duyet/ky-so-phat-hanh` | `admin.phe_duyet.ky_phat_hanh` | `PheDuyetController@kyPhatHanh` | `security_headers`, `auth:can_bo`, `csrf`, `permission:kyso.phat_hanh` | `LD_CO_QUAN` (SYSADMIN bị cấm) |
| `POST` | `/admin/phe-duyet/batch-sign` | `admin.phe_duyet.batch_sign` | `PheDuyetController@batchSign` | `security_headers`, `auth:can_bo`, `csrf`, `permission:kyso.phat_hanh` | `LD_CO_QUAN` |
| `GET` | `/admin/van-thu` | `admin.van_thu.index` | `VanThuController@index` | `security_headers`, `auth:can_bo`, `permission:van_thu.view` | `VAN_THU` |
| `POST` | `/admin/van-thu/dong-dau` | `admin.van_thu.dong_dau` | `VanThuController@dongDau` | `security_headers`, `auth:can_bo`, `csrf`, `permission:van_thu.dong_dau` | `VAN_THU` (Con dấu số tổ chức) |
| `GET` | `/admin/bao-cao/thong-tu-01` | `admin.bao_cao.tt01` | `BaoCaoController@thongTu01` | `security_headers`, `auth:can_bo`, `permission:bao_cao.view` | `LD_CO_QUAN`, `SYSADMIN` |
| `GET` | `/admin/he-thong/nguoi-dung` | `admin.he_thong.user.index` | `UserController@index` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` |
| `GET` | `/admin/he-thong/etl` | `admin.he_thong.etl` | `EtlController@index` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` (Wizard 4 bước) |
| `GET` | `/admin/he-thong/tich-hop` | `admin.he_thong.tich_hop` | `TichHopController@index` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` (9 Cổng dịch vụ) |
| `GET` | `/admin/he-thong/audit` | `admin.he_thong.audit` | `HeThongController@audit` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` (Chuỗi băm WORM) |
| `GET` | `/admin/he-thong/sao-luu` | `admin.he_thong.sao_luu` | `HeThongController@saoLuu` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` (RPO $\le 2$h, RTO $\le 4$h) |
| `GET` | `/admin/he-thong/su-co` | `admin.he_thong.su_co` | `HeThongController@suCo` | `security_headers`, `auth:can_bo`, `permission:he_thong.manage` | `SYSADMIN` (BCP/DRP) |

---

### III. RESTFUL API CỔNG MỞ (/API/V1)

| Phương thức | URI Endpoint | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Cơ chế xác thực & Phân quyền |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | `api.health` | Closure / `HealthCheckService` | `security_headers`, `rate_limit` | Công khai (Prometheus Metric) |
| `GET` | `/api/v1/tra-cuu/{ma}` | `api.public.tra_cuu` | `PublicApiController@traCuu` | `security_headers`, `rate_limit` | Công khai (Ẩn dữ liệu nhạy cảm) |
| `GET` | `/api/v1/thu-tuc-hanh-chinh` | `api.public.tthc` | `PublicApiController@danhMucTthc` | `security_headers`, `rate_limit` | Công khai (Phân trang MDM) |
| `GET` | `/api/v1/cong-dan/ho-so` | `api.citizen.ho_so.index` | `CitizenApiController@danhSachHoSo` | `security_headers`, `rate_limit`, `api_auth:cong_dan` | Bearer Token Scope `cong_dan` |
| `GET` | `/api/v1/cong-dan/ho-so/{ma}` | `api.citizen.ho_so.detail` | `CitizenApiController@chiTietHoSo` | `security_headers`, `rate_limit`, `api_auth:cong_dan` | Bearer Token (Chống IDOR) |
| `GET` | `/api/v1/lanh-dao/cho-ky` | `api.leader.cho_ky` | `LeaderApiController@danhSachChoKy` | `security_headers`, `rate_limit`, `api_auth:lanh_dao` | Bearer Token Scope `lanh_dao` |
| `POST` | `/api/v1/lanh-dao/ky-so` | `api.leader.ky_so` | `LeaderApiController@kySo` | `security_headers`, `rate_limit`, `api_auth:lanh_dao` | Bearer Token + PIN SmartCA |
| `POST` | `/api/v1/webhook/lgsp` | `api.webhook.lgsp` | `WebhookController@handleLgsp` | `security_headers`, `rate_limit` | Xác thực Chữ ký HMAC-SHA256 |
