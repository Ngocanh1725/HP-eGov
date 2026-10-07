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
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/thu-ly?tab=phoi_hop')) ?>" class="sidebar-link <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', 'tab=phoi_hop')) ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔄</span>
                    <span class="sidebar-text">Phối hợp liên thông</span>
                </a>
            </li>

            <li class="sidebar-heading">Phê duyệt & Văn thư</li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/phe-duyet')) ?>" class="sidebar-link">
                    <span class="sidebar-icon">✍</span>
                    <span class="sidebar-text">Trình ký & Phê duyệt</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?= e(url('/admin/van-thu')) ?>" class="sidebar-link">
                    <span class="sidebar-icon">📑</span>
                    <span class="sidebar-text">Văn thư & Đóng dấu</span>
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

            <li class="sidebar-item mt-3">
                <a href="<?= e(url('/admin/dang-xuat')) ?>" class="sidebar-link text-danger">
                    <span class="sidebar-icon">🚪</span>
                    <span class="sidebar-text">Đăng xuất</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
