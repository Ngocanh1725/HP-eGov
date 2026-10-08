<?php
// Lấy số liệu hồ sơ chờ xử lý thời gian thực cho Sidebar Badge
$sidebarStats = [
    'truc_tuyen' => 0,
    'dang_xu_ly' => 0,
    'cho_ky' => 0,
    'qua_han' => 0,
];
try {
    $dbSb = \App\Core\Database::connection();
    $sidebarStats['truc_tuyen'] = (int)$dbSb->query("SELECT COUNT(*) FROM ho_so WHERE kenh_nop = 'TRUC_TUYEN' AND trang_thai = 'CHO_TIEP_NHAN' AND is_deleted = 0")->fetchColumn();
    $sidebarStats['dang_xu_ly'] = (int)$dbSb->query("SELECT COUNT(*) FROM ho_so WHERE trang_thai IN ('DANG_XU_LY', 'THU_LY', 'MOI_TIEP_NHAN') AND is_deleted = 0")->fetchColumn();
    $sidebarStats['cho_ky'] = (int)$dbSb->query("SELECT COUNT(*) FROM ho_so WHERE trang_thai = 'CHO_PHE_DUYET' AND is_deleted = 0")->fetchColumn();
    $sidebarStats['qua_han'] = (int)$dbSb->query("SELECT COUNT(*) FROM ho_so WHERE sla_mau = 'DO' AND trang_thai NOT IN ('HOAN_THANH', 'DA_TRA_KQ', 'TU_CHOI', 'DA_HUY') AND is_deleted = 0")->fetchColumn();
} catch (\Throwable $e) {
    // Fallback nếu kết nối lỗi
}
?>
<!-- Sidebar Cán bộ hành chính HP-eGov (Collapsible) -->
<aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= e(url('/admin/dashboard')) ?>" class="sidebar-brand">
        <!-- Icon Biểu trưng Hải Phòng -->
        <svg class="sidebar-brand-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="46" fill="#C8102E" stroke="#D4A017" stroke-width="2.5"/>
            <polygon points="50,25 56,39 71,39 59,48 63,62 50,53 37,62 41,48 29,39 44,39" fill="#FFD700"/>
        </svg>
        <span class="sidebar-brand-title">HP-eGov CÁN BỘ</span>
    </a>

    <div class="sidebar-menu-wrapper">
        <ul class="sidebar-menu">
            <li class="sidebar-heading">Chỉ đạo & Điều hành</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/dashboard')) ?>" class="sidebar-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/HP-eGov/admin/dashboard') ? 'active' : '' ?>">
                    <span class="sidebar-icon">📊</span>
                    <span class="sidebar-text">Bàn làm việc</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/ioc')) ?>" class="sidebar-link">
                    <span class="sidebar-icon">🎯</span>
                    <span class="sidebar-text">Trung tâm IOC</span>
                    <span class="sidebar-badge">KPI</span>
                </a>
            </li>

            <li class="sidebar-heading">Bộ phận Một cửa (PH02)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/mot-cua')) ?>" class="sidebar-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/HP-eGov/admin/mot-cua' || ($_SERVER['REQUEST_URI'] ?? '') === '/HP-eGov/admin/mot-cua/dashboard') ? 'active' : '' ?>">
                    <span class="sidebar-icon">🏢</span>
                    <span class="sidebar-text">Bàn làm việc Một cửa</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/mot-cua/tiep-nhan')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/mot-cua/tiep-nhan')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📥</span>
                    <span class="sidebar-text">Tiếp nhận trực tiếp</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/mot-cua/truc-tuyen')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/mot-cua/truc-tuyen')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🌐</span>
                    <span class="sidebar-text">Hồ sơ trực tuyến</span>
                    <?php if ($sidebarStats['truc_tuyen'] > 0): ?>
                        <span class="badge" style="background:var(--color-primary-light); color:#fff; font-size:11px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $sidebarStats['truc_tuyen'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/mot-cua/tra-ket-qua')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/mot-cua/tra-ket-qua')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📤</span>
                    <span class="sidebar-text">Trả kết quả</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/mot-cua/so-theo-doi')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/mot-cua/so-theo-doi')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📖</span>
                    <span class="sidebar-text">Sổ theo dõi (Mẫu 06)</span>
                </a>
            </li>

            <li class="sidebar-heading">Thụ lý & Liên thông (PH03)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/thu-ly')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/thu-ly') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'tab=phoi_hop')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📂</span>
                    <span class="sidebar-text">Hộp việc Thụ lý</span>
                    <?php if ($sidebarStats['dang_xu_ly'] > 0): ?>
                        <span class="badge" style="background:#2563eb; color:#fff; font-size:11px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $sidebarStats['dang_xu_ly'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/thu-ly?tab=phoi_hop')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', 'tab=phoi_hop')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔄</span>
                    <span class="sidebar-text">Phối hợp liên thông</span>
                </a>
            </li>

            <li class="sidebar-heading">Phê duyệt & Ký số (PH04)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/phe-duyet/phong')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/phe-duyet/phong')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📋</span>
                    <span class="sidebar-text">Duyệt bước 1 (Phòng)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/phe-duyet/co-quan')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/phe-duyet/co-quan')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">✍️</span>
                    <span class="sidebar-text">Ký phát hành (Cơ quan)</span>
                    <?php if ($sidebarStats['cho_ky'] > 0): ?>
                        <span class="badge" style="background:#7c3aed; color:#fff; font-size:11px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $sidebarStats['cho_ky'] ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-heading">Tiến độ & SLA (PH06, PH07)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/sla/danh-sach-qua-han')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/sla/')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">⏱️</span>
                    <span class="sidebar-text">Quá hạn & Mẫu số 04</span>
                    <?php if ($sidebarStats['qua_han'] > 0): ?>
                        <span class="badge" style="background:var(--color-danger); color:#fff; font-size:11px; padding:2px 7px; border-radius:10px; margin-left:auto;"><?= $sidebarStats['qua_han'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/mau-thong-bao')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/mau-thong-bao')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">💬</span>
                    <span class="sidebar-text">Mẫu thông báo đa kênh</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/hop-thu-mo-phong')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/hop-thu-mo-phong')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📱</span>
                    <span class="sidebar-text">Hộp thư mô phỏng</span>
                </a>
            </li>

            <li class="sidebar-heading">Báo cáo & Thống kê (PH08)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/bao-cao')) ?>" class="sidebar-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/HP-eGov/admin/bao-cao') ? 'active' : '' ?>">
                    <span class="sidebar-icon">📊</span>
                    <span class="sidebar-text">Trung tâm Báo cáo</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/bao-cao/thong-tu-01')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/bao-cao/thong-tu-01')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📑</span>
                    <span class="sidebar-text">Mẫu TT 01/2018</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/bao-cao/so-hoa')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/bao-cao/so-hoa')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">💾</span>
                    <span class="sidebar-text">Số hóa NĐ 107</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/bao-cao/qua-han')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/bao-cao/qua-han')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">⏱️</span>
                    <span class="sidebar-text">Quá hạn & Mẫu 04</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/bao-cao/hai-long')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/bao-cao/hai-long')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">⭐</span>
                    <span class="sidebar-text">Hài lòng SIPAS</span>
                </a>
            </li>

            <li class="sidebar-heading">Văn thư số (PH05)</li>

            <li class="sidebar-item">
                <a href="<?= e(url('/admin/van-thu')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/van-thu') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'so-van-ban') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'cong-van')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔏</span>
                    <span class="sidebar-text">Đóng dấu phát hành</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/van-thu/so-van-ban')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/van-thu/so-van-ban')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📖</span>
                    <span class="sidebar-text">Sổ văn bản điện tử</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/van-thu/cong-van')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/van-thu/cong-van')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📑</span>
                    <span class="sidebar-text">Công văn đến / đi</span>
                </a>
            </li>

            <li class="sidebar-heading">Cá nhân & Tiện ích</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/ho-so-ca-nhan')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/ho-so-ca-nhan')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">👤</span>
                    <span class="sidebar-text">Hồ sơ cá nhân</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/ui-kit')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/ui-kit')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🎨</span>
                    <span class="sidebar-text">Thư viện UI Kit</span>
                </a>
            </li>

            <?php if (can('admin.nguoi_dung') || can('admin.vai_tro_quyen') || has_role('SYSADMIN')): ?>
            <li class="sidebar-heading">Quản trị hệ thống (PH11)</li>
            <?php if (can('admin.nguoi_dung') || has_role('SYSADMIN')): ?>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/nguoi-dung')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/nguoi-dung')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">👥</span>
                    <span class="sidebar-text">Tài khoản Cán bộ</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/cong-dan')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/cong-dan')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🪪</span>
                    <span class="sidebar-text">Tài khoản Công dân</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (can('admin.vai_tro_quyen') || has_role('SYSADMIN')): ?>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/vai-tro')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/vai-tro')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🛡️</span>
                    <span class="sidebar-text">Ma trận quyền RBAC</span>
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (can('admin.danh_muc') || has_role('SYSADMIN')): ?>
            <li class="sidebar-heading">Danh mục dùng chung (PH09)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/dvhc')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/dvhc')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🗺️</span>
                    <span class="sidebar-text">ĐVHC 3 cấp</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/don-vi')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/don-vi')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🏛️</span>
                    <span class="sidebar-text">Cơ cấu Tổ chức</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/tthc')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/tthc')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📋</span>
                    <span class="sidebar-text">Thủ tục hành chính</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/eform')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/eform')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📝</span>
                    <span class="sidebar-text">Thiết kế E-Form</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/linh-vuc')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/linh-vuc')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🏷️</span>
                    <span class="sidebar-text">Lĩnh vực & Chức danh</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/ngay-nghi')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/ngay-nghi')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">📅</span>
                    <span class="sidebar-text">Lịch nghỉ & Giờ làm việc</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/danh-muc/cau-hinh')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/danh-muc/cau-hinh')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">⚙️</span>
                    <span class="sidebar-text">Tham số hệ thống</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (can('admin.quy_trinh') || has_role('SYSADMIN')): ?>
            <li class="sidebar-heading">Quản trị quy trình (PH10)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/quy-trinh')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/quy-trinh')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔀</span>
                    <span class="sidebar-text">Quy trình BPMN-lite</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (has_role('SYSADMIN')): ?>
            <li class="sidebar-heading">Quản trị & Vận hành (SYSADMIN)</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/health')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/health')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🩺</span>
                    <span class="sidebar-text">Sức khỏe hệ thống</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/sao-luu')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/sao-luu')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">💾</span>
                    <span class="sidebar-text">Sao lưu & Phục hồi CSDL</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/audit')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/audit')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🛡️</span>
                    <span class="sidebar-text">Nhật ký kiểm toán WORM</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/nhat-ky-dang-nhap')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/nhat-ky-dang-nhap')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔐</span>
                    <span class="sidebar-text">Nhật ký đăng nhập</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/nhat-ky-tich-hop')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/nhat-ky-tich-hop')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🌐</span>
                    <span class="sidebar-text">Nhật ký tích hợp LGSP</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/ip-chan')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/ip-chan')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🚫</span>
                    <span class="sidebar-text">IP Chặn & Rate Limit</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/chinh-sach-bao-mat')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/chinh-sach-bao-mat')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔒</span>
                    <span class="sidebar-text">Chính sách bảo mật</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/su-co')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/su-co')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🚨</span>
                    <span class="sidebar-text">Sự cố & Diễn tập DRP</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/dao-tao')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/dao-tao')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🎓</span>
                    <span class="sidebar-text">Đào tạo 4 nhóm (§1.4)</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/etl')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/etl')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔄</span>
                    <span class="sidebar-text">Chuyển đổi dữ liệu ETL</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/he-thong/tich-hop')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/he-thong/tich-hop')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔌</span>
                    <span class="sidebar-text">Cổng tích hợp (9 dịch vụ)</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="sidebar-item mt-3">
                <a href="<?= e(url('/admin/dang-xuat')) ?>" class="sidebar-link text-danger">
                    <span class="sidebar-icon">🚪</span>
                    <span class="sidebar-text">Đăng xuất</span>
                </a>
            </li>

        </ul>
    </div>
</aside>
