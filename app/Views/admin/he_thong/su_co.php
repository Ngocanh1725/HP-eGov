<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<!-- Cảnh báo chu kỳ diễn tập định kỳ (6 tháng ATTT, 12 tháng DRP) -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="alert <?= $schedule['attt']['den_han'] ? 'alert-danger' : ($schedule['attt']['canh_bao'] ? 'alert-warning' : 'alert-success') ?> d-flex align-items-center justify-content-between p-3 m-0">
            <div>
                <strong>🛡️ Diễn tập An toàn thông tin (Định kỳ 6 tháng/lần)</strong>
                <div class="small">Lần cuối: <?= e($schedule['attt']['lan_cuoi']) ?> (Cách đây <?= $schedule['attt']['so_ngay_qua'] ?> ngày)</div>
            </div>
            <div>
                <?php if ($schedule['attt']['den_han']): ?>
                    <span class="badge bg-danger">ĐÃ ĐẾN HẠN DIỄN TẬP</span>
                <?php elseif ($schedule['attt']['canh_bao']): ?>
                    <span class="badge bg-warning text-dark">SẮP ĐẾN HẠN</span>
                <?php else: ?>
                    <span class="badge bg-success">ĐÚNG TIẾN ĐỘ</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert <?= $schedule['drp']['den_han'] ? 'alert-danger' : ($schedule['drp']['canh_bao'] ? 'alert-warning' : 'alert-success') ?> d-flex align-items-center justify-content-between p-3 m-0">
            <div>
                <strong>🔄 Diễn tập Chuyển đổi thảm họa DRP (Định kỳ 12 tháng/lần)</strong>
                <div class="small">Lần cuối: <?= e($schedule['drp']['lan_cuoi']) ?> (Cách đây <?= $schedule['drp']['so_ngay_qua'] ?> ngày)</div>
            </div>
            <div>
                <?php if ($schedule['drp']['den_han']): ?>
                    <span class="badge bg-danger">ĐÃ ĐẾN HẠN DIỄN TẬP</span>
                <?php elseif ($schedule['drp']['canh_bao']): ?>
                    <span class="badge bg-warning text-dark">SẮP ĐẾN HẠN</span>
                <?php else: ?>
                    <span class="badge bg-success">ĐÚNG TIẾN ĐỘ</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab chuyển đổi giữa Sổ theo dõi sự cố và Sổ diễn tập -->
<ul class="nav nav-tabs mb-4" id="incidentTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-bold" id="incidents-tab" data-bs-toggle="tab" data-bs-target="#incidents-pane" type="button">
            🚨 Sổ Theo Dõi Sự Cố An Toàn Thông Tin & Gián Đoạn Dịch Vụ
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold" id="drills-tab" data-bs-toggle="tab" data-bs-target="#drills-pane" type="button">
            📋 Kế Hoạch & Biên Bản Diễn Tập ATTT / DRP
        </button>
    </li>
</ul>

<div class="tab-content" id="incidentTabContent">
    <!-- TAB 1: SỔ THEO DÕI SỰ CỐ -->
    <div class="tab-pane fade show active" id="incidents-pane">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title m-0">Nhật ký Tiếp nhận & Ứng cứu Sự cố</h3>
                    <p class="text-muted small m-0">Quy trình xử lý theo phân định trách nhiệm RACI (§4.3, §4.4) giữa Sở TT&TT, đơn vị nghiệp vụ và nhà cung cấp</p>
                </div>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#incidentModal">
                    + Ghi nhận sự cố mới
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 140px;">Loại sự cố</th>
                                <th style="width: 110px;">Mức độ</th>
                                <th>Tiêu đề & Mô tả</th>
                                <th style="width: 180px;">RACI Phân định trách nhiệm</th>
                                <th style="width: 160px;">Thời gian phát hiện / khắc phục</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                                <th style="width: 80px;" class="text-center">Sửa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Không có sự cố nào được ghi nhận.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($incidents as $inc): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $inc['id'] ?></td>
                                        <td>
                                            <span class="badge bg-dark font-monospace"><?= e($inc['loai_su_co']) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $mClass = 'bg-secondary';
                                            if ($inc['muc_do'] === 'NGHIEM_TRONG') $mClass = 'bg-danger';
                                            elseif ($inc['muc_do'] === 'CAO') $mClass = 'bg-warning text-dark';
                                            elseif ($inc['muc_do'] === 'TRUNG_BINH') $mClass = 'bg-info text-white';
                                            ?>
                                            <span class="badge <?= $mClass ?>"><?= e($inc['muc_do']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= e($inc['tieu_de']) ?></div>
                                            <small class="text-muted text-truncate d-block" style="max-width: 320px;"><?= e($inc['mo_ta']) ?></small>
                                            <?php if (!empty($inc['bien_phap_khac_phuc'])): ?>
                                                <div class="small text-success mt-1"><strong>Khắc phục:</strong> <?= e($inc['bien_phap_khac_phuc']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div class="badge bg-light text-dark border text-wrap"><?= e($inc['raci_trach_nhiem']) ?></div>
                                            <?php if (!empty($inc['nguoi_xu_ly_ten'])): ?>
                                                <div class="small text-muted mt-1">Xử lý: <?= e($inc['nguoi_xu_ly_ten']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <div><strong>Phát hiện:</strong> <?= date('d/m/Y H:i', strtotime($inc['thoi_gian_phat_hien'])) ?></div>
                                            <?php if (!empty($inc['thoi_gian_khac_phuc'])): ?>
                                                <div class="text-success"><strong>Xong:</strong> <?= date('d/m/Y H:i', strtotime($inc['thoi_gian_khac_phuc'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($inc['trang_thai'] === 'DA_KHAC_PHUC'): ?>
                                                <span class="badge bg-success">Đã khắc phục</span>
                                            <?php elseif ($inc['trang_thai'] === 'DANG_XU_LY'): ?>
                                                <span class="badge bg-warning text-dark">Đang xử lý</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Mới tiếp nhận</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editIncident(<?= htmlspecialchars(json_encode($inc), ENT_QUOTES, 'UTF-8') ?>)">
                                                ✏️
                                            </button>
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

    <!-- TAB 2: BIÊN BẢN DIỄN TẬP -->
    <div class="tab-pane fade" id="drills-pane">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title m-0">Biên Bản Diễn Tập An Toàn Thông Tin & Phục Hồi Thảm Họa DRP</h3>
                    <p class="text-muted small m-0">Lưu trữ các đợt thực nghiệm khôi phục CSDL đo thời gian RTO thực tế và diễn tập tình huống ATTT</p>
                </div>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#drillModal">
                    + Thêm biên bản diễn tập
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 180px;">Loại hình diễn tập</th>
                                <th>Tiêu đề & Kịch bản thực nghiệm</th>
                                <th style="width: 140px;">Thời gian diễn tập</th>
                                <th style="width: 130px;" class="text-center">RTO Thực tế</th>
                                <th style="width: 100px;" class="text-center">Kết quả</th>
                                <th>Đánh giá & Kết luận</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($drills)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Chưa có biên bản diễn tập nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($drills as $d): ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?= $d['id'] ?></td>
                                        <td>
                                            <span class="badge <?= ($d['loai_dien_tap'] === 'AN_TOAN_THONG_TIN') ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                                <?= e($d['loai_dien_tap']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= e($d['tieu_de']) ?></div>
                                            <small class="text-muted d-block"><?= e($d['kich_ban']) ?></small>
                                        </td>
                                        <td class="small">
                                            <?= date('d/m/Y H:i', strtotime($d['thoi_gian_dien_tap'])) ?>
                                            <div class="text-muted small">Chủ trì: <?= e($d['nguoi_chu_tri_ten']) ?></div>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <?php if ($d['rto_thuc_te_phut'] !== null): ?>
                                                <span class="text-primary"><?= $d['rto_thuc_te_phut'] ?> phút</span>
                                                <div class="small text-muted font-monospace">(≤ 240 phút)</div>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= ($d['ket_qua'] === 'DAT') ? 'bg-success' : 'bg-danger' ?>">
                                                <?= e($d['ket_qua']) ?>
                                            </span>
                                        </td>
                                        <td class="small">
                                            <?= e($d['bien_ban_danh_gia']) ?>
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
</div>

<!-- Modal Thêm / Sửa sự cố -->
<div class="modal fade" id="incidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('/admin/he-thong/su-co/luu') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="inc-id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="incModalTitle">Ghi nhận Sự cố An toàn thông tin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Loại sự cố:</label>
                            <select name="loai_su_co" id="inc-type" class="form-select" required>
                                <option value="DUT_TSLCD">Đứt truyền dẫn Mạng số liệu chuyên dùng (TSLCD)</option>
                                <option value="DDOS">Tấn công từ chối dịch vụ (DDoS Layer 4/7)</option>
                                <option value="RANSOMWARE">Mã độc tống tiền / Vi-rút máy trạm (Ransomware)</option>
                                <option value="DEFACE">Tấn công thay đổi giao diện (Defacement)</option>
                                <option value="DB_FAILOVER">Sự cố CSDL / Treo khóa dữ liệu (DB Failover)</option>
                                <option value="MAT_DIEN_DC">Sự cố mất điện / Cháy nổ Trung tâm dữ liệu</option>
                                <option value="KHAC">Sự cố kỹ thuật khác</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mức độ nghiêm trọng:</label>
                            <select name="muc_do" id="inc-severity" class="form-select" required>
                                <option value="THAP">Thấp (Gián đoạn dưới 15 phút, không ảnh hưởng dữ liệu)</option>
                                <option value="TRUNG_BINH">Trung bình (Ảnh hưởng một số chức năng phụ)</option>
                                <option value="CAO">Cao (Gián đoạn dịch vụ công của một đơn vị)</option>
                                <option value="NGHIEM_TRONG">Nghiêm trọng (Ngừng toàn bộ dịch vụ hoặc rò rỉ dữ liệu)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Tiêu đề sự cố:</label>
                            <input type="text" name="tieu_de" id="inc-title" class="form-control" placeholder="Tóm tắt ngắn gọn sự cố..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô tả chi tiết:</label>
                            <textarea name="mo_ta" id="inc-desc" class="form-control" rows="3" placeholder="Hiện tượng, phạm vi ảnh hưởng, dấu hiệu phát hiện..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phân định trách nhiệm RACI:</label>
                            <input type="text" name="raci_trach_nhiem" id="inc-raci" class="form-control" value="Sở TT&TT (A/R) - Đơn vị nghiệp vụ (C)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Trạng thái xử lý:</label>
                            <select name="trang_thai" id="inc-status" class="form-select">
                                <option value="MOI">Mới tiếp nhận</option>
                                <option value="DANG_XU_LY">Đang xử lý ứng cứu</option>
                                <option value="DA_KHAC_PHUC">Đã khắc phục hoàn toàn</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Biện pháp khắc phục đã áp dụng:</label>
                            <textarea name="bien_phap_khac_phuc" id="inc-solution" class="form-control" rows="2" placeholder="Ghi nhận phương án kỹ thuật đã khắc phục..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu thông tin sự cố</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Thêm biên bản diễn tập -->
<div class="modal fade" id="drillModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('/admin/he-thong/su-co/luu-dien-tap') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Ghi nhận Biên bản Diễn tập Định kỳ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Loại hình diễn tập:</label>
                            <select name="loai_dien_tap" class="form-select" required>
                                <option value="AN_TOAN_THONG_TIN">Diễn tập An toàn thông tin (6 tháng/lần)</option>
                                <option value="CHUYEN_DOI_THAM_HOA_DRP">Diễn tập Chuyển đổi thảm họa DRP (12 tháng/lần)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Thời gian diễn tập:</label>
                            <input type="datetime-local" name="thoi_gian_dien_tap" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Tiêu đề diễn tập:</label>
                            <input type="text" name="tieu_de" class="form-control" placeholder="Tên đợt diễn tập..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Kịch bản diễn tập thực nghiệm:</label>
                            <textarea name="kich_ban" class="form-control" rows="3" placeholder="Tình huống giả định, các bước thực hiện..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">RTO Thực tế (phút):</label>
                            <input type="number" name="rto_thuc_te_phut" class="form-control" placeholder="Thời gian khôi phục (phút)" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kết quả đánh giá:</label>
                            <select name="ket_qua" class="form-select">
                                <option value="DAT">ĐẠT (Đáp ứng tiêu chuẩn kỹ thuật)</option>
                                <option value="KHONG_DAT">KHÔNG ĐẠT (Chưa đạt cam kết SLA/RTO)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Biên bản đánh giá & Nhận xét:</label>
                            <textarea name="bien_ban_danh_gia" class="form-control" rows="2" placeholder="Kết luận của Hội đồng đánh giá diễn tập..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Lưu Biên bản Diễn tập</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editIncident(data) {
    document.getElementById('inc-id').value = data.id;
    document.getElementById('incModalTitle').innerText = 'Cập nhật Sự cố #' + data.id;
    document.getElementById('inc-type').value = data.loai_su_co;
    document.getElementById('inc-severity').value = data.muc_do;
    document.getElementById('inc-title').value = data.tieu_de;
    document.getElementById('inc-desc').value = data.mo_ta;
    document.getElementById('inc-raci').value = data.raci_trach_nhiem;
    document.getElementById('inc-status').value = data.trang_thai;
    document.getElementById('inc-solution').value = data.bien_phap_khac_phuc || '';

    const modal = new bootstrap.Modal(document.getElementById('incidentModal'));
    modal.show();
}
</script>
