<?php $this->layout('layouts/admin', ['title' => $title ?? 'Dashboard Lãnh đạo Cơ quan']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                🏛️ Chỉ đạo Điều hành: <?= e($donVi['ten_don_vi'] ?? 'Ủy ban nhân dân') ?>
            </h1>
            <p class="text-muted mb-0">
                <strong>Thủ trưởng cơ quan:</strong> <?= e($user['ho_ten'] ?? 'Lãnh đạo') ?> &nbsp;|&nbsp; 
                <strong>Mã định danh:</strong> <?= e($donVi['ma_dinh_danh'] ?? '') ?> &nbsp;|&nbsp; 
                <strong>Kỳ báo cáo:</strong> Năm <?= date('Y') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/ioc')) ?>" class="btn btn-warning text-dark fw-bold btn-sm">
                🎯 Trung tâm IOC Thành phố
            </a>
            <a href="<?= e(url('/admin/phe_duyet/co_quan')) ?>" class="btn btn-primary btn-sm">
                ✍️ Ký số Phát hành (Batch)
            </a>
            <a href="<?= e(url('/admin/sla/danh-sach-qua-han')) ?>" class="btn btn-danger btn-sm">
                ⏱️ Xử lý Hồ sơ Quá hạn
            </a>
        </div>
    </div>
</div>

<!-- 4 Thẻ KPI Cơ quan -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-primary shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tổng hồ sơ tiếp nhận</span>
                    <h2 class="h3 fw-bold text-primary mb-0 mt-1"><?= number_format($kpi['tong_tiep_nhan']) ?></h2>
                    <small class="text-muted"><?= number_format($kpi['da_giai_quyet']) ?> đã giải quyết xong</small>
                </div>
                <div class="fs-1 text-primary opacity-25">📥</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-info shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tỷ lệ Nộp Trực tuyến</span>
                    <h2 class="h3 fw-bold text-info mb-0 mt-1"><?= number_format($kpi['ty_le_truc_tuyen'], 1) ?>%</h2>
                    <small class="text-muted"><?= number_format($kpi['truc_tuyen']) ?> hồ sơ trực tuyến</small>
                </div>
                <div class="fs-1 text-info opacity-25">🌐</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 <?= ($kpi['ty_le_dung_han'] >= 98.0) ? 'border-success' : 'border-danger' ?> shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tỷ lệ Đúng hạn</span>
                    <h2 class="h3 fw-bold <?= ($kpi['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?> mb-0 mt-1">
                        <?= number_format($kpi['ty_le_dung_han'], 1) ?>%
                    </h2>
                    <small class="<?= ($kpi['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?> fw-semibold">
                        <?= ($kpi['ty_le_dung_han'] >= 98.0) ? 'Đạt chuẩn (≥98%)' : 'Chưa đạt chuẩn (<98%)' ?>
                    </small>
                </div>
                <div class="fs-1 <?= ($kpi['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?> opacity-25">🎯</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-dashboard p-3 border-start border-4 border-danger shadow-sm bg-white rounded-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Đang quá hạn (ĐỎ)</span>
                    <h2 class="h3 fw-bold text-danger mb-0 mt-1"><?= number_format($kpi['dang_qua_han']) ?></h2>
                    <small class="text-danger">Cần ký Mẫu 04 xin lỗi công dân</small>
                </div>
                <div class="fs-1 text-danger opacity-25">⚠️</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Cột trái: Tiến độ giải quyết theo các Phòng ban -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    🏢 Tiến độ giải quyết hồ sơ theo Phòng ban chuyên môn
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Phòng ban</th>
                                <th class="text-center">Tổng nhận</th>
                                <th class="text-center">Đã hoàn thành</th>
                                <th class="text-center">Quá hạn</th>
                                <th class="text-end">Tỷ lệ đúng hạn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($phong_ban_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Không có dữ liệu phòng ban.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($phong_ban_list as $pb): 
                                    $daXong = (int)($pb['da_xong'] ?? 0);
                                    $dung = (int)($pb['dung_han'] ?? 0);
                                    $rate = $daXong > 0 ? round(($dung / $daXong) * 100, 1) : 100.0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark"><?= e($pb['ten_don_vi']) ?></span>
                                        </td>
                                        <td class="text-center"><?= (int)$pb['tong_ho_so'] ?></td>
                                        <td class="text-center text-success fw-semibold"><?= $daXong ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$pb['so_qua_han'] > 0): ?>
                                                <span class="badge bg-danger fs-6"><?= (int)$pb['so_qua_han'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold <?= ($rate >= 98.0) ? 'text-success' : 'text-danger' ?>">
                                                <?= number_format($rate, 1) ?>%
                                            </span>
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

    <!-- Cột phải: Xếp hạng Chuyên viên trong cơ quan -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    ⭐ Xếp hạng Năng suất Chuyên viên
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Chuyên viên</th>
                                <th class="text-center">Hoàn thành</th>
                                <th class="text-center">Quá hạn</th>
                                <th class="text-end">Đúng hạn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ranking_list)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Chưa có số liệu xếp hạng.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ranking_list as $idx => $rk): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <span class="badge bg-light text-dark me-1">#<?= $idx + 1 ?></span>
                                                <?= e($rk['ho_ten']) ?>
                                            </div>
                                            <small class="text-muted"><?= e($rk['phong_ban'] ?? '') ?></small>
                                        </td>
                                        <td class="text-center fw-bold text-success">
                                            <?= (int)$rk['da_hoan_thanh'] ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int)$rk['qua_han'] > 0): ?>
                                                <span class="text-danger fw-bold"><?= (int)$rk['qua_han'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold <?= ($rk['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-warning' ?>">
                                            <?= number_format((float)($rk['ty_le_dung_han'] ?? 100), 1) ?>%
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
</div>

<!-- Hàng 3: Danh sách hồ sơ quá hạn cần ký Văn bản xin lỗi Mẫu 04 -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h5 class="card-title mb-0 fw-bold text-danger">
                📋 Hồ sơ quá hạn cần xem xét ban hành Văn bản xin lỗi (Mẫu số 04 TT 01/2018/TT-VPCP)
            </h5>
            <small class="text-muted">Hồ sơ quá hạn bị khóa chuyển bước cho tới khi Lãnh đạo cơ quan ký số ban hành Mẫu 04</small>
        </div>
        <a href="<?= e(url('/admin/sla/danh-sach-qua-han')) ?>" class="btn btn-outline-danger btn-sm">
            Xem toàn bộ hồ sơ quá hạn →
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($overdue_list)): ?>
            <div class="text-center py-4 text-muted">
                <div class="fs-1">🎉</div>
                <p class="mb-0">Cơ quan không có hồ sơ nào bị quá hạn!</p>
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
                            <th>Trễ hạn</th>
                            <th>Văn bản xin lỗi (Mẫu 04)</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($overdue_list, 0, 5) as $hs): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-danger"><?= e($hs['ma_ho_so']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 260px;" title="<?= e($hs['ten_tthc']) ?>">
                                        <?= e($hs['ten_tthc']) ?>
                                    </div>
                                </td>
                                <td><?= e($hs['nguoi_nop_ho_ten']) ?></td>
                                <td><?= e($hs['ten_chuyen_vien'] ?? 'Chưa phân công') ?></td>
                                <td>
                                    <span class="badge bg-danger"><?= (int)$hs['so_gio_tre_han'] ?> giờ</span>
                                </td>
                                <td>
                                    <?php if ((int)$hs['da_co_van_ban_xin_loi'] === 1): ?>
                                        <span class="badge bg-success">
                                            ✓ Đã ký: <?= e($hs['so_van_ban_xin_loi'] ?? '') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Chưa lập Mẫu 04</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((int)$hs['da_co_van_ban_xin_loi'] === 1): ?>
                                        <a href="<?= e(url('/admin/sla/in-mau-04/' . $hs['ho_so_id'])) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                            In Mẫu 04
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= e(url('/admin/sla/van-ban-xin-loi/' . $hs['ho_so_id'])) ?>" class="btn btn-sm btn-danger">
                                            Soạn & Ký Mẫu 04
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
