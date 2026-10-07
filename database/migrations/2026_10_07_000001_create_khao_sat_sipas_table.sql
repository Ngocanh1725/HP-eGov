-- Migration: Tạo bảng khảo sát mức độ hài lòng SIPAS (Phiên 7)
-- Căn cứ: Quyết định 876/QĐ-BNV về đo lường mức độ hài lòng của người dân, tổ chức
CREATE TABLE IF NOT EXISTS `khao_sat_sipas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ho_so_id` int(11) NOT NULL,
  `cong_dan_id` int(11) NOT NULL,
  `diem_tiep_can` tinyint(4) NOT NULL DEFAULT 5 COMMENT 'Tiêu chí 1: Tiếp cận dịch vụ (1-5)',
  `diem_thu_tuc` tinyint(4) NOT NULL DEFAULT 5 COMMENT 'Tiêu chí 2: Thủ tục hành chính (1-5)',
  `diem_cong_chuc` tinyint(4) NOT NULL DEFAULT 5 COMMENT 'Tiêu chí 3: Tinh thần, trách nhiệm công chức (1-5)',
  `diem_ket_qua` tinyint(4) NOT NULL DEFAULT 5 COMMENT 'Tiêu chí 4: Tiến độ và kết quả dịch vụ (1-5)',
  `diem_phan_anh` tinyint(4) NOT NULL DEFAULT 5 COMMENT 'Tiêu chí 5: Tiếp nhận, xử lý phản ánh kiến nghị (1-5)',
  `diem_trung_binh` decimal(3,2) NOT NULL DEFAULT 5.00,
  `y_kien_dong_gop` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sipas_ho_so` (`ho_so_id`),
  KEY `idx_sipas_cong_dan` (`cong_dan_id`),
  CONSTRAINT `fk_sipas_ho_so` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khảo sát mức độ hài lòng SIPAS chuẩn QĐ 876/QĐ-BNV';
