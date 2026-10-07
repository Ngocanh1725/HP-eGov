<?php $this->layout('layouts/admin', ['title' => $title ?? 'Dashboard Lãnh đạo Phòng']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                🏢 Bàn làm việc Lãnh đạo Phòng – <?= e($donVi['ten_don_vi'] ?? 'Phòng Chuyên môn') ?>
            </h1>
            <p class="text-muted mb-0">
                <strong>Người dùng:</strong> <?= e($user['ho_ten'] ?? 'Lãnh đạo Phòng') ?> &nbsp;|&nbsp; 
                <strong>Giám sát:</strong> Toàn bộ tiến độ thụ lý và chất lượng phục vụ của cán bộ trong phòng
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/dashboard', ['view' => 'chuyen_vien'])) ?>" class="btn btn-outline-secondary btn-sm">
                👤 Xem Việc cá nhân
            </a>
            <?php if (in_array('LD_CO_QUAN', $roles ?? [], true)): ?>
                <a href="<?= e(url('/admin/dashboard', ['view' => 'co_quan'])) ?>" class="btn btn-outline-primary btn-sm">
                    🏛️ Xem Dashboard Cơ quan
                </a>
            <?php endif; ?>
            <a href="<?= e(url('/admin/phe_duyet/phong')) ?>" class="btn btn-primary btn-sm">
                📋 Duyệt hồ sơ (Bước 1)
            </a>
        </div>
    </div>
</div>

<!-- 4 Thẻ KPI phòng -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-primary shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đang xử lý</span>
                    <h2 class="h3 fw-bold text-primary mb-0 mt-1"><?= (int)$cards['dang_xu_ly'] ?></h2>
                    <small class="text-muted">Tổng hồ sơ phòng đang thụ lý</small>
                </div>
                <div class="fs-1 text-primary opacity-25">📂</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-warning shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Cảnh báo Vàng (Sắp trễ)</span>
                    <h2 class="h3 fw-bold text-warning mb-0 mt-1"><?= (int)$cards['sap_qua_han'] ?></h2>
                    <small class="text-muted">Cần nhắc nhở chuyên viên</small>
                </div>
                <div class="fs-1 text-warning opacity-25">⚠️</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-danger shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Hồ sơ quá hạn (ĐỎ)</span>
                    <h2 class="h3 fw-bold text-danger mb-0 mt-1"><?= (int)$cards['qua_han'] ?></h2>
                    <small class="text-danger fw-semibold">Cần giải trình & Mẫu 04</small>
                </div>
                <div class="fs-1 text-danger opacity-25">🚨</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-success shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tỷ lệ đúng hạn</span>
                    <h2 class="h3 fw-bold <?= ($cards['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?> mb-0 mt-1">
                        <?= number_format($cards['ty_le_dung_han'], 1) ?>%
                    </h2>
                    <small class="text-muted">Chỉ tiêu: ≥ 98.0%</small>
                </div>
                <div class="fs-1 text-success opacity-25">🎯</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Cột trái: Phân bổ công việc theo Chuyên viên trong phòng -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    👥 Phân bổ khối lượng hồ sơ theo Chuyên viên trong phòng
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Chuyên viên</th>
                                <th>Chức danh</th>
                                <th class="text-center">Đang thụ lý</th>
                                <th class="text-center">Trễ hạn (ĐỎ)</th>
                                <th class="text-center">Đã hoàn thành</th>
                                <th class="text-end">Tiến độ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($chuyen_vien_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Không tìm thấy chuyên viên trực thuộc phòng.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($chuyen_vien_list as $cv): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark"><?= e($cv['ho_ten']) ?></span>
                                        </td>
                                        <td>
                                            <span class="small text-muted"><?= e($cv['chuc_danh'] ?? 'Chuyên viên') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary fs-6"><?= (int)$cv['dang_xu_ly'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$cv['qua_han'] > 0): ?>
                                                <span class="badge bg-danger fs-6"><?= (int)$cv['qua_han'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-success fw-semibold"><?= (int)$cv['hoan_thanh'] ?></span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ((int)$cv['qua_han'] > 0): ?>
                                                <span class="badge bg-danger">Cần đôn đốc</span>
                                            <?php elseif ((int)$cv['dang_xu_ly'] > 0): ?>
                                                <span class="badge bg-success">Đang xử lý tốt</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Trống việc</span>
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
    </div>

    <!-- Cột phải: Biểu đồ tròn tải công việc -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    📊 Tỷ trọng hồ sơ đang xử lý
                </h5>
            </div>
            <div class="card-body">
                <div style="height: 260px;">
                    <canvas id="workloadChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hàng 3: Danh sách hồ sơ cảnh báo SLA Vàng & Đỏ cần chỉ đạo -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="card-title mb-0 fw-bold text-danger">
            🚨 Danh sách hồ sơ cần Lãnh đạo phòng chỉ đạo, đôn đốc (Vàng & Đỏ)
        </h5>
        <span class="badge bg-danger"><?= count($alert_list ?? []) ?> hồ sơ</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($alert_list)): ?>
            <div class="text-center py-4 text-muted">
                <div class="fs-1">🌟</div>
                <p class="mb-0">Tuyệt vời! Không có hồ sơ nào trong phòng bị cảnh báo Vàng hoặc Đỏ.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Mã hồ sơ</th>
                            <th>Thủ tục hành chính</th>
                            <th>Người nộp</th>
                            <th>Chuyên viên thụ lý</th>
                            <th>Hạn trả</th>
                            <th>Trạng thái SLA</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alert_list as $hs): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('/admin/thu-ly/' . $hs['id'])) ?>" class="fw-bold">
                                        <?= e($hs['ma_ho_so']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="<?= e($hs['ten_tthc']) ?>">
                                        <?= e($hs['ten_tthc']) ?>
                                    </div>
                                </td>
                                <td><?= e($hs['nguoi_nop_ho_ten']) ?></td>
                                <td>
                                    <span class="fw-semibold text-primary"><?= e($hs['ten_chuyen_vien'] ?? 'Chưa rõ') ?></span>
                                </td>
                                <td><?= date('H:i d/m/Y', strtotime($hs['ngay_hen_tra'])) ?></td>
                                <td>
                                    <?php if ($hs['sla_mau'] === 'DO'): ?>
                                        <span class="badge bg-danger">ĐỎ - QUÁ HẠN</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">VÀNG - SẮP TRỄ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(url('/admin/thu-ly/' . $hs['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                        Xem & Đôn đốc
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('workloadChart');
    if (ctx) {
        const cvList = <?= json_encode($chuyen_vien_list ?? []) ?>;
        const labels = cvList.map(item => item.ho_ten);
        const dataVals = cvList.map(item => parseInt(item.dang_xu_ly));

        const colors = ['#0B2E59', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#ec4899', '#8b5cf6'];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['Không có hồ sơ'],
                datasets: [{
                    data: dataVals.some(v => v > 0) ? dataVals : [1],
                    backgroundColor: colors.slice(0, Math.max(labels.length, 1))
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
