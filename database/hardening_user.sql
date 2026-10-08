-- ============================================================================
-- HỆ THỐNG HP-eGov HẢI PHÒNG - SCRIPT THIẾT LẬP TÀI KHOẢN CSDL AN TOÀN TỐI THIỂU
-- BẢO VỆ DỮ LIỆU CẤP ĐỘ 3 THEO NGHỊ ĐỊNH 85/2016/NĐ-CP & QUY TẮC BẤT BIẾN MASTER
-- ============================================================================

-- 1. TẠO TÀI KHOẢN ỨNG DỤNG 'hpegov_app' CHỈ TRUY CẬP TỪ LOCALHOST / WEBSERVER
CREATE USER IF NOT EXISTS 'hpegov_app'@'localhost' IDENTIFIED BY 'HpegovApp@Secure2026';
CREATE USER IF NOT EXISTS 'hpegov_app'@'127.0.0.1' IDENTIFIED BY 'HpegovApp@Secure2026';

-- 2. THU HỒI TOÀN BỘ ĐẶC QUYỀN MẶC ĐỊNH
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'hpegov_app'@'localhost';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'hpegov_app'@'127.0.0.1';

-- 3. CẤP ĐẶC QUYỀN NGHIỆP VỤ AN TOÀN TRÊN TOÀN BỘ CSDL hp_egov:
-- - SELECT: Đọc dữ liệu
-- - INSERT: Thêm bản ghi mới
-- - UPDATE: Cập nhật trạng thái
-- - EXECUTE: Thực thi Stored Procedures / Functions
-- (TUYỆT ĐỐI KHÔNG CẤP QUYỀN DELETE, DROP, ALTER, TRUNCATE TRÊN TOÀN CSDL)
GRANT SELECT, INSERT, UPDATE, EXECUTE ON `hp_egov`.* TO 'hpegov_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, EXECUTE ON `hp_egov`.* TO 'hpegov_app'@'127.0.0.1';

-- 4. ĐẶC QUYỀN DELETE CÓ ĐIỀU KIỆN (CHỈ TRÊN BẢNG KỸ THUẬT):
-- Bảng gioi_han_truy_cap: Cần xóa bản ghi hết hạn rate-limit (xóa sau mỗi chu kỳ 1 phút)
GRANT DELETE ON `hp_egov`.`gioi_han_truy_cap` TO 'hpegov_app'@'localhost';
GRANT DELETE ON `hp_egov`.`gioi_han_truy_cap` TO 'hpegov_app'@'127.0.0.1';

-- Bảng hang_doi_thong_bao: Cho phép dọn dẹp tin nhắn đã gửi thành công sau 90 ngày nếu có cron
GRANT DELETE ON `hp_egov`.`hang_doi_thong_bao` TO 'hpegov_app'@'localhost';
GRANT DELETE ON `hp_egov`.`hang_doi_thong_bao` TO 'hpegov_app'@'127.0.0.1';

-- 5. ÁP DỤNG THAY ĐỔI
FLUSH PRIVILEGES;

-- ============================================================================
-- HƯỚNG DẪN CẤU HÌNH TRONG .env KHI CHUYỂN SANG PRODUCTION:
-- DB_CONNECTION=mysql
-- DB_HOST=127.0.0.1
-- DB_PORT=3306
-- DB_DATABASE=hp_egov
-- DB_USERNAME=hpegov_app
-- DB_PASSWORD=HpegovApp@Secure2026
-- ============================================================================
