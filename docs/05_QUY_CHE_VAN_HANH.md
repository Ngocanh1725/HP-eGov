# QUY CHẾ QUẢN TRỊ & VẬN HÀNH HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)

**Ban hành kèm theo Quyết định số 2026/QĐ-UBND của Ủy ban nhân dân Thành phố Hải Phòng**  
*Căn cứ Luật An toàn thông tin mạng số 86/2015/QH13;*  
*Căn cứ Nghị định số 61/2018/NĐ-CP và Nghị định số 107/2021/NĐ-CP của Chính phủ;*  
*Căn cứ Nghị định số 85/2016/NĐ-CP và Thông tư số 12/2022/TT-BTTTT về bảo đảm an toàn hệ thống thông tin theo cấp độ (Cấp độ 3);*  
*Căn cứ Đề án Chuyển đổi số thành phố Hải Phòng đến năm 2025, định hướng đến năm 2030.*

---

## MỤC LỤC
1. [QUY ĐỊNH CHUNG & MA TRẬN TRÁCH NHIỆM RACI](#1-quy-định-chung--ma-trận-trách-nhiệm-raci)
2. [QUY TRÌNH CHUẨN VẬN HÀNH HỆ THỐNG (SOP)](#2-quy-trình-chuẩn-vận-hành-hệ-thống-sop)
3. [QUY TRÌNH SAO LƯU & PHỤC HỒI THẢM HỌA (DRP - RPO ≤ 2H, RTO ≤ 4H)](#3-quy-trình-sao-lưu--phục-hồi-thảm-họa-drp)
4. [QUY CHẾ AN TOÀN THÔNG TIN & KIỂM TOÁN WORM](#4-quy-chế-an-toàn-thông-tin--kiểm-toán-worm)
5. [KỊCH BẢN ỨNG CỨU SỰ CỐ KHẨN CẤP & BẢO TRÌ HỆ THỐNG](#5-kịch-bản-ứng-cứu-sự-cố-khẩn-cấp--bảo-trì-hệ-thống)
6. [QUY ĐỊNH ĐÀO TẠO & CHUYỂN GIAO CÔNG NGHỆ 4 NHÓM ĐỐI TƯỢNG](#6-quy-định-đào-tạo--chuyển-giao-công-nghệ-4-nhóm-đối-tượng)

---

## 1. QUY ĐỊNH CHUNG & MA TRẬN TRÁCH NHIỆM RACI

### 1.1. Phạm vi và Đối tượng áp dụng
Quy chế này quy định các hoạt động quản trị kỹ thuật, vận hành hạ tầng máy chủ, sao lưu - phục hồi cơ sở dữ liệu, giám sát an toàn thông tin, ứng cứu sự cố và chuyển giao công nghệ cho Hệ thống Một cửa điện tử liên thông thành phố Hải Phòng (HP-eGov).
Áp dụng đối với:
- **Sở Thông tin và Truyền thông Hải Phòng (Sở TT&TT):** Cơ quan quản lý nhà nước về CNTT và chủ quản vận hành hệ thống.
- **Trung tâm Thông tin & Truyền thông (thuộc Sở TT&TT):** Đơn vị trực tiếp quản trị hạ tầng Trung tâm Dữ liệu và hệ thống phần mềm (Đội ngũ SYSADMIN).
- **Các Sở, Ban, Ngành, UBND Quận, Huyện, Thị xã, Xã, Phường, Thị trấn:** Các đơn vị sử dụng và khai thác hệ thống hành chính.
- **Doanh nghiệp cung cấp hạ tầng viễn thông, chữ ký số (VNPT, Viettel, Ban Cơ yếu Chính phủ):** Các đối tác kỹ thuật phối hợp vận hành.

### 1.2. Ma trận Phân định Trách nhiệm RACI (§4.3, §4.4)
Ký hiệu:  
- **R (Responsible):** Người trực tiếp thực hiện công việc.  
- **A (Accountable):** Người chịu trách nhiệm phê duyệt cuối cùng và giải trình trước UBND Thành phố.  
- **C (Consulted):** Người được tham vấn, phối hợp chuyên môn.  
- **I (Informed):** Người được thông báo kết quả.

| Nhóm Nhiệm vụ Vận hành | Giám đốc Sở TT&TT | SysAdmin / Đội Kỹ thuật Sở | Lãnh đạo Đơn vị nghiệp vụ | Cán bộ Một cửa / Chuyên viên | Nhà mạng / Nhà cung cấp CA |
|:---|:---:|:---:|:---:|:---:|:---:|
| **Quản trị tài khoản & phân quyền RBAC** | I | **A / R** | C | I | - |
| **Vận hành sao lưu CSDL (Full/Diff/Log)** | I | **A / R** | - | - | C (Hạ tầng lưu trữ) |
| **Diễn tập phục hồi thảm họa DRP định kỳ** | **A** | **R** | C | I | C |
| **Kiểm toán ATTT & Kiểm tra chuỗi băm WORM** | **A** | **R** | - | - | C (VNCERT) |
| **Ứng cứu sự cố đứt tuyến truyền dẫn TSLCD** | A | **R** | I | I | **R** (VNPT/Viettel) |
| **Ứng cứu tấn công DDoS / Ransomware** | **A** | **R** | I | I | **C / R** (Cục ATTT) |
| **Bật/Tắt Chế độ bảo trì (Maintenance Site)** | **A** | **R** | I | I | I |
| **Đào tạo, sát hạch 4 nhóm cán bộ (§1.4)** | **A** | **R** | C | **R** (Tham gia) | - |

---

## 2. QUY TRÌNH CHUẨN VẬN HÀNH HỆ THỐNG (SOP)

### 2.1. Quy trình vận hành Hằng ngày (Daily SOP)
Mỗi ngày làm việc (trước 07:30 sáng và sau 17:30 chiều), quản trị viên hệ thống (SysAdmin) thực hiện các bước:
1. **Kiểm tra Dashboard Sức khỏe hệ thống (`/admin/he-thong/health`):**
   - Độ trễ CSDL Master phải $\le 15$ ms.
   - Dung lượng ổ đĩa khả dụng phải $\ge 20\%$ tổng dung lượng.
   - Hàng đợi thông báo SMS/Email (`hang_doi_thong_bao`) tồn đọng không vượt quá 200 tin.
2. **Kiểm tra Nhật ký Đăng nhập thất bại (`/admin/he-thong/nhat-ky-dang-nhap`):**
   - Rà soát các địa chỉ IP có trên 10 lần đăng nhập thất bại liên tiếp (dấu hiệu tấn công brute-force).
   - Đưa IP vi phạm vào danh sách chặn (`ip_chan`) nếu có dấu hiệu tấn công có chủ đích.
3. **Kiểm tra kết quả chạy Differential Backup (23:00 đêm hôm trước):**
   - Đối soát mã băm SHA-256 của bản Diff với nhật ký CSDL.
4. **Kiểm tra tiến trình Quét vi phạm SLA (`cron/sla_scan.php`):**
   - Bảo đảm cron quét mỗi 15 phút đang hoạt động bình thường, thông báo cảnh báo Vàng/Đỏ được gửi chính xác tới chuyên viên thụ lý.

### 2.2. Quy trình vận hành Hằng tuần (Weekly SOP)
Thực hiện vào sáng thứ Hai hằng tuần:
1. **Kiểm tra kết quả Full Backup Chủ nhật (00:00):**
   - Kiểm tra tệp sao lưu Full tại thư mục cục bộ `storage/backups/` và thư mục dự phòng tại Trung tâm DR Site `storage/backups/dr_site/`.
   - Kiểm tra dung lượng tệp và mã băm SHA-256.
2. **Kiểm tra toàn vẹn chuỗi băm WORM Audit Trail (`/admin/he-thong/audit`):**
   - Nhấn nút **"Kiểm tra toàn vẹn chuỗi băm"** để xác thực 100% các block ghi vết không bị can thiệp trái phép.
3. **Rà soát danh sách IP Chặn & Bộ đếm Rate Limit:**
   - Xóa bỏ các IP đã hết thời hạn phạt trong bảng `ip_chan`.
   - Làm sạch bộ đếm bảng `gioi_han_truy_cap`.
4. **Báo cáo sơ bộ tình hình an toàn hệ thống tuần:**
   - Xuất báo cáo sự cố (nếu có) trình Lãnh đạo Sở TT&TT.

### 2.3. Quy trình vận hành Hằng tháng (Monthly SOP)
Thực hiện vào ngày 01 đến ngày 05 hằng tháng:
1. **Tự động dọn dẹp bản sao lưu Differential cũ:**
   - Chạy lệnh dọn dẹp các tệp Differential backup cũ hơn 30 ngày (bản Full backup được lưu trữ vĩnh viễn hoặc theo niên hạn 5 năm).
2. **Đối soát dung lượng Storage lưu trữ tệp đính kèm và bản in số:**
   - Đánh giá tốc độ tăng trưởng lưu trữ, đề xuất nâng cấp ổ đĩa SAN/NAS nếu dung lượng đạt ngưỡng $\ge 80\%$.
3. **Rà soát tài khoản cán bộ luân chuyển hoặc thôi việc:**
   - Khóa các tài khoản không hoạt động quá 60 ngày hoặc có quyết định điều động công tác.
4. **Kiểm tra lịch diễn tập định kỳ ATTT (6 tháng) và DRP (12 tháng).**

---

## 3. QUY TRÌNH SAO LƯU & PHỤC HỒI THẢM HỌA (DRP)

### 3.1. Chính sách Sao lưu Dữ liệu (§2.3, §4.4)
Hệ thống tuân thủ nghiêm ngặt mô hình 3 tầng sao lưu đảm bảo **RPO $\le$ 2 giờ** và **RTO $\le$ 4 giờ**:

```
[ Cơ sở dữ liệu HP-eGov ]
        │
        ├── 1. Hằng tuần (Chủ nhật 00:00) ──> FULL BACKUP (mysqldump --single-transaction)
        │                                         ├── Nén + Băm SHA-256
        │                                         └── Sao chép sang DR SITE
        │
        ├── 2. Hằng ngày (23:00) ────────────> DIFFERENTIAL BACKUP (Thay đổi từ bản Full)
        │                                         └── Lưu giữ 30 ngày gần nhất
        │
        └── 3. Mỗi 2 giờ ────────────────────> TRANSACTION LOG BACKUP (Binlog / Audit Log)
                                                  └── Cam kết RPO <= 2 giờ
```

#### A. Full Backup (Sao lưu Toàn phần)
- **Thời gian:** 00:00 Chủ nhật hằng tuần.
- **Công cụ:** `C:\xampp\mysql\bin\mysqldump.exe --single-transaction --routines --triggers`.
- **An toàn dữ liệu:** Tính mã băm SHA-256 ngay sau khi xuất xong; tự động sao chép sang thư mục Trung tâm Phục hồi Thảm họa (`storage/backups/dr_site/`).
- **Lưu trữ:** Lưu giữ 52 tuần trong năm và đóng gói lưu kho lưu trữ lịch sử 5 năm.

#### B. Differential Backup (Sao lưu Khác biệt)
- **Thời gian:** 23:00 hằng ngày (từ Thứ Hai đến Thứ Bảy).
- **Nội dung:** Xuất toàn bộ các bản ghi mới tạo hoặc cập nhật kể từ thời điểm bản Full gần nhất trên các bảng nghiệp vụ chính (`ho_so`, `ho_so_tai_lieu`, `ho_so_luan_chuyen`, `chu_ky_so`, `van_ban_xin_loi`, `nhat_ky_kiem_toan`).
- **Thời hạn lưu trữ:** Giữ đúng 30 ngày gần nhất, sau 30 ngày tự động thanh lọc để tiết kiệm tài nguyên.

#### C. Transaction Log Backup (Sao lưu Nhật ký Giao dịch)
- **Thời gian:** Mỗi 2 giờ/lần (02:00, 04:00, 06:00, 08:00, 10:00, 12:00, 14:00, 16:00, 18:00, 20:00, 22:00).
- **Mục tiêu:** Bảo đảm chỉ số mất mát dữ liệu tối đa **RPO $\le$ 2 giờ**.
- **Cấu hình MySQL Binlog:** Bật cờ `log-bin = mysql-bin` và `binlog_format = ROW` trong tệp cấu hình `my.ini`.

### 3.2. Quy trình Phục hồi Thử nghiệm vào CSDL `hp_egov_restore_test`
Để bảo đảm năng lực khôi phục đáp ứng **RTO $\le$ 4 giờ (240 phút)**, quản trị viên bắt buộc phải thực hiện kiểm thử phục hồi:
1. Truy cập giao diện quản trị `/admin/he-thong/sao-luu`.
2. Tại dòng bản sao lưu cần kiểm tra, bấm nút **"🔄 Phục hồi thử (RTO)"**.
3. Hệ thống tự động thực hiện:
   - Kiểm tra tính toàn vẹn mã băm SHA-256 giữa tệp trên đĩa và bản ghi trong CSDL.
   - Tạo CSDL môi trường cô lập `hp_egov_restore_test`.
   - Nạp dữ liệu qua `C:\xampp\mysql\bin\mysql.exe`.
   - Đo lường thời gian thực thi chính xác từng mili-giây.
   - Đối soát số lượng bảng phục hồi thành công (yêu cầu $\ge 50$ bảng).
   - Tự động ghi nhận biên bản diễn tập vào bảng `dien_tap` phục vụ đoàn thanh tra ATTT.

### 3.3. Lệnh Tự động hóa Windows Task Scheduler (`schtasks`)
```cmd
REM 1. Tạo tác vụ Full Backup (00:00 Chủ nhật)
schtasks /Create /SC WEEKLY /D SUN /TN "HP-eGov\FullBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_full.php" /ST 00:00 /RU SYSTEM /RL HIGHEST /F

REM 2. Tạo tác vụ Differential Backup (23:00 Hằng ngày)
schtasks /Create /SC DAILY /TN "HP-eGov\DiffBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_diff.php" /ST 23:00 /RU SYSTEM /RL HIGHEST /F

REM 3. Tạo tác vụ Transaction Log (Mỗi 2 giờ)
schtasks /Create /SC HOURLY /MO 2 /TN "HP-eGov\LogBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_log.php" /RU SYSTEM /RL HIGHEST /F
```

---

## 4. QUY CHẾ AN TOÀN THÔNG TIN & KIỂM TOÁN WORM

### 4.1. Nguyên tắc Bất biến Nhật ký Kiểm toán (WORM - Write Once, Read Many)
- Mọi giao dịch thêm mới, cập nhật, xóa, phê duyệt, ký số và cấu hình đều được tự động ghi nhận vào bảng `nhat_ky_kiem_toan`.
- Mỗi bản ghi được bảo vệ bằng chuỗi mã băm SHA-256 nối tiếp:  
  $$\text{Hash}_n = \text{SHA256}(\text{Hash}_{n-1} + \text{Dữ liệu mới} + \text{Dữ liệu cũ} + \text{Thời gian} + \text{IP})$$
- CSDL được bảo vệ bằng Database Trigger cấp thấp ngăn cấm mọi thao tác `DELETE` hoặc `UPDATE` trực tiếp trên bảng `nhat_ky_kiem_toan`. Bất kỳ hành vi sửa đổi trực tiếp nào sẽ làm đứt gãy chuỗi băm và bị phát hiện ngay lập tức khi chạy `AuditService::verifyChain()`.

### 4.2. Tiêu chuẩn Chính sách Mật khẩu & Phiên làm việc
1. **Độ dài mật khẩu:** Tối thiểu 8 ký tự, bao gồm ít nhất 01 chữ hoa, 01 chữ thường, 01 chữ số và 01 ký tự đặc biệt (`!@#$%^&*`).
2. **Khóa tài khoản chống Brute-force:** Nhập sai quá 05 lần liên tiếp $\rightarrow$ Tài khoản tự động tạm khóa trong 15 phút.
3. **Thời hạn phiên làm việc (Session Timeout):** Sau 30 phút không phát sinh tương tác chuột/bàn phím, hệ thống tự động hủy phiên làm việc và yêu cầu đăng nhập lại.
4. **Giới hạn tần suất truy cập (Rate Limit Layer 7):** Giới hạn tối đa 60 requests/phút/IP đối với toàn bộ các endpoint công khai. Vượt quá ngưỡng sẽ bị trả mã lỗi HTTP 429 và tạm chặn 60 giây.

---

## 5. KỊCH BẢN ỨNG CỨU SỰ CỐ KHẨN CẤP & BẢO TRÌ HỆ THỐNG

### 5.1. Kịch bản 1: Đứt tuyến cáp truyền số liệu chuyên dùng (TSLCD)
- **Hiện tượng:** Cán bộ Một cửa tại UBND Quận/Huyện/Xã không truy cập được vào mạng nội bộ HP-eGov.
- **RACI:** Sở TT&TT (A/R) - VNPT Hải Phòng (R) - UBND Quận/Huyện (I).
- **Quy trình xử lý:**
  1. SysAdmin kiểm tra giám sát ping mạng TSLCD tới router đầu cuối tại đơn vị.
  2. Thông báo khẩn cấp cho Trung tâm Điều hành Mạng VNPT Hải Phòng yêu cầu kiểm tra tuyến cáp quang và kích hoạt định tuyến vô tuyến dự phòng (4G/5G APN dùng riêng).
  3. Cán bộ Một cửa chuyển sang sử dụng đường truyền 4G dự phòng đã cấp phát. Thời gian khắc phục triệt để không quá 04 giờ.

### 5.2. Kịch bản 2: Tấn công Từ chối Dịch vụ (DDoS)
- **Hiện tượng:** Cổng Dịch vụ công phản hồi chậm, thời gian phản hồi API $> 10,000$ ms hoặc trả lỗi HTTP 502/504.
- **RACI:** Sở TT&TT (A/R) - VNCERT/Cục ATTT (C) - VNPT ISP (R).
- **Quy trình xử lý:**
  1. Kích hoạt trung tâm lọc rửa lưu lượng (Anti-DDoS Scrubbing Center) tại cổng biên ISP.
  2. Bật chế độ Geo-IP blocking (chặn toàn bộ lưu lượng quốc tế ngoài lãnh thổ Việt Nam).
  3. Bật tường lửa ứng dụng WAF siết chặt Rate Limit xuống 30 requests/phút.

### 5.3. Kịch bản 3: Tấn công Mã độc Tống tiền (Ransomware)
- **Hiện tượng:** Xuất hiện tệp tin bị mã hóa đuôi lạ trên máy trạm cán bộ hoặc máy chủ ứng dụng.
- **RACI:** Sở TT&TT (A/R) - Đội Ứng cứu sự cố ATTT Hải Phòng (R) - Đơn vị nghiệp vụ (I).
- **Quy trình xử lý:**
  1. Rút cáp mạng cô lập ngay lập tức máy trạm/máy chủ bị nhiễm khỏi hệ thống mạng LAN.
  2. Đánh giá phạm vi lây lan. Do hệ thống HP-eGov áp dụng phân quyền tối thiểu và CSDL có phân vùng lưu trữ riêng biệt, mã độc không thể lây nhiễm vào phân vùng CSDL WORM.
  3. Format sạch máy trạm, khôi phục hệ điều hành từ bản image chuẩn của Sở TT&TT.

### 5.4. Chế độ Bảo trì Hệ thống (Static Maintenance Site)
Khi cần nâng cấp phiên bản phần mềm lớn hoặc xảy ra sự cố cần tạm dừng để ứng cứu:
1. SysAdmin truy cập `/admin/he-thong/chinh-sach-bao-mat` và bật công tắc **"Chế độ bảo trì hệ thống"**.
2. Hệ thống lập tức tạo tệp cờ `storage/maintenance.flag`.
3. Toàn bộ người dùng công dân và cán bộ thông thường sẽ nhận trang thông báo tĩnh `public/maintenance.html` (Mã HTTP 503 Service Unavailable, thông báo thời gian mở lại và số hotline).
4. Chỉ có tài khoản SYSADMIN được phép đăng nhập để thao tác kỹ thuật.
5. Sau khi hoàn thành nâng cấp, tắt công tắc bảo trì để đưa hệ thống trở lại hoạt động bình thường.

---

## 6. QUY ĐỊNH ĐÀO TẠO & CHUYỂN GIAO CÔNG NGHỆ 4 NHÓM ĐỐI TƯỢNG (§1.4)

Căn cứ Bảng §1.4 Báo cáo Thiết kế kiến trúc tổng thể, tất cả cán bộ tham gia vận hành hệ thống phải hoàn thành chương trình đào tạo và đạt kết quả sát hạch theo tiêu chí sau:

| STT | Nhóm Đối tượng | Nội dung Đào tạo Trọng tâm | Phương thức Đánh giá Sát hạch | Tiêu chuẩn ĐẠT để cấp quyền Vận hành |
|:---:|:---|:---|:---|:---|
| **1** | **Cán bộ Một cửa (`MOT_CUA`)** | - Quy trình tiếp nhận hồ sơ trực tiếp & trực tuyến.<br>- Nghiệp vụ số hóa hồ sơ đầu vào (scan/OCR/ký số bản sao điện tử).<br>- Tra cứu CSDLQG Dân cư qua LGSP.<br>- Quản lý và in Giấy tiếp nhận & Hẹn trả (Mẫu 01). | Kiểm tra trắc nghiệm lý thuyết & Thực hành xử lý 05 tình huống tiếp nhận thực tế trên phần mềm. | Điểm sát hạch **$\ge 90/100$ điểm**. |
| **2** | **Chuyên viên Thụ lý (`CHUYEN_VIEN`)** | - Tiếp nhận hồ sơ từ bộ phận Một cửa.<br>- Luân chuyển liên thông ngang (Sở - Sở) và dọc (Xã - Huyện - Sở).<br>- Xin ý kiến phối hợp và tổng hợp thẩm định.<br>- Dự thảo tờ trình và văn bản kết quả giải quyết TTHC. | Thực hành thao tác thụ lý trực tiếp trên hệ thống Staging. | Hoàn thành thụ lý **10 bộ hồ sơ mẫu** đúng hạn, không để xảy ra lỗi nghiệp vụ. |
| **3** | **Lãnh đạo Ký số (`LANH_DAO`)** | - Thẩm tra tờ trình của chuyên viên.<br>- Phê duyệt hồ sơ điện tử.<br>- Ký số văn bản kết quả bằng SmartCA / USB Token / Ban Cơ yếu CP.<br>- Ký số văn bản Xin lỗi công dân (Mẫu 04).<br>- Giám sát chỉ số KPI trên Dashboard IOC Hải Phòng. | Thực hành ký số điện tử trên hồ sơ thật qua thiết bị máy tính / di động. | **Ký số thành công 100%** hồ sơ mẫu hợp lệ, đúng quy trình phân cấp thẩm quyền. |
| **4** | **Quản trị viên Hệ thống (`SYSADMIN`)** | - Quản trị phân quyền ma trận RBAC.<br>- Cấu hình SLA và quy trình động BPMN-lite.<br>- Thực hiện quy trình sao lưu (Full, Diff, Binlog).<br>- Đối soát chuỗi băm WORM Audit Trail.<br>- Phục hồi CSDL thảm họa vào môi trường kiểm thử. | Diễn tập thực hành khôi phục CSDL từ bản sao lưu vào CSDL `hp_egov_restore_test` và đo thời gian. | Phục hồi thành công với **RTO < 4 giờ (240 phút)** và toàn vẹn dữ liệu SHA-256. |

### 6.1. Quản lý Môi trường Đào tạo (Staging Environment)
- Môi trường phục vụ công tác đào tạo, tập huấn và sát hạch cán bộ được cấu hình với cờ môi trường `APP_ENV=staging`.
- Toàn bộ giao diện người dùng công dân và cán bộ khi truy cập môi trường này đều hiển thị biểu ngữ cảnh báo màu cam nổi bật trên cùng:  
  `[⚠️ MÔI TRƯỜNG ĐÀO TẠO & THỰC HÀNH CÔNG NGHỆ (STAGING) — DỮ LIỆU THỬ NGHIỆM KHÔNG CÓ GIÁ TRỊ PHÁP LÝ]`.
- Dữ liệu phát sinh tại môi trường Staging tuyệt đối không được đồng bộ sang cơ sở dữ liệu chính thức (Production).

---
*Tài liệu này là căn cứ pháp lý và kỹ thuật bắt buộc để toàn bộ các cơ quan, đơn vị trên địa bàn thành phố Hải Phòng tuân thủ trong suốt quá trình khai thác, vận hành Hệ thống Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov).*
