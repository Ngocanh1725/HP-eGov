<?php $this->layout('layouts/admin', ['title' => 'Báo cáo Phối hợp Liên thông']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                🔄 Báo cáo Luân chuyển & Phối hợp Liên thông giữa các Cơ quan
            </h1>
            <p class="text-muted mb-0">
                Thống kê các tuyến luân chuyển hồ sơ liên thông ngang (giữa các Sở) và liên thông dọc (UBND TP ↔ Quận/Huyện ↔ Xã/Phường)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/lien-thong', ['export' => 'csv'])) ?>" class="btn btn-success btn-sm">
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
            CÁC TUYẾN LUÂN CHUYỂN PHỐI HỢP ĐIỆN TỬ
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th class="text-start">Cơ quan chuyển</th>
                        <th class="text-start">Cơ quan tiếp nhận phối hợp</th>
                        <th>Số lượt luân chuyển</th>
                        <th>Thời gian xử lý TB (Giờ)</th>
                        <th>Đánh giá phối hợp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="py-4 text-muted">Không có dữ liệu luân chuyển liên thông.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="text-start fw-semibold text-primary"><?= e($r['don_vi_gui']) ?></td>
                                <td class="text-start fw-semibold text-success"><?= e($r['don_vi_nhan']) ?></td>
                                <td class="fw-bold"><?= (int)$r['so_luot_chuyen'] ?></td>
                                <td><?= number_format((float)$r['thoi_gian_trung_binh_gio'], 1) ?> giờ</td>
                                <td>
                                    <span class="badge bg-success">Phối hợp tốt</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
