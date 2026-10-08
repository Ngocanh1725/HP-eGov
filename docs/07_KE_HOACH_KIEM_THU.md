# KẾ HOẠCH KIỂM THỬ HỆ THỐNG TOÀN DIỆN (QA/QC PLAN)
## DỰ ÁN HỆ THỐNG THÔNG TIN QUẢN LÝ HÀNH CHÍNH & MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)

> **Căn cứ tài liệu gốc:** `Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf` – Chương 3: Kế hoạch Kiểm thử & Đảm bảo Chất lượng (§3.1 – §3.4)  
> **Căn cứ pháp lý & tiêu chuẩn:**  
> - Nghị định số 61/2018/NĐ-CP & Nghị định số 107/2021/NĐ-CP về thực hiện cơ chế một cửa, một cửa liên thông và số hóa hồ sơ, kết quả giải quyết TTHC.  
> - Nghị định số 85/2016/NĐ-CP & Thông tư số 12/2022/TT-BTTTT về bảo đảm an toàn hệ thống thông tin theo cấp độ (Hệ thống Cấp độ 3).  
> - Thông tư số 01/2018/TT-VPCP hướng dẫn thi hành Nghị định 61/2018/NĐ-CP (quy trình xử lý, biểu mẫu hành chính, Mẫu số 04 Xin lỗi).  
> - Quy chuẩn kỹ thuật quốc gia QCVN 102:2016/BTTTT về cấu trúc mã định danh cơ quan, tổ chức và mã hồ sơ TTHC.  
> - Tiêu chuẩn quốc tế OWASP Top 10 và Cẩm nang Kiểm thử Bảo mật Web OWASP WSTG v4.2 (Web Security Testing Guide).  
> - Chuẩn quy ước mã nguồn & kiến trúc hệ thống: `.agents/rules/hp-egov.md`.

---

## 1. MỤC TIÊU VÀ PHẠM VI KIỂM THỬ

### 1.1. Mục tiêu tổng quát
1. Đảm bảo toàn bộ 15 phân hệ chức năng (PH01 – PH15) hoạt động chính xác 100% theo đặc tả nghiệp vụ, đáp ứng ma trận phân quyền 7 vai trò (RBAC).
2. Xác minh quy trình luân chuyển liên thông đa cấp không đứt đoạn, bảo đảm tính toàn vẹn 100% của tệp hồ sơ (SHA-256) và tính pháp lý của chữ ký số qua từng trạm xử lý.
3. Đo lường và kiểm chứng năng lực chịu tải của hệ thống đáp ứng các kịch bản thực tế của TP. Hải Phòng (5.000 CCU công dân và 1.200 CCU cán bộ đồng thời).
4. Đánh giá toàn diện an toàn thông tin theo chuẩn Cấp độ 3 và OWASP WSTG, không có lỗ hổng bảo mật mức Nghiêm trọng (Critical) hoặc Cao (High).
5. Tổ chức kiểm thử chấp nhận người dùng (UAT) thực địa 30 ngày tại UBND Quận Hồng Bàng và Sở GTVT Hải Phòng làm căn cứ nghiệm thu chính thức.

### 1.2. Phạm vi kiểm thử
- **Đối tượng:** Toàn bộ mã nguồn backend PHP 8.2 MVC, frontend HTML/CSS/JS thuần, cơ sở dữ liệu MySQL/MariaDB InnoDB, các cổng API `/api/v1`, dịch vụ nền cronjob và cổng tích hợp ngoài (VNeID, LGSP, C06, SmartCA, VNPost, ClamAV).
- **Phạm vi đơn vị hành chính:** UBND Thành phố Hải Phòng, các Sở/Ban/Ngành (Sở Xây dựng, Sở TN&MT, Sở KH&ĐT, Sở GTVT), UBND Quận/Huyện (đại diện Q. Hồng Bàng) và UBND Xã/Phường/Thị trấn (đại diện P. Hoàng Văn Thụ).

---

## 2. CHIẾN LƯỢC 5 LOẠI HÌNH KIỂM THỬ (§3.1)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       CHIẾN LƯỢC KIỂM THỬ TOÀN DIỆN HP-eGov                 │
├─────────────────┬─────────────────┬─────────────────┬───────────────────────┤
│ 1. FUNCTIONAL   │ 2. INTEGRATION  │ 3. PERFORMANCE  │ 4. SECURITY (CẤP ĐỘ 3)│
│ (FR01 - FR15)   │ Liên thông đa cấp│ 5.000 CCU DVC   │ OWASP Top 10 / WSTG   │
│ 100% Use Cases  │ Xã→Huyện→Sở→TP  │ 1.200 CCU Nội bộ│ SQLi, IDOR, XSS, CSRF │
│ 7 Vai trò RBAC  │ SHA-256, Ký số  │ Resp ≤1.0s, P95 │ WORM Hash Chain       │
└─────────────────┴─────────────────┴─────────────────┴───────────────────────┘
                                       │
                                       ▼
                     ┌───────────────────────────────────┐
                     │     5. USER ACCEPTANCE TEST       │
                     │  Thực địa 30 ngày: Q. Hồng Bàng   │
                     │  & Sở Giao thông Vận tải HP       │
                     └───────────────────────────────────┘
```

### 2.1. Kiểm thử chức năng (Functional Testing)
- **Phương pháp:** Kiểm thử hộp đen (Black-box) và hộp trắng (White-box) thông qua kiểm thử đơn vị tự động (Unit Tests) và kiểm thử chức năng tự động qua HTTP/DOM.
- **Độ bao phủ:** 100% chức năng phân hệ FR01 đến FR15:
  - `FR01` Cổng DVC trực tuyến (Nộp hồ sơ Wizard 4 bước, auto-fill VNeID, sinh mã QCVN 102).
  - `FR02` Tiếp nhận Một cửa (Quét QR CCCD, số hóa NĐ 107/2021, in Giấy hẹn Mẫu 01, Mẫu 02, Mẫu 03, Mẫu 06).
  - `FR03` Thụ lý & Thẩm định (Phân công, thẩm định, lấy ý kiến phối hợp, kiểm soát SLA 3 màu).
  - `FR04` Phê duyệt & Ký số (Lãnh đạo phòng duyệt bước 1, Lãnh đạo cơ quan ký phát hành OpenSSL/SmartCA).
  - `FR05` Văn thư số & Cấp số phát hành (Vào sổ văn bản, đóng dấu số tổ chức, đẩy kho số hóa).
  - `FR06` SLA Engine & Cảnh báo quá hạn (Tính giờ làm việc trừ ngày nghỉ lễ, khóa bước khi SLA ĐỎ, Mẫu 04 Xin lỗi).
  - `FR07` Thông báo đa kênh (SMS Brandname, Zalo ZNS, Outbox worker).
  - `FR08` Dashboard Chỉ đạo & Báo cáo IOC (Bản đồ 15 quận/huyện SVG, xếp hạng thi đua, báo cáo TT 01, NĐ 107).
  - `FR09` Quản lý Danh mục dùng chung (MDM ĐVHC, TTHC quốc gia, Phí lệ phí).
  - `FR10` Thiết kế Quy trình & E-Form (BPMN-lite, JSON Schema dynamic form).
  - `FR11` Quản lý Phân quyền & Định danh (RBAC 7 vai trò, SSO VNeID, Audit trail WORM).
  - `FR12` Giám sát An toàn thông tin (Nhật ký truy cập, IP block, CSRF, CSP).
  - `FR13` Sao lưu & Phục hồi CSDL (Full, Differential, Transaction Log, Diễn tập DRP).
  - `FR14` Chuyển đổi dữ liệu & Tích hợp (ETL 4 bước, REST API `/api/v1`, Webhook LGSP).
  - `FR15` Đào tạo & Chuyển giao công nghệ (4 nhóm cán bộ, Staging banner).

### 2.2. Kiểm thử tích hợp & Liên thông đa cấp (Integration Testing)
- **Kịch bản Tuyến cơ sở (Xã $\rightarrow$ Huyện):**
  - Công dân nộp hồ sơ Đăng ký biến động quyền sử dụng đất tại Bộ phận Một cửa UBND Xã/Phường.
  - Cán bộ Một cửa cấp xã tiếp nhận, số hóa hồ sơ và chuyển liên thông lên Chi nhánh Văn phòng Đăng ký đất đai / Phòng TN&MT UBND Quận/Huyện.
  - Chuyên viên cấp Huyện thụ lý, thẩm định cơ sở dữ liệu địa chính.
  - Lãnh đạo UBND Quận phê duyệt và ký số quyết định.
  - Văn thư huyện vào sổ, đóng dấu và chuyển kết quả điện tử về lại UBND Xã để trả cho công dân.
- **Kịch bản Tuyến liên ngành logistics cảng biển:**
  - Dự án Kho bãi logistics khu vực Cảng Đình Vũ: Tiếp nhận tại UBND Quận Hồng Bàng $\rightarrow$ Lấy ý kiến Sở Xây dựng (Quy hoạch) $\rightarrow$ Sở TN&MT (Đánh giá tác động môi trường) $\rightarrow$ Sở KH&ĐT (Chủ trương đầu tư) $\rightarrow$ Trình Lãnh đạo UBND Thành phố phê duyệt ký số.
- **Tiêu chuẩn kiểm chứng:**
  - Toàn vẹn 100% tệp đính kèm: Tính và so sánh mã băm SHA-256 tại từng trạm luân chuyển. Phát hiện và cảnh báo ngay lập tức nếu tệp bị sửa đổi hoặc tráo đổi nội dung.
  - Xác thực chữ ký số: Chứng thư số và chữ ký PKCS#7 / SHA256withRSA của từng cán bộ/cơ quan được xác minh hợp lệ tại mỗi bước chuyển tiếp.
  - Tuyệt đối không xảy ra tình trạng Deadlock CSDL hoặc mất trạng thái hồ sơ trong quá trình luân chuyển.

### 2.3. Kiểm thử hiệu năng & Chịu tải (Performance Testing)
- **Công cụ:** Kịch bản k6 (`tests/performance/portal_5000ccu.js`, `tests/performance/noibo_1200ccu.js`) và kế hoạch kiểm thử Apache JMeter.
- **Kịch bản 1: Cổng DVC phục vụ Công dân:**
  - 5.000 người dùng ảo đồng thời (5.000 CCU).
  - Hành vi: 60% tra cứu tiến độ hồ sơ, 25% xem danh mục TTHC và tải biểu mẫu, 15% nộp hồ sơ trực tuyến có đính kèm tệp PDF kích thước từ 2MB đến 10MB.
- **Kịch bản 2: Hệ thống Nội bộ Cán bộ công chức:**
  - 1.200 cán bộ công chức đồng thời (1.200 CCU).
  - Hành vi: Cán bộ Một cửa quét QR & số hóa, Chuyên viên thụ lý & chuyển bước, Lãnh đạo ký số hàng loạt (Batch signing 20-50 hồ sơ/lượt).
- **Tiêu chuẩn nghiệm thu hiệu năng (§3.2):**
  - Thời gian phản hồi trung bình (Average Response Time): $\le$ 1.0 giây.
  - Phân vị thứ 95 (P95 Response Time): $\le$ 1.5 giây.
  - Tỷ lệ lỗi giao dịch (Error Rate): $\le$ 0.01% (dưới 1 lỗi trên 10.000 giao dịch).
  - Tải CPU máy chủ ứng dụng: $\le$ 75%.
  - Bộ nhớ RAM tiêu thụ: $\le$ 80%.
  - Không có hiện tượng rò rỉ kết nối cơ sở dữ liệu (Connection leak) sau 12 giờ chạy liên tục.

### 2.4. Kiểm thử An toàn thông tin Cấp độ 3 (Security Testing)
- **Tiêu chuẩn căn cứ:** Nghị định 85/2016/NĐ-CP, Thông tư 12/2022/TT-BTTTT và Cẩm nang OWASP WSTG v4.2.
- **Danh mục kiểm thử bắt buộc:**
  1. *SQL Injection (SQLi):* Thử nghiệm payload SQLi trên 100% các trường tìm kiếm, lọc, sắp xếp, tham số URL. Đảm bảo 100% câu truy vấn dùng PDO Prepared Statements.
  2. *Insecure Direct Object References (IDOR):* Thay đổi tham số `ma_ho_so`, `hoso_id` của công dân A sang công dân B $\rightarrow$ Phải nhận mã lỗi HTTP 403 Forbidden.
  3. *Cross-Site Scripting (XSS):* Kiểm tra Reflected XSS và Stored XSS trong tất cả các form nhập liệu, trường tên, ghi chú, ý kiến xử lý. Đảm bảo 100% biến đầu ra được lọc qua hàm `e()` (htmlspecialchars UTF-8).
  4. *Cross-Site Request Forgery (CSRF):* Kiểm tra mọi form POST/PUT/DELETE. Request thiếu hoặc sai token CSRF $\rightarrow$ Phải nhận mã lỗi HTTP 419 Page Expired.
  5. *File Upload Security:* Thử tải file giả mạo MIME, file 2 phần mở rộng (`tailieu.php.pdf`), file thực thi `.exe`, `.sh`, file vượt quá dung lượng 10MB $\rightarrow$ Chặn đứng và xóa ngay file tạm.
  6. *Brute-force & Password Policy:* Thử đăng nhập sai liên tiếp 5 lần $\rightarrow$ Tự động khóa tài khoản trong 15 phút.
  7. *DDoS & Rate Limiting:* Gửi request dồn dập vượt 60 request/phút/IP $\rightarrow$ Trả về mã lỗi HTTP 429 Too Many Requests kèm header `Retry-After`.
  8. *HTTP Security Headers & Cookies:* Kiểm tra có đầy đủ `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`. Cookie phiên phải có cờ `HttpOnly`, `SameSite=Strict`.
  9. *Phân tách quyền SYSADMIN:* Xác minh Quản trị viên hệ thống tuyệt đối không có quyền can thiệp nghiệp vụ hồ sơ (`hoso.*`) và không có quyền ký số (`kyso.*`).
  10. *Nguyên tắc bất biến dữ liệu:* Thử thực thi câu lệnh `DELETE FROM ho_so` $\rightarrow$ Phải bị Trigger CSDL chặn đứng và ném lỗi SQLSTATE 45000.
  11. *Toàn vẹn Chuỗi băm WORM:* Kiểm tra hàm băm `SHA256(prev_hash | data)` của `nhat_ky_kiem_toan`, phát hiện tức thời bất kỳ hành vi sửa đổi dữ liệu trái phép nào.

### 2.5. Kiểm thử chấp nhận người dùng (UAT Testing)
- **Thời gian thực địa:** 30 ngày làm việc liên tục.
- **Địa điểm thử nghiệm:**
  - *Điểm thực địa 1:* Bộ phận Tiếp nhận & Trả kết quả UBND Quận Hồng Bàng. Thủ tục: Đất đai, Hộ tịch, Cấp giấy phép xây dựng nhà ở riêng lẻ. Quy mô: Kiểm thử trên 1.420 hồ sơ thực tế.
  - *Điểm thực địa 2:* Bộ phận Một cửa Sở Giao thông Vận tải Hải Phòng. Thủ tục: Cấp đổi Giấy phép lái xe, Cấp phù hiệu xe ô tô vận tải container cảng biển.
- **Tiêu chí đánh giá:**
  - Thời gian tiếp nhận trung bình tại quầy rút ngắn từ 12 phút xuống dưới 4 phút nhờ tính năng quét QR CCCD chip C06.
  - Tỷ lệ ký số thành công trên thiết bị di động (SmartCA) đạt 100%, độ trễ ký số dưới 2.0 giây.
  - Chỉ số hài lòng của người dân và doanh nghiệp (SIPAS) đạt trên 95%.

---

## 3. MÔI TRƯỜNG KIỂM THỬ & TIÊU CHÍ VÀO/RA

### 3.1. Cấu hình môi trường
| Thành phần | Môi trường Dev (Hiện tại) | Môi trường Staging (Mạng TSLCD HP) | Môi trường Production |
|---|---|---|---|
| **Máy chủ Web** | Apache 2.4 (XAMPP Windows) | NGINX 1.24 + PHP-FPM 8.2 (Linux RHEL 9) | Cụm 02 NGINX Reverse Proxy + Cụm 04 Node Web |
| **Cơ sở dữ liệu**| MariaDB 10.4.32 (Single node) | MariaDB 10.11 LTS Enterprise | Cụm Master – Replica (Failover < 30s) SSD NVMe |
| **Mạng kết nối** | Localhost (127.0.0.1) | Mạng truyền số liệu chuyên dùng (TSLCD) cấp 2 | Mạng TSLCD + DMZ Internet bảo vệ qua WAF F5 |
| **Dung lượng lưu trữ**| Thư mục local `storage/` | SAN Storage 5TB RAID-10 | SAN Storage 20TB + Offsite Backup DR Site |

### 3.2. Tiêu chí vào (Entry Criteria)
1. Toàn bộ mã nguồn đã hoàn thành biên dịch và cú pháp hợp lệ (`php -l` đạt 100%).
2. Cơ sở dữ liệu đã nạp đầy đủ cấu trúc bảng, ràng buộc, trigger chống DELETE và dữ liệu danh mục ban đầu.
3. Bộ khóa demo OpenSSL và cấu hình kết nối đã sẵn sàng trong thư mục `storage/keys/`.

### 3.3. Tiêu chí ra (Exit Criteria)
1. **100% test cases tự động (Unit, Integration, Security, Functional, UAT)** chạy thành công (`0 failed`) trong `tests/run.php`.
2. Không còn bất kỳ lỗi nào ở mức độ Nghiêm trọng (Blocker/Critical) hoặc Mức cao (Major).
3. Đầy đủ các biên bản kiểm thử ATTT theo chuẩn OWASP WSTG và Biên bản bàn giao kỹ thuật UAT có chữ ký các bên.
