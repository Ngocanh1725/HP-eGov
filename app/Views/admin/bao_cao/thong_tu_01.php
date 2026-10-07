<?php $this->layout('layouts/admin', ['title' => 'Báo cáo Thông tư 01/2018/TT-VPCP']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                📑 Báo cáo Tình hình Giải quyết TTHC (Mẫu biểu 01 TT 01/2018/TT-VPCP)
            </h1>
            <p class="text-muted mb-0">
                <strong>Kỳ báo cáo:</strong> <?= e($date_range['label']) ?> (Từ <?= date('d/m/Y', strtotime($date_range['tu_ngay'])) ?> đến <?= date('d/m/Y', strtotime($date_range['den_ngay'])) ?>)
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/thong-tu-01', array_merge($filters, ['export' => 'csv']))) ?>" class="btn btn-success btn-sm">
                📥 Xuất CSV (Excel)
            </a>
            <a href="<?= e(url('/admin/bao-cao/thong-tu-01', array_merge($filters, ['print' => '1']))) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                🖨️ In Báo cáo (A4 ngang)
            </a>
            <a href="<?= e(url('/admin/bao-cao')) ?>" class="btn btn-outline-secondary btn-sm">
                ← Danh mục báo cáo
            </a>
        </div>
    </div>
</div>

<!-- Bộ lọc kỳ, đơn vị, lĩnh vực -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= e(url('/admin/bao-cao/thong-tu-01')) ?>" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Kỳ báo cáo:</label>
                <select name="ky" class="form-select form-select-sm" id="selectKy" onchange="toggleCustomDate()">
                    <option value="thang_nay" <?= ($filters['ky'] ?? '') === 'thang_nay' ? 'selected' : '' ?>>Tháng này</option>
                    <option value="thang_truoc" <?= ($filters['ky'] ?? '') === 'thang_truoc' ? 'selected' : '' ?>>Tháng trước</option>
                    <option value="quy_nay" <?= ($filters['ky'] ?? '') === 'quy_nay' ? 'selected' : '' ?>>Quý này</option>
                    <option value="nam_nay" <?= ($filters['ky'] ?? '') === 'nam_nay' ? 'selected' : '' ?>>Năm nay</option>
                    <option value="tuy_chon" <?= ($filters['ky'] ?? '') === 'tuy_chon' ? 'selected' : '' ?>>Tùy chọn khoảng ngày</option>
                </select>
            </div>
            <div class="col-md-2" id="divTuNgay" style="<?= ($filters['ky'] ?? '') === 'tuy_chon' ? '' : 'display:none;' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Từ ngày:</label>
                <input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?= e($filters['tu_ngay'] ?? $date_range['tu_ngay']) ?>">
            </div>
            <div class="col-md-2" id="divDenNgay" style="<?= ($filters['ky'] ?? '') === 'tuy_chon' ? '' : 'display:none;' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">Đến ngày:</label>
                <input type="date" name="den_ngay" class="form-control form-control-sm" value="<?= e($filters['den_ngay'] ?? $date_range['den_ngay']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Đơn vị / Cơ quan:</label>
                <select name="don_vi_id" class="form-select form-select-sm">
                    <option value="">-- Toàn thành phố (Tất cả) --</option>
                    <?php foreach ($don_vi_list as $dv): ?>
                        <option value="<?= $dv['id'] ?>" <?= ((string)($filters['don_vi_id'] ?? '') === (string)$dv['id']) ? 'selected' : '' ?>>
                            <?= e($dv['ten_don_vi']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    🔍 Xem số liệu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bảng số liệu Thông tư 01/2018 (Biểu 01) -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-primary">
            Biểu mẫu 01: TỔNG HỢP TÌNH HÌNH TIẾP NHẬN VÀ GIẢI QUYẾT TTHC
        </h5>
        <span class="badge bg-primary">Đơn vị tính: Hồ sơ</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0 text-center small">
                <thead class="table-light align-middle">
                    <tr>
                        <th rowspan="2" style="width: 45px;">STT</th>
                        <th rowspan="2" style="width: 120px;">Mã định danh</th>
                        <th rowspan="2" class="text-start" style="min-width: 220px;">Cơ quan / Đơn vị thực hiện</th>
                        <th rowspan="2" style="width: 65px;">Tồn kỳ trước</th>
                        <th colspan="3">Tiếp nhận mới trong kỳ</th>
                        <th colspan="4">Kết quả đã giải quyết</th>
                        <th colspan="3">Đang giải quyết</th>
                        <th rowspan="2" style="width: 90px;">Tỷ lệ đúng hạn (%)</th>
                    </tr>
                    <tr>
                        <th style="width: 65px;">Trực tiếp</th>
                        <th style="width: 65px;">Trực tuyến</th>
                        <th style="width: 70px;" class="fw-bold bg-light">Tổng nhận</th>
                        <th style="width: 65px;">Trước hạn</th>
                        <th style="width: 65px;">Đúng hạn</th>
                        <th style="width: 65px;">Quá hạn</th>
                        <th style="width: 70px;" class="fw-bold bg-light">Tổng giải quyết</th>
                        <th style="width: 65px;">Trong hạn</th>
                        <th style="width: 65px;">Quá hạn</th>
                        <th style="width: 70px;" class="fw-bold bg-light">Tổng đang xử lý</th>
                    </tr>
                    <tr class="text-muted" style="font-size: 10px;">
                        <th>(1)</th>
                        <th>(2)</th>
                        <th>(3)</th>
                        <th>(4)</th>
                        <th>(5)</th>
                        <th>(6)</th>
                        <th>(7)=(5)+(6)</th>
                        <th>(8)</th>
                        <th>(9)</th>
                        <th>(10)</th>
                        <th>(11)=(8)+(9)+(10)</th>
                        <th>(12)</th>
                        <th>(13)</th>
                        <th>(14)=(12)+(13)</th>
                        <th>(15)=(8+9)/(11)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="15" class="py-4 text-muted">
                                Không phát sinh hồ sơ nào trong khoảng thời gian đã chọn.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td class="font-monospace"><?= e($r['ma_dinh_danh']) ?></td>
                                <td class="text-start fw-semibold text-dark"><?= e($r['ten_don_vi']) ?></td>
                                <td><?= number_format((int)$r['ton_ky_truoc']) ?></td>
                                <td><?= number_format((int)$r['tiep_nhan_truc_tiep']) ?></td>
                                <td><?= number_format((int)$r['tiep_nhan_truc_tuyen']) ?></td>
                                <td class="fw-bold bg-light"><?= number_format((int)$r['tong_so_nhan']) ?></td>
                                <td class="text-success"><?= number_format((int)$r['giai_quyet_truoc_han']) ?></td>
                                <td class="text-primary"><?= number_format((int)$r['giai_quyet_dung_han']) ?></td>
                                <td class="text-danger"><?= number_format((int)$r['giai_quyet_qua_han']) ?></td>
                                <td class="fw-bold bg-light text-success"><?= number_format((int)$r['tong_da_giai_quyet']) ?></td>
                                <td><?= number_format((int)$r['dang_xu_ly_trong_han']) ?></td>
                                <td class="text-danger fw-bold"><?= number_format((int)$r['dang_xu_ly_qua_han']) ?></td>
                                <td class="fw-bold bg-light"><?= number_format((int)$r['tong_dang_giai_quyet']) ?></td>
                                <td class="fw-bold <?= ($r['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format((float)$r['ty_le_dung_han'], 1) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Hàng Tổng cộng -->
                        <tr class="table-warning fw-bold fs-6">
                            <td colspan="3" class="text-center">TỔNG CỘNG TOÀN THÀNH PHỐ</td>
                            <td><?= number_format((int)$totals['ton_ky_truoc']) ?></td>
                            <td><?= number_format((int)$totals['tiep_nhan_truc_tiep']) ?></td>
                            <td><?= number_format((int)$totals['tiep_nhan_truc_tuyen']) ?></td>
                            <td class="text-primary"><?= number_format((int)$totals['tong_so_nhan']) ?></td>
                            <td class="text-success"><?= number_format((int)$totals['giai_quyet_truoc_han']) ?></td>
                            <td class="text-primary"><?= number_format((int)$totals['giai_quyet_dung_han']) ?></td>
                            <td class="text-danger"><?= number_format((int)$totals['giai_quyet_qua_han']) ?></td>
                            <td class="text-success"><?= number_format((int)$totals['tong_da_giai_quyet']) ?></td>
                            <td><?= number_format((int)$totals['dang_xu_ly_trong_han']) ?></td>
                            <td class="text-danger"><?= number_format((int)$totals['dang_xu_ly_qua_han']) ?></td>
                            <td><?= number_format((int)$totals['tong_dang_giai_quyet']) ?></td>
                            <td class="<?= ($totals['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?>">
                                <?= number_format((float)$totals['ty_le_dung_han'], 1) ?>%
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCustomDate() {
    const val = document.getElementById('selectKy').value;
    const tuNgay = document.getElementById('divTuNgay');
    const denNgay = document.getElementById('divDenNgay');
    if (val === 'tuy_chon') {
        tuNgay.style.display = 'block';
        denNgay.style.display = 'block';
    } else {
        tuNgay.style.display = 'none';
        denNgay.style.display = 'none';
    }
}
</script>
