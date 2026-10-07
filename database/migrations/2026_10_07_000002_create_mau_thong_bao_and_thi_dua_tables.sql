-- Migration: Tạo bảng quản lý mẫu thông báo đa kênh và nhật ký điểm thi đua đơn vị (Phiên 11)
-- Căn cứ: §2.2 Báo cáo Kiến trúc HP-eGov

-- 1. Bảng mẫu thông báo đa kênh (SMS Brandname, Zalo ZNS, Email, In-app)
CREATE TABLE IF NOT EXISTS `mau_thong_bao` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_mau` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã mẫu định danh (TIEP_NHAN, DANG_THU_LY, YEU_CAU_BO_SUNG, DA_CO_KET_QUA, XIN_LOI_QUA_HAN)',
    `ten_mau` VARCHAR(150) NOT NULL COMMENT 'Tên hiển thị của mẫu thông báo',
    `su_kien` VARCHAR(50) NOT NULL COMMENT 'Loại sự kiện kích hoạt',
    `kenh_sms_active` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Bật SMS, 0: Tắt',
    `kenh_zalo_active` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Bật Zalo ZNS, 0: Tắt',
    `kenh_email_active` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Bật Email, 0: Tắt',
    `kenh_inapp_active` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Bật In-app, 0: Tắt',
    `tieu_de_mau` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề thông báo',
    `noi_dung_sms` TEXT NOT NULL COMMENT 'Mẫu nội dung SMS Brandname UBND TP HP',
    `noi_dung_zalo` TEXT NOT NULL COMMENT 'Mẫu nội dung tin Zalo ZNS',
    `noi_dung_email` TEXT NULL COMMENT 'Mẫu nội dung Email thông báo',
    `noi_dung_inapp` TEXT NOT NULL COMMENT 'Mẫu nội dung thông báo trên hệ thống',
    `danh_sach_bien` VARCHAR(255) NOT NULL DEFAULT '{ma_ho_so},{ten_tthc},{ho_ten},{trang_thai},{ngay_hen_tra},{link_tra_cuu}',
    `trang_thai` TINYINT NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình mẫu tin nhắn thông báo đa kênh';

-- 2. Dữ liệu mẫu ban đầu cho các sự kiện TTHC
INSERT INTO `mau_thong_bao` (`ma_mau`, `ten_mau`, `su_kien`, `kenh_sms_active`, `kenh_zalo_active`, `kenh_email_active`, `kenh_inapp_active`, `tieu_de_mau`, `noi_dung_sms`, `noi_dung_zalo`, `noi_dung_email`, `noi_dung_inapp`, `danh_sach_bien`) VALUES
('TIEP_NHAN', 'Thông báo tiếp nhận hồ sơ thành công', 'TIEP_NHAN', 1, 1, 1, 1, 
 'UBND TP HP: Tiếp nhận thành công hồ sơ {ma_ho_so}', 
 'UBND TP HP thong bao: Ho so {ma_ho_so} ({ten_tthc}) da duoc tiep nhan. Hen tra ket qua: {ngay_hen_tra}. Tra cuu tai: {link_tra_cuu}', 
 'Kính gửi {ho_ten}, Trung tâm Phục vụ HCC TP. Hải Phòng đã tiếp nhận hồ sơ {ma_ho_so} - Thủ tục: {ten_tthc}. Thời gian hẹn trả: {ngay_hen_tra}. Vui lòng quét mã QR hoặc truy cập {link_tra_cuu} để theo dõi tiến độ.', 
 'Kính gửi Quý công dân/Doanh nghiệp {ho_ten},<br><br>Hồ sơ của Quý vị với mã số <strong>{ma_ho_so}</strong> ({ten_tthc}) đã được tiếp nhận thành công vào hệ thống Một cửa điện tử liên thông TP. Hải Phòng.<br>Ngày hẹn trả kết quả: <strong>{ngay_hen_tra}</strong>.<br><br>Trân trọng cảm ơn!', 
 'Hồ sơ {ma_ho_so} của công dân {ho_ten} đã được tiếp nhận thành công vào hệ thống. Hạn trả: {ngay_hen_tra}.', 
 '{ma_ho_so},{ten_tthc},{ho_ten},{ngay_hen_tra},{link_tra_cuu}'),

('DANG_THU_LY', 'Thông báo hồ sơ đang được thẩm định', 'DANG_THU_LY', 0, 1, 1, 1, 
 'UBND TP HP: Hồ sơ {ma_ho_so} đang được thụ lý', 
 'UBND TP HP: Ho so {ma_ho_so} da duoc phan cong chuyen vien va dang trong qua trinh tham dinh.', 
 'Kính gửi {ho_ten}, hồ sơ {ma_ho_so} của Quý vị đã được phân công thụ lý thẩm định theo quy trình giải quyết TTHC. Tra cứu: {link_tra_cuu}', 
 'Kính gửi {ho_ten},<br>Hồ sơ {ma_ho_so} đang được thụ lý chuyên môn tại cơ quan giải quyết.', 
 'Hồ sơ {ma_ho_so} đã chuyển sang bước thẩm định chuyên môn.', 
 '{ma_ho_so},{ten_tthc},{ho_ten},{link_tra_cuu}'),

('YEU_CAU_BO_SUNG', 'Thông báo yêu cầu sửa đổi, bổ sung hồ sơ', 'YEU_CAU_BO_SUNG', 1, 1, 1, 1, 
 'UBND TP HP: Yêu cầu bổ sung hồ sơ {ma_ho_so}', 
 'UBND TP HP thong bao: Ho so {ma_ho_so} can bo sung thong tin/giay to theo yeu cau. Vui long kiem tra tai: {link_tra_cuu}', 
 'Kính gửi {ho_ten}, hồ sơ {ma_ho_so} của Quý vị cần hoàn thiện, bổ sung tài liệu. Vui lòng truy cập Cổng DVC Hải Phòng hoặc liên hệ Bộ phận Một cửa để bổ sung trước hạn: {ngay_hen_tra}. Tra cứu chi tiết: {link_tra_cuu}', 
 'Kính gửi {ho_ten},<br>Bộ phận Một cửa/Cơ quan chuyên môn yêu cầu Quý vị bổ sung tài liệu cho hồ sơ <strong>{ma_ho_so}</strong>.<br>Thời gian tính hạn SLA đang tạm dừng chờ Quý vị hoàn thiện hồ sơ.', 
 'Cán bộ đã phát yêu cầu bổ sung tài liệu cho hồ sơ {ma_ho_so}. SLA tạm dừng.', 
 '{ma_ho_so},{ten_tthc},{ho_ten},{ngay_hen_tra},{link_tra_cuu}'),

('DA_CO_KET_QUA', 'Thông báo có kết quả giải quyết TTHC', 'DA_CO_KET_QUA', 1, 1, 1, 1, 
 'UBND TP HP: Hồ sơ {ma_ho_so} đã có kết quả', 
 'UBND TP HP thong bao: Ho so {ma_ho_so} da co ket qua giai quyet. Quy vi vui long nhan ket qua dien tu tai: {link_tra_cuu} hoac tai Bo phan Mot cua.', 
 'Kính gửi {ho_ten}, hồ sơ {ma_ho_so} của Quý vị đã được phê duyệt và ban hành kết quả chính thức có chữ ký số điện tử. Quý vị có thể tải kết quả ngay tại: {link_tra_cuu} hoặc nhận bản giấy qua bưu điện/Bộ phận Một cửa.', 
 'Kính gửi {ho_ten},<br>Hồ sơ <strong>{ma_ho_so}</strong> đã được giải quyết thành công. Kết quả điện tử có chữ ký số đã sẵn sàng trong Kho dữ liệu công dân của Quý vị.', 
 'Hồ sơ {ma_ho_so} đã hoàn tất và ban hành kết quả có chữ ký số.', 
 '{ma_ho_so},{ten_tthc},{ho_ten},{link_tra_cuu}'),

('XIN_LOI_QUA_HAN', 'Phiếu xin lỗi và hẹn lại ngày trả kết quả', 'XIN_LOI_QUA_HAN', 1, 1, 1, 1, 
 'UBND TP HP: Phiếu xin lỗi và hẹn lại kết quả hồ sơ {ma_ho_so}', 
 'UBND TP HP chan thanh xin loi Quy vi ve su cham tre ho so {ma_ho_so}. Ngay hen tra moi: {ngay_hen_tra}. Tra cuu van ban: {link_tra_cuu}', 
 'Kính gửi {ho_ten}, UBND Thành phố/Cơ quan giải quyết chân thành cáo lỗi vì chưa thể trả kết quả hồ sơ {ma_ho_so} đúng hẹn do nguyên nhân khách quan. Lãnh đạo cơ quan đã ký ban hành Văn bản xin lỗi Mẫu số 04 và hẹn lại ngày trả kết quả mới là: {ngay_hen_tra}. Xem chi tiết: {link_tra_cuu}', 
 'Kính gửi {ho_ten},<br>Cơ quan giải quyết chân thành gửi lời xin lỗi đến Quý vị về việc trễ hạn giải quyết hồ sơ <strong>{ma_ho_so}</strong>. Lãnh đạo cơ quan đã ký Văn bản xin lỗi và hẹn lại ngày trả kết quả: <strong>{ngay_hen_tra}</strong>.', 
 'Đã ban hành Văn bản xin lỗi Mẫu số 04 cho hồ sơ quá hạn {ma_ho_so}. Ngày hẹn mới: {ngay_hen_tra}.', 
 '{ma_ho_so},{ten_tthc},{ho_ten},{ngay_hen_tra},{link_tra_cuu}')
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 3. Bảng nhật ký điểm thi đua đơn vị (§2.2, §4.3)
CREATE TABLE IF NOT EXISTS `diem_thi_dua_don_vi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `don_vi_id` INT NOT NULL COMMENT 'Đơn vị hành chính',
    `ho_so_id` INT NULL COMMENT 'Hồ sơ liên quan',
    `loai_su_kien` VARCHAR(50) NOT NULL COMMENT 'QUA_HAN_SLA, XIN_LOI_DUNG_HAN, HOAN_THANH_TRUOC_HAN...',
    `diem_thay_doi` DECIMAL(4,1) NOT NULL COMMENT 'Điểm cộng hoặc trừ (+1.0, -1.0)',
    `ly_do` VARCHAR(255) NOT NULL,
    `thoi_gian` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dtd_donvi` (`don_vi_id`),
    INDEX `idx_dtd_hoso` (`ho_so_id`),
    CONSTRAINT `fk_dtd_donvi` FOREIGN KEY (`don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dtd_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký ghi nhận điểm thi đua giải quyết TTHC của các đơn vị';
