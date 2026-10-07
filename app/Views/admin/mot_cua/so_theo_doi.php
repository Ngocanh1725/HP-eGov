<?php
/**
 * HP-eGov - Sổ theo dõi hồ sơ TTHC (Mẫu số 06 - Thông tư 01/2018/TT-VPCP)
 * Hỗ trợ lọc nâng cao, in khổ ngang A4 Landscape, xuất file CSV định dạng UTF-8 BOM
 */
$title = 'Sổ theo dõi hồ sơ - Mẫu 06 TT 01/2018/TT-VPCP';
$donVi = $donVi ?? [];
$danhSachHoSo = $danhSachHoSo ?? [];
$danhSachTthc = $danhSachTthc ?? [];
$filters = $filters ?? [];
?>

<div class="container-fluid py-4">
    <!-- Tiêu đề & Hành động -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= route('admin.motcua.dashboard') ?>">Một cửa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sổ theo dõi hồ sơ</li>
                </ol>
            </nav>
            <h1 class="h3 font-weight-bold text-gray-800 mb-0">Sổ theo dõi hồ sơ giải quyết TTHC</h1>
            <p class="text-muted small mb-0">Mẫu số 06 ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Nút xuất CSV -->
            <a href="<?= route('admin.motcua.exportSoTheoDoi', $filters) ?>" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất CSV (Excel)
            </a>
            <!-- Nút in ấn -->
            <button onclick="window.print()" class="btn btn-primary shadow-sm">
                <i class="bi bi-printer me-1"></i> In Sổ theo dõi (A4 ngang)
            </button>
            <a href="<?= route('admin.motcua.dashboard') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Khối bộ lọc tìm kiếm -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 d-print-none">
        <div class="card-body p-3">
            <form action="<?= route('admin.motcua.soTheoDoi') ?>" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small font-weight-semibold">Từ ngày tiếp nhận</label>
                    <input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['tu_ngay'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-semibold">Đến ngày</label>
                    <input type="date" name="den_ngay" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['den_ngay'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small font-weight-semibold">Thủ tục hành chính</label>
                    <select name="tthc_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả thủ tục hành chính --</option>
                        <?php foreach ($danhSachTthc as $tt): ?>
                            <option value="<?= $tt['id'] ?>" <?= ($filters['tthc_id'] ?? '') == $tt['id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($tt['ma_tthc']) ?>] <?= htmlspecialchars($tt['ten_tthc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small font-weight-semibold">Trạng thái hồ sơ</label>
                    <select name="trang_thai" class="form-select form-select-sm">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="MOI_TIEP_NHAN" <?= ($filters['trang_thai'] ?? '') === 'MOI_TIEP_NHAN' ? 'selected' : '' ?>>Mới tiếp nhận</option>
                        <option value="DANG_XU_LY" <?= ($filters['trang_thai'] ?? '') === 'DANG_XU_LY' ? 'selected' : '' ?>>Đang giải quyết</option>
                        <option value="HOAN_THANH" <?= ($filters['trang_thai'] ?? '') === 'HOAN_THANH' ? 'selected' : '' ?>>Đã hoàn thành</option>
                        <option value="DA_TRA_KET_QUA" <?= ($filters['trang_thai'] ?? '') === 'DA_TRA_KET_QUA' ? 'selected' : '' ?>>Đã trả kết quả</option>
                        <option value="CHO_BO_SUNG" <?= ($filters['trang_thai'] ?? '') === 'CHO_BO_SUNG' ? 'selected' : '' ?>>Yêu cầu bổ sung</option>
                        <option value="TU_CHOI" <?= ($filters['trang_thai'] ?? '') === 'TU_CHOI' ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill">
                        <i class="bi bi-filter me-1"></i> Lọc dữ liệu
                    </button>
                    <a href="<?= route('admin.motcua.soTheoDoi') ?>" class="btn btn-sm btn-light border" title="Đặt lại bộ lọc">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Khung Sổ theo dõi in ấn & hiển thị -->
    <div class="card border-0 shadow-sm rounded-3 p-4 so-theo-doi-container">
        <!-- Tiêu đề in -->
        <div class="text-center mb-4">
            <h5 class="text-uppercase mb-1" style="font-size: 13pt;"><?= htmlspecialchars($donVi['ten_don_vi'] ?? 'BỘ PHẬN TIẾP NHẬN VÀ TRẢ KẾT QUẢ') ?></h5>
            <h3 class="font-weight-bold text-uppercase mb-1" style="font-size: 17pt;">SỔ THEO DÕI HỒ SƠ</h3>
            <div class="font-italic text-muted" style="font-size: 11pt;">(Mẫu số 06 ban hành kèm theo Thông tư số 01/2018/TT-VPCP ngày 23/11/2018 của Văn phòng Chính phủ)</div>
            <?php if (!empty($filters['tu_ngay']) || !empty($filters['den_ngay'])): ?>
                <div class="small mt-2">
                    Thời gian theo dõi: 
                    <?= !empty($filters['tu_ngay']) ? 'từ ngày ' . date('d/m/Y', strtotime($filters['tu_ngay'])) : '' ?>
                    <?= !empty($filters['den_ngay']) ? ' đến ngày ' . date('d/m/Y', strtotime($filters['den_ngay'])) : '' ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle text-dark table-so" style="font-size: 11pt; border-color: #333;">
                <thead class="text-center align-middle bg-light" style="background-color: #f2f2f2 !important;">
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th style="width: 140px;">Mã số hồ sơ</th>
                        <th style="width: 160px;">Tên tổ chức, cá nhân</th>
                        <th style="width: 180px;">Địa chỉ, Số điện thoại</th>
                        <th>Tên thủ tục hành chính</th>
                        <th style="width: 110px;">Thời gian tiếp nhận</th>
                        <th style="width: 80px;">Thời hạn (ngày)</th>
                        <th style="width: 110px;">Thời gian hẹn trả</th>
                        <th style="width: 110px;">Thời gian trả thực tế</th>
                        <th style="width: 120px;">Ký nhận / Vận đơn</th>
                        <th style="width: 100px;">Ghi chú</th>
                    </tr>
                    <tr class="text-muted font-italic" style="font-size: 9pt;">
                        <th>(1)</th>
                        <th>(2)</th>
                        <th>(3)</th>
                        <th>(4)</th>
                        <th>(5)</th>
                        <th>(6)</th>
                        <th>(7)</th>
                        <th>(8)</th>
                        <th>(9)</th>
                        <th>(10)</th>
                        <th>(11)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachHoSo)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                Không có dữ liệu hồ sơ nào trong khoảng thời gian đã chọn.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $stt = 1;
                        foreach ($danhSachHoSo as $hs): 
                            $ngayNhan = !empty($hs['ngay_tiep_nhan']) ? date('d/m/Y H:i', strtotime($hs['ngay_tiep_nhan'])) : (!empty($hs['ngay_nop']) ? date('d/m/Y H:i', strtotime($hs['ngay_nop'])) : '---');
                            $ngayHen = !empty($hs['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hs['ngay_hen_tra'])) : '---';
                            $ngayTra = !empty($hs['ngay_tra_ket_qua']) ? date('d/m/Y H:i', strtotime($hs['ngay_tra_ket_qua'])) : '';
                        ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $stt++ ?></td>
                                <td class="font-monospace font-weight-bold"><?= htmlspecialchars($hs['ma_ho_so'] ?? '') ?></td>
                                <td class="font-weight-semibold">
                                    <?= htmlspecialchars($hs['nguoi_nop_ho_ten'] ?? '') ?>
                                    <?php if (!empty($hs['nguoi_nop_cccd'])): ?>
                                        <div class="small text-muted font-monospace">CCCD: <?= htmlspecialchars($hs['nguoi_nop_cccd']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small"><?= htmlspecialchars($hs['nguoi_nop_dia_chi'] ?? '---') ?></div>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($hs['nguoi_nop_dien_thoai'] ?? '---') ?></div>
                                </td>
                                <td>
                                    <div class="small font-weight-semibold"><?= htmlspecialchars($hs['ten_tthc'] ?? '') ?></div>
                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($hs['ma_tthc'] ?? '') ?></div>
                                </td>
                                <td class="text-center small"><?= $ngayNhan ?></td>
                                <td class="text-center font-weight-bold"><?= htmlspecialchars($hs['thoi_han_ngay'] ?? $hs['thoi_han_giai_quyet'] ?? '---') ?></td>
                                <td class="text-center small font-weight-semibold text-primary"><?= $ngayHen ?></td>
                                <td class="text-center small <?= !empty($ngayTra) ? 'text-success font-weight-bold' : 'text-muted' ?>">
                                    <?= !empty($ngayTra) ? $ngayTra : 'Chưa trả' ?>
                                </td>
                                <td class="text-center small">
                                    <?php if (!empty($hs['ma_van_don_buu_chinh'])): ?>
                                        <div class="font-monospace font-weight-bold text-info"><?= htmlspecialchars($hs['ma_van_don_buu_chinh']) ?></div>
                                        <span class="badge bg-light text-secondary border">VNPost</span>
                                    <?php elseif (!empty($hs['nguoi_nhan_ho_ten'])): ?>
                                        <div class="font-weight-bold"><?= htmlspecialchars($hs['nguoi_nhan_ho_ten']) ?></div>
                                        <span class="badge bg-light text-secondary border">Đã ký nhận</span>
                                    <?php else: ?>
                                        <span class="text-muted font-italic">Chờ nhận</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <span class="badge bg-secondary-subtle text-dark border"><?= htmlspecialchars($hs['trang_thai'] ?? '') ?></span>
                                    <?php if (!empty($hs['ghi_chu'])): ?>
                                        <div class="small text-muted text-truncate mt-1" style="max-width: 100px;"><?= htmlspecialchars($hs['ghi_chu']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phần ký tên cuối sổ (khi in) -->
        <div class="row mt-5 pt-3 d-none d-print-flex text-center" style="font-size: 12pt;">
            <div class="col-6">
                <strong>NGƯỜI LẬP SỔ</strong><br>
                <span class="font-italic">(Ký, ghi rõ họ tên)</span>
                <br><br><br><br>
                <strong><?= htmlspecialchars($_SESSION['user']['ho_ten'] ?? 'Cán bộ Một cửa') ?></strong>
            </div>
            <div class="col-6">
                <strong>TRƯỞNG BỘ PHẬN MỘT CỬA</strong><br>
                <span class="font-italic">(Ký, đóng dấu hoặc xác nhận)</span>
                <br><br><br><br>
                <strong>.......................................................</strong>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 10mm 15mm 10mm 15mm;
    }
    body {
        background: none !important;
        font-family: "Times New Roman", Times, serif !important;
        color: #000 !important;
    }
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    .so-theo-doi-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .table-so th, .table-so td {
        border: 1px solid #000 !important;
        padding: 4px 6px !important;
        font-size: 10pt !important;
    }
    .table-so thead {
        background-color: #eee !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .d-print-none {
        display: none !important;
    }
    .d-print-flex {
        display: flex !important;
    }
}
</style>
