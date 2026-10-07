<?php $this->layout('layouts/admin', ['title' => 'Báo cáo Đo lường Hài lòng SIPAS']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-success mb-1">
                ⭐ Báo cáo Đo lường Sự Hài lòng của Người dân & Tổ chức (SIPAS)
            </h1>
            <p class="text-muted mb-0">
                Theo 5 nhóm tiêu chí chuẩn ban hành tại Quyết định số 876/QĐ-BNV của Bộ Nội vụ
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/hai-long', ['export' => 'csv'])) ?>" class="btn btn-success btn-sm">
                📥 Xuất CSV (Excel)
            </a>
            <a href="<?= e(url('/admin/bao-cao/hai-long', ['print' => '1'])) ?>" class="btn btn-outline-success btn-sm" target="_blank">
                🖨️ In Báo cáo (A4 ngang)
            </a>
            <a href="<?= e(url('/admin/bao-cao')) ?>" class="btn btn-outline-secondary btn-sm">
                ← Danh mục báo cáo
            </a>
        </div>
    </div>
</div>

<!-- 5 Tiêu chí SIPAS Thẻ Card -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-white rounded-3 text-center">
            <small class="text-muted fw-semibold">1. Tiếp cận dịch vụ</small>
            <h3 class="fw-bold text-primary mb-0 mt-2"><?= $summary['tb_tiep_can'] ?></h3>
            <span class="small text-muted">/ 5.0 điểm</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-white rounded-3 text-center">
            <small class="text-muted fw-semibold">2. Thủ tục hành chính</small>
            <h3 class="fw-bold text-primary mb-0 mt-2"><?= $summary['tb_thu_tuc'] ?></h3>
            <span class="small text-muted">/ 5.0 điểm</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-white rounded-3 text-center">
            <small class="text-muted fw-semibold">3. Tinh thần công chức</small>
            <h3 class="fw-bold text-primary mb-0 mt-2"><?= $summary['tb_cong_chuc'] ?></h3>
            <span class="small text-muted">/ 5.0 điểm</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-white rounded-3 text-center">
            <small class="text-muted fw-semibold">4. Tiến độ, kết quả</small>
            <h3 class="fw-bold text-primary mb-0 mt-2"><?= $summary['tb_ket_qua'] ?></h3>
            <span class="small text-muted">/ 5.0 điểm</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-white rounded-3 text-center">
            <small class="text-muted fw-semibold">5. Tiếp nhận phản ánh</small>
            <h3 class="fw-bold text-primary mb-0 mt-2"><?= $summary['tb_phan_anh'] ?></h3>
            <span class="small text-muted">/ 5.0 điểm</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card p-3 shadow-sm border-0 bg-success text-white rounded-3 text-center">
            <small class="fw-semibold">TỶ LỆ HÀI LÒNG</small>
            <h3 class="fw-bold text-white mb-0 mt-2"><?= $ty_le_hai_long ?>%</h3>
            <span class="small"><?= $dat_chi_tieu ? '✓ Đạt chỉ tiêu (≥92%)' : 'Chưa đạt' ?></span>
        </div>
    </div>
</div>

<!-- Bảng chi tiết đánh giá gần nhất -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-success">
            DANH SÁCH Ý KIẾN KHẢO SÁT HÀI LÒNG CỦA CÔNG DÂN
        </h5>
        <span class="badge bg-success"><?= count($list) ?> lượt đánh giá gần nhất</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 45px;">STT</th>
                        <th>Mã hồ sơ</th>
                        <th>Thủ tục hành chính</th>
                        <th>Cơ quan tiếp nhận</th>
                        <th class="text-center">Điểm TB (1-5)</th>
                        <th>Ý kiến phản hồi / Đóng góp</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr>
                            <td colspan="7" class="py-4 text-center text-muted">Chưa có khảo sát nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list as $idx => $item): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="fw-bold text-primary font-monospace"><?= e($item['ma_ho_so']) ?></td>
                                <td><?= e($item['ten_tthc']) ?></td>
                                <td><?= e($item['ten_don_vi']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fw-bold">⭐ <?= $item['diem_trung_binh'] ?></span>
                                </td>
                                <td><?= e($item['y_kien_dong_gop'] ?? 'Rất hài lòng với thái độ phục vụ.') ?></td>
                                <td class="text-muted"><?= date('H:i d/m/Y', strtotime($item['ngay_danh_gia'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
