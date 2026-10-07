<?php $this->layout('layouts/admin', ['title' => $title ?? 'Bàn làm việc Chuyên viên']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                👋 Chào buổi làm việc, <?= e($user['ho_ten'] ?? 'Chuyên viên') ?>!
            </h1>
            <p class="text-muted mb-0">
                <strong>Đơn vị:</strong> <?= e($donVi['ten_don_vi'] ?? 'Phòng chuyên môn') ?> &nbsp;|&nbsp; 
                <strong>Vai trò:</strong> Chuyên viên thụ lý &nbsp;|&nbsp; 
                <strong>Hôm nay:</strong> <?= date('d/m/Y') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array('LD_PHONG', $roles ?? [], true)): ?>
                <a href="<?= e(url('/admin/dashboard', ['view' => 'phong'])) ?>" class="btn btn-outline-primary btn-sm">
                    🏢 Xem Dashboard Phòng
                </a>
            <?php endif; ?>
            <?php if (in_array('LD_CO_QUAN', $roles ?? [], true)): ?>
                <a href="<?= e(url('/admin/dashboard', ['view' => 'co_quan'])) ?>" class="btn btn-outline-primary btn-sm">
                    🏛️ Xem Dashboard Cơ quan
                </a>
            <?php endif; ?>
            <a href="<?= e(url('/admin/thu-ly')) ?>" class="btn btn-primary btn-sm">
                📂 Đến Hộp việc Thụ lý
            </a>
        </div>
    </div>
</div>

<!-- 4 Thẻ KPI cá nhân -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-primary shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đang thụ lý</span>
                    <h2 class="h3 fw-bold text-primary mb-0 mt-1"><?= (int)$cards['dang_xu_ly'] ?></h2>
                    <small class="text-muted">Hồ sơ chờ bạn xử lý</small>
                </div>
                <div class="fs-1 text-primary opacity-25">📂</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-warning shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Cảnh báo sắp quá hạn</span>
                    <h2 class="h3 fw-bold text-warning mb-0 mt-1"><?= (int)$cards['sap_qua_han'] ?></h2>
                    <small class="text-muted">Còn ≤ 24h hoặc t &gt; 75% T</small>
                </div>
                <div class="fs-1 text-warning opacity-25">⏱️</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-danger shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đã trễ hạn (ĐỎ)</span>
                    <h2 class="h3 fw-bold text-danger mb-0 mt-1"><?= (int)$cards['qua_han'] ?></h2>
                    <small class="text-danger fw-semibold">Bị khóa chuyển bước</small>
                </div>
                <div class="fs-1 text-danger opacity-25">🚨</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-success shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đã hoàn thành</span>
                    <h2 class="h3 fw-bold text-success mb-0 mt-1"><?= (int)$cards['da_giai_quyet'] ?></h2>
                    <small class="text-muted">Đã trả kết quả</small>
                </div>
                <div class="fs-1 text-success opacity-25">✅</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Cột trái: Hồ sơ cần ưu tiên xử lý -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    ⚡ Hồ sơ cần ưu tiên giải quyết gấp (Sắp xếp theo hạn SLA)
                </h5>
                <a href="<?= e(url('/admin/thu-ly')) ?>" class="btn btn-sm btn-outline-secondary">
                    Xem tất cả (<?= (int)$cards['dang_xu_ly'] ?>) →
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($priority_list)): ?>
                    <div class="text-center py-5 text-muted">
                        <div class="fs-1 mb-2">🎉</div>
                        <p class="mb-0">Tuyệt vời! Hiện không có hồ sơ nào bị dồn ứ hoặc sắp quá hạn.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Mã hồ sơ</th>
                                    <th>Người nộp / Thủ tục</th>
                                    <th>Bước hiện tại</th>
                                    <th>Hẹn trả</th>
                                    <th>Cảnh báo SLA</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($priority_list as $hs): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= e(url('/admin/thu-ly/' . $hs['id'])) ?>" class="fw-bold text-decoration-none">
                                                <?= e($hs['ma_ho_so']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($hs['nguoi_nop_ho_ten']) ?></div>
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 240px;" title="<?= e($hs['ten_tthc']) ?>">
                                                <?= e($hs['ten_tthc']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= e($hs['ten_buoc'] ?? 'Thụ lý') ?></span>
                                        </td>
                                        <td>
                                            <span class="small"><?= date('H:i d/m/Y', strtotime($hs['ngay_hen_tra'])) ?></span>
                                        </td>
                                        <td>
                                            <?= render_sla_badge($hs['sla_mau'], $hs['ngay_hen_tra'], (float)$hs['thoi_gian_da_dung_gio'], (int)$hs['tong_thoi_han_gio']) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= e(url('/admin/thu-ly/' . $hs['id'])) ?>" class="btn btn-sm btn-primary">
                                                Thụ lý
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
    </div>

    <!-- Cột phải: Biểu đồ năng suất & Hướng dẫn -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    📈 Năng suất giải quyết (7 ngày qua)
                </h5>
            </div>
            <div class="card-body">
                <div style="height: 220px;">
                    <canvas id="productivityChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3 bg-light">
            <div class="card-body p-3">
                <h6 class="fw-bold text-primary mb-2">📌 Quy định Kiểm soát Tiến độ SLA:</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1"><span class="badge bg-success">XANH</span>: Đang trong hạn an toàn (còn &gt; 24h và ≤ 75% thời hạn).</li>
                    <li class="mb-1"><span class="badge bg-warning text-dark">VÀNG</span>: Đã dùng &gt; 75% hạn hoặc còn ≤ 24h. Cần ưu tiên làm ngay!</li>
                    <li class="mb-1"><span class="badge bg-danger">ĐỎ</span>: Đã quá hạn. Hồ sơ bị khóa chuyển bước cho tới khi Lãnh đạo ký Mẫu số 04.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('productivityChart');
    if (ctx) {
        const prodData = <?= json_encode($productivity ?? []) ?>;
        const labels = prodData.map(item => item.ngay);
        const dataVals = prodData.map(item => parseInt(item.so_luong));

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['Hôm nay'],
                datasets: [{
                    label: 'Hồ sơ đã xong',
                    data: dataVals.length ? dataVals : [0],
                    backgroundColor: '#10b981',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>
