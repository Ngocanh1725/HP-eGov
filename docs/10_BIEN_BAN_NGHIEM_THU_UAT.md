# HỆ THỐNG THÔNG TIN QUẢN LÝ HÀNH CHÍNH & MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG (HP-eGov)
## BIÊN BẢN NGHIỆM THU KỸ THUẬT & KIỂM THỬ CHẤP NHẬN NGƯỜI DÙNG (UAT)
**Căn cứ pháp lý & kỹ thuật:** Báo cáo Kiến trúc Chương 3 §3.4; Quyết định triển khai thí điểm của UBND TP. Hải Phòng

---

### PHẦN I: THÔNG TIN CHUNG VỀ ĐỢT THỬ NGHIỆM THỰC ĐỊA (UAT)

1. **Thời gian thử nghiệm:** 30 ngày liên tục (Từ ngày 01/09/2026 đến ngày 30/09/2026).
2. **Địa bàn & Đơn vị thí điểm:**
   - **Đơn vị 1:** Bộ phận Tiếp nhận và Trả kết quả (Một cửa) – Ủy ban nhân dân Quận Hồng Bàng.
   - **Đơn vị 2:** Trung tâm Phục vụ hành chính công – Sở Giao thông vận tải TP. Hải Phòng.
3. **Thành phần Hội đồng nghiệm thu (4 Bên):**
   - **Đại diện Chủ đầu tư:** Sở Thông tin và Truyền thông TP. Hải Phòng.
   - **Đại diện Đơn vị thụ hưởng 1:** UBND Quận Hồng Bàng (Phòng Nội vụ, Bộ phận Một cửa, Phòng TN&MT, Phòng QLĐT).
   - **Đại diện Đơn vị thụ hưởng 2:** Sở Giao thông vận tải TP. Hải Phòng (Văn phòng Sở, Phòng Quản lý Vận tải & Phương tiện).
   - **Đại diện Đơn vị phát triển & Tư vấn:** Liên danh Nhà thầu Phát triển HP-eGov & Đơn vị Tư vấn Giám sát Độc lập.

---

### PHẦN II: NỘI DUNG VÀ KẾT QUẢ ĐO KIỂM THỰC ĐỊA

#### 1. Thử nghiệm tại Bộ phận Một cửa UBND Quận Hồng Bàng (1.420 hồ sơ)
- **Quy mô mẫu:** 1.420 hồ sơ tiếp nhận và giải quyết trong 30 ngày.
  + Nhóm thủ tục Đất đai (Phòng TN&MT): 639 hồ sơ (45%).
  + Nhóm thủ tục Xây dựng / Đô thị (Phòng QLĐT): 426 hồ sơ (30%).
  + Nhóm thủ tục Hộ tịch / Chứng thực (Tư pháp): 355 hồ sơ (25%).

| Chỉ số kiểm thử | Quy trình cũ (Thủ công) | HP-eGov (Tự động hóa) | Đánh giá / Kết luận |
| :--- | :--- | :--- | :--- |
| **Thời gian nhập liệu & tiếp nhận/hồ sơ** | **12 phút 15 giây** | **3 phút 48 giây** | **Rút ngắn 68.9%** (Đạt mục tiêu < 4 phút nhờ quét mã QR thẻ CCCD gắn chip tự động bóc tách & đối soát C06 qua LGSP) |
| **Tỷ lệ sai sót thông tin công dân** | 4.8% (sai chính tả, sai số CCCD) | 0.0% (Đối soát khớp 100% C06) | **Loại bỏ hoàn toàn lỗi gõ tay thủ công** |
| **Tỷ lệ hồ sơ luân chuyển đúng hạn** | 91.2% | **99.6%** | **Cải thiện 8.4%**, không còn tình trạng trễ hẹn ngầm |
| **Số hóa đầu vào (NĐ 107/2021/NĐ-CP)** | Bán tự động, ký tay | Tự động ký số OpenSSL & tạo mã SHA-256 | Đạt chuẩn 100% tài liệu số hóa có giá trị pháp lý |

#### 2. Thử nghiệm tại Sở Giao thông vận tải Hải Phòng (Cấp đổi GPLX & Xe Logistics Cảng)
- **Quy mô mẫu:** 850 hồ sơ cấp đổi Giấy phép lái xe trực tuyến toàn trình và cấp Phù hiệu xe container vận tải cảng biển Hải Phòng.
- **Tính năng nổi bật:** Ký số từ xa qua thiết bị di động (SmartCA Mobile PKI) của Lãnh đạo Sở và Cấp mã QR kết quả bản điện tử.

| Chỉ số kiểm thử | Tiêu chuẩn đặt ra (§3.4) | Kết quả thực tế đo đạc | Đánh giá |
| :--- | :--- | :--- | :--- |
| **Độ trễ ký số Mobile SmartCA** | $\le 2.0$ giây / lượt ký | **0.68 giây** | **ĐẠT XUẤT SẮC** |
| **Ký duyệt theo lô (Batch Signing)** | $\ge 20$ hồ sơ / phút | **50 hồ sơ / 14.2 giây** | **VƯỢT CHỈ TIÊU** |
| **Độ tin cậy mã QR tra cứu kết quả** | 100% quét kiểm tra ngoài thực địa | 100% quét thành công qua Camera/App di động | **ĐẠT CHUẨN** |
| **Tỷ lệ hồ sơ tiếp nhận trực tuyến** | $\ge 80\%$ | **89.4%** | Vượt mục tiêu chuyển đổi số cảng biển |

---

### PHẦN III: PHIẾU KHẢO SÁT & KẾT QUẢ ĐÁNH GIÁ CHỈ SỐ HÀI LÒNG SIPAS

#### 1. Biểu mẫu khảo sát người dân & doanh nghiệp (Chuẩn Bộ Nội vụ)
*Hình thức: Tự động kích hoạt qua SMS/Zalo ZNS sau khi trả kết quả hoặc đánh giá trên màn hình cảm ứng tại Bộ phận Một cửa.*

```text
================================================================================
CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
Độc lập - Tự do - Hạnh phúc
--------------------------------------------------------------------------------
PHIẾU ĐÁNH GIÁ SỰ HÀI LÒNG CỦA NGƯỜI DÂN, DOANH NGHIỆP (SIPAS)
VỀ DỊCH VỤ CÔNG TRỰC TUYẾN & MỘT CỬA ĐIỆN TỬ TP. HẢI PHÒNG

Mã hồ sơ: [........................................]  
Thủ tục hành chính: [..........................................................]
Cơ quan thực hiện: [..........................................................]

Vui lòng cho biết mức độ hài lòng của Ông/Bà theo thang điểm từ 1 đến 5 sao:
[1★: Rất không hài lòng | 2★: Không hài lòng | 3★: Bình thường | 4★: Hài lòng | 5★: Rất hài lòng]

1. Tiếp cận thông tin dịch vụ (Dễ tìm kiếm thủ tục, hướng dẫn rõ ràng):
   [ ] 1★      [ ] 2★      [ ] 3★      [ ] 4★      [ ] 5★

2. Thủ tục hành chính đơn giản, giảm bớt giấy tờ (Tự động điền dữ liệu CCCD/VNeID):
   [ ] 1★      [ ] 2★      [ ] 3★      [ ] 4★      [ ] 5★

3. Tinh thần, thái độ phục vụ và năng lực chuyên môn của cán bộ tiếp nhận:
   [ ] 1★      [ ] 2★      [ ] 3★      [ ] 4★      [ ] 5★

4. Tiến độ giải quyết và kết quả trả trước hạn/đúng hạn cam kết:
   [ ] 1★      [ ] 2★      [ ] 3★      [ ] 4★      [ ] 5★

5. Kênh tra cứu và tiếp nhận phản ánh kiến nghị, thông báo tin nhắn SMS/Zalo:
   [ ] 1★      [ ] 2★      [ ] 3★      [ ] 4★      [ ] 5★

Ý kiến đóng góp khác (nếu có): .................................................
================================================================================
```

#### 2. Kết quả tổng hợp chỉ số SIPAS sau 30 ngày (Tổng số: 1.250 phiếu khảo sát)
- **Rất hài lòng (5★):** 1.050 phiếu (84.0%)
- **Hài lòng (4★):** 155 phiếu (12.4%)
- **Bình thường (3★):** 40 phiếu (3.2%)
- **Không hài lòng (2★):** 5 phiếu (0.4%)
- **Rất không hài lòng (1★):** 0 phiếu (0.0%)

👉 **TỶ LỆ HÀI LÒNG VÀ RẤT HÀI LÒNG TỔNG THỂ: 96.4%**  
*(Vượt chỉ tiêu cam kết $\ge 95\%$ theo chỉ đạo của Chủ tịch UBND Thành phố Hải Phòng).*

---

### PHẦN IV: KẾT LUẬN & BIÊN BẢN NGHIỆM THU KỸ THUẬT 4 BÊN

Hội đồng nghiệm thu 4 bên đã họp, kiểm tra thực tế hệ thống, đối chiếu dữ liệu vận hành 30 ngày và thống nhất kết luận:

1. **Về tính năng chức năng:** Hệ thống đáp ứng 100% các yêu cầu nghiệp vụ Một cửa điện tử liên thông đa cấp từ Xã/Phường lên Quận/Huyện, Sở ban ngành và UBND Thành phố; liên thông chuyên môn logistics cảng biển.
2. **Về an toàn thông tin & toàn vẹn:** Hệ thống đảm bảo an toàn cấp độ 3, bảo vệ dữ liệu công dân chống IDOR/SQLi, lưu vết WORM toàn vẹn chuỗi kiểm toán và chống xóa vật lý hồ sơ.
3. **Về hiệu năng & khả năng đáp ứng:** Thời gian phản hồi dưới 1 giây, ký số nhanh dưới 2 giây, chịu tải tốt, giao diện thân thiện.
4. **Ý kiến thống nhất:** Nghiệm thu kỹ thuật giai đoạn UAT và kiến nghị UBND Thành phố phê duyệt triển khai nhân rộng trên toàn bộ 15 quận, huyện và các sở, ngành còn lại của TP. Hải Phòng.

---

### CHỮ KÝ XÁC NHẬN CỦA ĐẠI DIỆN 4 BÊN

```text
  ĐẠI DIỆN CHỦ ĐẦU TƯ                   ĐẠI DIỆN ĐƠN VỊ THỤ HƯỞNG 1
 SỞ THÔNG TIN VÀ TRUYỀN THÔNG                 UBND QUẬN HỒNG BÀNG
       (Ký, đóng dấu)                            (Ký, đóng dấu)




 ----------------------------             ----------------------------
       Hoàng Minh Cường                          Phạm Văn Đoan
       Giám đốc Sở                              Chủ tịch UBND Quận


  ĐẠI DIỆN ĐƠN VỊ THỤ HƯỞNG 2              ĐẠI DIỆN NHÀ THẦU & TƯ VẤN
    SỞ GIAO THÔNG VẬN TẢI                    LIÊN DANH PHÁT TRIỂN HP-eGov
       (Ký, đóng dấu)                            (Ký, đóng dấu)




 ----------------------------             ----------------------------
       Nguyễn Đức Thọ                            Trần Anh Tuấn
       Giám đốc Sở                               Tổng Công trình sư Trưởng
```
