<!-- Header Cổng Dịch vụ công TP. Hải Phòng -->
<header class="portal-header">
    <div class="portal-topbar">
        <div>
            <span>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM — Độc lập - Tự do - Hạnh phúc</span>
        </div>
        <div class="d-flex align-center gap-3">
            <span>Tổng đài Dịch vụ công: <strong>0225 1022</strong></span>
            <span>Hỗ trợ kỹ thuật: <strong>hotro@haiphong.gov.vn</strong></span>
        </div>
    </div>

    <div class="portal-brand-bar">
        <a href="<?= e(url('/')) ?>" class="portal-brand">
            <!-- Quốc huy & Biểu trưng Hải Phòng cách điệu (Pure SVG không bản quyền) -->
            <svg class="portal-emblem-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <!-- Nền tròn đỏ cờ -->
                <circle cx="50" cy="50" r="48" fill="#C8102E" stroke="#D4A017" stroke-width="3"/>
                <!-- Bánh răng tượng trưng công nghiệp cảng biển Hải Phòng -->
                <circle cx="50" cy="50" r="38" fill="none" stroke="#D4A017" stroke-width="1.5" stroke-dasharray="4,2"/>
                <!-- Bông lúa hai bên -->
                <path d="M 22 75 Q 16 50 28 30 Q 32 45 28 65 Z" fill="#D4A017" opacity="0.9"/>
                <path d="M 78 75 Q 84 50 72 30 Q 68 45 72 65 Z" fill="#D4A017" opacity="0.9"/>
                <!-- Sóng biển tượng trưng Cảng Hải Phòng -->
                <path d="M 28 78 Q 50 68 72 78 Q 50 84 28 78 Z" fill="#D4A017"/>
                <!-- Ngôi sao vàng năm cánh trung tâm -->
                <polygon points="50,22 56,38 73,38 59,48 64,64 50,54 36,64 41,48 27,38 44,38" fill="#FFD700"/>
            </svg>

            <div class="portal-brand-text">
                <h1>ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</h1>
                <p>CỔNG DỊCH VỤ CÔNG & HỆ THỐNG MỘT CỬA ĐIỆN TỬ LIÊN THÔNG (HP-eGov)</p>
            </div>
        </a>

        <div class="portal-auth-actions">
            <?php if (auth('cong_dan')): ?>
                <span class="text-white fw-semibold">Xin chào, <?= e(auth('cong_dan')['ho_ten'] ?? 'Công dân') ?></span>
                <a href="<?= e(url('/cong-dan/ho-so')) ?>" class="btn btn-sm btn-gold">Hồ sơ của tôi</a>
                <a href="<?= e(url('/dang-xuat')) ?>" class="btn btn-sm btn-outline text-white">Đăng xuất</a>
            <?php else: ?>
                <a href="<?= e(url('/tra-cuu')) ?>" class="btn btn-sm btn-outline text-white">Tra cứu hồ sơ</a>
                <a href="<?= e(url('/dang-nhap')) ?>" class="btn btn-sm btn-gold">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4"/></svg>
                    Đăng nhập DVC / VNeID
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menu điều hướng chính của Portal -->
    <nav class="portal-nav-bar">
        <ul class="portal-nav">
            <li><a href="<?= e(url('/')) ?>" class="portal-nav-link <?= ($_SERVER['REQUEST_URI'] === '/HP-eGov/' || $_SERVER['REQUEST_URI'] === '/HP-eGov/public/') ? 'active' : '' ?>">Trang chủ</a></li>
            <li><a href="<?= e(url('/thu-tuc-hanh-chinh')) ?>" class="portal-nav-link">Thủ tục hành chính</a></li>
            <li><a href="<?= e(url('/nop-ho-so')) ?>" class="portal-nav-link">Nộp hồ sơ trực tuyến</a></li>
            <li><a href="<?= e(url('/tra-cuu')) ?>" class="portal-nav-link">Tra cứu tiến độ & QR</a></li>
            <li><a href="<?= e(url('/kiem-tra-chu-ky')) ?>" class="portal-nav-link">Kiểm tra Chữ ký số</a></li>
            <li><a href="<?= e(url('/danh-gia-hai-long')) ?>" class="portal-nav-link">Đánh giá hài lòng (SIPAS)</a></li>
            <li><a href="<?= e(url('/ho-tro')) ?>" class="portal-nav-link">Hướng dẫn & Trợ giúp</a></li>
            <li style="margin-left:auto;"><a href="<?= e(url('/admin/dang-nhap')) ?>" class="portal-nav-link text-warning">Dành cho Cán bộ</a></li>
        </ul>
    </nav>
</header>
