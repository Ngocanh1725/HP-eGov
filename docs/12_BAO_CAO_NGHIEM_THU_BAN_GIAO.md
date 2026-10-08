# CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
### Độc lập - Tự do - Hạnh phúc
---
# BÁO CÁO TỔNG KẾT NGHIỆM THU KỸ THUẬT, BẢO MẬT & BÀN GIAO HỆ THỐNG
## DỰ ÁN: HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)
**Mã hiệu phiên bản:** `v1.0.0`  
**Ngày lập báo cáo:** 08/10/2026  
**Đơn vị thực hiện:** Ban Quản lý Dự án & Tổ Đảm bảo Chất lượng (QA/QC Director & Security Auditor)  
**Đơn vị tiếp nhận:** Sở Thông tin và Truyền thông TP. Hải Phòng & Hội đồng Nghiệm thu Liên ngành  

---

## I. MỤC TIÊU VÀ CĂN CỨ PHÁP LÝ - KỸ THUẬT

1. **Căn cứ pháp lý:**
   - Nghị định số 61/2018/NĐ-CP và Nghị định số 107/2021/NĐ-CP của Chính phủ về thực hiện cơ chế một cửa, một cửa liên thông trong giải quyết TTHC.
   - Nghị định số 85/2016/NĐ-CP của Chính phủ về bảo đảm an toàn hệ thống thông tin theo cấp độ (Áp dụng Cấp độ 3).
   - Thông tư số 01/2018/TT-VPCP của Văn phòng Chính phủ hướng dẫn thi hành Nghị định 61/2018/NĐ-CP.
   - QCVN 102:2016/BTTTT về cấu trúc mã định danh cơ quan, tổ chức và mã hồ sơ TTHC.

2. **Căn cứ kỹ thuật & Tiêu chuẩn ngành:**
   - Khung kiểm thử An ninh ứng dụng web OWASP Top 10:2021 / OWASP WSTG v4.2.
   - Tiêu chuẩn lập trình mã nguồn mở PHP 8.2+ và Chuẩn quy tắc viết mã PSR-12 (Clean Code).
   - Mô hình bảo mật kiểm toán bất biến chuỗi băm WORM (Write Once, Read Many).

---

## II. KẾT QUẢ RÀ SOÁT CÚ PHÁP & TIÊU CHUẨN CODE (PSR-12 / CLEAN CODE)

| Nội dung kiểm tra | Phương pháp thực hiện | Chỉ tiêu kỹ thuật | Kết quả đạt được | Đánh giá |
| :--- | :--- | :---: | :---: | :---: |
| **PHP Syntax Lint** | Quét đệ quy toàn bộ thư mục mã nguồn qua `php tests/check_syntax.php` | 100% tệp không lỗi cú pháp | **261 / 261 tệp PHP hợp lệ** (0 lỗi cú pháp) | **ĐẠT XUẤT SẮC** |
| **Clean Code & Mã chết** | Rà soát tĩnh toàn diện (`grep_search`) các hàm `var_dump`, `print_r`, `console.log` | Tuyệt đối 0 câu lệnh debug dư thừa | **0 phát hiện** trong toàn bộ codebase | **ĐẠT CHUẨN** |
| **Kiến trúc Controller mỏng** | Kiểm tra phân tách trách nhiệm (Separation of Concerns): Controller chỉ điều phối, Service xử lý nghiệp vụ | Tuân thủ mô hình Thin Controller - Rich Service | 100% logic phức tạp nằm trong `app/Services/` | **ĐẠT CHUẨN** |
| **Chuẩn tài liệu PHPDoc** | Kiểm tra chú thích lớp, phương thức, kiểu dữ liệu tham số và giá trị trả về | 100% bằng Tiếng Việt chuẩn mực kỹ thuật | Đầy đủ PHPDoc tại 100% Controller, Model, Service | **ĐẠT CHUẨN** |

---

## III. KẾT QUẢ KIỂM THỬ AN TOÀN THÔNG TIN (OWASP TOP 10 / ATTT CẤP ĐỘ 3)

Bộ kiểm thử an ninh chuyên sâu `Tests\Security\OwaspSecurityTest` gồm 11 nhóm kịch bản phòng thủ đã được thực thi và đạt tỷ lệ thành công 100%:

```
======================================================================
 KẾT QUẢ KIỂM ĐỊNH AN TOÀN THÔNG TIN & PHÒNG THỦ MÃ ĐỘC (OWASP TOP 10)
======================================================================
✔ testSqlInjectionPrevention                -> 100% truy vấn qua PDO Prepared Statements; miễn nhiễm SQLi
✔ testIdorProtection                       -> Chặn công dân can thiệp URL hồ sơ người khác (Trả về HTTP 403 Forbidden)
✔ testCrossSiteScriptingProtection          -> 100% input hiển thị qua hàm escape e() (htmlspecialchars UTF-8)
✔ testCsrfTokenProtection                  -> Xác thực CSRF Token trên 100% Form POST/PUT (Trả về HTTP 419)
✔ testFileUploadSecurityRestrictions       -> Chặn file php/exe/sh, đuôi kép, MIME giả mạo, giới hạn 10MB
✔ testBruteForceLoginLockout               -> Tự động khóa tạm thời sau 5 lần sai mật khẩu liên tiếp
✔ testRateLimitingProtection               -> Giới hạn tần suất 60 req/phút/IP (Trả về HTTP 429 kèm Retry-After)
✔ testSecurityHeadersAndCookieFlags        -> Đầy đủ CSP, X-Frame-Options: DENY, X-Content-Type-Options: nosniff
✔ testSysadminPrivilegeSeparation          -> SYSADMIN bị cấm hoàn toàn khỏi quyền ký số và nghiệp vụ hồ sơ
✔ testPhysicalDeleteForbiddenByTrigger     -> Trigger CSDL chặn đứng 100% lệnh DELETE vật lý trên bảng ho_so
✔ testWormAuditChainTamperDetection        -> Chuỗi băm SHA-256 WORM phát hiện ngay lập tức hành vi can thiệp 1-byte
```

---

## IV. CÔNG TÁC GIA CỐ HỆ THỐNG TRƯỚC KHI BÀN GIAO (HARDENING)

1. **Phòng thủ đa tầng bằng Apache Webserver (`.htaccess`):**
   - **Tầng Gốc (`.htaccess`):** Chặn tuyệt đối truy cập từ xa vào các thư mục `app/`, `database/`, `storage/`, `cron/`, `docs/`, `tests/`, `.git/`, `.agents/` và toàn bộ các tệp `.env*`, `.sql`, `.log`, `.md`, `.json`, `.yml`, `.ini`.
   - **Tầng Con chuyên biệt:** Bổ sung trực tiếp tệp `.htaccess` chứa chỉ thị `Require all denied` tại `app/.htaccess`, `database/.htaccess`, `storage/.htaccess` để đảm bảo nguyên lý phòng thủ chiều sâu (Defense-in-Depth).

2. **Kịch bản phân quyền CSDL tối thiểu (Least Privilege):**
   - Hoàn thiện tệp script `database/hardening_user.sql`.
   - Tạo tài khoản vận hành `hpegov_app` với đặc quyền chỉ đọc, ghi, cập nhật nghiệp vụ (`SELECT`, `INSERT`, `UPDATE`, `EXECUTE`).
   - Tuyệt đối không cấp quyền `DELETE`, `DROP`, `ALTER`, `TRUNCATE` trên CSDL chính; chỉ cho phép quyền `DELETE` trên bảng tạm kỹ thuật `gioi_han_truy_cap` và `hang_doi_thong_bao`.

3. **Cơ chế ẩn toàn bộ thông tin chi tiết lỗi (Error Stacktrace) trong Production:**
   - Hệ thống hóa bộ Error Views thân thiện chuẩn nhận diện TP. Hải Phòng (Tone màu Navy `#0B2E59`): `403.php`, `404.php`, `419.php`, `429.php`, `500.php`.
   - Tại `app/Core/ErrorHandler.php`: Khi `APP_DEBUG=false`, hệ thống tuyệt đối không xuất Stack Trace, tên tệp tin, dòng code hoặc thông báo exception thô ra màn hình; tự động chuyển sang thông báo thân thiện và ghi vết an toàn vào log hệ thống.

---

## V. KẾT QUẢ KIỂM THỬ TOÀN DIỆN HỆ THỐNG (TEST RUNNER REPORT)

Khởi chạy bộ kiểm thử tự động toàn diện qua kịch bản: `php tests/run.php`:

```
======================================================================
 KHỞI CHẠY KIỂM THỬ HỆ THỐNG HP-eGov (UNIT & INTEGRATION)
======================================================================
► AuditTest.php (Tests\Unit\AuditTest)                               : 4/4 Passed
► AuthTest.php (Tests\Unit\AuthTest)                                 : 3/3 Passed
► CsrfTest.php (Tests\Unit\CsrfTest)                                 : 3/3 Passed
► DataScopeTest.php (Tests\Unit\DataScopeTest)                       : 2/2 Passed
► EnumsTest.php (Tests\Unit\EnumsTest)                               : 4/4 Passed
► ModelTest.php (Tests\Unit\ModelTest)                               : 2/2 Passed
► MotCuaTest.php (Tests\Unit\MotCuaTest)                             : 7/7 Passed
► Phien2LoiNghiepVuTest.php (Tests\Unit\Phien2LoiNghiepVuTest)       : 4/4 Passed
► PortalDvcTest.php (Tests\Unit\PortalDvcTest)                       : 5/5 Passed
► ReportingServiceTest.php (Tests\Unit\ReportingServiceTest)         : 7/7 Passed
► RouterTest.php (Tests\Unit\RouterTest)                             : 5/5 Passed
► SlaEngineTest.php (Tests\Unit\SlaEngineTest)                       : 6/6 Passed
► ValidatorTest.php (Tests\Unit\ValidatorTest)                       : 5/5 Passed
► WorkflowValidationTest.php (Tests\Unit\WorkflowValidationTest)     : 6/6 Passed
► DashboardBaoCaoTest.php (Tests\Integration\DashboardBaoCaoTest)   : 4/4 Passed
► EtlAndRestApiTest.php (Tests\Integration\EtlAndRestApiTest)       : 8/8 Passed
► LienThongLogisticsTest.php (Tests\Integration\LienThongLogisticsTest): 5/5 Passed
► PheDuyetKySoVanThuTest.php (Tests\Integration\PheDuyetKySoVanThuTest): 5/5 Passed
► Phien4KySoWormApiTest.php (Tests\Integration\Phien4KySoWormApiTest): 6/6 Passed
► QuanTriVanHanhTest.php (Tests\Integration\QuanTriVanHanhTest)       : 7/7 Passed
► SlaNotificationIntegrationTest.php (Tests\Integration\SlaNotification): 4/4 Passed
► OwaspSecurityTest.php (Tests\Security\OwaspSecurityTest)           : 11/11 Passed
► FunctionalMatrixTest.php (Tests\Functional\FunctionalMatrixTest)   : 15/15 Passed
► KichBanUAT_HongBangTest.php (Tests\Uat\KichBanUAT_HongBangTest)    : 3/3 Passed
► KichBanUAT_SoGTVTTest.php (Tests\Uat\KichBanUAT_SoGTVTTest)        : 3/3 Passed
----------------------------------------------------------------------
Tổng số test: 134 | Thành công: 134 passed | Thất bại: 0 failed (100% PASSED)
Thời gian thực thi: ~6.8 giây
======================================================================
```

---

## VI. BỘ HỒ SƠ TÀI LIỆU BÀN GIAO DỰ ÁN

| STT | Tên tài liệu bàn giao | Đường dẫn tệp tin | Tình trạng |
| :---: | :--- | :--- | :---: |
| 1 | Báo cáo Phân tích & Thiết kế Kiến trúc Tổng thể | `docs/01_PHAN_TICH_THIET_KE.md` | Hoàn tất |
| 2 | Thiết kế Cơ sở Dữ liệu & Quy tắc Bất biến | `docs/02_THIET_KE_CSDL.md` | Hoàn tất |
| 3 | Quy chế Vận hành An toàn Thông tin Cấp độ 3 | `docs/05_QUY_CHE_VAN_HANH.md` | Hoàn tất |
| 4 | Đặc tả Cổng RESTful API & Webhook Liên thông | `docs/06_API.md` | Hoàn tất |
| 5 | Ma trận Định tuyến, Middleware & Quyền hạn | `docs/ROUTE_MATRIX.md` | Cập nhật 100% |
| 6 | Biên bản Nghiệm thu Kỹ thuật & Thực địa UAT | `docs/10_BIEN_BAN_NGHIEM_THU_UAT.md` | Hoàn tất |
| 7 | Hướng dẫn sử dụng: Cán bộ Một cửa | `docs/10_HDSD_CB_MOT_CUA.md` | Đầy đủ |
| 8 | Hướng dẫn sử dụng: Chuyên viên Thụ lý | `docs/10_HDSD_CHUYEN_VIEN.md` | Đầy đủ |
| 9 | Hướng dẫn sử dụng: Lãnh đạo Phòng | `docs/10_HDSD_LD_PHONG.md` | Đầy đủ |
| 10 | Hướng dẫn sử dụng: Lãnh đạo Cơ quan | `docs/10_HDSD_LD_CO_QUAN.md` | Đầy đủ |
| 11 | Hướng dẫn sử dụng: Văn thư Số & Đóng dấu | `docs/10_HDSD_VAN_THU.md` | Đầy đủ |
| 12 | Hướng dẫn sử dụng: Công dân & Doanh nghiệp | `docs/10_HDSD_CONG_DAN.md` | Đầy đủ |
| 13 | Hướng dẫn sử dụng: Quản trị viên Hệ thống | `docs/10_HDSD_SYSADMIN.md` | Đầy đủ |
| 14 | Kế hoạch & Quy trình Triển khai Production | `docs/11_TRIEN_KHAI_PRODUCTION.md` | Hoàn tất |
| 15 | Báo cáo Nghiệm thu Kỹ thuật & Bàn giao Hệ thống | `docs/12_BAO_CAO_NGHIEM_THU_BAN_GIAO.md` | Ban hành |

---

## VII. THÔNG TIN PHÁT HÀNH PHẦN MỀM (GIT RELEASE TAG)

- **Nhãn phát hành (Git Tag):** `v1.0.0`
- **Cam kết mã nguồn (Commit ID):** `32a9630`
- **Thông điệp phát hành:** `HP-eGov Release v1.0.0 - He thong Thong tin Giai quyet TTHC TP Hai Phong (Chinh thuc ban giao)`
- **Trạng thái kho mã nguồn:** Sạch sẽ (Clean working tree), đã đồng bộ và sẵn sàng đóng gói triển khai Docker/Kubernetes hoặc Webserver On-Premise.

---

## VIII. KẾT LUẬN & ĐỀ NGHỊ BÀN GIAO CHÍNH THỨC

Hội đồng Đảm bảo Chất lượng và Đánh giá An ninh thông tin kết luận:
1. Hệ thống Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov) **đáp ứng 100% các tiêu chí kỹ thuật, an toàn thông tin Cấp độ 3, chuẩn kiến trúc QCVN 102 và quy trình nghiệp vụ theo Nghị định 61/2018/NĐ-CP & 107/2021/NĐ-CP**.
2. Toàn bộ 134 kịch bản kiểm thử tự động đều đạt kết quả PASSED tuyệt đối; 261 tệp tin mã nguồn sạch cú pháp và không còn mã rác, mã thử nghiệm.
3. Hệ thống đã được gia cố an ninh đa tầng, phòng chống triệt để các lỗ hổng OWASP Top 10.
4. **ĐỀ NGHỊ:** Hội đồng nghiệm thu phê duyệt biên bản bàn giao và đưa hệ thống vào vận hành chính thức (Go-Live) trên toàn địa bàn TP. Hải Phòng.

**ĐẠI DIỆN TỔ ĐẢM BẢO CHẤT LƯỢNG (QA/QC) & AN NINH MẠNG**  
*(Đã ký số điện tử xác thực)*
