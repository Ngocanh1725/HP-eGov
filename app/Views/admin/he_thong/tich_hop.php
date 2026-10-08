<?php
/**
 * View: Quản trị Cổng Tích hợp & Kết nối Liên thông 9 dịch vụ (§1.1, §2.3)
 * Layout: layouts/admin.php
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="page-title">
                <span class="icon-box bg-primary-subtle text-primary">🔌</span>
                Quản trị Cổng tích hợp & Kết nối liên thông (§1.1, §2.3)
            </h1>
            <p class="text-muted mb-0">Quản lý tham số, chế độ vận hành (Mock/Live) và giám sát trạng thái 9 cổng kết nối đối tác nền tảng</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline" id="btnPingAll">
                ⚡ Kiểm tra kết nối toàn bộ 9 cổng
            </button>
            <a href="<?= e(url('/admin/he-thong/nhat-ky-tich-hop')) ?>" class="btn btn-primary">
                📜 Xem toàn bộ Nhật ký tích hợp
            </a>
        </div>
    </div>
</div>

<!-- LƯỚI 9 CỔNG DỊCH VỤ TÍCH HỢP -->
<div class="grid grid-3 gap-3 mb-4" id="servicesGrid">
    <?php foreach ($services as $s): ?>
    <?php 
        $tk = $s['thong_ke'] ?? [];
        $isGood = ($s['trang_thai_ket_noi'] === 'KET_NOI_TOT');
        $statusBadge = $isGood 
            ? '<span class="badge badge-success">● KẾT NỐI TỐT</span>' 
            : (($s['trang_thai_ket_noi'] === 'LOI_KET_NOI') ? '<span class="badge badge-danger">● LỖI KẾT NỐI</span>' : '<span class="badge badge-secondary">● CHƯA KIỂM TRA</span>');
        
        $modeBadge = ($s['che_do'] === 'LIVE') 
            ? '<span class="badge badge-warning text-dark font-weight-bold">LIVE (TRỰC TIẾP)</span>' 
            : '<span class="badge badge-info">MOCK GATEWAY</span>';
    ?>
    <div class="card service-card" id="card-service-<?= e($s['ma_dich_vu']) ?>">
        <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong class="text-primary"><?= e($s['ma_dich_vu']) ?></strong>
                <span class="mx-1">·</span>
                <?= $modeBadge ?>
            </div>
            <div id="status-badge-<?= e($s['ma_dich_vu']) ?>">
                <?= $statusBadge ?>
            </div>
        </div>
        <div class="card-body p-3">
            <h4 class="h6 font-weight-bold mb-1"><?= e($s['ten_dich_vu']) ?></h4>
            <div class="text-muted small mb-2">🏢 <?= e($s['nha_cung_cap']) ?></div>

            <div class="bg-light p-2 rounded mb-2 small text-truncate" title="<?= e($s['endpoint_url']) ?>">
                <code><?= e($s['endpoint_url']) ?></code>
            </div>

            <!-- Thống kê nhanh từ nhat_ky_tich_hop -->
            <div class="grid grid-3 gap-1 text-center py-2 border-top border-bottom small mb-3">
                <div>
                    <div class="text-muted">Tổng gọi</div>
                    <strong><?= number_format((int)($tk['tong_luot'] ?? 0)) ?></strong>
                </div>
                <div>
                    <div class="text-muted">Tỷ lệ lỗi</div>
                    <strong class="<?= ((float)($tk['ty_le_loi'] ?? 0) > 0) ? 'text-danger' : 'text-success' ?>">
                        <?= number_format((float)($tk['ty_le_loi'] ?? 0), 1) ?>%
                    </strong>
                </div>
                <div>
                    <div class="text-muted">Độ trễ TB</div>
                    <strong class="text-info"><?= number_format((float)($tk['do_tre_tb_ms'] ?? 0), 0) ?> ms</strong>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-1">
                <small class="text-muted" id="last-ping-<?= e($s['ma_dich_vu']) ?>">
                    <?= $s['lan_kiem_tra_cuoi'] ? date('H:i d/m', strtotime($s['lan_kiem_tra_cuoi'])) : 'Chưa kiểm tra' ?>
                </small>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline btn-config"
                            data-service='<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>'>
                        ⚙️ Cấu hình
                    </button>
                    <button type="button" class="btn btn-sm btn-primary btn-ping" data-code="<?= e($s['ma_dich_vu']) ?>">
                        ⚡ Ping test
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- BẢNG 20 GIAO DỊCH GẦN NHẤT -->
<div class="card mb-4">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">📡 20 Giao dịch kết nối tích hợp gần nhất (Trực tiếp từ nhat_ky_tich_hop)</h3>
        <span class="text-muted small">Tự động ghi nhận mọi truy vấn Mock/Live</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Dịch vụ</th>
                        <th>Phương thức / Endpoint</th>
                        <th class="text-center">Mã HTTP</th>
                        <th class="text-center">Độ trễ</th>
                        <th>Thời gian gọi</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Chưa có giao dịch tích hợp nào được ghi nhận. Hãy thử nhấn "Ping test" một dịch vụ phía trên.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <?php 
                        $code = (int)($log['status_code'] ?? 0);
                        $isSuccess = ($code >= 200 && $code < 300);
                    ?>
                    <tr>
                        <td><strong>#<?= (int)$log['id'] ?></strong></td>
                        <td><span class="badge badge-primary"><?= e($log['dich_vu']) ?></span></td>
                        <td>
                            <code><?= e($log['method']) ?></code>
                            <span class="text-muted small ml-1"><?= e($log['endpoint']) ?></span>
                        </td>
                        <td class="text-center font-weight-bold <?= $isSuccess ? 'text-success' : 'text-danger' ?>">
                            <?= $code ?: 'ERR' ?>
                        </td>
                        <td class="text-center text-info font-weight-bold">
                            <?= number_format((float)$log['thoi_gian_xu_ly_ms']) ?> ms
                        </td>
                        <td><small class="text-muted"><?= date('H:i:s d/m/Y', strtotime($log['created_at'])) ?></small></td>
                        <td>
                            <?php if ($isSuccess): ?>
                                <span class="badge badge-success">THÀNH CÔNG</span>
                            <?php else: ?>
                                <span class="badge badge-danger">LỖI KẾT NỐI</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CẤU HÌNH THAM SỐ CỔNG TÍCH HỢP -->
<div class="modal-backdrop" id="modalConfigService" style="display: none;">
    <div class="modal-card">
        <div class="modal-header border-bottom">
            <h3 class="modal-title">⚙️ Cấu hình Tham số Cổng kết nối</h3>
            <button type="button" class="btn-close" data-modal-close="modalConfigService">&times;</button>
        </div>
        <form id="formUpdateConfig">
            <?= csrf_field() ?>
            <input type="hidden" name="ma_dich_vu" id="cfgMaDichVu">
            <div class="modal-body p-3">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Tên dịch vụ:</label>
                    <input type="text" class="form-control" id="cfgTenDichVu" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Chế độ vận hành (Mode) *:</label>
                    <select class="form-control" name="che_do" id="cfgCheDo">
                        <option value="MOCK">MOCK GATEWAY (Dữ liệu giả lập an toàn Sandbox)</option>
                        <option value="LIVE">LIVE PRODUCTION (Kết nối trực tiếp Endpoint thật)</option>
                    </select>
                    <small class="text-muted">Ở môi trường diễn tập/kiểm thử, khuyến nghị sử dụng chế độ MOCK.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Endpoint URL *:</label>
                    <input type="url" class="form-control" name="endpoint_url" id="cfgEndpointUrl" required>
                </div>
                <div class="grid grid-2 gap-2 mb-3">
                    <div>
                        <label class="form-label small">API Key / Client ID:</label>
                        <input type="text" class="form-control form-control-sm" name="api_key" id="cfgApiKey">
                    </div>
                    <div>
                        <label class="form-label small">Secret Key / Token:</label>
                        <input type="password" class="form-control form-control-sm" name="secret_key" id="cfgSecretKey">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Thời gian Timeout (giây):</label>
                    <input type="number" class="form-control form-control-sm" name="thoi_gian_timeout_giay" id="cfgTimeout" min="5" max="120" value="30">
                </div>
            </div>
            <div class="modal-footer border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline" data-modal-close="modalConfigService">Đóng</button>
                <button type="submit" class="btn btn-primary" id="btnSaveConfig">Lưu cấu hình</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Mở modal cấu hình
    document.querySelectorAll('.btn-config').forEach(btn => {
        btn.addEventListener('click', function () {
            const rawData = this.getAttribute('data-service');
            if (!rawData) return;
            const s = JSON.parse(rawData);

            document.getElementById('cfgMaDichVu').value = s.ma_dich_vu;
            document.getElementById('cfgTenDichVu').value = s.ten_dich_vu + ' (' + s.ma_dich_vu + ')';
            document.getElementById('cfgCheDo').value = s.che_do;
            document.getElementById('cfgEndpointUrl').value = s.endpoint_url;
            document.getElementById('cfgApiKey').value = s.api_key || '';
            document.getElementById('cfgSecretKey').value = s.secret_key || '';
            document.getElementById('cfgTimeout').value = s.thoi_gian_timeout_giay || 30;

            App.modal.open('modalConfigService');
        });
    });

    // 2. Lưu cấu hình dịch vụ
    document.getElementById('formUpdateConfig')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveConfig');
        btn.disabled = true;
        btn.textContent = '⏳ Đang lưu...';

        try {
            const formData = new FormData(this);
            const res = await fetch('<?= url("/admin/he-thong/tich-hop/update") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                App.toast.success(data.message);
                App.modal.close('modalConfigService');
                setTimeout(() => window.location.reload(), 800);
            } else {
                App.toast.error(data.message || 'Lỗi lưu cấu hình');
            }
        } catch (err) {
            App.toast.error('Thất bại: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Lưu cấu hình';
        }
    });

    // 3. Ping test từng cổng
    async function pingOneService(maDichVu, btnEl) {
        if (btnEl) {
            btnEl.disabled = true;
            btnEl.textContent = '⏳ Đang ping...';
        }

        try {
            const res = await fetch('<?= url("/admin/he-thong/tich-hop/ping") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf_token=<?= csrf_token() ?>&ma_dich_vu=' + encodeURIComponent(maDichVu)
            });
            const data = await res.json();

            const badgeEl = document.getElementById('status-badge-' + maDichVu);
            const timeEl = document.getElementById('last-ping-' + maDichVu);

            if (data.success) {
                App.toast.success(data.message);
                if (badgeEl) badgeEl.innerHTML = '<span class="badge badge-success">● KẾT NỐI TỐT (' + data.latency_ms + 'ms)</span>';
            } else {
                App.toast.error(data.message);
                if (badgeEl) badgeEl.innerHTML = '<span class="badge badge-danger">● LỖI KẾT NỐI</span>';
            }

            if (timeEl) {
                timeEl.textContent = 'Vừa kiểm tra';
            }
        } catch (err) {
            App.toast.error('Lỗi ping ' + maDichVu + ': ' + err.message);
        } finally {
            if (btnEl) {
                btnEl.disabled = false;
                btnEl.textContent = '⚡ Ping test';
            }
        }
    }

    document.querySelectorAll('.btn-ping').forEach(btn => {
        btn.addEventListener('click', function () {
            const code = this.getAttribute('data-code');
            pingOneService(code, this);
        });
    });

    // 4. Ping toàn bộ 9 cổng
    document.getElementById('btnPingAll')?.addEventListener('click', async function () {
        this.disabled = true;
        this.textContent = '⏳ Đang kiểm tra lần lượt 9 cổng...';
        const buttons = document.querySelectorAll('.btn-ping');
        for (const b of buttons) {
            const code = b.getAttribute('data-code');
            await pingOneService(code, b);
        }
        this.disabled = false;
        this.textContent = '⚡ Kiểm tra kết nối toàn bộ 9 cổng';
        App.toast.success('Đã hoàn tất kiểm tra toàn bộ 9 cổng tích hợp!');
    });
});
</script>
