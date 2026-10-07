<?php $this->layout('layouts/admin', ['title' => 'Báo cáo Hồ sơ Quá hạn & Mẫu 04']) ?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 fw-bold text-danger mb-1">
                ⚠️ Báo cáo Hồ sơ Quá hạn & Ban hành Văn bản xin lỗi (Mẫu số 04)
            </h1>
            <p class="text-muted mb-0">
                Theo quy định tại Điều 12 Nghị định 61/2018/NĐ-CP và Thông tư 01/2018/TT-VPCP của Văn phòng Chính phủ
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(url('/admin/bao-cao/qua-han', array_merge($filters, ['export' => 'csv']))) ?>" class="btn btn-success btn-sm">
                📥 Xuất CSV (Excel)
            </a>
            <a href="<?= e(url('/admin/bao-cao/qua-han', array_merge($filters, ['print' => '1']))) ?>" class="btn btn-outline-danger btn-sm" target="_blank">
                🖨️ In Báo cáo (A4 ngang)
            </a>
            <a href="<?= e(url('/admin/bao-cao')) ?>" class="btn btn-outline-secondary btn-sm">
                ← Danh mục báo cáo
            </a>
        </div>
    </div>
</div>

<!-- Thống kê nhanh 2 thẻ -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 border-start border-4 border-danger shadow-sm bg-white rounded-3">
            <span class="text-muted small fw-semibold text-uppercase">Tổng số hồ sơ đang quá hạn</span>
            <h2 class="h3 fw-bold text-danger mb-0 mt-1"><?= (int)$tong_qua_han ?> hồ sơ</h2>
            <small class="text-muted">Đang trong vùng SLA ĐỎ (t &gt; T)</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 border-start border-4 border-success shadow-sm bg-white rounded-3">
            <span class="text-muted small fw-semibold text-uppercase">Đã ban hành Văn bản xin lỗi Mẫu 04</span>
            <h2 class="h3 fw-bold text-success mb-0 mt-1"><?= (int)$da_co_mau_04 ?> / <?= (int)$tong_qua_han ?> hồ sơ</h2>
            <small class="text-muted">Đã được Lãnh đạo cơ quan ký số điện tử</small>
        </div>
    </div>
</div>

<!-- Bảng chi tiết hồ sơ quá hạn -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-danger">
            DANH SÁCH CHI TIẾT HỒ SƠ QUÁ HẠN & TRẠNG THÁI MẪU SỐ 04
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 45px;">STT</th>
                        <th>Mã hồ sơ</th>
                        <th>Tên thủ tục hành chính</th>
                        <th>Đơn vị thụ lý</th>
                        <th>Chuyên viên</th>
                        <th>Người nộp</th>
                        <th>Ngày hẹn trả</th>
                        <th class="text-center">Số giờ trễ</th>
                        <th class="text-center">Văn bản xin lỗi Mẫu 04</th>
                        <th>Lý do quá hạn & Hạn mới</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="py-4 text-center text-muted">
                                🎉 Không có hồ sơ nào bị quá hạn!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $idx => $r): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <span class="fw-bold text-danger font-monospace"><?= e($r['ma_ho_so']) ?></span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 220px;" title="<?= e($r['ten_tthc']) ?>">
                                        <?= e($r['ten_tthc']) ?>
                                    </div>
                                </td>
                                <td><?= e($r['ten_don_vi']) ?></td>
                                <td><?= e($r['ten_chuyen_vien'] ?? 'Chưa phân công') ?></td>
                                <td><?= e($r['nguoi_nop_ho_ten']) ?></td>
                                <td><?= date('H:i d/m/Y', strtotime($r['ngay_hen_tra'])) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?= (int)$r['so_gio_tre_han'] ?>h</span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$r['da_co_mau_04'] === 1): ?>
                                        <span class="badge bg-success">
                                            ✓ Đã có (Số: <?= e($r['so_van_ban'] ?? '') ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Chưa ban hành</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$r['da_co_mau_04'] === 1): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?= e($r['ly_do_qua_han'] ?? '') ?>">
                                            <?= e($r['ly_do_qua_han'] ?? '') ?>
                                        </div>
                                        <div class="small text-primary">Hạn mới: <?= date('d/m/Y', strtotime($r['ngay_hen_moi'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa có giải trình</span>
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
