<!-- Topbar Cán bộ HP-eGov -->
<header class="admin-topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" data-toggle="sidebar" title="Thu gọn / Mở rộng menu">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="unit-node-badge" title="Đơn vị và bộ phận công tác hiện tại">
            <?php 
                $curDv = current_don_vi();
                $dvList = \App\Services\AuthService::donViList();
            ?>
            <?php if (count($dvList) > 1): ?>
                <div class="dropdown" style="display:inline-block;">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-dropdown-toggle style="font-size:0.8rem; padding: 2px 8px; border:none; background:none; color:inherit; font-weight:600; cursor:pointer;">
                        <span>🏢 <?= e($curDv['ten_don_vi'] ?? 'Đơn vị công tác') ?> ▾</span>
                    </button>
                    <div class="dropdown-menu">
                        <div class="dropdown-header">Chuyển đơn vị làm việc kiêm nhiệm:</div>
                        <?php foreach ($dvList as $dv): ?>
                            <a href="<?= e(url('/admin/chuyen-don-vi/' . $dv['id'])) ?>" class="dropdown-item <?= ((int)$dv['id'] === (int)($curDv['id'] ?? 0)) ? 'active fw-bold' : '' ?>">
                                <?= e($dv['ten_don_vi']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <span>🏢 <?= e($curDv['ten_don_vi'] ?? 'UBND TP. Hải Phòng') ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="topbar-right">
        <!-- Nút chuyển chế độ Tối / Sáng -->
        <button type="button" class="topbar-btn" data-toggle="dark-mode" title="Chuyển chế độ Giao diện Tối/Sáng">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        <!-- Chuông thông báo & Dropdown -->
        <div class="dropdown">
            <button type="button" class="topbar-btn" data-dropdown-toggle title="Thông báo hệ thống">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span class="topbar-badge">3</span>
            </button>
            <div class="dropdown-menu">
                <div class="dropdown-header d-flex justify-between align-center">
                    <span>Thông báo mới</span>
                    <span class="badge-status badge-status-moi-tiep-nhan">3 chưa đọc</span>
                </div>
                <a href="#" class="dropdown-item">
                    <div class="d-flex align-center justify-between mb-1">
                        <span class="badge-sla badge-sla-vang"><span class="sla-dot"></span>Cảnh báo SLA Vàng</span>
                        <small class="text-muted">10 phút trước</small>
                    </div>
                    <div class="fw-semibold" style="font-size:0.85rem;">Hồ sơ 000.04.15.H21-260115-0042 còn dưới 24h</div>
                </a>
                <a href="#" class="dropdown-item">
                    <div class="d-flex align-center justify-between mb-1">
                        <span class="badge-status badge-status-cho-tiep-nhan">Trực tuyến</span>
                        <small class="text-muted">30 phút trước</small>
                    </div>
                    <div class="fw-semibold" style="font-size:0.85rem;">Có 2 hồ sơ nộp trực tuyến mới qua Cổng DVC</div>
                </a>
                <div style="padding:0.6rem;text-align:center;background:var(--bg-muted);">
                    <a href="#" style="font-size:0.825rem;font-weight:600;">Xem toàn bộ thông báo</a>
                </div>
            </div>
        </div>

        <!-- Thông tin cán bộ & Dropdown tài khoản -->
        <div class="dropdown">
            <button type="button" class="user-profile-btn" data-dropdown-toggle>
                <div class="user-avatar">
                    <?= e(mb_substr(auth()['ho_ten'] ?? 'CB', 0, 1, 'UTF-8')) ?>
                </div>
                <div class="user-info-text text-left">
                    <div class="user-name"><?= e(auth()['ho_ten'] ?? 'Cán bộ') ?></div>
                    <div class="user-role"><?= e(implode(', ', \App\Services\AuthService::roles() ?: ['Cán bộ'])) ?></div>
                </div>
            </button>
            <div class="dropdown-menu">
                <div class="dropdown-header">Tài khoản Cán bộ</div>
                <a href="<?= e(url('/admin/ho-so-ca-nhan')) ?>" class="dropdown-item">Hồ sơ cá nhân & Bảo mật</a>
                <div style="border-top:1px solid var(--border-color);margin:4px 0;"></div>
                <a href="<?= e(url('/admin/dang-xuat')) ?>" class="dropdown-item text-danger">Đăng xuất</a>
            </div>
        </div>
    </div>
</header>
