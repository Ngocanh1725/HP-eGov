# MA TRẬN ĐIỀU HƯỚNG, MIDDLEWARE & PHÂN QUYỀN TRUY CẬP (ROUTE MATRIX)
## HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)
**Căn cứ kiểm soát an ninh:** OWASP WSTG v4.2 & Nghị định 85/2016/NĐ-CP (Cấp độ 3)

---

### I. CỔNG DỊCH VỤ CÔNG & CÔNG DÂN (PORTAL ZONE)

| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | `home` | `HomeController@index` | `security_headers` | Public (Tất cả) |
| `GET` | `/ping` | `ping` | `HomeController@ping` | `security_headers` | Public |
| `GET` | `/captcha` | `captcha` | `CaptchaController@render` | `security_headers` | Public (Mã chống Bot) |
| `GET` | `/dvc/thu-tuc` | `portal.dvc` | `DvcController@index` | `security_headers` | Public |
| `GET` | `/thu-tuc-hanh-chinh` | - | `DvcController@index` | `security_headers` | Public (Alias) |
| `GET` | `/dvc/thu-tuc/{ma}` | `portal.dvc.detail` | `DvcController@detail` | `security_headers` | Public |
| `GET` | `/tra-cuu` | `portal.tra_cuu` | `TraCuuController@index` | `security_headers`, `rate_limit` | Public (Mã / QR Camera) |
| `GET` | `/kiem-tra-chu-ky` | `portal.kiem_tra_chu_ky` | `KiemTraChuKyController@index` | `security_headers` | Public |
| `POST` | `/kiem-tra-chu-ky/verify-file` | `portal.kiem_tra_chu_ky.verify_file` | `KiemTraChuKyController@verifyFile` | `security_headers`, `csrf` | Public (Tải file đối soát PKI) |
| `GET` | `/tro-giup` | `portal.help` | `HelpController@index` | `security_headers` | Public |
| `GET` | `/ho-tro` | - | `HelpController@index` | `security_headers` | Public (Alias) |
| `GET` | `/nop-ho-so` | - | Closure $\rightarrow$ redirect `/dvc/thu-tuc` | `security_headers` | Public |
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
| `GET` | `/cong-dan/ho-so/{ma}` | `portal.ho_so.detail` | `HoSoCaNhanController@show` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Chống IDOR - 403) |
| `GET` | `/cong-dan/ho-so/{ma}/bo-sung` | `portal.ho_so.bo_sung` | `HoSoCaNhanController@boSungForm` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Chống IDOR - 403) |
| `POST` | `/cong-dan/ho-so/{ma}/bo-sung` | `portal.ho_so.bo_sung.submit` | `HoSoCaNhanController@submitBoSung` | `security_headers`, `auth:cong_dan`, `csrf` | `CONG_DAN` (Chống IDOR - 403) |
| `GET` | `/cong-dan/kho-du-lieu` | `portal.kho_du_lieu` | `HoSoCaNhanController@khoDuLieu` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |
| `GET` | `/cong-dan/ho-so/{ma}/danh-gia-sipas` | `portal.sipas` | `HoSoCaNhanController@sipasForm` | `security_headers`, `auth:cong_dan` | `CONG_DAN` (Chống IDOR - 403) |
| `POST` | `/cong-dan/ho-so/{ma}/danh-gia-sipas` | `portal.sipas.submit` | `HoSoCaNhanController@submitSipas` | `security_headers`, `auth:cong_dan`, `csrf` | `CONG_DAN` (Chống IDOR - 403) |
| `GET` | `/cong-dan/thong-bao` | `portal.notifications` | `NotificationPortalController@index` | `security_headers`, `auth:cong_dan` | `CONG_DAN` |

---

### II. HỆ THỐNG MỘT CỬA NỘI BỘ & QUẢN TRỊ (ADMIN ZONE)

#### 1. Xác thực cán bộ & Hồ sơ cá nhân
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin` | - | Closure $\rightarrow$ redirect `/admin/dang-nhap` | `security_headers` | Public |
| `GET` | `/admin/dang-nhap` | `admin.login` | `AdminAuthController@showLogin` | `security_headers` | Cán bộ chưa đăng nhập |
| `POST` | `/admin/dang-nhap` | `admin.login.submit` | `AdminAuthController@login` | `security_headers`, `csrf`, `rate_limit` | Chống Brute-force (khóa 5 lần) |
| `GET` | `/admin/dang-xuat` | `admin.logout` | `AdminAuthController@logout` | `security_headers` | Cán bộ nội bộ |
| `GET` | `/admin/doi-mat-khau-lan-dau` | `admin.force_change_pwd` | `AdminAuthController@showForceChangePassword` | `security_headers` | Cán bộ kích hoạt tài khoản |
| `POST` | `/admin/doi-mat-khau-lan-dau` | `admin.force_change_pwd.submit` | `AdminAuthController@forceChangePassword` | `security_headers`, `csrf` | Cán bộ kích hoạt tài khoản |
| `GET` | `/admin/chuyen-don-vi/{id}` | `admin.switch_don_vi` | `AdminAuthController@switchDonVi` | `security_headers`, `auth:can_bo` | Cán bộ kiêm nhiệm |
| `GET` | `/admin/ho-so-ca-nhan` | `admin.profile` | `ProfileController@index` | `security_headers`, `auth:can_bo` | Cán bộ |
| `POST` | `/admin/doi-mat-khau` | `admin.change_pwd` | `ProfileController@changePassword` | `security_headers`, `auth:can_bo`, `csrf` | Cán bộ |

#### 2. Dashboard, IOC & UI-Kit
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | `admin.dashboard` | `DashboardController@index` | `security_headers`, `auth:can_bo` | 6 vai trò cán bộ |
| `GET` | `/admin/ioc` | `admin.ioc` | `DashboardController@ioc` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN`, `SYSADMIN` |
| `GET` | `/admin/api/ioc-stats` | `admin.api.ioc_stats` | `DashboardController@apiIocStats` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN`, `SYSADMIN` |
| `GET` | `/admin/api/ioc-district/{id}` | `admin.api.ioc_district` | `DashboardController@apiDistrictDetail` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN`, `SYSADMIN` |
| `GET` | `/admin/ui-kit` | `admin.ui_kit` | `UiKitController@index` | `security_headers`, `auth:can_bo` | Cán bộ / Developer |

#### 3. Bộ phận Một cửa (CB_MOT_CUA - PH02)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/mot-cua` | `admin.motcua.dashboard` | `MotCuaController@dashboard` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/tiep-nhan` | `admin.motcua.tiepNhanForm` | `MotCuaController@tiepNhanForm` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/api/verify-cccd` | `admin.motcua.verifyCccd` | `MotCuaController@apiVerifyCccd` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` (C06 LGSP) |
| `POST` | `/admin/mot-cua/tiep-nhan` | `admin.motcua.submitTiepNhan` | `MotCuaController@submitTiepNhan` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/in-giay-hen/{id}` | `admin.motcua.inGiayHen` | `MotCuaController@inGiayHen` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `GET` | `/admin/ho-so/{id}/in-phieu-hen` | `admin.hoso.inPhieuHen` | `MotCuaController@inGiayHen` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` (Alias) |
| `GET` | `/admin/mot-cua/truc-tuyen` | `admin.motcua.hoSoTrucTuyen` | `MotCuaController@danhSachTrucTuyen` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/truc-tuyen/{id}` | `admin.motcua.chiTietTrucTuyen` | `MotCuaController@chiTietTrucTuyen` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/truc-tuyen/{id}/tiep-nhan` | `admin.motcua.tiepNhanTrucTuyen` | `MotCuaController@tiepNhanTrucTuyen` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/truc-tuyen/{id}/yeu-cau-bo-sung` | `admin.motcua.yeuCauBoSung` | `MotCuaController@yeuCauBoSung` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/truc-tuyen/{id}/in-phieu-bo-sung` | `admin.motcua.inPhieuBoSung` | `MotCuaController@inPhieuBoSung` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/truc-tuyen/{id}/tu-choi` | `admin.motcua.tuChoiTiepNhan` | `MotCuaController@tuChoiTiepNhan` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/truc-tuyen/{id}/in-phieu-tu-choi` | `admin.motcua.inPhieuTuChoi` | `MotCuaController@inPhieuTuChoi` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/tra-ket-qua` | `admin.motcua.traKetQua` | `MotCuaController@traKetQua` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/tra-ket-qua` | `admin.motcua.submitTraKetQua` | `MotCuaController@submitTraKetQua` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `POST` | `/admin/mot-cua/de-xuat-huy` | `admin.motcua.deXuatHuy` | `MotCuaController@deXuatHuy` | `security_headers`, `auth:can_bo`, `csrf` | `CB_MOT_CUA` |
| `GET` | `/admin/mot-cua/so-theo-doi` | `admin.motcua.soTheoDoi` | `MotCuaController@soTheoDoi` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` (Mẫu 06) |
| `GET` | `/admin/mot-cua/so-theo-doi/export` | `admin.motcua.exportSoTheoDoi` | `MotCuaController@exportSoTheoDoi` | `security_headers`, `auth:can_bo` | `CB_MOT_CUA` (CSV UTF-8 BOM) |

#### 4. Phân hệ Thụ lý & Phối hợp Liên thông (CHUYEN_VIEN, LD_PHONG - PH03)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/thu-ly` | `admin.thuly.inbox` | `ThuLyController@inbox` | `security_headers`, `auth:can_bo` | `CHUYEN_VIEN`, `LD_PHONG` |
| `GET` | `/admin/thu-ly/{id}` | `admin.thuly.detail` | `ThuLyController@detail` | `security_headers`, `auth:can_bo` | `CHUYEN_VIEN` (Data Scope) |
| `POST` | `/admin/thu-ly/{id}/phan-cong` | `admin.thuly.phan_cong` | `ThuLyController@phanCong` | `security_headers`, `auth:can_bo`, `csrf` | `LD_PHONG` |
| `POST` | `/admin/thu-ly/{id}/chuyen-tiep` | `admin.thuly.chuyen_tiep` | `ThuLyController@chuyenTiep` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/{id}/tra-lai` | `admin.thuly.tra_lai` | `ThuLyController@traLai` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN`, `LD_PHONG` |
| `POST` | `/admin/thu-ly/{id}/yeu-cau-bo-sung` | `admin.thuly.yeu_cau_bo_sung` | `ThuLyController@yeuCauBoSung` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/{id}/tiep-tuc-bo-sung` | `admin.thuly.tiep_tuc_bo_sung` | `ThuLyController@tiepTucSauBoSung` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/{id}/lay-y-kien` | `admin.thuly.lay_y_kien` | `ThuLyController@layYKien` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` (Liên thông) |
| `POST` | `/admin/thu-ly/y-kien/{id}/tra-loi` | `admin.thuly.tra_loi_y_kien` | `ThuLyController@traLoiYKien` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` (Phối hợp) |
| `POST` | `/admin/thu-ly/{id}/du-thao` | `admin.thuly.luu_du_thao` | `ThuLyController@luuDuThao` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/{id}/thu-hoi` | `admin.thuly.thu_hoi` | `ThuLyController@thuHoi` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` |
| `POST` | `/admin/thu-ly/api/verify-lgsp` | `admin.thuly.verify_lgsp` | `ThuLyController@apiVerifyLgsp` | `security_headers`, `auth:can_bo`, `csrf` | `CHUYEN_VIEN` (C06/Đăng kiểm) |

#### 5. Phân hệ Phê duyệt & Ký số điện tử (LD_PHONG, LD_CO_QUAN - PH04)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/phe-duyet/phong` | `admin.phe_duyet.phong` | `PheDuyetController@indexPhong` | `security_headers`, `auth:can_bo` | `LD_PHONG` |
| `GET` | `/admin/phe-duyet/phong/{id}` | `admin.phe_duyet.chi_tiet_phong` | `PheDuyetController@chiTietPhong` | `security_headers`, `auth:can_bo` | `LD_PHONG` |
| `POST` | `/admin/phe-duyet/phong/{id}/ky-so` | `admin.phe_duyet.ky_so_phong` | `PheDuyetController@kySoPhong` | `security_headers`, `auth:can_bo`, `csrf` | `LD_PHONG` (SYSADMIN bị chặn) |
| `POST` | `/admin/phe-duyet/phong/{id}/tra-lai` | `admin.phe_duyet.tra_lai_phong` | `PheDuyetController@traLaiPhong` | `security_headers`, `auth:can_bo`, `csrf` | `LD_PHONG` |
| `GET` | `/admin/phe-duyet/co-quan` | `admin.phe_duyet.co_quan` | `PheDuyetController@indexCoQuan` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN` |
| `GET` | `/admin/phe-duyet/co-quan/{id}` | `admin.phe_duyet.chi_tiet_co_quan` | `PheDuyetController@chiTietCoQuan` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN` |
| `POST` | `/admin/phe-duyet/co-quan/{id}/ky-so` | `admin.phe_duyet.ky_so_co_quan` | `PheDuyetController@kySoCoQuan` | `security_headers`, `auth:can_bo`, `csrf` | `LD_CO_QUAN` (SYSADMIN bị chặn) |
| `POST` | `/admin/phe-duyet/co-quan/batch-sign` | `admin.phe_duyet.batch_sign` | `PheDuyetController@batchSignAction` | `security_headers`, `auth:can_bo`, `csrf` | `LD_CO_QUAN` (Ký lô SmartCA) |
| `GET` | `/admin/phe-duyet/cho-huy` | `admin.phe_duyet.cho_huy` | `PheDuyetController@danhSachChoHuy` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN` |
| `POST` | `/admin/phe-duyet/cho-huy/{id}/duyet` | `admin.phe_duyet.duyet_huy` | `PheDuyetController@duyetHuy` | `security_headers`, `auth:can_bo`, `csrf` | `LD_CO_QUAN` |

#### 6. Quản trị Văn thư số & Phát hành (VAN_THU - PH05)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/van-thu` | `admin.van_thu.index` | `VanThuController@index` | `security_headers`, `auth:can_bo` | `VAN_THU` |
| `GET` | `/admin/van-thu/dong-dau/{id}` | `admin.van_thu.dong_dau` | `VanThuController@dongDau` | `security_headers`, `auth:can_bo` | `VAN_THU` |
| `POST` | `/admin/van-thu/dong-dau/{id}` | `admin.van_thu.xu_ly_dong_dau` | `VanThuController@xuLyDongDau` | `security_headers`, `auth:can_bo`, `csrf` | `VAN_THU` (Dấu số cơ quan) |
| `GET` | `/admin/van-thu/so-van-ban` | `admin.van_thu.so_van_ban` | `VanThuController@soVanBan` | `security_headers`, `auth:can_bo` | `VAN_THU` |
| `POST` | `/admin/van-thu/so-van-ban/tao` | `admin.van_thu.tao_so_van_ban` | `VanThuController@taoSoVanBan` | `security_headers`, `auth:can_bo`, `csrf` | `VAN_THU` |
| `GET` | `/admin/van-thu/cong-van` | `admin.van_thu.cong_van` | `VanThuController@congVan` | `security_headers`, `auth:can_bo` | `VAN_THU` |
| `GET` | `/admin/van-thu/cong-van/xuat-csv/{id}` | `admin.van_thu.xuat_csv` | `VanThuController@xuatCsv` | `security_headers`, `auth:can_bo` | `VAN_THU` (CSV UTF-8 BOM) |

#### 7. Giám sát SLA & Mẫu 04 Xin lỗi (PH06)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/sla/danh-sach-qua-han` | `admin.sla.qua_han` | `SlaController@danhSachQuaHan` | `security_headers`, `auth:can_bo` | Cán bộ / Lãnh đạo |
| `GET` | `/admin/sla/van-ban-xin-loi/{id}` | `admin.sla.form_mau_04` | `SlaController@formMau04` | `security_headers`, `auth:can_bo` | `LD_CO_QUAN` |
| `POST` | `/admin/sla/ky-so-mau-04/{id}` | `admin.sla.ky_so_mau_04` | `SlaController@kySoMau04` | `security_headers`, `auth:can_bo`, `csrf` | `LD_CO_QUAN` (Mở khóa luân chuyển) |
| `GET` | `/admin/sla/in-mau-04/{id}` | `admin.sla.in_mau_04` | `SlaController@inMau04` | `security_headers`, `auth:can_bo` | Cán bộ / Công dân |

#### 8. Mẫu Thông báo Đa kênh & Báo cáo Thống kê Động (PH07, PH08)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/he-thong/mau-thong-bao` | `admin.mau_thong_bao` | `ThongBaoController@mauThongBao` | `security_headers`, `auth:can_bo` | `SYSADMIN` |
| `POST` | `/admin/he-thong/mau-thong-bao/{id}` | `admin.mau_thong_bao.update` | `ThongBaoController@updateMauThongBao` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` |
| `GET` | `/admin/he-thong/hop-thu-mo-phong` | `admin.hop_thu_mo_phong` | `ThongBaoController@hopThuMoPhong` | `security_headers`, `auth:can_bo` | `SYSADMIN` |
| `GET` | `/admin/bao-cao` | `admin.bao_cao.index` | `BaoCaoController@index` | `security_headers`, `auth:can_bo` | Lãnh đạo, `SYSADMIN` |
| `GET` | `/admin/bao-cao/thong-tu-01` | `admin.bao_cao.thong_tu_01` | `BaoCaoController@thongTu01` | `security_headers`, `auth:can_bo` | Biểu mẫu TT 01/2018 |
| `GET` | `/admin/bao-cao/so-hoa` | `admin.bao_cao.so_hoa` | `BaoCaoController@soHoa` | `security_headers`, `auth:can_bo` | Chỉ tiêu NĐ 107/2021 |
| `GET` | `/admin/bao-cao/qua-han` | `admin.bao_cao.qua_han` | `BaoCaoController@quaHan` | `security_headers`, `auth:can_bo` | Thống kê trễ hạn |
| `GET` | `/admin/bao-cao/hai-long` | `admin.bao_cao.hai_long` | `BaoCaoController@haiLong` | `security_headers`, `auth:can_bo` | Chỉ số SIPAS 5 tiêu chí |
| `GET` | `/admin/bao-cao/truc-tuyen` | `admin.bao_cao.truc_tuyen` | `BaoCaoController@trucTuyen` | `security_headers`, `auth:can_bo` | Tỷ lệ nộp trực tuyến |
| `GET` | `/admin/bao-cao/lien-thong` | `admin.bao_cao.lien_thong` | `BaoCaoController@lienThong` | `security_headers`, `auth:can_bo` | Điều phối liên thông |

#### 9. Danh mục dùng chung MDM & Thiết kế Quy trình (PH09, PH10)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/danh-muc/dvhc` | `admin.dvhc` | `DvhcController@index` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (ĐVHC 3 cấp) |
| `POST` | `/admin/danh-muc/dvhc` | `admin.dvhc.store` | `DvhcController@store` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` |
| `POST` | `/admin/danh-muc/dvhc/sap-nhap` | `admin.dvhc.merge` | `DvhcController@merge` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` (Sáp nhập ĐVHC) |
| `POST` | `/admin/danh-muc/dvhc/{id}/ngung-hieu-luc` | `admin.dvhc.toggle` | `DvhcController@toggleActive` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` |
| `GET` | `/admin/danh-muc/dvhc/export` | `admin.dvhc.export` | `DvhcController@export` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (CSV) |
| `GET` | `/admin/danh-muc/don-vi` | `admin.don_vi` | `DonViController@index` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (Cây tổ chức) |
| `POST` | `/admin/danh-muc/don-vi` | `admin.don_vi.store` | `DonViController@store` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` |
| `GET` | `/admin/danh-muc/tthc` | `admin.tthc` | `TthcController@index` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (TTHC Quốc gia) |
| `GET` | `/admin/danh-muc/tthc/dong-bo` | `admin.tthc.sync_preview` | `TthcController@syncPreview` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` |
| `POST` | `/admin/danh-muc/tthc/dong-bo/ap-dung` | `admin.tthc.apply_sync` | `TthcController@applySync` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` |
| `GET` | `/admin/danh-muc/eform` | `admin.eform` | `EformController@index` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (E-Form Designer) |
| `POST` | `/admin/danh-muc/eform/luu` | `admin.eform.save` | `EformController@save` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.danh_muc` | `SYSADMIN` |
| `GET` | `/admin/danh-muc/ngay-nghi` | `admin.holidays` | `ConfigController@holidays` | `security_headers`, `auth:can_bo`, `can:admin.danh_muc` | `SYSADMIN` (Lịch làm việc) |
| `GET` | `/admin/quy-trinh` | `admin.workflow` | `WorkflowController@index` | `security_headers`, `auth:can_bo`, `can:admin.quy_trinh` | `SYSADMIN` (BPMN-lite) |
| `GET` | `/admin/quy-trinh/{id}` | `admin.workflow.detail` | `WorkflowController@detail` | `security_headers`, `auth:can_bo`, `can:admin.quy_trinh` | `SYSADMIN` |
| `POST` | `/admin/quy-trinh/{id}/nhan-ban-phien-ban` | `admin.workflow.clone_version` | `WorkflowController@createVersion` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.quy_trinh` | `SYSADMIN` |

#### 10. Vận hành Quản trị Hệ thống, An toàn thông tin & ETL (PH11, PH12, PH13, PH14, PH15)
| Phương thức | URI Pattern | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Vai trò / Quyền hạn |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/he-thong/nguoi-dung` | `admin.users` | `UserController@index` | `security_headers`, `auth:can_bo`, `can:admin.nguoi_dung` | `SYSADMIN` |
| `POST` | `/admin/he-thong/nguoi-dung/{id}/mo-khoa` | `admin.users.unlock` | `UserController@unlock` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.nguoi_dung` | `SYSADMIN` (Mở khóa BruteForce) |
| `GET` | `/admin/he-thong/vai-tro` | `admin.roles.matrix` | `RoleController@matrix` | `security_headers`, `auth:can_bo`, `can:admin.vai_tro_quyen` | `SYSADMIN` (Ma trận RBAC) |
| `POST` | `/admin/he-thong/vai-tro/ma-tran` | `admin.roles.update_matrix` | `RoleController@updateMatrix` | `security_headers`, `auth:can_bo`, `csrf`, `can:admin.vai_tro_quyen` | `SYSADMIN` |
| `GET` | `/admin/he-thong/audit` | `admin.he_thong.audit` | `HeThongController@audit` | `security_headers`, `auth:can_bo` | `SYSADMIN` (WORM Audit Trail) |
| `POST` | `/admin/he-thong/audit/verify-chain` | `admin.he_thong.verify_chain` | `HeThongController@verifyChain` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Đối soát SHA-256) |
| `GET` | `/admin/he-thong/nhat-ky-dang-nhap` | `admin.he_thong.nhat_ky_dang_nhap` | `HeThongController@nhatKyDangNhap` | `security_headers`, `auth:can_bo` | `SYSADMIN` |
| `GET` | `/admin/he-thong/nhat-ky-tich-hop` | `admin.he_thong.nhat_ky_tich_hop` | `HeThongController@nhatKyTichHop` | `security_headers`, `auth:can_bo` | `SYSADMIN` |
| `GET` | `/admin/he-thong/ip-chan` | `admin.he_thong.ip_chan` | `HeThongController@ipChan` | `security_headers`, `auth:can_bo` | `SYSADMIN` (Chặn IP xấu) |
| `POST` | `/admin/he-thong/ip-chan/block` | `admin.he_thong.block_ip` | `HeThongController@blockIpAction` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` |
| `POST` | `/admin/he-thong/ip-chan/unblock` | `admin.he_thong.unblock_ip` | `HeThongController@unblockIpAction` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` |
| `GET` | `/admin/he-thong/sao-luu` | `admin.he_thong.sao_luu` | `HeThongController@saoLuu` | `security_headers`, `auth:can_bo` | `SYSADMIN` (RPO $\le 2$h, RTO $\le 4$h) |
| `POST` | `/admin/he-thong/sao-luu/run` | `admin.he_thong.run_backup` | `HeThongController@runBackupAction` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` |
| `POST` | `/admin/he-thong/sao-luu/test-restore` | `admin.he_thong.test_restore` | `HeThongController@testRestoreAction` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Diễn tập phục hồi) |
| `GET` | `/admin/he-thong/health` | `admin.he_thong.health` | `HeThongController@health` | `security_headers`, `auth:can_bo` | `SYSADMIN` (Health Checks) |
| `GET` | `/admin/he-thong/su-co` | `admin.he_thong.su_co` | `HeThongController@suCo` | `security_headers`, `auth:can_bo` | `SYSADMIN` (BCP/DRP) |
| `GET` | `/admin/he-thong/dao-tao` | `admin.he_thong.dao_tao` | `HeThongController@daoTao` | `security_headers`, `auth:can_bo` | `SYSADMIN` (Chuyển giao CN) |
| `GET` | `/admin/he-thong/etl` | `admin.he_thong.etl` | `EtlController@index` | `security_headers`, `auth:can_bo` | `SYSADMIN` (ETL 4 bước) |
| `POST` | `/admin/he-thong/etl/extract` | `admin.he_thong.etl.extract` | `EtlController@extract` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Bước 1: Extract) |
| `POST` | `/admin/he-thong/etl/profile` | `admin.he_thong.etl.profile` | `EtlController@profile` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Bước 2: Staging) |
| `POST` | `/admin/he-thong/etl/transform` | `admin.he_thong.etl.transform` | `EtlController@transform` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Bước 3: Transform) |
| `POST` | `/admin/he-thong/etl/load` | `admin.he_thong.etl.load` | `EtlController@load` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Bước 4: Load) |
| `GET` | `/admin/he-thong/tich-hop` | `admin.he_thong.tich_hop` | `TichHopController@index` | `security_headers`, `auth:can_bo` | `SYSADMIN` (9 Cổng dịch vụ) |
| `POST` | `/admin/he-thong/tich-hop/ping` | `admin.he_thong.tich_hop.ping` | `TichHopController@ping` | `security_headers`, `auth:can_bo`, `csrf` | `SYSADMIN` (Kiểm tra độ trễ) |

---

### III. RESTFUL API CỔNG MỞ (/API/V1)

| Phương thức | URI Endpoint | Tên Tuyến đường | Controller / Action | Middleware áp dụng | Cơ chế xác thực & Phân quyền |
| :---: | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/health` | `api.health` | Closure / `HealthCheckService` | `security_headers`, `rate_limit` | Công khai (Prometheus/Uptime Kuma) |
| `GET` | `/api/v1/tra-cuu/{ma}` | `api.public.tra_cuu` | `PublicApiController@traCuu` | `security_headers`, `rate_limit` | Công khai (Ẩn dữ liệu nhạy cảm) |
| `GET` | `/api/v1/thu-tuc-hanh-chinh` | `api.public.tthc` | `PublicApiController@danhMucTthc` | `security_headers`, `rate_limit` | Công khai (Phân trang MDM) |
| `GET` | `/api/v1/cong-dan/ho-so` | `api.citizen.ho_so.index` | `CitizenApiController@danhSachHoSo` | `security_headers`, `rate_limit`, `api_auth:cong_dan` | Bearer Token Scope `cong_dan` |
| `GET` | `/api/v1/cong-dan/ho-so/{ma}` | `api.citizen.ho_so.detail` | `CitizenApiController@chiTietHoSo` | `security_headers`, `rate_limit`, `api_auth:cong_dan` | Bearer Token (Chống IDOR) |
| `GET` | `/api/v1/lanh-dao/cho-ky` | `api.leader.cho_ky` | `LeaderApiController@danhSachChoKy` | `security_headers`, `rate_limit`, `api_auth:lanh_dao` | Bearer Token Scope `lanh_dao` |
| `POST` | `/api/v1/lanh-dao/ky-so` | `api.leader.ky_so` | `LeaderApiController@kySo` | `security_headers`, `rate_limit`, `api_auth:lanh_dao` | Bearer Token + PIN SmartCA |
| `POST` | `/api/v1/webhook/lgsp` | `api.webhook.lgsp` | `WebhookController@handleLgsp` | `security_headers`, `rate_limit` | Xác thực Chữ ký HMAC-SHA256 |
| `POST` | `/api/v1/webhook/payment` | `api.webhook.payment` | `WebhookController@handlePayment` | `security_headers`, `rate_limit` | Xác thực Thanh toán PayGov/VNPAY |
| `POST` | `/api/v1/webhook/thanh-toan` | `api.webhook.thanh_toan` | `WebhookController@handlePayment` | `security_headers`, `rate_limit` | Alias Webhook Thanh toán |
