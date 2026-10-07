# DANH MỤC THƯ VIỆN BÊN THỨ BA (THIRD-PARTY LIBRARIES)

> **Hệ thống:** HP-eGov – Một cửa điện tử liên thông & Cổng Dịch vụ công TP. Hải Phòng  
> **Chính sách mạng:** Toàn bộ thư viện được tải về lưu trữ và phục vụ **cục bộ (On-Premise)** trong thư mục `public/assets/vendor/` và `public/assets/fonts/`. **Tuyệt đối không sử dụng CDN internet** do hệ thống vận hành trên mạng Truyền số liệu chuyên dùng (TSLCD) cấp II theo QCVN và Quy chuẩn An toàn thông tin Cấp độ 3.

---

## 1. DANH SÁCH THƯ VIỆN & GIẤY PHÉP BẢN QUYỀN

| STT | Tên thư viện | Phiên bản | Tệp lưu trữ cục bộ | Giấy phép (License) | Mục đích sử dụng trong HP-eGov |
|:---:|---|:---:|---|:---:|---|
| 1 | **Be Vietnam Pro** | v12 | `public/assets/fonts/be-vietnam-pro.css`<br>`public/assets/fonts/font_*.woff2` | [SIL Open Font License 1.1 (OFL)](https://scripts.sil.org/OFL) | Font chữ định danh chuẩn của hệ thống, thiết kế tối ưu hóa hiển thị cho tiếng Việt có dấu, đáp ứng tiêu chuẩn thể thức văn bản hành chính nhà nước. |
| 2 | **Chart.js** | 4.4.1 | `public/assets/vendor/chartjs/chart.umd.js` | [MIT License](https://opensource.org/licenses/MIT) | Biểu đồ điều hành IOC Trung tâm Giám sát Hải Phòng (PH08), trực quan hóa tỷ lệ đúng/quá hạn theo đơn vị, phân bố thời gian giải quyết. |
| 3 | **qrcode-generator** | 1.4.4 | `public/assets/vendor/qrcode/qrcode.min.js` | [MIT License](https://opensource.org/licenses/MIT) | Sinh mã QR code tra cứu tiến độ hồ sơ in trên Giấy tiếp nhận và hẹn trả kết quả (Mẫu số 01/TT01), Giấy hẹn và tem biên nhận. |
| 4 | **html5-qrcode** | 2.3.8 | `public/assets/vendor/html5-qrcode/html5-qrcode.min.js` | [Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0) | Quét mã QR thẻ Căn cước công dân gắn chip (CCCD) bằng webcam máy tính tại quầy tiếp nhận Một cửa (PH02) để tự động trích xuất thông tin công dân. |

---

## 2. CAM KẾT AN TOÀN MÃ NGUỒN

- 100% tệp JavaScript và Font được phục vụ trực tiếp từ máy chủ web nội bộ.
- Được kiểm soát bằng chính sách bảo mật HTTP Content-Security-Policy:
  ```http
  Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; font-src 'self'; img-src 'self' data:; connect-src 'self';
  ```
- Không chứa mã theo dõi (tracking scripts), không có backdoor hoặc kết nối bí mật ra internet bên ngoài.
