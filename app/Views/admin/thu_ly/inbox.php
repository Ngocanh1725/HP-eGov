<?php
/**
 * HP-eGov - Hộp việc Thụ lý & Phối hợp Liên thông (PH03)
 * Dành cho Chuyên viên thẩm định (CHUYEN_VIEN) và Lãnh đạo phòng (LD_PHONG)
 */
$this->layout('layouts/admin', ['title' => $pageTitle]);
$this->startSection('content');
?>

<div class="container-fluid py-3">
    <!-- Tiêu đề trang -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('/admin/dashboard') ?>">Bàn làm việc</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thụ lý & Liên thông</li>
                </ol>
            </nav>
            <h1 class="h3 font-weight-bold text-gray-800 mb-0">Hộp việc Thụ lý & Phối hợp Liên thông</h1>
            <p class="text-muted small mb-0">
                Đơn vị công tác: <strong class="text-primary"><?= htmlspecialchars($currentUser['ten_don_vi'] ?? 'Cơ quan hành chính') ?></strong> 
                | Cán bộ: <strong><?= htmlspecialchars($currentUser['ho_ten'] ?? '') ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/admin/thu-ly?tab=' . $tab) ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
            </a>
        </div>
    </div>

    <!-- Thông báo Flash nếu có -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Thanh 6 Tabs Hộp việc -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-2">
            <ul class="nav nav-pills card-header-pills flex-wrap gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'cho_xu_ly' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'cho_xu_ly']) ?>">
                        <i class="bi bi-inbox me-1"></i> Chờ xử lý
                        <span class="badge bg-secondary ms-1"><?= $counts['cho_xu_ly'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'dang_xu_ly' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'dang_xu_ly']) ?>">
                        <i class="bi bi-hourglass-split me-1"></i> Đang thụ lý
                        <span class="badge bg-primary ms-1"><?= $counts['dang_xu_ly'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'da_chuyen' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'da_chuyen']) ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i> Đã chuyển
                        <span class="badge bg-light text-dark border ms-1"><?= $counts['da_chuyen'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'phoi_hop' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'phoi_hop']) ?>">
                        <i class="bi bi-people me-1"></i> Phối hợp liên ngành
                        <span class="badge bg-info ms-1"><?= $counts['phoi_hop'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'sap_qua_han' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'sap_qua_han']) ?>">
                        <i class="bi bi-exclamation-triangle text-warning me-1"></i> Sắp quá hạn (SLA Vàng)
                        <span class="badge bg-warning text-dark ms-1"><?= $counts['sap_qua_han'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'qua_han' ? 'active' : '' ?>" href="<?= url('/admin/thu-ly', ['tab' => 'qua_han']) ?>">
                        <i class="bi bi-x-octagon text-danger me-1"></i> Quá hạn (SLA Đỏ)
                        <span class="badge bg-danger ms-1"><?= $counts['qua_han'] ?></span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="card-body p-3 bg-light border-bottom">
            <form action="<?= url('/admin/thu-ly') ?>" method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control bg-white" placeholder="Tìm theo mã hồ sơ, người nộp, CCCD..." value="<?= htmlspecialchars($q) ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <select name="tthc_id" class="form-select form-select-sm bg-white">
                        <option value="">-- Tất cả thủ tục hành chính --</option>
                        <?php foreach ($tthcList as $tt): ?>
                            <option value="<?= $tt['id'] ?>" <?= $tthcId == $tt['id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($tt['ma_tthc']) ?>] <?= htmlspecialchars($tt['ten_tthc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-filter me-1"></i> Lọc
                    </button>
                    <?php if (!empty($q) || $tthcId > 0): ?>
                        <a href="<?= url('/admin/thu-ly', ['tab' => $tab]) ?>" class="btn btn-light btn-sm border" title="Bỏ lọc">
                            <i class="bi bi-x"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bảng danh sách hồ sơ -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small font-weight-bold">
                    <tr>
                        <th style="width: 40px;" class="text-center">STT</th>
                        <th style="width: 180px;">Mã hồ sơ</th>
                        <th>Thủ tục hành chính / Bước hiện tại</th>
                        <th style="width: 170px;">Người nộp</th>
                        <th style="width: 160px;">Chuyên viên</th>
                        <th style="width: 130px;" class="text-center">Hạn giải quyết</th>
                        <th style="width: 110px;" class="text-center">Tiến độ SLA</th>
                        <th style="width: 120px;" class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachHoSo)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="bi bi-folder2-open text-muted opacity-50" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0">Không có hồ sơ nào trong mục này.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $stt = 1;
                        foreach ($danhSachHoSo as $hs): 
                            $slaMau = $hs['sla_mau'] ?? 'XANH';
                            $badgeClass = match($slaMau) {
                                'DO' => 'bg-danger text-white',
                                'VANG' => 'bg-warning text-dark',
                                default => 'bg-success-subtle text-success border border-success'
                            };
                            $ngayHen = !empty($hs['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hs['ngay_hen_tra'])) : '---';
                        ?>
                            <tr>
                                <td class="text-center text-muted font-weight-bold"><?= $stt++ ?></td>
                                <td>
                                    <a href="<?= url('/admin/thu-ly/' . $hs['id']) ?>" class="font-monospace font-weight-bold text-primary text-decoration-none">
                                        <?= htmlspecialchars($hs['ma_ho_so']) ?>
                                    </a>
                                    <div class="small text-muted">
                                        <?= !empty($hs['ngay_tiep_nhan']) ? date('d/m/Y', strtotime($hs['ngay_tiep_nhan'])) : date('d/m/Y', strtotime($hs['ngay_nop'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-semibold text-dark text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($hs['ten_tthc']) ?>">
                                        <?= htmlspecialchars($hs['ten_tthc']) ?>
                                    </div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-arrow-right-short text-primary"></i>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($hs['ten_buoc'] ?? 'Chưa xác định') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-semibold text-dark"><?= htmlspecialchars($hs['nguoi_nop_ho_ten']) ?></div>
                                    <div class="small text-muted font-monospace"><i class="bi bi-person-vcard me-1"></i><?= htmlspecialchars($hs['nguoi_nop_cccd'] ?? '---') ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($hs['ten_chuyen_vien'])): ?>
                                        <div class="font-weight-bold text-dark"><i class="bi bi-person-check text-success me-1"></i><?= htmlspecialchars($hs['ten_chuyen_vien']) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">Chưa phân công</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="small font-weight-semibold"><?= $ngayHen ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass ?> px-2 py-1">
                                        <?= $slaMau === 'DO' ? 'Quá hạn' : ($slaMau === 'VANG' ? 'Sắp quá hạn' : 'Đúng hạn') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="<?= url('/admin/thu-ly/' . $hs['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil-square me-1"></i> Thụ lý
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
