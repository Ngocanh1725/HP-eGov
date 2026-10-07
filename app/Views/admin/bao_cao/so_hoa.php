<?php $this->layout('layouts/admin', ['title' => 'Báo cáo Số hóa Nghị định 107/2021/NĐ-CP']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                💾 Báo cáo Kết quả Số hóa Hồ sơ & Kết quả TTHC (NĐ 107/2021/NĐ-CP)
            </h1>
            <p class="text-muted mb-0">
                <strong>Kỳ báo cáo:</strong> <?= e($date_range['label']) ?> (Từ <?= date('d/m/Y', strtotime($date_range['tu_ngay'])) ?> đến <?= date('d/m/Y', strtotime($date_range['den_ngay'])) ?>)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/so-hoa', array_merge($filters, ['export' => 'csv']))) ?>" class="btn btn-success btn-sm">
                📥 Xuất CSV (Excel)
            </a>
            <a href="<?= e(url('/admin/bao-cao/so-hoa', array_merge($filters, ['print' => '1']))) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                🖨️ In Báo cáo (A4 ngang)
            </a>
            <a href="<?= e(url('/admin/bao-cao')) ?>" class="btn btn-outline-secondary btn-sm">
                ← Danh mục báo cáo
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= e(url('/admin/bao-cao/so-hoa')) ?>" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Kỳ báo cáo:</label>
                <select name="ky" class="form-select form-select-sm">
                    <option value="thang_nay" <?= ($filters['ky'] ?? '') === 'thang_nay' ? 'selected' : '' ?>>Tháng này</option>
                    <option value="thang_truoc" <?= ($filters['ky'] ?? '') === 'thang_truoc' ? 'selected' : '' ?>>Tháng trước</option>
                    <option value="quy_nay" <?= ($filters['ky'] ?? '') === 'quy_nay' ? 'selected' : '' ?>>Quý này</option>
                    <option value="nam_nay" <?= ($filters['ky'] ?? '') === 'nam_nay' ? 'selected' : '' ?>>Năm nay</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Cơ quan / Đơn vị:</label>
                <select name="don_vi_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả đơn vị --</option>
                    <?php foreach ($don_vi_list as $dv): ?>
                        <option value="<?= $dv['id'] ?>" <?= ((string)($filters['don_vi_id'] ?? '') === (string)$dv['id']) ? 'selected' : '' ?>>
                            <?= e($dv['ten_don_vi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    🔍 Xem số liệu
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-primary">
            TIẾN ĐỘ SỐ HÓA HÀNH CHÍNH THEO CÁC ĐƠN VỊ TRÊN ĐỊA BÀN THÀNH PHỐ
        </h5>
        <span class="badge bg-info text-white">Chỉ tiêu bắt buộc: 100%</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th class="text-start">Cơ quan / Đơn vị</th>
                        <th>Tổng tiếp nhận</th>
                        <th>Số hóa thành phần (Đầu vào)</th>
                        <th>Tỷ lệ SH thành phần (%)</th>
                        <th>Số hóa kết quả điện tử</th>
                        <th>Tỷ lệ SH kết quả (%)</th>
                        <th>Đồng bộ Kho dữ liệu cá nhân</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="py-4 text-muted">Không có dữ liệu.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="text-start fw-semibold"><?= e($r['ten_don_vi']) ?></td>
                                <td class="fw-bold"><?= number_format((int)$r['tong_tiep_nhan']) ?></td>
                                <td class="text-primary"><?= number_format((int)$r['so_hoa_thanh_phan']) ?></td>
                                <td class="fw-bold text-primary"><?= number_format((float)$r['ty_le_thanh_phan'], 1) ?>%</td>
                                <td class="text-success"><?= number_format((int)$r['so_hoa_ket_qua']) ?></td>
                                <td class="fw-bold text-success"><?= number_format((float)$r['ty_le_ket_qua'], 1) ?>%</td>
                                <td><?= number_format((int)$r['dong_bo_kho_du_lieu']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
