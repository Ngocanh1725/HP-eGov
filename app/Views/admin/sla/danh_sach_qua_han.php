<?php
/**
 * View: Danh sách hồ sơ quá hạn SLA (màu ĐỎ) & Lập Văn bản xin lỗi Mẫu 04
 * HP-eGov - Trung tâm Phục vụ Hành chính công TP. Hải Phòng
 */
$this->layout('layouts/admin', ['title' => $title ?? 'Giám sát Hồ sơ Quá hạn SLA']);
?>

<div class="container-fluid px-4 py-4">
    <!-- Tiêu đề & Breadcrumb -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-navy fw-bold mb-1">
                <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>Giám sát Hồ sơ Quá hạn SLA & Mẫu số 04
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/admin/dashboard">Bàn làm việc</a></li>
                    <li class="breadcrumb-item active">Hồ sơ quá hạn SLA</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/thu-ly" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Hộp việc Thụ lý
            </a>
            <button class="btn btn-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Làm mới dữ liệu
            </button>
        </div>
    </div>

    <!-- 3 Thẻ thống kê KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Tổng hồ sơ quá hạn (SLA Đỏ)</div>
                            <div class="fs-2 fw-bold text-danger mt-1"><?= number_format($totalQuaHan) ?></div>
                            <small class="text-muted">Bị khóa chuyển bước quy trình</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i class="bi bi-clock-history fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Chưa có Văn bản xin lỗi</div>
                            <div class="fs-2 fw-bold text-warning mt-1"><?= number_format($chuaCoMau04) ?></div>
                            <small class="text-danger fw-semibold">Cần LĐ Cơ quan ký ban hành gấp</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                            <i class="bi bi-file-earmark-break fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase">Đã ban hành Mẫu số 04</div>
                            <div class="fs-2 fw-bold text-success mt-1"><?= number_format($daCoMau04) ?></div>
                            <small class="text-muted">Đã mở khóa hoàn tất hồ sơ</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                            <i class="bi bi-check2-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Danh sách hồ sơ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <form method="GET" action="/admin/sla/danh-sach-qua-han" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0 bg-light" 
                               placeholder="Tìm theo mã hồ sơ, tên người nộp, tên thủ tục..." value="<?= e($q ?? '') ?>">
                        <button class="btn btn-navy" type="submit">Tìm kiếm</button>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
                        <i class="bi bi-info-circle me-1"></i>Hồ sơ quá hạn tự động trừ điểm thi đua đơn vị (-1 điểm)
                    </span>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 50px;">STT</th>
                            <th>Mã hồ sơ / TTHC</th>
                            <th>Người nộp hồ sơ</th>
                            <th>Thời hạn cam kết</th>
                            <th>Tiến độ SLA</th>
                            <th>Văn bản xin lỗi (Mẫu 04)</th>
                            <th class="text-end pe-3" style="width: 180px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSach)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check text-success fs-1 d-block mb-2"></i>
                                    Không có hồ sơ nào bị quá hạn giải quyết tại đơn vị. Xin chúc mừng!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSach as $idx => $hs): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?= $idx + 1 ?></td>
                                    <td>
                                        <a href="/admin/thu-ly/<?= $hs['id'] ?>" class="fw-bold text-navy text-decoration-none">
                                            <?= e($hs['ma_ho_so']) ?>
                                        </a>
                                        <div class="small text-muted text-truncate" style="max-width: 320px;" title="<?= e($hs['ten_tthc']) ?>">
                                            <?= e($hs['ten_tthc']) ?>
                                        </div>
                                        <div class="small text-secondary">
                                            <i class="bi bi-geo-alt me-1"></i><?= e($hs['ten_don_vi'] ?? 'Bộ phận Một cửa') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= e($hs['ho_ten_nguoi_nop']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($hs['so_dien_thoai_nguoi_nop'] ?? '---') ?></div>
                                        <?php if (!empty($hs['ten_chuyen_vien'])): ?>
                                            <div class="badge bg-light text-dark border mt-1">CV: <?= e($hs['ten_chuyen_vien']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-muted">Tiếp nhận:</span> 
                                            <strong><?= !empty($hs['ngay_tiepNhan']) ? date('d/m/Y H:i', strtotime($hs['ngay_tiepNhan'])) : '---' ?></strong>
                                        </div>
                                        <div class="small text-danger">
                                            <span class="text-muted">Hẹn cũ:</span> 
                                            <strong><?= !empty($hs['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hs['ngay_hen_tra'])) : '---' ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger text-white px-2 py-1">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>QUÁ HẠN (ĐỎ)
                                        </span>
                                        <div class="small text-muted mt-1">
                                            Đã dùng: <strong><?= number_format((float)($hs['thoi_gian_da_dung_gio'] ?? 0), 1) ?>h</strong> / <?= number_format((float)($hs['tong_thoi_han_gio'] ?? 0)) ?>h
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($hs['vbxl_id']) && $hs['vbxl_trang_thai'] === 'DA_KY'): ?>
                                            <div class="text-success fw-bold small">
                                                <i class="bi bi-patch-check-fill me-1"></i>Đã ký số: <?= e($hs['vbxl_so']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                Ngày ký: <?= date('d/m/Y', strtotime($hs['vbxl_ngay_ky'])) ?>
                                            </div>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success mt-1">Đã mở khóa</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                                <i class="bi bi-lock-fill me-1"></i>Chưa có (ĐANG KHÓA)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if (!empty($hs['vbxl_id']) && $hs['vbxl_trang_thai'] === 'DA_KY'): ?>
                                            <a href="/admin/sla/in-mau-04/<?= $hs['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-printer me-1"></i>In Mẫu 04
                                            </a>
                                        <?php else: ?>
                                            <a href="/admin/sla/van-ban-xin-loi/<?= $hs['id'] ?>" class="btn btn-sm btn-danger">
                                                <i class="bi bi-pencil-square me-1"></i>Lập Mẫu 04
                                            </a>
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
</div>
