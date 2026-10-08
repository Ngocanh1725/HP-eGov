# ĐẶC TẢ KỸ THUẬT REST API & CỔNG KẾT NỐI MỞ HP-eGov HẢI PHÒNG (V1)

> **Cơ quan chủ quản:** Ủy ban nhân dân Thành phố Hải Phòng  
> **Đơn vị quản lý kỹ thuật:** Sở Thông tin và Truyền thông TP. Hải Phòng  
> **Căn cứ pháp lý & Kiến trúc:** Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov (§1.1, §1.3, §3.3)  
> **Quy chuẩn an toàn:** Cấp độ 3 – Nghị định 85/2016/NĐ-CP, Thông tư 03/2017/TT-BTTTT  
> **Phiên bản tài liệu:** v1.0.0 (Phát hành: 08/10/2026)

---

## 1. TỔNG QUAN KIẾN TRÚC REST API

Cổng kết nối mở HP-eGov REST API cung cấp giao diện trao đổi dữ liệu JSON tiêu chuẩn giữa CSDL lõi của Thành phố với các ứng dụng công dân (Mobile App Công dân Hải Phòng SmartCity), ứng dụng điều hành dành cho Lãnh đạo (Mobile App Lãnh đạo), Cổng Dịch vụ công Quốc gia và Trục liên thông chia sẻ dữ liệu thành phố (LGSP/NDXP).

- **Giao thức:** HTTPS (TLS 1.3)
- **Base URL:** `http://localhost/HP-eGov/api/v1` (Dev) / `https://egov.haiphong.gov.vn/api/v1` (Production)
- **Định dạng dữ liệu:** `application/json; charset=utf-8`
- **Xác thực:** HTTP Authorization Bearer Token (chuẩn RFC 6750)
- **Mã hóa ký số:** HMAC-SHA256 (cho Webhook LGSP), RSA SHA256withRSA (Ký số lãnh đạo qua SmartCA)

---

## 2. CHÍNH SÁCH BẢO MẬT & RATE LIMITING (§3.3)

### 2.1. Xác thực Bearer Token
Mọi yêu cầu tới các API có bảo vệ phải đính kèm Header:
```http
Authorization: Bearer <API_BEARER_TOKEN>
```
Token được mã hóa một chiều bằng thuật toán SHA-256 lưu trong bảng `api_tokens` với thời hạn hiệu lực (`expires_at`) và danh sách phạm vi quyền hạn (`scopes`).

### 2.2. Phân định phạm vi quyền (Scopes)
| Mã Scope | Ý nghĩa | Áp dụng cho |
|---|---|---|
| `*` | Toàn quyền truy cập mọi endpoint | SysAdmin / Hệ thống lõi |
| `tra_cuu` | Tra cứu tiến độ hồ sơ công khai | Cổng thông tin, Kiosk tra cứu |
| `thu_tuc` | Đọc danh mục thủ tục hành chính | Hệ thống tích hợp ngoài |
| `cong_dan` | Truy cập danh sách & chi tiết hồ sơ của chính công dân | Mobile App Công dân |
| `lanh_dao` | Xem hồ sơ chờ ký & ký số duyệt hồ sơ | Mobile App Lãnh đạo |
| `webhook` | Đẩy dữ liệu liên thông từ trục LGSP | Webhook Gateway LGSP |

### 2.3. Giới hạn tần suất truy cập (Rate Limit)
- **Mặc định:** Tối đa **60 requests / phút / IP**.
- **Headers trả về:**
  - `X-RateLimit-Limit`: Ngưỡng giới hạn (60).
  - `X-RateLimit-Remaining`: Số lượt gọi còn lại trong cửa sổ 60 giây.
  - `Retry-After`: Số giây cần chờ trước khi gửi tiếp (khi bị chặn HTTP 429).

---

## 3. CHUẨN ĐỊNH DẠNG PHẢN HỒI JSON

### 3.1. Phản hồi Thành công (HTTP 200 / 201)
```json
{
  "status": "success",
  "code": 200,
  "message": "Thông điệp phản hồi",
  "data": { ... },
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

### 3.2. Phản hồi Lỗi (HTTP 4xx / 5xx)
```json
{
  "status": "error",
  "code": 401,
  "message": "Bearer Token không hợp lệ hoặc đã hết hạn",
  "errors": [],
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

### 3.3. Bảng mã lỗi HTTP
- `200 OK`: Thành công.
- `400 Bad Request`: Thiếu tham số hoặc dữ liệu gửi lên không đúng định dạng.
- `401 Unauthorized`: Thiếu Bearer Token, token sai hoặc token đã hết hạn.
- `403 Forbidden`: Token không đủ quyền truy cập scope yêu cầu hoặc vi phạm chống IDOR.
- `404 Not Found`: Không tìm thấy tài nguyên hoặc mã hồ sơ yêu cầu.
- `429 Too Many Requests`: Vượt quá tần suất 60 req/phút.
- `500 Internal Server Error`: Lỗi máy chủ nội bộ (ghi log tại `storage/logs/`).
- `503 Service Unavailable`: Dịch vụ gián đoạn hoặc CSDL tạm ngưng.

---

## 4. CHI TIẾT DANH MỤC CÁC ENDPOINT

### 4.1. Giám sát Sức khỏe Hạ tầng (Health Check)
- **Endpoint:** `GET /api/v1/health`
- **Xác thực:** Công khai (Public).
- **Mục đích:** Prometheus, Zabbix, Uptime Kuma giám sát uptime, latency và đĩa.
- **Phản hồi 200 OK:**
```json
{
  "status": "UP",
  "timestamp": "2026-10-08T08:00:00+07:00",
  "checks": {
    "database": { "status": "UP", "latency_ms": 0.18 },
    "disk_usage": { "free_gb": 45.2, "used_percent": 68.5 },
    "storage": { "size_mb": 3.25 },
    "notification_queue": { "pending": 0 }
  },
  "environment": {
    "php_version": "8.2.12",
    "mysql_version": "10.4.32",
    "active_sessions": 7
  }
}
```

---

### 4.2. Tra cứu Tiến độ Hồ sơ (Công khai tối thiểu §1.1)
- **Endpoint:** `GET /api/v1/tra-cuu/{ma_ho_so}`
- **Xác thực:** Công khai (Public).
- **Quy chuẩn bảo mật:** Chỉ trả về trạng thái tiến độ; tuyệt đối **KHÔNG để lộ CCCD, địa chỉ, số điện thoại hay tài liệu đính kèm** của công dân.
- **cURL mẫu:**
```bash
curl -X GET "http://localhost/HP-eGov/api/v1/tra-cuu/ETL-MC-SQL-2021-0001" \
     -H "Accept: application/json"
```
- **Phản hồi 200 OK:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Tra cứu thông tin hồ sơ thành công",
  "data": {
    "ma_ho_so": "ETL-MC-SQL-2021-0001",
    "ten_thu_tuc": "Đăng ký khai sinh",
    "ma_thu_tuc": "1.000123.000.00.00.H21",
    "co_quan_tiep_nhan": "Bộ phận Một cửa UBND Quận Hồng Bàng",
    "trang_thai": "DA_TRA_KET_QUA",
    "ten_trang_thai": "Đã trả kết quả",
    "sla_mau": "XANH",
    "ngay_tiep_nhan": "2021-03-10 08:30:00",
    "ngay_hen_tra": "2021-03-11 17:00:00"
  },
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

---

### 4.3. Danh mục Thủ tục Hành chính Công khai
- **Endpoint:** `GET /api/v1/thu-tuc-hanh-chinh`
- **Tham số truy vấn (Query Params):**
  - `q`: Từ khóa tìm kiếm tên thủ tục hoặc mã TTHC (tùy chọn).
  - `don_vi_id`: ID cơ quan thực hiện (tùy chọn).
  - `muc_do`: Mức độ DVC (`DVC_TOAN_TRINH` / `DVC_MOT_PHAN`).
  - `page`: Trang hiện tại (mặc định 1).
  - `limit`: Số bản ghi trên trang (mặc định 15, tối đa 50).
- **Phản hồi 200 OK:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Lấy danh mục thủ tục hành chính thành công",
  "data": {
    "items": [
      {
        "id": 1,
        "ma_tthc": "1.000123.000.00.00.H21",
        "ten_tthc": "Đăng ký khai sinh",
        "muc_do": "DVC_TOAN_TRINH",
        "thoi_han_giai_quyet_gio": 24,
        "le_phi": 0,
        "phi": 0,
        "co_quan_thuc_hien": "UBND Quận Hồng Bàng"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total_items": 12,
      "total_pages": 1
    }
  },
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

---

### 4.4. Danh sách & Chi tiết Hồ sơ của Công dân (Mobile App Công dân)
- **Xác thực:** Bắt buộc Bearer Token có scope `cong_dan`.
- **Token mẫu Dev:** `hpegov_citizen_token_secret_sample_2026`

#### A. Danh sách hồ sơ:
- **Endpoint:** `GET /api/v1/cong-dan/ho-so`
- **Headers:** `Authorization: Bearer hpegov_citizen_token_secret_sample_2026`
- **cURL mẫu:**
```bash
curl -X GET "http://localhost/HP-eGov/api/v1/cong-dan/ho-so" \
     -H "Authorization: Bearer hpegov_citizen_token_secret_sample_2026"
```

#### B. Chi tiết hồ sơ (Kèm chống IDOR):
- **Endpoint:** `GET /api/v1/cong-dan/ho-so/{ma_ho_so}`
- **Bảo vệ IDOR:** Nếu công dân A gửi mã hồ sơ của công dân B, hệ thống tự động chặn và trả mã **HTTP 403 Forbidden**:
```json
{
  "status": "error",
  "code": 403,
  "message": "Bạn không có quyền truy cập hồ sơ của công dân khác (IDOR Protected)",
  "errors": [],
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```
- **Khi hợp lệ (HTTP 200 OK):** Trả về đầy đủ thành phần hồ sơ, danh sách tài liệu kết quả số hóa và lịch sử luân chuyển các bước quy trình.

---

### 4.5. Phê duyệt & Ký số Hồ sơ (Mobile App Lãnh đạo)
- **Xác thực:** Bắt buộc Bearer Token có scope `lanh_dao`.
- **Token mẫu Dev:** `hpegov_leader_token_secret_sample_2026`

#### A. Danh sách hồ sơ chờ ký:
- **Endpoint:** `GET /api/v1/lanh-dao/cho-ky`
- **Headers:** `Authorization: Bearer hpegov_leader_token_secret_sample_2026`
- **Phản hồi 200 OK:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Lấy danh sách hồ sơ chờ ký thành công",
  "data": {
    "tong_so": 5,
    "items": [
      {
        "id": 105,
        "ma_ho_so": "HP-303-202610-0015",
        "nguoi_nop_ho_ten": "Nguyễn Văn An",
        "ten_tthc": "Cấp phép xây dựng nhà ở",
        "co_quan": "UBND Quận Hồng Bàng",
        "trang_thai": "CHO_PHE_DUYET",
        "sla_mau": "XANH",
        "ngay_tiep_nhan": "2026-10-06 08:30:00",
        "ngay_hen_tra": "2026-10-15 17:00:00"
      }
    ]
  },
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

#### B. Thực hiện ký số từ xa (SmartCA Mobile):
- **Endpoint:** `POST /api/v1/lanh-dao/ky-so`
- **Headers:**
  - `Authorization: Bearer hpegov_leader_token_secret_sample_2026`
  - `Content-Type: application/json`
- **Request Body:**
```json
{
  "ho_so_id": 105,
  "ma_pin_smartca": "123456",
  "y_kien": "Đồng ý phê duyệt cấp phép xây dựng theo đề xuất của phòng Quản lý đô thị.",
  "loai_chu_ky": "DUYET_BUOC_1"
}
```
- **Phản hồi 200 OK:**
```json
{
  "status": "success",
  "code": 200,
  "message": "Ký số điện tử hồ sơ qua Mobile App thành công",
  "data": {
    "ho_so_id": 105,
    "ma_ho_so": "HP-303-202610-0015",
    "loai_chu_ky": "DUYET_BUOC_1",
    "trang_thai_moi": "CHO_DONG_DAU",
    "serial": "HP-SMARTCA-2026-9988",
    "sha256": "8eb325b00ed1da4f39df35ff401695fa2a7a48e86c72d3ea5fe6853355447d7c",
    "thoi_gian_ky": "2026-10-08 08:15:30"
  },
  "timestamp": "2026-10-08T08:15:30+07:00"
}
```

---

### 4.6. Webhook Đồng bộ từ LGSP / NDXP Hải Phòng
- **Endpoint:** `POST /api/v1/webhook/lgsp`
- **Header bắt buộc:** `X-LGSP-Signature: <hex_hmac_sha256>`
- **Cơ chế xác thực:** Hệ thống sử dụng khóa bí mật `secret_key` cấu hình tại dịch vụ `LGSP` (trong bảng `cau_hinh_tich_hop`, mặc định: `lgsp_hmac_secret_sha256_hp_connect_webhook`) để kiểm tra tính toàn vẹn:
  $$\text{HMAC} = \text{hash\_hmac}('sha256', \text{RawRequestBody}, \text{SecretKey})$$
- **Nếu thiếu hoặc sai HMAC:** Hệ thống từ chối ngay với mã **HTTP 401** hoặc **HTTP 403 Forbidden**:
```json
{
  "status": "error",
  "code": 403,
  "message": "Chữ ký HMAC không hợp lệ hoặc dữ liệu bị giả mạo trên đường truyền",
  "errors": [],
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```
- **Khi HMAC hợp lệ (HTTP 200 OK):**
```json
{
  "status": "success",
  "code": 200,
  "message": "Webhook LGSP đã được tiếp nhận và xác thực HMAC thành công",
  "data": {
    "event_received": "DONG_BO_KET_QUA_GIAI_QUYET",
    "ma_ho_so": "HP-303-202610-0001",
    "verified": true
  },
  "timestamp": "2026-10-08T08:00:00+07:00"
}
```

---

## 5. HƯỚNG DẪN KIỂM THỬ NHANH BẰNG CURL

### 5.1. Kiểm thử Tra cứu công khai
```bash
curl -i -X GET "http://localhost/HP-eGov/api/v1/tra-cuu/ETL-MC-SQL-2021-0001"
```

### 5.2. Kiểm thử Token không hợp lệ (Kỳ vọng 401)
```bash
curl -i -X GET "http://localhost/HP-eGov/api/v1/cong-dan/ho-so" \
     -H "Authorization: Bearer token_gia_mao_khong_ton_tai"
```

### 5.3. Kiểm thử Token sai quyền Scope (Kỳ vọng 403)
```bash
# Dùng token công dân gọi API lãnh đạo
curl -i -X GET "http://localhost/HP-eGov/api/v1/lanh-dao/cho-ky" \
     -H "Authorization: Bearer hpegov_citizen_token_secret_sample_2026"
```

### 5.4. Kiểm thử Webhook LGSP với chữ ký HMAC hợp lệ
```bash
# Tạo payload
PAYLOAD='{"event":"DONG_BO_KET_QUA_GIAI_QUYET","ma_ho_so":"HP-303-202610-0001","trang_thai":"DA_TRA_KET_QUA"}'

# Sinh chữ ký HMAC-SHA256 với secret 'lgsp_hmac_secret_sha256_hp_connect_webhook'
SIG=$(php -r 'echo hash_hmac("sha256", "{\"event\":\"DONG_BO_KET_QUA_GIAI_QUYET\",\"ma_ho_so\":\"HP-303-202610-0001\",\"trang_thai\":\"DA_TRA_KET_QUA\"}", "lgsp_hmac_secret_sha256_hp_connect_webhook");')

# Gửi Webhook
curl -i -X POST "http://localhost/HP-eGov/api/v1/webhook/lgsp" \
     -H "Content-Type: application/json" \
     -H "X-LGSP-Signature: $SIG" \
     -d "$PAYLOAD"
```

---
*Tài liệu này là thành phần bàn giao chính thức của phân hệ Quản trị Tích hợp & REST API (Phiên 14/16).*
