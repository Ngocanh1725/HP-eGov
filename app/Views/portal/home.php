<?php
$this->layout('layouts/portal', [
    'title' => $title ?? 'Cổng Dịch vụ công TP. Hải Phòng',
    'description' => 'Hệ thống thông tin giải quyết thủ tục hành chính và Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov)'
]);
$this->startSection('content');
?>

<!-- Banner Chào mừng & Tìm kiếm nhanh TTHC -->
<section class="card mb-4" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%); color:#ffffff; padding: 3rem 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: none;">
    <div style="max-width: 820px; margin: 0 auto; text-align: center;">
        <span class="badge" style="background: rgba(212, 160, 23, 0.2); color: var(--color-accent-gold); border: 1px solid var(--color-accent-gold); padding: 4px 14px; border-radius: 9999px; font-size: 0.825rem; font-weight: 700; margin-bottom: 1rem; display: inline-block;">
            CHÍNH QUYỀN ĐIỆN TỬ TP. HẢI PHÒNG
        </span>
        <h2 style="font-size: 2.2rem; color: #ffffff; font-weight: 800; margin-bottom: 0.75rem; letter-spacing: -0.5px;">
            Dịch vụ công Trực tuyến & Một cửa Điện tử
        </h2>
        <p style="font-size: 1.05rem; color: #cbd5e1; margin-bottom: 2rem;">
            Công khai, minh bạch, lấy người dân và doanh nghiệp làm trung tâm phục vụ. Cam kết giải quyết đúng và trước hạn ≥ 98%.
        </p>

        <!-- Thanh tìm kiếm TTHC nổi bật -->
        <div style="background: #ffffff; border-radius: var(--radius-lg); padding: 6px; display: flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <input type="text" class="form-control" style="border:none;box-shadow:none;font-size:1rem;padding:0.75rem 1rem;" placeholder="Nhập tên thủ tục hành chính (Ví dụ: Khai sinh, Cấp giấy phép xây dựng, Biến động đất đai...)">
            <button type="button" class="btn btn-primary btn-lg" style="padding: 0.75rem 1.75rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                Tìm kiếm
            </button>
        </div>
    </div>
</section>

<!-- Chỉ số thực hiện thời gian thực -->
<section class="mb-4">
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Đơn vị hành chính</div>
                <div class="kpi-value"><?= e((string)($dvhcCount ?? 26)) ?></div>
                <div class="kpi-subtext">15 quận/huyện & các xã/phường</div>
            </div>
            <div class="kpi-icon-box">🏛</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Thủ tục công bố</div>
                <div class="kpi-value"><?= e((string)($tthcCount ?? 12)) ?></div>
                <div class="kpi-subtext">Đã đồng bộ CSDL Quốc gia</div>
            </div>
            <div class="kpi-icon-box">📜</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Hồ sơ đã tiếp nhận</div>
                <div class="kpi-value">205</div>
                <div class="kpi-subtext">Trong 6 tháng gần nhất</div>
            </div>
            <div class="kpi-icon-box">📁</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-info">
                <div class="kpi-label">Tỷ lệ đúng hạn</div>
                <div class="kpi-value text-success">98.5%</div>
                <div class="kpi-subtext">Chỉ tiêu thành phố: ≥ 98%</div>
            </div>
            <div class="kpi-icon-box" style="background:#DCFCE7;color:#16A34A;">🎯</div>
        </div>
    </div>
</section>

<!-- Các nhóm dịch vụ công trực tuyến nổi bật -->
<section class="mb-4">
    <div class="card" style="background:var(--bg-surface);padding:2rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);box-shadow:var(--shadow-card);">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h3>Dịch vụ công Trực tuyến Tiêu biểu</h3>
                <p class="text-muted" style="margin-bottom:0;">Nộp hồ sơ trực tuyến hoàn toàn qua Cổng DVC Hải Phòng với định danh VNeID</p>
            </div>
            <a href="<?= e(url('/thu-tuc-hanh-chinh')) ?>" class="btn btn-outline-primary btn-sm">Xem tất cả thủ tục &rarr;</a>
        </div>

        <div class="d-grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="card" style="padding:1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-hover);">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="badge-status badge-status-hoan-thanh">Toàn trình</span>
                    <small class="text-muted">Cấp Xã/Phường</small>
                </div>
                <h4 style="font-size:1.05rem;color:var(--color-primary);margin-bottom:0.4rem;">Đăng ký khai sinh trực tuyến</h4>
                <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;">Liên thông đăng ký khai sinh, thường trú và cấp thẻ BHYT trẻ em dưới 6 tuổi.</p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted" style="font-size:0.8rem;">Thời hạn: <strong>1 ngày</strong></span>
                    <a href="<?= e(url('/nop-ho-so?ma=0001')) ?>" class="btn btn-sm btn-primary">Nộp hồ sơ</a>
                </div>
            </div>

            <div class="card" style="padding:1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-hover);">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="badge-status badge-status-hoan-thanh">Toàn trình</span>
                    <small class="text-muted">Cấp Xã/Phường</small>
                </div>
                <h4 style="font-size:1.05rem;color:var(--color-primary);margin-bottom:0.4rem;">Chứng thực bản sao điện tử</h4>
                <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;">Chứng thực bản sao điện tử từ bản chính giấy có ký số và lưu kho dữ liệu công dân.</p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted" style="font-size:0.8rem;">Thời hạn: <strong>4 giờ</strong></span>
                    <a href="<?= e(url('/nop-ho-so?ma=0002')) ?>" class="btn btn-sm btn-primary">Nộp hồ sơ</a>
                </div>
            </div>

            <div class="card" style="padding:1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-hover);">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="badge-status badge-status-dang-xu-ly">Một phần</span>
                    <small class="text-muted">Cấp Quận/Huyện</small>
                </div>
                <h4 style="font-size:1.05rem;color:var(--color-primary);margin-bottom:0.4rem;">Cấp Giấy phép xây dựng nhà ở riêng lẻ</h4>
                <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;">Cấp phép xây dựng công trình nhà ở riêng lẻ tại đô thị thuộc thẩm quyền UBND quận/huyện.</p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted" style="font-size:0.8rem;">Thời hạn: <strong>15 ngày</strong></span>
                    <a href="<?= e(url('/nop-ho-so?ma=0021')) ?>" class="btn btn-sm btn-primary">Nộp hồ sơ</a>
                </div>
            </div>

            <div class="card" style="padding:1.25rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-hover);">
                <div class="d-flex justify-between align-center mb-2">
                    <span class="badge-status badge-status-dang-xu-ly">Liên thông</span>
                    <small class="text-muted">Cấp Thành phố</small>
                </div>
                <h4 style="font-size:1.05rem;color:var(--color-primary);margin-bottom:0.4rem;">Chủ trương đầu tư dự án logistics cảng biển</h4>
                <p class="text-muted" style="font-size:0.85rem;margin-bottom:1rem;">Liên thông đa cơ quan: Quận Hồng Bàng &rarr; Sở Xây dựng &rarr; Sở TN&MT &rarr; Sở KH&ĐT &rarr; UBND TP.</p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted" style="font-size:0.8rem;">Thời hạn: <strong>35 ngày</strong></span>
                    <a href="<?= e(url('/nop-ho-so?ma=0042')) ?>" class="btn btn-sm btn-primary">Nộp hồ sơ</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tra cứu nhanh tiến độ hồ sơ -->
<section class="card" style="background:var(--bg-surface);padding:2rem;border-radius:var(--radius-lg);border:1px solid var(--border-color);box-shadow:var(--shadow-card);">
    <div class="d-flex justify-between align-center flex-wrap gap-3">
        <div style="max-width:540px;">
            <h3>Tra cứu Tiến độ Hồ sơ & Biên nhận QR</h3>
            <p class="text-muted mb-0">Nhập mã hồ sơ ghi trên Giấy tiếp nhận và hẹn trả kết quả để xem chi tiết lịch sử xử lý, tiến độ SLA và kết quả ký số.</p>
        </div>
        <div class="d-flex gap-2" style="flex:1;max-width:440px;">
            <input type="text" class="form-control" placeholder="Mã hồ sơ: 000.04.15.H21-..." value="000.04.15.H21-260115-0042-000101">
            <button type="button" class="btn btn-gold" style="white-space:nowrap;">Tra cứu</button>
        </div>
    </div>
</section>

<?php $this->endSection(); ?>
