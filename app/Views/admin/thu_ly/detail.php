<?php
/**
 * HP-eGov - Màn hình Thụ lý, Thẩm định & Phối hợp Liên thông chi tiết
 */
$this->layout('layouts/admin', ['title' => $pageTitle]);
$this->startSection('content');

$slaMau = $hoSo['sla_mau'] ?? 'XANH';
$isLdPhong = false;
if (isset($_SESSION['roles'])) {
    $roles = (array)$_SESSION['roles'];
    $isLdPhong = in_array('LD_PHONG', $roles) || in_array('SYSADMIN', $roles);
}
?>

<div class="container-fluid py-3">
    <!-- Header thông tin hồ sơ & Nút tác vụ -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('/admin/thu-ly') ?>">Hộp việc Thụ lý</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($hoSo['ma_ho_so']) ?></li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 font-weight-bold text-gray-800 mb-0 font-monospace text-primary">
                    <?= htmlspecialchars($hoSo['ma_ho_so']) ?>
                </h1>
                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                    <?= htmlspecialchars($hoSo['ten_buoc'] ?? 'Đang xử lý') ?>
                </span>
                <?php if ($slaMau === 'DO'): ?>
                    <span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-x-octagon me-1"></i>SLA Quá hạn</span>
                <?php elseif ($slaMau === 'VANG'): ?>
                    <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i>SLA Sắp quá hạn</span>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="bi bi-check2-circle me-1"></i>SLA Đúng hạn</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0 mt-1">
                <strong><?= htmlspecialchars($hoSo['ten_tthc']) ?></strong> | 
                Đơn vị đang xử lý: <strong><?= htmlspecialchars($hoSo['ten_don_vi_hien_tai'] ?? '') ?></strong>
            </p>
        </div>

        <!-- Các nút hành động chính -->
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!$isReadOnly): ?>
                <!-- Nút Phân công (LD_PHONG) -->
                <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPhanCong">
                    <i class="bi bi-person-gear me-1"></i> Phân công
                </button>

                <!-- Nút Lấy ý kiến phối hợp -->
                <button type="button" class="btn btn-outline-info btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLayYKien">
                    <i class="bi bi-people me-1"></i> Lấy ý kiến liên ngành
                </button>

                <!-- Nút Yêu cầu bổ sung -->
                <button type="button" class="btn btn-outline-warning btn-sm shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#modalYeuCauBoSung">
                    <i class="bi bi-pause-circle me-1"></i> Yêu cầu bổ sung
                </button>

                <!-- Nút Trả lại -->
                <button type="button" class="btn btn-outline-danger btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTraLai">
                    <i class="bi bi-arrow-return-left me-1"></i> Trả lại
                </button>

                <!-- Nút Chuyển tiếp -->
                <button type="button" class="btn btn-primary btn-sm shadow-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalChuyenTiep">
                    <i class="bi bi-send-check me-1"></i> Chuyển tiếp bước sau
                </button>
            <?php else: ?>
                <span class="badge bg-secondary p-2"><i class="bi bi-lock me-1"></i>Hồ sơ đang ở đơn vị khác (Chế độ chỉ đọc)</span>
            <?php endif; ?>

            <a href="<?= url('/admin/thu-ly') ?>" class="btn btn-secondary btn-sm shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Thông báo Flash -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Khối nội dung 2 cột -->
    <div class="row g-3">
        <!-- Cột trái (8 phần): Công cụ tác nghiệp -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom p-2">
                    <ul class="nav nav-tabs card-header-tabs" id="thuLyTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active font-weight-bold" id="eform-tab" data-bs-toggle="tab" data-bs-target="#eform-pane" type="button" role="tab">
                                <i class="bi bi-ui-checks me-1"></i> E-Form & Đối soát LGSP
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link font-weight-bold" id="files-tab" data-bs-toggle="tab" data-bs-target="#files-pane" type="button" role="tab">
                                <i class="bi bi-files me-1"></i> Hồ sơ đính kèm (<?= count($taiLieuList) ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link font-weight-bold" id="duThao-tab" data-bs-toggle="tab" data-bs-target="#duThao-pane" type="button" role="tab">
                                <i class="bi bi-file-earmark-diff me-1"></i> Dự thảo tờ trình / Kết quả
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link font-weight-bold" id="phoiHop-tab" data-bs-toggle="tab" data-bs-target="#phoiHop-pane" type="button" role="tab">
                                <i class="bi bi-chat-left-text me-1"></i> Ý kiến liên ngành (<?= count($yKienPhoiHop) ?>)
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3">
                    <div class="tab-content" id="thuLyTabContent">
                        
                        <!-- TAB 1: E-FORM & ĐỐI SOÁT LGSP -->
                        <div class="tab-pane fade show active" id="eform-pane" role="tabpanel">
                            <!-- Công cụ đối soát LGSP -->
                            <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <h6 class="font-weight-bold text-primary mb-0">
                                        <i class="bi bi-shield-check me-1"></i> Đối soát dữ liệu số qua LGSP Hải Phòng
                                    </h6>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnVerifyC06">
                                            <i class="bi bi-person-vcard me-1"></i> C06 Dân cư
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success" id="btnVerifyLand">
                                            <i class="bi bi-geo-alt me-1"></i> Đất đai (VILIS)
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" id="btnVerifyVehicle">
                                            <i class="bi bi-truck me-1"></i> Đăng kiểm GTVT
                                        </button>
                                    </div>
                                </div>
                                <div id="lgspResultBox" class="alert alert-info py-2 px-3 mb-0 small d-none">
                                    <div class="d-flex align-items-center">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        <span id="lgspResultText">Đang truy vấn trục liên thông LGSP...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin người nộp -->
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label class="small text-muted d-block">Người nộp / Tổ chức:</label>
                                    <strong class="text-dark fs-6" id="field_ho_ten"><?= htmlspecialchars($hoSo['nguoi_nop_ho_ten']) ?></strong>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small text-muted d-block">Số định danh / CCCD:</label>
                                    <span class="font-monospace font-weight-bold text-dark" id="field_cccd"><?= htmlspecialchars($hoSo['nguoi_nop_cccd'] ?? '---') ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small text-muted d-block">Số điện thoại:</label>
                                    <span><?= htmlspecialchars($hoSo['nguoi_nop_sdt'] ?? '---') ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <label class="small text-muted d-block">Địa chỉ liên hệ:</label>
                                    <span><?= htmlspecialchars($hoSo['nguoi_nop_dia_chi'] ?? '---') ?></span>
                                </div>
                            </div>

                            <hr>

                            <!-- Bảng dữ liệu E-form -->
                            <h6 class="font-weight-bold text-secondary mb-2">Thông tin kê khai điện tử (E-Form):</h6>
                            <?php if (empty($eformData)): ?>
                                <p class="text-muted small font-italic">Không có dữ liệu biểu mẫu điện tử.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light small">
                                            <tr>
                                                <th style="width: 35%;">Trường thông tin</th>
                                                <th>Nội dung kê khai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($eformData as $k => $v): ?>
                                                <tr>
                                                    <td class="font-weight-semibold text-muted small"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$k))) ?></td>
                                                    <td><?= is_array($v) ? htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE)) : htmlspecialchars((string)$v) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- TAB 2: TÀI LIỆU ĐÍNH KÈM -->
                        <div class="tab-pane fade" id="files-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase font-weight-bold">
                                        <tr>
                                            <th>Tên tài liệu</th>
                                            <th style="width: 110px;">Loại</th>
                                            <th style="width: 140px;">Mã băm SHA-256</th>
                                            <th style="width: 120px;">Ký số</th>
                                            <th style="width: 100px;" class="text-end">Xem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($taiLieuList)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">Không có tài liệu đính kèm.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($taiLieuList as $tl): ?>
                                                <tr>
                                                    <td>
                                                        <div class="font-weight-semibold text-dark"><?= htmlspecialchars($tl['ten_goc']) ?></div>
                                                        <small class="text-muted"><?= round($tl['kich_thuoc_byte'] / 1024, 1) ?> KB | <?= htmlspecialchars($tl['mime_type']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($tl['loai_tai_lieu']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="font-monospace small text-muted" title="<?= htmlspecialchars($tl['sha256']) ?>">
                                                            <?= substr($tl['sha256'], 0, 10) ?>...
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($tl['chu_ky_so_id'])): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-patch-check me-1"></i>Hợp lệ</span>
                                                        <?php else: ?>
                                                            <span class="text-muted small font-italic">Chưa ký</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?= url('/storage/' . htmlspecialchars($tl['ten_luu'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: DỰ THẢO TỜ TRÌNH / KẾT QUẢ -->
                        <div class="tab-pane fade" id="duThao-pane" role="tabpanel">
                            <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/du-thao") ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label small font-weight-bold">Tiêu đề dự thảo / Tờ trình thẩm định:</label>
                                    <input type="text" name="tieu_de_du_thao" class="form-control" value="Tờ trình thẩm định hồ sơ <?= htmlspecialchars($hoSo['ma_ho_so']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small font-weight-bold">Nội dung biên bản thẩm định / Dự thảo quyết định:</label>
                                    <textarea name="noi_dung_du_thao" rows="10" class="form-control font-monospace" required>
KÍNH TRÌNH LÃNH ĐẠO CƠ QUAN

Căn cứ hồ sơ đề nghị giải quyết TTHC số <?= htmlspecialchars($hoSo['ma_ho_so']) ?> của Ông/Bà (Tổ chức) <?= htmlspecialchars($hoSo['nguoi_nop_ho_ten']) ?>.
Sau khi kiểm tra, đối soát dữ liệu với CSDL Quốc gia và các quy định pháp luật hiện hành:
1. Về tính pháp lý của hồ sơ: Đầy đủ, hợp lệ theo quy định.
2. Về kết quả thẩm định thực tế: Đạt yêu cầu tiêu chuẩn kỹ thuật/nghiệp vụ.
3. Kiến nghị: Kính trình Lãnh đạo xem xét, phê duyệt kết quả giải quyết TTHC.

Chuyên viên thẩm định: <?= htmlspecialchars($currentUser['ho_ten'] ?? '') ?>
                                    </textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-1"></i> Lưu bản dự thảo
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- TAB 4: Ý KIẾN LIÊN NGÀNH -->
                        <div class="tab-pane fade" id="phoiHop-pane" role="tabpanel">
                            <?php if (empty($yKienPhoiHop)): ?>
                                <p class="text-muted small text-center py-4">Chưa có yêu cầu lấy ý kiến phối hợp nào.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($yKienPhoiHop as $yk): ?>
                                        <div class="list-group-item p-3 mb-2 rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-primary">
                                                    Từ: <?= htmlspecialchars($yk['ten_don_vi_yeu_cau']) ?> ➔ Đến: <?= htmlspecialchars($yk['ten_don_vi_phoi_hop']) ?>
                                                </strong>
                                                <span class="badge <?= $yk['trang_thai'] === 'DA_TRA_LOI' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= $yk['trang_thai'] === 'DA_TRA_LOI' ? 'Đã phản hồi' : 'Chờ ý kiến' ?>
                                                </span>
                                            </div>
                                            <p class="mb-2 small"><strong>Nội dung yêu cầu:</strong> <?= htmlspecialchars($yk['noi_dung_yeu_cau']) ?></p>
                                            <div class="small text-muted mb-2">Hạn trả lời: <?= date('d/m/Y H:i', strtotime($yk['han_tra_loi'])) ?></div>

                                            <?php if (!empty($yk['noi_dung_tra_loi'])): ?>
                                                <div class="alert alert-success py-2 px-3 mb-0 small">
                                                    <strong>Phản hồi (<?= date('d/m/Y H:i', strtotime($yk['ngay_tra_loi'])) ?>):</strong><br>
                                                    <?= nl2br(htmlspecialchars($yk['noi_dung_tra_loi'])) ?>
                                                </div>
                                            <?php elseif ((int)$yk['don_vi_phoi_hop_id'] === (int)$currentUser['don_vi_hien_tai_id']): ?>
                                                <!-- Form trả lời cho đơn vị phối hợp -->
                                                <form action="<?= url("/admin/thu-ly/y-kien/{$yk['id']}/tra-loi") ?>" method="POST" class="mt-2">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="y_kien_id" value="<?= $yk['id'] ?>">
                                                    <textarea name="noi_dung_tra_loi" rows="2" class="form-control form-control-sm mb-2" placeholder="Nhập nội dung phản hồi ý kiến chuyên môn..." required></textarea>
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-send me-1"></i> Gửi phản hồi ý kiến
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải (4 phần): SLA & Timeline -->
        <div class="col-lg-4">
            <!-- Thẻ SLA & Tiến độ -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white font-weight-bold py-2">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> Kiểm soát hạn giải quyết SLA
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted">Ngày hẹn trả:</span>
                        <strong class="text-dark"><?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) : '---' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted">Tổng thời hạn cam kết:</span>
                        <span><?= $hoSo['thoi_han_giai_quyet_ngay'] ?? 0 ?> ngày (<?= $hoSo['tong_thoi_han_gio'] ?? 0 ?> giờ)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted">Thời gian tạm dừng (bổ sung):</span>
                        <span class="badge bg-light text-dark border"><?= $hoSo['so_gio_tam_dung'] ?? 0 ?> giờ</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small text-muted">Trạng thái toàn vẹn file:</span>
                        <?php if ($fileIntegrity['valid']): ?>
                            <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-shield-check me-1"></i>Toàn vẹn 100%</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white"><i class="bi bi-shield-x me-1"></i>Cảnh báo sai lệch!</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Thẻ Timeline luân chuyển -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white font-weight-bold py-2">
                    <i class="bi bi-clock-history text-primary me-1"></i> Lịch sử luân chuyển liên thông
                </div>
                <div class="card-body p-3">
                    <div class="timeline-wrapper" style="border-left: 2px solid #e2e8f0; margin-left: 8px; padding-left: 12px;">
                        <?php foreach ($timeline as $lc): ?>
                            <div class="mb-3 position-relative">
                                <div style="position: absolute; left: -19px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: #3b82f6; border: 2px solid #fff;"></div>
                                <div class="small font-weight-bold text-dark">
                                    <?= htmlspecialchars($lc['hanh_dong']) ?>
                                    <span class="text-muted font-weight-normal float-end" style="font-size: 0.75rem;">
                                        <?= date('d/m H:i', strtotime($lc['thoi_gian_chuyen'])) ?>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    Bởi: <strong><?= htmlspecialchars($lc['ten_nguoi_chuyen'] ?? 'Hệ thống') ?></strong>
                                    (<?= htmlspecialchars($lc['ten_tu_don_vi'] ?? '') ?>)
                                </div>
                                <?php if (!empty($lc['noi_dung_y_kien'])): ?>
                                    <div class="small bg-light p-1 rounded mt-1 border" style="font-size: 0.8rem;">
                                        <?= htmlspecialchars($lc['noi_dung_y_kien']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PHÂN CÔNG (LD_PHONG) -->
<div class="modal fade" id="modalPhanCong" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/phan-cong") ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-gear me-2"></i>Phân công chuyên viên thụ lý</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Chọn chuyên viên thụ lý: <span class="text-danger">*</span></label>
                    <select name="chuyen_vien_id" class="form-select" required>
                        <option value="">-- Chọn chuyên viên trong phòng --</option>
                        <?php foreach ($chuyenVienList as $cv): ?>
                            <option value="<?= $cv['id'] ?>" <?= ($hoSo['chuyen_vien_thu_ly_id'] == $cv['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cv['ho_ten']) ?> (Đang xử lý: <?= $cv['so_ho_so_dang_xu_ly'] ?> hồ sơ)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Hệ thống tự động hiển thị số lượng hồ sơ đang thụ lý để hỗ trợ cân bằng tải.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Ý kiến chỉ đạo giao việc:</label>
                    <textarea name="y_kien" class="form-control" rows="3" placeholder="Giao chuyên viên thẩm định hồ sơ theo quy định..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary font-weight-bold">Xác nhận giao việc</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CHUYỂN TIẾP -->
<div class="modal fade" id="modalChuyenTiep" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/chuyen-tiep") ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-send-check me-2"></i>Chuyển tiếp bước xử lý kế tiếp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($nextSteps)): ?>
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Bước chuyển tiếp tiếp theo:</label>
                        <select name="den_buoc_id" class="form-select">
                            <?php foreach ($nextSteps as $b): ?>
                                <option value="<?= $b['id'] ?>">
                                    [Bước <?= $b['stt_buoc'] ?>] <?= htmlspecialchars($b['ten_buoc']) ?> 
                                    <?= !empty($b['ten_don_vi_xu_ly']) ? '➔ ' . htmlspecialchars($b['ten_don_vi_xu_ly']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Ý kiến xử lý / Tờ trình:</label>
                    <textarea name="noi_dung_y_kien" class="form-control" rows="3" placeholder="Hồ sơ đã được thẩm định đạt yêu cầu, chuyển bước tiếp theo..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary font-weight-bold">Xác nhận chuyển tiếp</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TRẢ LẠI -->
<div class="modal fade" id="modalTraLai" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/tra-lai") ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left me-2"></i>Trả lại hồ sơ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Lý do trả lại hồ sơ: <span class="text-danger">*</span></label>
                    <textarea name="ly_do" class="form-control" rows="3" placeholder="Nêu rõ lý do trả lại để cấp dưới hoặc chuyên viên thẩm định lại..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger font-weight-bold">Xác nhận trả lại</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL YÊU CẦU BỔ SUNG (TẠM DỪNG SLA) -->
<div class="modal fade" id="modalYeuCauBoSung" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/yeu-cau-bo-sung") ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="bi bi-pause-circle me-2"></i>Yêu cầu bổ sung hồ sơ (Tạm dừng tính SLA)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Theo quy định, yêu cầu bổ sung sẽ <strong>tạm dừng thời gian tính SLA</strong> của hồ sơ cho đến khi công dân nộp lại.
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Lý do yêu cầu bổ sung: <span class="text-danger">*</span></label>
                    <textarea name="ly_do_bo_sung" class="form-control" rows="2" placeholder="Nêu rõ các thiếu sót trong hồ sơ..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Danh sách giấy tờ cần bổ sung:</label>
                    <textarea name="danh_sach_giay_to" class="form-control" rows="2" placeholder="1. Bản sao có chứng thực...&#10;2. Giấy chứng nhận..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Thời hạn bổ sung (ngày):</label>
                    <input type="number" name="han_ngay" class="form-control form-control-sm" value="30" min="1" max="60">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-warning font-weight-bold">Gửi yêu cầu bổ sung</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL LẤY Ý KIẾN PHỐI HỢP -->
<div class="modal fade" id="modalLayYKien" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= url("/admin/thu-ly/{$hoSo['id']}/lay-y-kien") ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Gửi yêu cầu lấy ý kiến phối hợp liên phòng / liên sở</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Đơn vị nhận yêu cầu phối hợp: <span class="text-danger">*</span></label>
                    <select name="don_vi_phoi_hop_id" class="form-select" required>
                        <option value="">-- Chọn cơ quan / đơn vị phối hợp --</option>
                        <?php foreach ($donViList as $dv): ?>
                            <option value="<?= $dv['id'] ?>">[<?= $dv['loai_don_vi'] ?? '' ?>] <?= htmlspecialchars($dv['ten_don_vi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Nội dung đề nghị có ý kiến: <span class="text-danger">*</span></label>
                    <textarea name="noi_dung_yeu_cau" class="form-control" rows="3" placeholder="Đề nghị đơn vị có ý kiến thẩm định chuyên môn về quy hoạch / môi trường / thuế..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small font-weight-bold">Hạn trả lời ý kiến: <span class="text-danger">*</span></label>
                    <input type="date" name="han_tra_loi" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-info text-white font-weight-bold">Gửi yêu cầu phối hợp</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const box = document.getElementById('lgspResultBox');
    const text = document.getElementById('lgspResultText');

    function callLgsp(type, data) {
        box.classList.remove('d-none', 'alert-success', 'alert-danger');
        box.classList.add('alert-info');
        text.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Đang truy vấn trục liên thông LGSP Hải Phòng...';

        const formData = new FormData();
        formData.append('loai', type);
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
        for (const [k, v] of Object.entries(data)) {
            formData.append(k, v);
        }

        fetch('<?= url('/admin/thu-ly/api/verify-lgsp') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            box.classList.remove('alert-info');
            if (res.success) {
                box.classList.add('alert-success');
                text.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i> XÁC THỰC THÀNH CÔNG:</strong> ' + res.message;
            } else {
                box.classList.add('alert-danger');
                text.innerHTML = '<strong><i class="bi bi-exclamation-triangle-fill me-1"></i> ĐỐI SOÁT THẤT BẠI:</strong> ' + res.message;
            }
        })
        .catch(() => {
            box.classList.remove('alert-info');
            box.classList.add('alert-danger');
            text.innerHTML = 'Lỗi kết nối tới trục LGSP.';
        });
    }

    document.getElementById('btnVerifyC06').addEventListener('click', function() {
        const cccd = document.getElementById('field_cccd').innerText.trim();
        const hoTen = document.getElementById('field_ho_ten').innerText.trim();
        callLgsp('c06', { so_cccd: cccd, ho_ten: hoTen });
    });

    document.getElementById('btnVerifyLand').addEventListener('click', function() {
        callLgsp('land', { so_thua: '45', so_to: '12', chu_su_dung: '<?= htmlspecialchars($hoSo['nguoi_nop_ho_ten']) ?>' });
    });

    document.getElementById('btnVerifyVehicle').addEventListener('click', function() {
        callLgsp('vehicle', { bien_so: '15A-888.88', chu_xe: '<?= htmlspecialchars($hoSo['nguoi_nop_ho_ten']) ?>' });
    });
});
</script>

<?php $this->endSection(); ?>
