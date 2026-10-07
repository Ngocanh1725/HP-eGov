-- ============================================================================
-- HỆ THỐNG THÔNG TIN QUẢN LÝ HÀNH CHÍNH & MỘT CỬA ĐIỆN TỬ LIÊN THÔNG TP. HẢI PHÒNG
-- TÊN HỆ THỐNG: HP-eGov
-- CSDL: MariaDB 10.4+ / MySQL 8.0+ (Quản trị qua phpMyAdmin)
-- BẢNG MÃ: utf8mb4 / COLLATE: utf8mb4_unicode_ci
-- BÁM SÁT: Báo cáo Kiến trúc & Kế hoạch Triển khai HP-eGov.pdf
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `hp_egov` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hp_egov`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. NHÓM BẢNG: TỔ CHỨC, ĐỊNH DANH & PHÂN QUYỀN RBAC ĐA CẤP (§4.1)
-- ----------------------------------------------------------------------------

-- 1.1 Danh mục đơn vị hành chính (Hỗ trợ cấu trúc thời gian chuyển đổi mô hình)
DROP TABLE IF EXISTS `dm_dvhc`;
CREATE TABLE `dm_dvhc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_dvhc` VARCHAR(10) NOT NULL UNIQUE COMMENT 'Mã Tổng cục Thống kê (ví dụ: 31, 303, 311...)',
    `ten_dvhc` VARCHAR(150) NOT NULL COMMENT 'Tên đơn vị hành chính',
    `cap_dvhc` ENUM('TINH', 'HUYEN', 'XA') NOT NULL COMMENT 'Cấp hành chính',
    `loai_dvhc` ENUM('THANH_PHO', 'QUAN', 'HUYEN', 'PHUONG', 'XA', 'THI_TRAN') NOT NULL COMMENT 'Loại hình ĐVHC',
    `parent_id` INT NULL COMMENT 'Đơn vị hành chính cấp trên',
    `hieu_luc_tu` DATE NOT NULL DEFAULT '2020-01-01' COMMENT 'Thời điểm bắt đầu có hiệu lực',
    `hieu_luc_den` DATE NULL COMMENT 'Thời điểm hết hiệu lực (phục vụ sắp xếp ĐVHC)',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Đang hoạt động, 0: Ngừng',
    INDEX `idx_dvhc_parent` (`parent_id`),
    INDEX `idx_dvhc_cap` (`cap_dvhc`),
    CONSTRAINT `fk_dvhc_parent` FOREIGN KEY (`parent_id`) REFERENCES `dm_dvhc` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục Đơn vị hành chính chuẩn Tổng cục Thống kê';

-- 1.2 Cây tổ chức cơ quan, đơn vị, phòng ban
DROP TABLE IF EXISTS `don_vi`;
CREATE TABLE `don_vi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_dinh_danh` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã định danh cơ quan chuẩn QCVN 102:2016/BTTTT',
    `ten_don_vi` VARCHAR(255) NOT NULL COMMENT 'Tên cơ quan, đơn vị, phòng ban',
    `ten_viet_tat` VARCHAR(50) NULL COMMENT 'Tên viết tắt (ví dụ: UBND_TP, SXD, UBND_HB)',
    `loai_don_vi` ENUM('UBND_TP', 'SO_NGANH', 'UBND_HUYEN', 'UBND_XA', 'PHONG_BAN', 'BO_PHAN_MOT_CUA') NOT NULL COMMENT 'Phân loại cơ quan/bộ phận',
    `parent_id` INT NULL COMMENT 'Đơn vị quản lý cấp trên trực tiếp',
    `dvhc_id` INT NULL COMMENT 'Địa bàn hành chính tương ứng',
    `path` VARCHAR(255) NOT NULL DEFAULT '/' COMMENT 'Materialized path dạng /1/5/12/ phục vụ Data Scope',
    `cap_do` INT NOT NULL DEFAULT 1 COMMENT 'Cấp bậc trong cây phân cấp tổ chức',
    `dia_chi` VARCHAR(255) NULL COMMENT 'Trụ sở cơ quan',
    `dien_thoai` VARCHAR(20) NULL COMMENT 'Số điện thoại liên hệ',
    `email` VARCHAR(100) NULL COMMENT 'Hộp thư điện tử công vụ',
    `thu_tu` INT NOT NULL DEFAULT 0 COMMENT 'Thứ tự hiển thị',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
    INDEX `idx_donvi_parent` (`parent_id`),
    INDEX `idx_donvi_dvhc` (`dvhc_id`),
    INDEX `idx_donvi_path` (`path`),
    CONSTRAINT `fk_donvi_parent` FOREIGN KEY (`parent_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_donvi_dvhc` FOREIGN KEY (`dvhc_id`) REFERENCES `dm_dvhc` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cây tổ chức cơ quan, đơn vị và phòng ban';

-- 1.3 Chức danh cán bộ, công chức
DROP TABLE IF EXISTS `chuc_danh`;
CREATE TABLE `chuc_danh` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_chuc_danh` VARCHAR(50) NOT NULL UNIQUE,
    `ten_chuc_danh` VARCHAR(100) NOT NULL,
    `mo_ta` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục chức danh, vị trí công tác';

-- 1.4 Tài khoản cán bộ, công chức
DROP TABLE IF EXISTS `nguoi_dung`;
CREATE TABLE `nguoi_dung` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ten_dang_nhap` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập hệ thống nội bộ',
    `mat_khau` VARCHAR(255) NOT NULL COMMENT 'Mã băm Bcrypt chuẩn cost 12',
    `ho_ten` VARCHAR(150) NOT NULL COMMENT 'Họ và tên cán bộ',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email công vụ',
    `so_dien_thoai` VARCHAR(20) NULL COMMENT 'Số điện thoại',
    `so_cccd` VARCHAR(20) NULL COMMENT 'Số thẻ CCCD',
    `chuc_danh_id` INT NULL COMMENT 'Chức danh công tác',
    `don_vi_chinh_id` INT NOT NULL COMMENT 'Đơn vị công tác chính',
    `so_lan_sai` INT NOT NULL DEFAULT 0 COMMENT 'Số lần đăng nhập sai liên tiếp',
    `khoa_den` DATETIME NULL COMMENT 'Thời điểm mở khóa tài khoản',
    `yeu_cau_doi_mk` TINYINT NOT NULL DEFAULT 0 COMMENT '1: Bắt buộc đổi mật khẩu lần đầu',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Đang hoạt động, 0: Bị khóa',
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT '1: Đã xóa logic',
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_donvi` (`don_vi_chinh_id`),
    INDEX `idx_user_chucdanh` (`chuc_danh_id`),
    CONSTRAINT `fk_user_donvi` FOREIGN KEY (`don_vi_chinh_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_user_chucdanh` FOREIGN KEY (`chuc_danh_id`) REFERENCES `chuc_danh` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tài khoản cán bộ, công chức vận hành hệ thống';

-- 1.5 Tài khoản công dân / doanh nghiệp (Định danh VNeID / Doanh nghiệp)
DROP TABLE IF EXISTS `cong_dan`;
CREATE TABLE `cong_dan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loai_chu_the` ENUM('CA_NHAN', 'TO_CHUC') NOT NULL DEFAULT 'CA_NHAN' COMMENT 'Loại chủ thể nộp hồ sơ',
    `so_cccd` VARCHAR(20) NULL UNIQUE COMMENT 'Số CCCD định danh từ VNeID',
    `ma_so_thue` VARCHAR(20) NULL UNIQUE COMMENT 'Mã số thuế doanh nghiệp/tổ chức',
    `vneid_sub` VARCHAR(100) NULL UNIQUE COMMENT 'Mã định danh OpenID Connect từ VNeID mức 2',
    `ho_ten` VARCHAR(150) NOT NULL COMMENT 'Họ và tên cá nhân / Tên người đại diện',
    `ngay_sinh` DATE NULL COMMENT 'Ngày sinh',
    `gioi_tinh` ENUM('NAM', 'NU', 'KHAC') NULL COMMENT 'Giới tính',
    `que_quan` VARCHAR(255) NULL COMMENT 'Quê quán',
    `dia_chi_thuong_tru` VARCHAR(255) NULL COMMENT 'Nơi thường trú theo C06',
    `dia_chi_hien_tai` VARCHAR(255) NULL COMMENT 'Nơi ở hiện nay',
    `email` VARCHAR(100) NULL COMMENT 'Hộp thư điện tử liên hệ',
    `so_dien_thoai` VARCHAR(20) NULL COMMENT 'Số điện thoại nhận tin nhắn SMS/Zalo',
    `ten_doanh_nghiep` VARCHAR(255) NULL COMMENT 'Tên doanh nghiệp nếu là tổ chức',
    `nguoi_dai_dien` VARCHAR(150) NULL COMMENT 'Người đại diện theo pháp luật',
    `mat_khau` VARCHAR(255) NULL COMMENT 'Mật khẩu nếu dùng tài khoản độc lập',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cd_cccd` (`so_cccd`),
    INDEX `idx_cd_mst` (`ma_so_thue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tài khoản định danh công dân và doanh nghiệp nộp TTHC';

-- 1.6 Danh mục 7 vai trò chuẩn (§4.1)
DROP TABLE IF EXISTS `vai_tro`;
CREATE TABLE `vai_tro` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_vai_tro` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã vai trò chuẩn Enum',
    `ten_vai_tro` VARCHAR(100) NOT NULL COMMENT 'Tên vai trò bằng tiếng Việt',
    `mo_ta` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục 7 vai trò nghiệp vụ chuẩn';

-- 1.7 Danh mục mã quyền thao tác (Permissions)
DROP TABLE IF EXISTS `quyen`;
CREATE TABLE `quyen` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_quyen` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Mã quyền dot-notation (ví dụ: hoso.tiep_nhan)',
    `ten_quyen` VARCHAR(150) NOT NULL COMMENT 'Tên quyền hạn',
    `nhom_quyen` VARCHAR(50) NOT NULL COMMENT 'Nhóm phân hệ',
    `mo_ta` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng từ điển các quyền thao tác trong hệ thống';

-- 1.8 Bảng liên kết Vai trò - Quyền hạn
DROP TABLE IF EXISTS `vai_tro_quyen`;
CREATE TABLE `vai_tro_quyen` (
    `vai_tro_id` INT NOT NULL,
    `quyen_id` INT NOT NULL,
    PRIMARY KEY (`vai_tro_id`, `quyen_id`),
    CONSTRAINT `fk_vtq_vaitro` FOREIGN KEY (`vai_tro_id`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vtq_quyen` FOREIGN KEY (`quyen_id`) REFERENCES `quyen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ma trận phân quyền chi tiết giữa Vai trò và Quyền';

-- 1.9 Bảng gán Người dùng - Vai trò theo Đơn vị (Hỗ trợ kiêm nhiệm & Data Scope)
DROP TABLE IF EXISTS `nguoi_dung_vai_tro`;
CREATE TABLE `nguoi_dung_vai_tro` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nguoi_dung_id` INT NOT NULL,
    `vai_tro_id` INT NOT NULL,
    `don_vi_id` INT NOT NULL COMMENT 'Đơn vị áp dụng vai trò này',
    `is_chinh` TINYINT NOT NULL DEFAULT 0 COMMENT '1: Vai trò chính mặc định',
    INDEX `idx_ndvt_user` (`nguoi_dung_id`),
    INDEX `idx_ndvt_vaitro` (`vai_tro_id`),
    INDEX `idx_ndvt_donvi` (`don_vi_id`),
    UNIQUE KEY `uk_user_role_donvi` (`nguoi_dung_id`, `vai_tro_id`, `don_vi_id`),
    CONSTRAINT `fk_ndvt_user` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ndvt_vaitro` FOREIGN KEY (`vai_tro_id`) REFERENCES `vai_tro` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ndvt_donvi` FOREIGN KEY (`don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gán vai trò cho cán bộ gắn liền với đơn vị công tác';


-- ----------------------------------------------------------------------------
-- 2. NHÓM BẢNG: DANH MỤC DÙNG CHUNG (MASTER DATA MANAGEMENT - MDM)
-- ----------------------------------------------------------------------------

-- 2.1 Danh mục Lĩnh vực TTHC
DROP TABLE IF EXISTS `dm_linh_vuc`;
CREATE TABLE `dm_linh_vuc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_linh_vuc` VARCHAR(50) NOT NULL UNIQUE,
    `ten_linh_vuc` VARCHAR(150) NOT NULL,
    `thu_tu` INT NOT NULL DEFAULT 0,
    `trang_thai` TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục Lĩnh vực TTHC';

-- 2.2 Danh mục Thủ tục hành chính (Đồng bộ CSDL TTHC Quốc gia)
DROP TABLE IF EXISTS `thu_tuc_hanh_chinh`;
CREATE TABLE `thu_tuc_hanh_chinh` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_tthc` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã TTHC quốc gia theo Cục KSTTHC',
    `ma_rut_gon` VARCHAR(4) NOT NULL COMMENT 'Mã rút gọn 4 số phục vụ sinh mã QCVN 102',
    `ten_tthc` VARCHAR(255) NOT NULL COMMENT 'Tên đầy đủ của thủ tục',
    `linh_vuc_id` INT NOT NULL COMMENT 'Thuộc lĩnh vực',
    `cap_thuc_hien` ENUM('TINH', 'HUYEN', 'XA') NOT NULL COMMENT 'Cấp thẩm quyền giải quyết',
    `muc_do_dvc` ENUM('MOT_PHAN', 'TOAN_TRINH') NOT NULL DEFAULT 'TOAN_TRINH' COMMENT 'Mức độ cung cấp DVC',
    `thoi_han_giai_quyet_ngay` INT NOT NULL COMMENT 'Thời hạn giải quyết (ngày làm việc)',
    `thoi_han_giai_quyet_gio` INT NOT NULL COMMENT 'Thời hạn quy đổi sang giờ làm việc (8h/ngày)',
    `phi_le_phi` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Phí, lệ phí quy định (VND)',
    `mo_ta_phi` VARCHAR(255) NULL,
    `can_cu_phap_ly` TEXT NULL COMMENT 'Văn bản quy phạm pháp luật quy định',
    `trang_thai_cong_bo` ENUM('CONG_BO', 'SUA_DOI', 'BAI_BO') NOT NULL DEFAULT 'CONG_BO',
    `ngay_cong_bo` DATE NULL,
    `ngay_dong_bo` DATETIME NULL COMMENT 'Lần đồng bộ gần nhất từ CSDL TTHC QG',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Đang áp dụng, 0: Hết hiệu lực',
    INDEX `idx_tthc_linhvuc` (`linh_vuc_id`),
    INDEX `idx_tthc_cap` (`cap_thuc_hien`),
    CONSTRAINT `fk_tthc_linhvuc` FOREIGN KEY (`linh_vuc_id`) REFERENCES `dm_linh_vuc` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục Thủ tục hành chính quốc gia và địa phương';

-- 2.3 Thành phần hồ sơ theo TTHC
DROP TABLE IF EXISTS `thanh_phan_ho_so`;
CREATE TABLE `thanh_phan_ho_so` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tthc_id` INT NOT NULL,
    `ma_giay_to` VARCHAR(50) NOT NULL COMMENT 'Mã định danh thành phần giấy tờ',
    `ten_giay_to` VARCHAR(255) NOT NULL COMMENT 'Tên giấy tờ yêu cầu nộp',
    `so_ban_chinh` INT NOT NULL DEFAULT 1,
    `so_ban_sao` INT NOT NULL DEFAULT 0,
    `bat_buoc` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Bắt buộc, 0: Tùy chọn',
    `loai_dinh_kem` VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
    `thu_tu` INT NOT NULL DEFAULT 1,
    INDEX `idx_tphs_tthc` (`tthc_id`),
    CONSTRAINT `fk_tphs_tthc` FOREIGN KEY (`tthc_id`) REFERENCES `thu_tuc_hanh_chinh` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách thành phần giấy tờ yêu cầu trong hồ sơ TTHC';

-- 2.4 Cấu trúc biểu mẫu điện tử E-Form JSON
DROP TABLE IF EXISTS `bieu_mau_eform`;
CREATE TABLE `bieu_mau_eform` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tthc_id` INT NOT NULL UNIQUE,
    `ten_bieu_mau` VARCHAR(255) NOT NULL,
    `phien_ban` VARCHAR(20) NOT NULL DEFAULT '1.0',
    `cau_truc_json` LONGTEXT NOT NULL COMMENT 'Cấu trúc schema JSON mô tả trường và quy tắc validate',
    `trang_thai` TINYINT NOT NULL DEFAULT 1,
    CONSTRAINT `fk_eform_tthc` FOREIGN KEY (`tthc_id`) REFERENCES `thu_tuc_hanh_chinh` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Định nghĩa biểu mẫu điện tử E-Form và trường auto-fill VNeID';

-- 2.5 Danh mục Trạng thái hồ sơ
DROP TABLE IF EXISTS `dm_trang_thai`;
CREATE TABLE `dm_trang_thai` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_trang_thai` VARCHAR(50) NOT NULL UNIQUE,
    `ten_trang_thai` VARCHAR(100) NOT NULL,
    `nhom_chuan_tt01` VARCHAR(50) NOT NULL COMMENT 'Quy về 6 nhóm chuẩn Thông tư 01/2018',
    `mau_badge` VARCHAR(20) NOT NULL DEFAULT 'primary',
    `thu_tu` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục trạng thái hồ sơ hành chính';

-- 2.6 Danh mục ngày nghỉ lễ theo Bộ luật Lao động (phục vụ tính SLA)
DROP TABLE IF EXISTS `dm_ngay_nghi`;
CREATE TABLE `dm_ngay_nghi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ngay` DATE NOT NULL UNIQUE COMMENT 'Ngày dương lịch',
    `nam` INT NOT NULL,
    `ten_ngay_nghi` VARCHAR(150) NOT NULL,
    `loai` ENUM('LE_QUOC_GIA', 'NGHI_BU', 'NGHI_CUOI_TUAN') NOT NULL DEFAULT 'LE_QUOC_GIA',
    `ghi_chu` VARCHAR(255) NULL,
    INDEX `idx_nghi_nam` (`nam`),
    INDEX `idx_nghi_ngay` (`ngay`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch ngày nghỉ lễ và nghỉ bù để trừ khi tính SLA';

-- 2.7 Cấu hình tham số hệ thống tập trung
DROP TABLE IF EXISTS `cau_hinh_he_thong`;
CREATE TABLE `cau_hinh_he_thong` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `khoa` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Tên khóa cấu hình',
    `gia_tri` TEXT NOT NULL COMMENT 'Giá trị cấu hình',
    `kieu_du_lieu` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT 'string, int, boolean, json',
    `nhom` VARCHAR(50) NOT NULL DEFAULT 'CHUNG',
    `mo_ta` VARCHAR(255) NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình tham số vận hành hệ thống tập trung';


-- ----------------------------------------------------------------------------
-- 3. NHÓM BẢNG: QUẢN TRỊ QUY TRÌNH LUÂN CHUYỂN NGHIỆP VỤ (BPMN-LITE)
-- ----------------------------------------------------------------------------

-- 3.1 Quy trình nghiệp vụ theo TTHC
DROP TABLE IF EXISTS `quy_trinh`;
CREATE TABLE `quy_trinh` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tthc_id` INT NOT NULL,
    `ten_quy_trinh` VARCHAR(255) NOT NULL,
    `phien_ban` INT NOT NULL DEFAULT 1,
    `tong_thoi_han_gio` INT NOT NULL COMMENT 'Tổng số giờ làm việc chuẩn',
    `is_active` TINYINT NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_qt_tthc` (`tthc_id`),
    CONSTRAINT `fk_qt_tthc` FOREIGN KEY (`tthc_id`) REFERENCES `thu_tuc_hanh_chinh` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quy trình giải quyết TTHC';

-- 3.2 Các bước trong quy trình luân chuyển
DROP TABLE IF EXISTS `quy_trinh_buoc`;
CREATE TABLE `quy_trinh_buoc` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quy_trinh_id` INT NOT NULL,
    `stt_buoc` INT NOT NULL COMMENT 'Thứ tự bước 1, 2, 3...',
    `ten_buoc` VARCHAR(150) NOT NULL,
    `loai_buoc` ENUM('TIEP_NHAN', 'PHAN_CONG', 'THU_LY', 'LAY_Y_KIEN', 'THAM_TRA', 'PHE_DUYET', 'DONG_DAU', 'TRA_KET_QUA') NOT NULL,
    `vai_tro_xu_ly` VARCHAR(50) NOT NULL COMMENT 'Mã vai trò phụ trách bước này',
    `don_vi_xu_ly_id` INT NULL COMMENT 'Nếu bước thuộc đơn vị liên thông khác thì chỉ định, NULL là cùng đơn vị',
    `thoi_han_gio` INT NOT NULL COMMENT 'Thời hạn tối đa của bước này (giờ làm việc)',
    `cho_phep_tra_lai` TINYINT NOT NULL DEFAULT 1,
    `ghi_chu` VARCHAR(255) NULL,
    INDEX `idx_qtb_qt` (`quy_trinh_id`),
    CONSTRAINT `fk_qtb_qt` FOREIGN KEY (`quy_trinh_id`) REFERENCES `quy_trinh` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qtb_donvi` FOREIGN KEY (`don_vi_xu_ly_id`) REFERENCES `don_vi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Định nghĩa từng bước trong quy trình';

-- 3.3 Chuyển tiếp giữa các bước
DROP TABLE IF EXISTS `quy_trinh_chuyen_tiep`;
CREATE TABLE `quy_trinh_chuyen_tiep` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quy_trinh_id` INT NOT NULL,
    `tu_buoc_id` INT NOT NULL,
    `den_buoc_id` INT NOT NULL,
    `hanh_dong` VARCHAR(50) NOT NULL COMMENT 'CHUYEN_TIEP, TRA_LAI, BO_SUNG, TU_CHOI',
    `dieu_kien` VARCHAR(255) NULL COMMENT 'Điều kiện rẽ nhánh nếu có',
    INDEX `idx_qtct_qt` (`quy_trinh_id`),
    CONSTRAINT `fk_qtct_qt` FOREIGN KEY (`quy_trinh_id`) REFERENCES `quy_trinh` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qtct_tu` FOREIGN KEY (`tu_buoc_id`) REFERENCES `quy_trinh_buoc` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qtct_den` FOREIGN KEY (`den_buoc_id`) REFERENCES `quy_trinh_buoc` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cấu hình chuyển tiếp và rẽ nhánh giữa các bước';


-- ----------------------------------------------------------------------------
-- 4. NHÓM BẢNG: QUẢN LÝ HỒ SƠ & LUÂN CHUYỂN NGHIỆP VỤ THỰC TẾ
-- ----------------------------------------------------------------------------

-- 4.1 Bảng bộ đếm mã hồ sơ (Khóa transaction cấp mã không trùng lặp QCVN 102)
DROP TABLE IF EXISTS `bo_dem_ma_ho_so`;
CREATE TABLE `bo_dem_ma_ho_so` (
    `don_vi_id` INT NOT NULL,
    `nam` INT NOT NULL,
    `so_hien_tai` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`don_vi_id`, `nam`),
    CONSTRAINT `fk_bodem_donvi` FOREIGN KEY (`don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bộ đếm số tự tăng sinh mã hồ sơ theo đơn vị và theo năm';

-- 4.2 Bảng hồ sơ thủ tục hành chính cốt lõi
DROP TABLE IF EXISTS `ho_so`;
CREATE TABLE `ho_so` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ma_ho_so` VARCHAR(60) NOT NULL UNIQUE COMMENT 'Mã QCVN 102:2016 (MãĐV-yyMMdd-MãTTHC-STT)',
    `tthc_id` INT NOT NULL,
    `quy_trinh_id` INT NOT NULL,
    `buoc_hien_tai_id` INT NULL COMMENT 'Bước đang dừng trong quy trình',
    `don_vi_tiep_nhan_id` INT NOT NULL COMMENT 'Đơn vị khởi tạo tiếp nhận',
    `don_vi_hien_tai_id` INT NOT NULL COMMENT 'Đơn vị đang thụ lý xử lý hồ sơ (phục vụ liên thông)',
    `chuyen_vien_thu_ly_id` INT NULL COMMENT 'Chuyên viên được phân công thụ lý chính',
    `cong_dan_id` INT NOT NULL COMMENT 'Công dân/doanh nghiệp nộp hồ sơ',
    `kenh_nop` ENUM('TRUC_TUYEN', 'TRUC_TIEP') NOT NULL DEFAULT 'TRUC_TUYEN',
    `nguoi_nop_ho_ten` VARCHAR(150) NOT NULL,
    `nguoi_nop_cccd` VARCHAR(20) NULL,
    `nguoi_nop_sdt` VARCHAR(20) NULL,
    `nguoi_nop_email` VARCHAR(100) NULL,
    `nguoi_nop_dia_chi` VARCHAR(255) NULL,
    `du_lieu_eform` LONGTEXT NOT NULL COMMENT 'Dữ liệu kê khai điện tử (JSON)',
    `trang_thai` VARCHAR(50) NOT NULL DEFAULT 'CHO_TIEP_NHAN' COMMENT 'Theo Enum TrangThaiHoSo',
    `ngay_nop` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ngay_tiep_nhan` DATETIME NULL COMMENT 'Thời điểm Một cửa tiếp nhận chính thức',
    `ngay_hen_tra` DATETIME NULL COMMENT 'Thời hạn cam kết trả kết quả theo SLA',
    `ngay_hoan_thanh` DATETIME NULL COMMENT 'Thời điểm Lãnh đạo ký duyệt & đóng dấu số',
    `ngay_tra_ket_qua` DATETIME NULL COMMENT 'Thời điểm công dân nhận kết quả thực tế',
    `sla_mau` ENUM('XANH', 'VANG', 'DO') NOT NULL DEFAULT 'XANH' COMMENT 'Trạng thái kiểm soát tiến độ SLA',
    `tong_thoi_han_gio` INT NOT NULL COMMENT 'Tổng số giờ làm việc quy định',
    `thoi_gian_da_dung_gio` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Số giờ làm việc thực tế đã trôi qua',
    `so_gio_tam_dung` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Số giờ tạm dừng chờ công dân bổ sung',
    `hinh_thuc_nhan_kq` ENUM('TRUC_TIEP', 'BUU_DIEN', 'DIEN_TU') NOT NULL DEFAULT 'DIEN_TU',
    `ma_van_don_vnpost` VARCHAR(50) NULL COMMENT 'Mã bưu gửi nếu chuyển phát bưu điện',
    `dia_chi_nhan_kq` VARCHAR(255) NULL,
    `is_deleted` TINYINT NOT NULL DEFAULT 0 COMMENT 'Nguyên tắc bất biến: 1 là hủy logic',
    `deleted_at` DATETIME NULL,
    `can_cu_huy` TEXT NULL COMMENT 'Căn cứ pháp lý khi hủy hồ sơ',
    `bien_ban_giai_trinh` TEXT NULL COMMENT 'Biên bản giải trình hủy hồ sơ',
    `nguoi_duyet_huy_id` INT NULL COMMENT 'Lãnh đạo cơ quan phê duyệt hủy hồ sơ',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hs_mahoso` (`ma_ho_so`),
    INDEX `idx_hs_tthc` (`tthc_id`),
    INDEX `idx_hs_donvi_tn` (`don_vi_tiep_nhan_id`),
    INDEX `idx_hs_donvi_ht` (`don_vi_hien_tai_id`),
    INDEX `idx_hs_chuyenvien` (`chuyen_vien_thu_ly_id`),
    INDEX `idx_hs_congdan` (`cong_dan_id`),
    INDEX `idx_hs_trangthai` (`trang_thai`),
    INDEX `idx_hs_sla` (`sla_mau`),
    INDEX `idx_hs_is_deleted` (`is_deleted`),
    INDEX `idx_hs_ngay_nop` (`ngay_nop`),
    CONSTRAINT `fk_hs_tthc` FOREIGN KEY (`tthc_id`) REFERENCES `thu_tuc_hanh_chinh` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hs_quytrinh` FOREIGN KEY (`quy_trinh_id`) REFERENCES `quy_trinh` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hs_buoc` FOREIGN KEY (`buoc_hien_tai_id`) REFERENCES `quy_trinh_buoc` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hs_donvi_tn` FOREIGN KEY (`don_vi_tiep_nhan_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hs_donvi_ht` FOREIGN KEY (`don_vi_hien_tai_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hs_chuyenvien` FOREIGN KEY (`chuyen_vien_thu_ly_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hs_congdan` FOREIGN KEY (`cong_dan_id`) REFERENCES `cong_dan` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hs_nguoiduyethuy` FOREIGN KEY (`nguoi_duyet_huy_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng hồ sơ thủ tục hành chính cốt lõi';

-- 4.3 Tệp tin đính kèm và tài liệu số hóa (§1.4 NĐ 107/2021/NĐ-CP)
DROP TABLE IF EXISTS `ho_so_tai_lieu`;
CREATE TABLE `ho_so_tai_lieu` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL,
    `thanh_phan_id` INT NULL COMMENT 'Thành phần giấy tờ theo danh mục TTHC',
    `ten_goc` VARCHAR(255) NOT NULL COMMENT 'Tên tệp tin lúc tải lên',
    `ten_luu` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Tên tệp ngẫu nhiên UUIDv4 lưu trong storage',
    `duong_dan` VARCHAR(500) NOT NULL COMMENT 'Đường dẫn vật lý trong storage/uploads',
    `mime_type` VARCHAR(100) NOT NULL,
    `kich_thuoc_byte` BIGINT NOT NULL,
    `sha256` VARCHAR(64) NOT NULL COMMENT 'Mã băm SHA-256 đối soát toàn vẹn',
    `loai_tai_lieu` ENUM('DAU_VAO', 'SO_HOA', 'DU_THAO', 'KET_QUA', 'BO_SUNG') NOT NULL DEFAULT 'DAU_VAO',
    `so_hoa_boi_id` INT NULL COMMENT 'Cán bộ Một cửa thực hiện số hóa',
    `ngay_so_hoa` DATETIME NULL,
    `chu_ky_so_id` INT NULL COMMENT 'Chữ ký số xác thực số hóa nếu có',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_hstl_hoso` (`ho_so_id`),
    INDEX `idx_hstl_tp` (`thanh_phan_id`),
    INDEX `idx_hstl_sha256` (`sha256`),
    CONSTRAINT `fk_hstl_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hstl_tp` FOREIGN KEY (`thanh_phan_id`) REFERENCES `thanh_phan_ho_so` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hstl_user` FOREIGN KEY (`so_hoa_boi_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tệp tin đính kèm và tài liệu số hóa đầu vào/kết quả';

-- 4.4 Lịch sử luân chuyển hồ sơ giữa các bước và các cơ quan
DROP TABLE IF EXISTS `ho_so_luan_chuyen`;
CREATE TABLE `ho_so_luan_chuyen` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL,
    `tu_buoc_id` INT NULL,
    `den_buoc_id` INT NULL,
    `tu_don_vi_id` INT NOT NULL,
    `den_don_vi_id` INT NOT NULL,
    `nguoi_chuyen_id` INT NOT NULL,
    `nguoi_nhan_id` INT NULL,
    `hanh_dong` VARCHAR(50) NOT NULL COMMENT 'TIEP_NHAN, PHAN_CONG, TRINH_DUYET, TRA_LAI, KY_SO, DONG_DAU, TRA_KQ',
    `trang_thai_truoc` VARCHAR(50) NOT NULL,
    `trang_thai_sau` VARCHAR(50) NOT NULL,
    `noi_dung_y_kien` TEXT NULL COMMENT 'Ý kiến chỉ đạo, nhận xét, căn cứ',
    `thoi_gian_chuyen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `han_xu_ly_buoc` DATETIME NULL,
    INDEX `idx_hslc_hoso` (`ho_so_id`),
    CONSTRAINT `fk_hslc_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hslc_tub` FOREIGN KEY (`tu_buoc_id`) REFERENCES `quy_trinh_buoc` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hslc_denb` FOREIGN KEY (`den_buoc_id`) REFERENCES `quy_trinh_buoc` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_hslc_tudv` FOREIGN KEY (`tu_don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hslc_dendv` FOREIGN KEY (`den_don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hslc_nguoic` FOREIGN KEY (`nguoi_chuyen_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hslc_nguoin` FOREIGN KEY (`nguoi_nhan_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký luân chuyển hồ sơ toàn trình';

-- 4.5 Lấy ý kiến phối hợp liên phòng / liên sở (Liên thông ngang)
DROP TABLE IF EXISTS `ho_so_y_kien_phoi_hop`;
CREATE TABLE `ho_so_y_kien_phoi_hop` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL,
    `don_vi_yeu_cau_id` INT NOT NULL,
    `nguoi_yeu_cau_id` INT NOT NULL,
    `don_vi_phoi_hop_id` INT NOT NULL,
    `nguoi_tra_loi_id` INT NULL,
    `noi_dung_yeu_cau` TEXT NOT NULL,
    `han_tra_loi` DATETIME NOT NULL,
    `noi_dung_tra_loi` TEXT NULL,
    `ngay_tra_loi` DATETIME NULL,
    `trang_thai` ENUM('CHO_Y_KIEN', 'DA_TRA_LOI') NOT NULL DEFAULT 'CHO_Y_KIEN',
    INDEX `idx_ykph_hoso` (`ho_so_id`),
    CONSTRAINT `fk_ykph_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ykph_dvyc` FOREIGN KEY (`don_vi_yeu_cau_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ykph_dvph` FOREIGN KEY (`don_vi_phoi_hop_id`) REFERENCES `don_vi` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ykph_nguoiyc` FOREIGN KEY (`nguoi_yeu_cau_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ykph_nguoitl` FOREIGN KEY (`nguoi_tra_loi_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yêu cầu và phản hồi ý kiến phối hợp liên ngành';

-- 4.6 Yêu cầu bổ sung hồ sơ (Tạm dừng tính SLA)
DROP TABLE IF EXISTS `ho_so_bo_sung`;
CREATE TABLE `ho_so_bo_sung` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL,
    `nguoi_yeu_cau_id` INT NOT NULL,
    `ly_do_bo_sung` TEXT NOT NULL,
    `danh_sach_giay_to_yeu_cau` TEXT NOT NULL,
    `han_bo_sung` DATETIME NOT NULL,
    `ngay_yeu_cau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ngay_nop_bo_sung` DATETIME NULL,
    `ghi_chu_cong_dan` TEXT NULL,
    `trang_thai` ENUM('CHO_BO_SUNG', 'DA_BO_SUNG') NOT NULL DEFAULT 'CHO_BO_SUNG',
    INDEX `idx_hsbs_hoso` (`ho_so_id`),
    CONSTRAINT `fk_hsbs_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_hsbs_user` FOREIGN KEY (`nguoi_yeu_cau_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử yêu cầu sửa đổi, bổ sung hồ sơ';

-- 4.7 Văn bản xin lỗi và hẹn lại ngày trả kết quả (Mẫu số 04 TT 01/2018/TT-VPCP)
DROP TABLE IF EXISTS `van_ban_xin_loi`;
CREATE TABLE `van_ban_xin_loi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL UNIQUE,
    `so_van_ban` VARCHAR(50) NOT NULL COMMENT 'Số ký hiệu văn bản xin lỗi',
    `nguoi_ky_id` INT NOT NULL COMMENT 'Lãnh đạo cơ quan ký số',
    `ly_do_qua_han` TEXT NOT NULL COMMENT 'Nguyên nhân quá hạn giải quyết',
    `ngay_hen_moi` DATETIME NOT NULL COMMENT 'Ngày hẹn trả kết quả mới',
    `ngay_ky` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `file_dinh_kem_id` INT NULL,
    `trang_thai` ENUM('DU_THAO', 'DA_KY') NOT NULL DEFAULT 'DA_KY',
    CONSTRAINT `fk_vbxl_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_vbxl_user` FOREIGN KEY (`nguoi_ky_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_vbxl_file` FOREIGN KEY (`file_dinh_kem_id`) REFERENCES `ho_so_tai_lieu` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Văn bản xin lỗi công dân khi trễ hạn TTHC theo Mẫu 04';

-- 4.8 Kết quả giải quyết thủ tục hành chính
DROP TABLE IF EXISTS `ket_qua_giai_quyet`;
CREATE TABLE `ket_qua_giai_quyet` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL UNIQUE,
    `so_quyet_dinh` VARCHAR(100) NOT NULL COMMENT 'Số giấy phép, quyết định, chứng chỉ',
    `trich_yeu` VARCHAR(500) NOT NULL COMMENT 'Trích yếu nội dung kết quả',
    `nguoi_ky_chinh_id` INT NOT NULL COMMENT 'Lãnh đạo cơ quan ký ban hành',
    `ngay_ky` DATETIME NOT NULL,
    `ngay_hieu_luc` DATE NULL,
    `ngay_het_han` DATE NULL,
    `file_ket_qua_id` INT NULL COMMENT 'Tệp tin kết quả PDF chính thức',
    `file_ban_in_html` LONGTEXT NULL COMMENT 'Bản in thể thức hành chính A4',
    `ma_tra_cuu_ket_qua` VARCHAR(50) NOT NULL UNIQUE,
    `trang_thai` TINYINT NOT NULL DEFAULT 1,
    CONSTRAINT `fk_kq_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kq_nguoiky` FOREIGN KEY (`nguoi_ky_chinh_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kq_file` FOREIGN KEY (`file_ket_qua_id`) REFERENCES `ho_so_tai_lieu` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kết quả giải quyết TTHC chính thức';

-- 4.9 Đánh giá sự hài lòng của công dân (Chỉ số SIPAS)
DROP TABLE IF EXISTS `danh_gia_hai_long`;
CREATE TABLE `danh_gia_hai_long` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NOT NULL UNIQUE,
    `cong_dan_id` INT NOT NULL,
    `diem_tiep_can` TINYINT NOT NULL COMMENT 'Thang điểm 1-5: Tiếp cận dịch vụ',
    `diem_thu_tuc` TINYINT NOT NULL COMMENT 'Thang điểm 1-5: Thủ tục rõ ràng',
    `diem_cong_chuc` TINYINT NOT NULL COMMENT 'Thang điểm 1-5: Thái độ công chức',
    `diem_ket_qua` TINYINT NOT NULL COMMENT 'Thang điểm 1-5: Kết quả giải quyết',
    `diem_phan_anh` TINYINT NOT NULL COMMENT 'Thang điểm 1-5: Tiếp nhận phản ánh kiến nghị',
    `diem_trung_binh` DECIMAL(3,1) NOT NULL,
    `y_kien_dong_gop` TEXT NULL,
    `ngay_danh_gia` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_dghl_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_dghl_congdan` FOREIGN KEY (`cong_dan_id`) REFERENCES `cong_dan` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Khảo sát mức độ hài lòng của công dân (SIPAS)';

-- 4.10 Kho dữ liệu điện tử cá nhân / tổ chức (Tái sử dụng giấy tờ)
DROP TABLE IF EXISTS `kho_du_lieu_cong_dan`;
CREATE TABLE `kho_du_lieu_cong_dan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cong_dan_id` INT NOT NULL,
    `ho_so_id` INT NULL COMMENT 'Hồ sơ đã cấp giấy tờ này',
    `ten_tai_lieu` VARCHAR(255) NOT NULL,
    `ma_loai_giay_to` VARCHAR(100) NOT NULL,
    `duong_dan` VARCHAR(500) NOT NULL,
    `sha256` VARCHAR(64) NOT NULL,
    `ngay_so_hoa` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ngay_het_han` DATE NULL,
    `nguon_cap` VARCHAR(100) NOT NULL DEFAULT 'HP_EGOV',
    `trang_thai` TINYINT NOT NULL DEFAULT 1,
    INDEX `idx_kho_congdan` (`cong_dan_id`),
    CONSTRAINT `fk_kho_congdan` FOREIGN KEY (`cong_dan_id`) REFERENCES `cong_dan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kho tài liệu số hóa của cá nhân, doanh nghiệp để tái sử dụng';


-- ----------------------------------------------------------------------------
-- 5. NHÓM BẢNG: KÝ SỐ ĐIỆN TỬ & VĂN THƯ SỐ (§1.4, §2.1)
-- ----------------------------------------------------------------------------

-- 5.1 Chứng thư số (Demo OpenSSL & SmartCA/USB Token/Ban Cơ yếu)
DROP TABLE IF EXISTS `chung_thu_so`;
CREATE TABLE `chung_thu_so` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `chu_the_id` INT NOT NULL COMMENT 'ID người dùng nếu cá nhân, ID đơn vị nếu tổ chức',
    `loai_chu_the` ENUM('CA_NHAN', 'TO_CHUC') NOT NULL,
    `ten_chu_the` VARCHAR(255) NOT NULL,
    `so_serial` VARCHAR(100) NOT NULL UNIQUE,
    `nha_cung_cap` ENUM('SMARTCA', 'USB_TOKEN', 'BAN_CO_YEU') NOT NULL,
    `ngay_hieu_luc` DATETIME NOT NULL,
    `ngay_het_han` DATETIME NOT NULL,
    `public_cert_pem` TEXT NOT NULL COMMENT 'Nội dung chứng thư công khai PEM',
    `trang_thai` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Hợp lệ, 0: Thu hồi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý chứng thư số của cán bộ và cơ quan';

-- 5.2 Nhật ký chữ ký số trên các tài liệu
DROP TABLE IF EXISTS `chu_ky_so`;
CREATE TABLE `chu_ky_so` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `chung_thu_so_id` INT NOT NULL,
    `ho_so_tai_lieu_id` INT NOT NULL,
    `loai_chu_ky` ENUM('SO_HOA', 'DUYET_BUOC_1', 'PHAT_HANH', 'DONG_DAU_CO_QUAN') NOT NULL,
    `nguoi_ky_id` INT NOT NULL,
    `sha256_truoc_ky` VARCHAR(64) NOT NULL COMMENT 'Băm SHA-256 của tài liệu trước khi ký',
    `chu_ky_binary_base64` TEXT NOT NULL COMMENT 'Chuỗi chữ ký số OpenSSL RSA-SHA256 Base64',
    `thoi_gian_ky` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ket_qua_xac_thuc` TINYINT NOT NULL DEFAULT 1 COMMENT '1: Hợp lệ, 0: Sai lệch',
    `ghi_chu` VARCHAR(255) NULL,
    INDEX `idx_cks_tailieu` (`ho_so_tai_lieu_id`),
    CONSTRAINT `fk_cks_cert` FOREIGN KEY (`chung_thu_so_id`) REFERENCES `chung_thu_so` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cks_tailieu` FOREIGN KEY (`ho_so_tai_lieu_id`) REFERENCES `ho_so_tai_lieu` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cks_user` FOREIGN KEY (`nguoi_ky_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký chi tiết các chữ ký số áp dụng lên văn bản';

-- 5.3 Sổ văn bản điện tử (Văn thư số)
DROP TABLE IF EXISTS `so_van_ban`;
CREATE TABLE `so_van_ban` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `don_vi_id` INT NOT NULL,
    `nam` INT NOT NULL,
    `ten_so` VARCHAR(100) NOT NULL,
    `loai_so` ENUM('DI', 'DEN') NOT NULL DEFAULT 'DI',
    `so_hien_tai` INT NOT NULL DEFAULT 0,
    `ky_hieu_mau` VARCHAR(50) NOT NULL COMMENT 'Ví dụ: /QĐ-UBND, /GP-SXD',
    `trang_thai` TINYINT NOT NULL DEFAULT 1,
    UNIQUE KEY `uk_so_donvi_nam` (`don_vi_id`, `nam`, `ten_so`),
    CONSTRAINT `fk_svb_donvi` FOREIGN KEY (`don_vi_id`) REFERENCES `don_vi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý sổ văn bản đi và đến của cơ quan';

-- 5.4 Sổ theo dõi văn bản đến / đi
DROP TABLE IF EXISTS `van_ban_den_di`;
CREATE TABLE `van_ban_den_di` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `so_van_ban_id` INT NOT NULL,
    `ho_so_id` INT NULL COMMENT 'Liên kết tới hồ sơ nếu có',
    `so_thu_tu` INT NOT NULL,
    `so_ky_hieu` VARCHAR(100) NOT NULL,
    `ngay_ban_hanh` DATE NOT NULL,
    `trich_yeu` TEXT NOT NULL,
    `co_quan_ban_hanh` VARCHAR(255) NOT NULL,
    `nguoi_ky` VARCHAR(100) NULL,
    `ngay_vao_so` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `file_dinh_kem_id` INT NULL,
    INDEX `idx_vb_so` (`so_van_ban_id`),
    CONSTRAINT `fk_vb_so` FOREIGN KEY (`so_van_ban_id`) REFERENCES `so_van_ban` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_vb_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_vb_file` FOREIGN KEY (`file_dinh_kem_id`) REFERENCES `ho_so_tai_lieu` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết văn bản đi/đến trong sổ văn thư số';


-- ----------------------------------------------------------------------------
-- 6. NHÓM BẢNG: TRUYỀN THÔNG & THÔNG BÁO ĐA KÊNH (§2.2)
-- ----------------------------------------------------------------------------

-- 6.1 Thông báo giao diện (In-app Notifications)
DROP TABLE IF EXISTS `thong_bao`;
CREATE TABLE `thong_bao` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nguoi_nhan_id` INT NULL COMMENT 'Nếu là cán bộ nội bộ',
    `cong_dan_id` INT NULL COMMENT 'Nếu là công dân',
    `tieu_de` VARCHAR(255) NOT NULL,
    `noi_dung` TEXT NOT NULL,
    `lien_ket` VARCHAR(255) NULL,
    `loai` VARCHAR(50) NOT NULL DEFAULT 'HE_THONG',
    `da_doc` TINYINT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tb_user` (`nguoi_nhan_id`),
    INDEX `idx_tb_cd` (`cong_dan_id`),
    INDEX `idx_tb_doc` (`da_doc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thông báo in-app cho cán bộ và công dân';

-- 6.2 Hàng đợi thông báo ngoại vi (Outbox Pattern: SMS Brandname & Zalo ZNS)
DROP TABLE IF EXISTS `hang_doi_thong_bao`;
CREATE TABLE `hang_doi_thong_bao` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ho_so_id` INT NULL,
    `kenh` ENUM('SMS', 'ZALO', 'EMAIL') NOT NULL,
    `nguoi_nhan` VARCHAR(100) NOT NULL COMMENT 'Số điện thoại hoặc Email',
    `tieu_de` VARCHAR(255) NULL,
    `noi_dung` TEXT NOT NULL,
    `trang_thai` ENUM('CHO_GUI', 'DANG_GUI', 'THANH_CONG', 'THAT_BAI') NOT NULL DEFAULT 'CHO_GUI',
    `so_lan_thu` INT NOT NULL DEFAULT 0,
    `phan_hoi_gateway` TEXT NULL,
    `thoi_gian_tao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `thoi_gian_gui` DATETIME NULL,
    INDEX `idx_hdtb_status` (`trang_thai`),
    CONSTRAINT `fk_hdtb_hoso` FOREIGN KEY (`ho_so_id`) REFERENCES `ho_so` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hàng đợi tin nhắn SMS Brandname và Zalo ZNS';

-- 6.3 Nhật ký Webhook gửi ra ngoài
DROP TABLE IF EXISTS `webhook_log`;
CREATE TABLE `webhook_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `su_kien` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `url_dich` VARCHAR(500) NOT NULL,
    `status_code` INT NULL,
    `thoi_gian_goi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký gửi webhook đối tác';


-- ----------------------------------------------------------------------------
-- 7. NHÓM BẢNG: AN TOÀN THÔNG TIN, KIỂM TOÁN WORM & QUẢN TRỊ HỆ THỐNG (§3.3, §4.3)
-- ----------------------------------------------------------------------------

-- 7.1 Nhật ký kiểm toán bất biến (Audit Trail WORM với chuỗi băm Hash Chain)
DROP TABLE IF EXISTS `nhat_ky_kiem_toan`;
CREATE TABLE `nhat_ky_kiem_toan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nguoi_dung_id` INT NULL,
    `cong_dan_id` INT NULL,
    `hanh_dong` VARCHAR(100) NOT NULL COMMENT 'INSERT, UPDATE, DELETE, VIEW, SIGN, APPROVE...',
    `bang_tac_dong` VARCHAR(100) NOT NULL,
    `ban_ghi_id` INT NULL,
    `du_lieu_cu` LONGTEXT NULL COMMENT 'Dữ liệu trước khi sửa (JSON)',
    `du_lieu_moi` LONGTEXT NULL COMMENT 'Dữ liệu sau khi sửa (JSON)',
    `dia_chi_ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `prev_hash` VARCHAR(64) NOT NULL COMMENT 'Băm của bản ghi trước đó trong chuỗi',
    `hash` VARCHAR(64) NOT NULL COMMENT 'SHA256(prev_hash + data)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`nguoi_dung_id`),
    INDEX `idx_audit_bang` (`bang_tac_dong`, `ban_ghi_id`),
    INDEX `idx_audit_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký kiểm toán an ninh WORM phát hiện can thiệp CSDL';

-- 7.2 Nhật ký đăng nhập & Chống Brute-force
DROP TABLE IF EXISTS `nhat_ky_dang_nhap`;
CREATE TABLE `nhat_ky_dang_nhap` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ten_dang_nhap` VARCHAR(100) NOT NULL,
    `vai_tro` VARCHAR(50) NULL,
    `dia_chi_ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `trang_thai` ENUM('THANH_CONG', 'THAT_BAI', 'KHOA') NOT NULL,
    `ly_do` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_nkdn_user` (`ten_dang_nhap`),
    INDEX `idx_nkdn_ip` (`dia_chi_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử đăng nhập và phát hiện tấn công vét cạn';

-- 7.3 Nhật ký tích hợp trục LGSP, NDXP, C06
DROP TABLE IF EXISTS `nhat_ky_tich_hop`;
CREATE TABLE `nhat_ky_tich_hop` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `dich_vu` VARCHAR(100) NOT NULL COMMENT 'VNEID, LGSP_C06, CSDL_TTHC, SMS, ZALO, VNPOST',
    `endpoint` VARCHAR(255) NOT NULL,
    `method` VARCHAR(10) NOT NULL DEFAULT 'POST',
    `request_payload` LONGTEXT NULL,
    `response_payload` LONGTEXT NULL,
    `status_code` INT NULL,
    `thoi_gian_xu_ly_ms` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_nkth_dv` (`dich_vu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký gọi dịch vụ ngoài qua trục LGSP/NDXP';

-- 7.4 Giới hạn tần suất truy cập (Rate Limiting <= 60 req/phút/IP)
DROP TABLE IF EXISTS `gioi_han_truy_cap`;
CREATE TABLE `gioi_han_truy_cap` (
    `ip_address` VARCHAR(45) NOT NULL,
    `endpoint` VARCHAR(100) NOT NULL,
    `so_request` INT NOT NULL DEFAULT 1,
    `thoi_gian_bat_dau` INT NOT NULL,
    PRIMARY KEY (`ip_address`, `endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý rate limit chống tấn công Layer 7';

-- 7.5 Danh sách IP bị chặn truy cập
DROP TABLE IF EXISTS `ip_chan`;
CREATE TABLE `ip_chan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL UNIQUE,
    `ly_do` VARCHAR(255) NOT NULL,
    `chan_den` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách IP vi phạm bị khóa truy cập';

-- 7.6 Quản lý sao lưu & Phục hồi thảm họa (RPO <= 2h, RTO <= 4h)
DROP TABLE IF EXISTS `sao_luu`;
CREATE TABLE `sao_luu` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loai_sao_luu` ENUM('FULL', 'DIFFERENTIAL', 'LOG') NOT NULL,
    `ten_file` VARCHAR(255) NOT NULL,
    `duong_dan` VARCHAR(500) NOT NULL,
    `dung_luong_byte` BIGINT NOT NULL,
    `sha256` VARCHAR(64) NOT NULL,
    `vi_tri_luu` VARCHAR(100) NOT NULL DEFAULT 'ON_PREMISE',
    `thoi_gian_bat_dau` DATETIME NOT NULL,
    `thoi_gian_hoan_thanh` DATETIME NULL,
    `trang_thai` ENUM('DANG_CHAY', 'THANH_CONG', 'THAT_BAI') NOT NULL DEFAULT 'DANG_CHAY',
    `ghi_chu` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử sao lưu và cam kết an toàn dữ liệu';

-- 7.7 Quản lý sự cố an toàn thông tin & Kế hoạch duy trì hoạt động (BCP/DRP)
DROP TABLE IF EXISTS `su_co`;
CREATE TABLE `su_co` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loai_su_co` VARCHAR(100) NOT NULL COMMENT 'DUT_TSLCD, DDOS, RANSOMWARE, DB_FAILOVER...',
    `muc_do` ENUM('THAP', 'TRUNG_BINH', 'CAO', 'NGHIEM_TRONG') NOT NULL,
    `tieu_de` VARCHAR(255) NOT NULL,
    `mo_ta` TEXT NOT NULL,
    `thoi_gian_phat_hien` DATETIME NOT NULL,
    `thoi_gian_khac_phuc` DATETIME NULL,
    `nguoi_xu_ly_id` INT NULL,
    `raci_trach_nhiem` VARCHAR(100) NULL COMMENT 'SO_TTTT_R_A / SO_NGANH_R',
    `bien_phap_khac_phuc` TEXT NULL,
    `trang_thai` ENUM('MOI', 'DANG_XU_LY', 'DA_KHAC_PHUC') NOT NULL DEFAULT 'MOI',
    CONSTRAINT `fk_suco_user` FOREIGN KEY (`nguoi_xu_ly_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sổ theo dõi sự cố an toàn thông tin và gián đoạn dịch vụ';

-- 7.8 Lịch sử và biên bản diễn tập an toàn thông tin & DRP
DROP TABLE IF EXISTS `dien_tap`;
CREATE TABLE `dien_tap` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loai_dien_tap` ENUM('AN_TOAN_THONG_TIN', 'CHUYEN_DOI_THAM_HOA_DRP') NOT NULL,
    `tieu_de` VARCHAR(255) NOT NULL,
    `kich_ban` TEXT NOT NULL,
    `thoi_gian_dien_tap` DATETIME NOT NULL,
    `rto_thuc_te_phut` INT NULL COMMENT 'Thời gian phục hồi thực tế tính theo phút',
    `ket_qua` ENUM('DAT', 'KHONG_DAT') NOT NULL,
    `bien_ban_danh_gia` TEXT NULL,
    `nguoi_chu_tri_id` INT NOT NULL,
    CONSTRAINT `fk_dientap_user` FOREIGN KEY (`nguoi_chu_tri_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký diễn tập ATTT (6 tháng) và khôi phục thảm họa (12 tháng)';

-- 7.9 Lịch sử phiên bản cấu hình tham số
DROP TABLE IF EXISTS `phien_ban_cau_hinh`;
CREATE TABLE `phien_ban_cau_hinh` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `khoa_cau_hinh` VARCHAR(100) NOT NULL,
    `gia_tri_cu` TEXT NULL,
    `gia_tri_moi` TEXT NOT NULL,
    `nguoi_thay_doi_id` INT NOT NULL,
    `thoi_gian_thay_doi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pbch_user` FOREIGN KEY (`nguoi_thay_doi_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử kiểm soát phiên bản thay đổi cấu hình';


-- ----------------------------------------------------------------------------
-- 8. NHÓM BẢNG: CHUYỂN ĐỔI DỮ LIỆU ETL DỮ LIỆU LỊCH SỬ 5 NĂM (§1.3.A)
-- ----------------------------------------------------------------------------

-- 8.1 Lô chuyển đổi dữ liệu
DROP TABLE IF EXISTS `etl_lo`;
CREATE TABLE `etl_lo` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ten_lo` VARCHAR(100) NOT NULL,
    `he_thong_nguon` VARCHAR(100) NOT NULL COMMENT 'MSSQL_CU, MYSQL_CU...',
    `tong_so_ban_ghi` INT NOT NULL DEFAULT 0,
    `so_thanh_cong` INT NOT NULL DEFAULT 0,
    `so_loi` INT NOT NULL DEFAULT 0,
    `trang_thai` ENUM('STAGING', 'TRANSFORMING', 'LOADED', 'ERROR') NOT NULL DEFAULT 'STAGING',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lô tiến trình chuyển đổi dữ liệu lịch sử';

-- 8.2 Bảng trung gian Staging lưu dữ liệu nguyên trạng
DROP TABLE IF EXISTS `etl_staging_ho_so`;
CREATE TABLE `etl_staging_ho_so` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lo_id` INT NOT NULL,
    `ma_ho_so_cu` VARCHAR(100) NULL,
    `ten_tthc_cu` VARCHAR(255) NULL,
    `ma_tthc_cu` VARCHAR(100) NULL,
    `co_quan_cu` VARCHAR(255) NULL,
    `nguoi_nop` VARCHAR(255) NULL,
    `so_cmnd_cccd` VARCHAR(50) NULL,
    `ngay_tiep_nhan_cu` VARCHAR(50) NULL,
    `ngay_hen_tra_cu` VARCHAR(50) NULL,
    `trang_thai_cu` VARCHAR(50) NULL,
    `danh_sach_file_cu` TEXT NULL,
    `du_lieu_raw_json` LONGTEXT NULL,
    `trang_thai_etl` ENUM('CHUA_XU_LY', 'HOP_LE', 'LOI', 'DA_NAP') NOT NULL DEFAULT 'CHUA_XU_LY',
    `ghi_chu_loi` TEXT NULL,
    INDEX `idx_etl_lo` (`lo_id`),
    CONSTRAINT `fk_etl_lo` FOREIGN KEY (`lo_id`) REFERENCES `etl_lo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Vùng đệm Staging nạp nguyên trạng dữ liệu cũ';

-- 8.3 Nhật ký lỗi trong quá trình ETL
DROP TABLE IF EXISTS `etl_loi`;
CREATE TABLE `etl_loi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lo_id` INT NOT NULL,
    `staging_id` INT NULL,
    `loai_loi` VARCHAR(100) NOT NULL,
    `mo_ta_loi` TEXT NOT NULL,
    `du_lieu_loi` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_etl_err_lo` FOREIGN KEY (`lo_id`) REFERENCES `etl_lo` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_etl_err_stg` FOREIGN KEY (`staging_id`) REFERENCES `etl_staging_ho_so` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết các lỗi làm sạch và chuẩn hóa dữ liệu ETL';


-- ----------------------------------------------------------------------------
-- 9. NHÓM BẢNG: ĐÀO TẠO & CHUYỂN GIAO CÔNG NGHỆ 4 NHÓM CÁN BỘ (§1.4)
-- ----------------------------------------------------------------------------

-- 9.1 Khóa đào tạo theo nhóm đối tượng
DROP TABLE IF EXISTS `dao_tao_khoa`;
CREATE TABLE `dao_tao_khoa` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nhom_doi_tuong` ENUM('MOT_CUA', 'CHUYEN_VIEN', 'LANH_DAO', 'SYSADMIN') NOT NULL,
    `ten_khoa` VARCHAR(255) NOT NULL,
    `noi_dung_trong_tam` TEXT NOT NULL,
    `phuong_thuc_danh_gia` TEXT NOT NULL,
    `nguong_dat` VARCHAR(100) NOT NULL COMMENT 'Ví dụ: >= 90/100, 10 bộ hồ sơ không lỗi...',
    `ngay_bat_dau` DATE NOT NULL,
    `ngay_ket_thuc` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chương trình đào tạo chuyển giao cho 4 nhóm đối tượng';

-- 9.2 Học viên và kết quả sát hạch thực hành
DROP TABLE IF EXISTS `dao_tao_hoc_vien`;
CREATE TABLE `dao_tao_hoc_vien` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `khoa_id` INT NOT NULL,
    `nguoi_dung_id` INT NOT NULL,
    `so_ho_so_thuc_hanh` INT NOT NULL DEFAULT 0,
    `diem_so` DECIMAL(5,2) NULL,
    `ket_qua` ENUM('CHUA_THI', 'DAT', 'KHONG_DAT') NOT NULL DEFAULT 'CHUA_THI',
    `ngay_sat_hach` DATE NULL,
    `nhan_xet` TEXT NULL,
    INDEX `idx_dthv_khoa` (`khoa_id`),
    CONSTRAINT `fk_dthv_khoa` FOREIGN KEY (`khoa_id`) REFERENCES `dao_tao_khoa` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dthv_user` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kết quả sát hạch thực hành của học viên từng nhóm';


-- ----------------------------------------------------------------------------
-- 10. TRIGGERS BẢO VỆ NGUYÊN TẮC BẤT BIẾN DỮ LIỆU (§4.1, §4.3)
-- ----------------------------------------------------------------------------

DELIMITER $$

-- Trigger 1: Tuyệt đối ngăn chặn xóa vật lý hồ sơ hành chính
DROP TRIGGER IF EXISTS `tg_hoso_prevent_delete`$$
CREATE TRIGGER `tg_hoso_prevent_delete`
BEFORE DELETE ON `ho_so`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Nghiêm cấm xóa vật lý hồ sơ hành chính! Hãy cập nhật is_deleted = 1 (DA_HUY) kèm căn cứ pháp lý theo quy định.';
END$$

-- Trigger 2: Tuyệt đối ngăn chặn xóa tài liệu hồ sơ
DROP TRIGGER IF EXISTS `tg_tailieu_prevent_delete`$$
CREATE TRIGGER `tg_tailieu_prevent_delete`
BEFORE DELETE ON `ho_so_tai_lieu`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Nghiêm cấm xóa vật lý tài liệu đính kèm của hồ sơ TTHC!';
END$$

-- Trigger 3: Tuyệt đối ngăn chặn xóa nhật ký luân chuyển
DROP TRIGGER IF EXISTS `tg_luanchuyen_prevent_delete`$$
CREATE TRIGGER `tg_luanchuyen_prevent_delete`
BEFORE DELETE ON `ho_so_luan_chuyen`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Không được phép xóa lịch sử luân chuyển nghiệp vụ của hồ sơ!';
END$$

-- Trigger 4: Tuyệt đối ngăn chặn xóa chữ ký số
DROP TRIGGER IF EXISTS `tg_kyso_prevent_delete`$$
CREATE TRIGGER `tg_kyso_prevent_delete`
BEFORE DELETE ON `chu_ky_so`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Chữ ký số là chứng thư pháp lý bất biến, nghiêm cấm thao tác DELETE!';
END$$

-- Trigger 5: Mô phỏng lưu trữ WORM - Chặn UPDATE trên bảng Nhật ký kiểm toán
DROP TRIGGER IF EXISTS `tg_audit_prevent_update`$$
CREATE TRIGGER `tg_audit_prevent_update`
BEFORE UPDATE ON `nhat_ky_kiem_toan`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Bảng nhat_ky_kiem_toan là kho WORM bất biến, nghiêm cấm chỉnh sửa dữ liệu!';
END$$

-- Trigger 6: Mô phỏng lưu trữ WORM - Chặn DELETE trên bảng Nhật ký kiểm toán
DROP TRIGGER IF EXISTS `tg_audit_prevent_delete`$$
CREATE TRIGGER `tg_audit_prevent_delete`
BEFORE DELETE ON `nhat_ky_kiem_toan`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'LỖI BẢO MẬT: Bảng nhat_ky_kiem_toan là kho WORM bất biến, nghiêm cấm xóa nhật ký!';
END$$

DELIMITER ;


-- ----------------------------------------------------------------------------
-- 11. VIEWS BÁO CÁO THỐNG KÊ & CHỈ ĐẠO ĐIỀU HÀNH (IOC DASHBOARD)
-- ----------------------------------------------------------------------------

-- View 1: Tổng hợp hồ sơ với trạng thái chuẩn Thông tư 01/2018/TT-VPCP
DROP VIEW IF EXISTS `v_ho_so_tong_hop`;
CREATE VIEW `v_ho_so_tong_hop` AS
SELECT 
    h.id,
    h.ma_ho_so,
    h.tthc_id,
    t.ma_tthc,
    t.ten_tthc,
    t.cap_thuc_hien,
    t.muc_do_dvc,
    h.don_vi_tiep_nhan_id,
    dv_tn.ten_don_vi AS ten_don_vi_tiep_nhan,
    dv_tn.ma_dinh_danh AS ma_dinh_danh_don_vi,
    h.don_vi_hien_tai_id,
    dv_ht.ten_don_vi AS ten_don_vi_hien_tai,
    h.chuyen_vien_thu_ly_id,
    u.ho_ten AS ten_chuyen_vien,
    h.cong_dan_id,
    h.nguoi_nop_ho_ten,
    h.kenh_nop,
    h.trang_thai,
    -- Quy về 6 nhóm trạng thái chuẩn theo Thông tư 01/2018
    CASE 
        WHEN h.trang_thai IN ('CHO_TIEP_NHAN', 'MOI_TIEP_NHAN') THEN 'MOI_TIEP_NHAN'
        WHEN h.trang_thai = 'DANG_XU_LY' THEN 'DANG_XU_LY'
        WHEN h.trang_thai = 'CHO_BO_SUNG' THEN 'CHO_BO_SUNG'
        WHEN h.trang_thai = 'CHO_PHE_DUYET' THEN 'CHO_PHE_DUYET'
        WHEN h.trang_thai IN ('HOAN_THANH', 'TU_CHOI') THEN 'HOAN_THANH'
        WHEN h.trang_thai = 'DA_TRA_KET_QUA' THEN 'DA_TRA_KET_QUA'
        ELSE 'KHAC'
    END AS nhom_trang_thai_tt01,
    h.ngay_nop,
    h.ngay_tiep_nhan,
    h.ngay_hen_tra,
    h.ngay_hoan_thanh,
    h.ngay_tra_ket_qua,
    h.sla_mau,
    h.tong_thoi_han_gio,
    h.thoi_gian_da_dung_gio,
    h.is_deleted,
    -- Đánh giá tiến độ giải quyết
    CASE 
        WHEN h.ngay_hoan_thanh IS NOT NULL AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 'DUNG_HAN'
        WHEN h.ngay_hoan_thanh IS NOT NULL AND h.ngay_hoan_thanh > h.ngay_hen_tra THEN 'QUA_HAN'
        WHEN h.ngay_hoan_thanh IS NULL AND NOW() > h.ngay_hen_tra THEN 'DANG_QUA_HAN'
        ELSE 'TRONG_HAN'
    END AS tien_do_xu_ly
FROM `ho_so` h
JOIN `thu_tuc_hanh_chinh` t ON h.tthc_id = t.id
JOIN `don_vi` dv_tn ON h.don_vi_tiep_nhan_id = dv_tn.id
JOIN `don_vi` dv_ht ON h.don_vi_hien_tai_id = dv_ht.id
LEFT JOIN `nguoi_dung` u ON h.chuyen_vien_thu_ly_id = u.id
WHERE h.is_deleted = 0;

-- View 2: Tỷ lệ giải quyết hồ sơ đúng hạn theo từng cơ quan/đơn vị
DROP VIEW IF EXISTS `v_ty_le_dung_han_don_vi`;
CREATE VIEW `v_ty_le_dung_han_don_vi` AS
SELECT 
    dv.id AS don_vi_id,
    dv.ma_dinh_danh,
    dv.ten_don_vi,
    dv.loai_don_vi,
    COUNT(h.id) AS tong_ho_so_tiep_nhan,
    SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) AS so_ho_so_truc_tuyen,
    ROUND(SUM(CASE WHEN h.kenh_nop = 'TRUC_TUYEN' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(h.id), 0), 2) AS ty_le_truc_tuyen_pt,
    SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END) AS so_ho_so_da_giai_quyet,
    SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) AS so_giai_quyet_dung_han,
    SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh > h.ngay_hen_tra THEN 1 ELSE 0 END) AS so_giai_quyet_qua_han,
    ROUND(
        SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.ngay_hoan_thanh <= h.ngay_hen_tra THEN 1 ELSE 0 END) * 100.0 
        / NULLIF(SUM(CASE WHEN h.trang_thai IN ('HOAN_THANH', 'DA_TRA_KET_QUA') THEN 1 ELSE 0 END), 0), 
        2
    ) AS ty_le_dung_han_pt,
    SUM(CASE WHEN h.trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KET_QUA') AND h.sla_mau = 'DO' THEN 1 ELSE 0 END) AS so_dang_qua_han
FROM `don_vi` dv
LEFT JOIN `ho_so` h ON dv.id = h.don_vi_tiep_nhan_id AND h.is_deleted = 0
WHERE dv.loai_don_vi IN ('UBND_TP', 'SO_NGANH', 'UBND_HUYEN', 'UBND_XA')
GROUP BY dv.id, dv.ma_dinh_danh, dv.ten_don_vi, dv.loai_don_vi;

-- View 3: Danh sách hồ sơ quá hạn cần đính kèm Văn bản xin lỗi Mẫu số 04
DROP VIEW IF EXISTS `v_ho_so_qua_han`;
CREATE VIEW `v_ho_so_qua_han` AS
SELECT 
    h.id AS ho_so_id,
    h.ma_ho_so,
    t.ten_tthc,
    dv.ten_don_vi,
    u.ho_ten AS ten_chuyen_vien,
    h.nguoi_nop_ho_ten,
    h.ngay_tiep_nhan,
    h.ngay_hen_tra,
    TIMESTAMPDIFF(HOUR, h.ngay_hen_tra, NOW()) AS so_gio_tre_han,
    h.trang_thai,
    h.sla_mau,
    CASE WHEN xl.id IS NOT NULL THEN 1 ELSE 0 END AS da_co_van_ban_xin_loi,
    xl.so_van_ban AS so_van_ban_xin_loi,
    xl.ngay_hen_moi
FROM `ho_so` h
JOIN `thu_tuc_hanh_chinh` t ON h.tthc_id = t.id
JOIN `don_vi` dv ON h.don_vi_hien_tai_id = dv.id
LEFT JOIN `nguoi_dung` u ON h.chuyen_vien_thu_ly_id = u.id
LEFT JOIN `van_ban_xin_loi` xl ON h.id = xl.ho_so_id
WHERE h.is_deleted = 0 
  AND (h.sla_mau = 'DO' OR (h.ngay_hoan_thanh IS NULL AND NOW() > h.ngay_hen_tra));

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- HOÀN TẤT DDL SCHEMA CSDL HP-eGov (50 BẢNG, 6 TRIGGERS, 3 VIEWS BÁO CÁO)
-- ============================================================================
