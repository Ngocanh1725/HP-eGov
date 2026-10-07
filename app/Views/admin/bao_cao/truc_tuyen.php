<?php $this->layout('layouts/admin', ['title' => 'Báo cáo DVC Trực tuyến Toàn trình']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                🌐 Báo cáo Hồ sơ Dịch vụ công Trực tuyến Toàn trình & Một phần
            </h1>
            <p class="text-muted mb-0">
                Đánh giá chỉ tiêu chuyển đổi số và cung cấp dịch vụ công trực tuyến của TP. Hải Phòng (Chỉ tiêu toàn trình ≥ 80%)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/truc-tuyen', ['export' => 'csv'])) ?>" class="btn btn-success btn-sm">
                📥 Xuất CSV (Excel)
            </a>
            <a href="<?= e(url('/admin/bao-cao')) ?>" class="btn btn-outline-secondary btn-sm">
                ← Danh mục báo cáo
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="card-title mb-0 fw-bold text-primary">
            TỶ LỆ NỘP HỒ SƠ TRỰC TUYẾN THEO MỨC ĐỘ DỊCH VỤ CÔNG TẠI CÁC ĐƠN VỊ
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th class="text-start">Cơ quan / Đơn vị</th>
                        <th>Tổng số hồ sơ</th>
                        <th>Nộp trực tuyến</th>
                        <th>DVC Toàn trình</th>
                        <th>Tỷ lệ Toàn trình (%)</th>
                        <th>DVC Một phần</th>
                        <th>Nộp trực tiếp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="py-4 text-muted">Không có dữ liệu.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="text-start fw-semibold"><?= e($r['ten_don_vi']) ?></td>
                                <td class="fw-bold"><?= number_format((int)$r['tong_so']) ?></td>
                                <td class="text-primary fw-bold"><?= number_format((int)$r['tong_truc_tuyen']) ?></td>
                                <td class="text-success fw-bold"><?= number_format((int)$r['toan_trinh']) ?></td>
                                <td class="fw-bold <?= ($r['ty_le_toan_trinh'] >= 80.0) ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format((float)$r['ty_le_toan_trinh'], 1) ?>%
                                </td>
                                <td><?= number_format((int)$r['mot_phan']) ?></td>
                                <td><?= number_format((int)$r['truc_tiep']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
