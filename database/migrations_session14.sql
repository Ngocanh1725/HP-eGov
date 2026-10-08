-- Phiên 14: Bảng API Token, Cấu hình Tích hợp & Bảng Ánh xạ ETL

-- 1. Bảng API Tokens (Xác thực Bearer Token cho REST API /api/v1)
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nguoi_dung_id` INT NULL COMMENT 'ID cán bộ nếu là token nội bộ/lãnh đạo',
    `cong_dan_id` INT NULL COMMENT 'ID công dân nếu là token công dân',
    `ten_token` VARCHAR(100) NOT NULL COMMENT 'Tên gợi nhớ của token (ví dụ: Mobile App Lãnh đạo)',
    `token_hash` VARCHAR(64) NOT NULL UNIQUE COMMENT 'SHA-256 hash của Bearer Token',
    `scopes` VARCHAR(255) NOT NULL DEFAULT '*' COMMENT 'Phạm vi quyền: tra_cuu, thu_tuc, cong_dan, lanh_dao, webhook',
    `last_used_at` DATETIME NULL,
    `expires_at` DATETIME NOT NULL COMMENT 'Thời hạn hết hạn của token',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Hoạt động, 0: Vô hiệu hóa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token_hash` (`token_hash`),
    CONSTRAINT `fk_apitoken_user` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_apitoken_citizen` FOREIGN KEY (`cong_dan_id`) REFERENCES `cong_dan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý Bearer Token truy cập REST API /api/v1';

-- 2. Bảng Cấu hình Tích hợp (Quản lý 9 cổng kết nối §1.1, §2.3)
CREATE TABLE IF NOT EXISTS `cau_hinh_tich_hop` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_dich_vu` VARCHAR(50) NOT NULL UNIQUE COMMENT 'VNEID, LGSP, C06, CSDL_TTHC_QG, SMARTCA, SMS, ZALO_ZNS, VNPOST, CLAMAV',
    `ten_dich_vu` VARCHAR(255) NOT NULL,
    `nha_cung_cap` VARCHAR(150) NOT NULL,
    `endpoint_url` VARCHAR(500) NOT NULL,
    `che_do` ENUM('MOCK', 'LIVE') NOT NULL DEFAULT 'MOCK',
    `api_key` VARCHAR(255) NULL,
    `secret_key` VARCHAR(255) NULL,
    `thoi_gian_timeout_giay` INT NOT NULL DEFAULT 30,
    `trang_thai_ket_noi` ENUM('KET_NOI_TOT', 'LOI_KET_NOI', 'CHUA_KIEM_TRA') NOT NULL DEFAULT 'CHUA_KIEM_TRA',
    `lan_kiem_tra_cuoi` DATETIME NULL,
    `thong_tin_phan_hoi` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình tham số và trạng thái kết nối các dịch vụ ngoài';

-- 3. Bảng Ánh xạ Mã TTHC Cũ -> Mã Quốc Gia (ETL Transform)
CREATE TABLE IF NOT EXISTS `etl_anh_xa_tthc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_tthc_cu` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Mã thủ tục hệ thống Một cửa cũ',
    `ten_tthc_cu` VARCHAR(255) NOT NULL,
    `ma_tthc_moi` VARCHAR(100) NOT NULL COMMENT 'Mã TTHC Quốc gia mới',
    `thu_tuc_id` INT NULL COMMENT 'ID thủ tục chuẩn trong thu_tuc_hanh_chinh',
    `ghi_chu` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_map_tthc_cu` (`ma_tthc_cu`),
    CONSTRAINT `fk_etl_map_tthc` FOREIGN KEY (`thu_tuc_id`) REFERENCES `thu_tuc_hanh_chinh` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ánh xạ mã TTHC Một cửa cũ sang mã Quốc gia';

-- 4. Bảng Ánh xạ Mã ĐVHC Cũ -> Mã Tổng Cục Thống Kê (ETL Transform)
CREATE TABLE IF NOT EXISTS `etl_anh_xa_dvhc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_dvhc_cu` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Mã đơn vị Một cửa cũ',
    `ten_dvhc_cu` VARCHAR(255) NOT NULL,
    `ma_tctk` VARCHAR(50) NOT NULL COMMENT 'Mã ĐVHC chuẩn Tổng cục Thống kê (dm_dvhc)',
    `don_vi_id` INT NULL COMMENT 'ID đơn vị trong don_vi',
    `ghi_chu` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_map_dvhc_cu` (`ma_dvhc_cu`),
    CONSTRAINT `fk_etl_map_dvhc` FOREIGN KEY (`don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng ánh xạ đơn vị hành chính cũ sang mã TCTK và ID đơn vị chuẩn';
