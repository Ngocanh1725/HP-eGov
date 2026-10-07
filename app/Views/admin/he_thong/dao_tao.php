<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<!-- Banner nhận diện môi trường Staging nếu cờ APP_ENV=staging -->
<?php if (getenv('APP_ENV') === 'staging' || \App\Core\Config::get('app.env') === 'staging'): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between p-3 mb-4 shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4">⚠️</span>
            <div>
                <strong class="text-dark">MÔI TRƯỜNG ĐÀO TẠO & THỰC HÀNH CÔNG NGHỆ (STAGING)</strong>
                <div class="small text-muted">Dữ liệu hồ sơ, chữ ký số và biên lai tại môi trường này là dữ liệu thử nghiệm, hoàn toàn không có giá trị pháp lý thực tế.</div>
            </div>
        </div>
        <span class="badge bg-warning text-dark px-3 py-2 fw-bold">APP_ENV=staging</span>
    </div>
<?php endif; ?>

<!-- 4 Thẻ Khóa Đào tạo theo 4 nhóm đối tượng (§1.4) -->
<div class="row mb-4">
    <?php foreach ($courses as $c): ?>
        <div class="col-md-3 mb-3">
            <div class="card h-100 <?= ($selectedCourse && $selectedCourse['id'] == $c['id']) ? 'border-primary shadow-sm' : '' ?>">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="badge bg-primary"><?= e($c['nhom_doi_tuong']) ?></span>
                    <small class="text-muted"><?= $c['tong_hoc_vien'] ?> học viên</small>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="card-title fs-6 fw-bold mb-2">
                            <a href="<?= url('/admin/he-thong/dao-tao?khoa_id=' . $c['id']) ?>" class="text-decoration-none <?= ($selectedCourse && $selectedCourse['id'] == $c['id']) ? 'text-primary' : 'text-dark' ?>">
                                <?= e($c['ten_khoa']) ?>
                            </a>
                        </h5>
                        <p class="small text-muted mb-2 text-truncate d-block" title="<?= e($c['noi_dung_trong_tam']) ?>">
                            <?= e($c['noi_dung_trong_tam']) ?>
                        </p>
                        <div class="small text-info-emphasis mb-2">
                            <strong>Tiêu chí đạt:</strong> <?= e($c['nguong_dat']) ?>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-muted">Tỷ lệ đạt chuẩn:</span>
                            <strong class="text-success"><?= $c['ty_le_dat'] ?>%</strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $c['ty_le_dat'] ?>%;"></div>
                        </div>
                        <a href="<?= url('/admin/he-thong/dao-tao?khoa_id=' . $c['id']) ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">
                            Xem danh sách & Chấm điểm
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Chi tiết Khóa học đang chọn & Danh sách học viên -->
<?php if ($selectedCourse): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="card-title m-0"><?= e($selectedCourse['ten_khoa']) ?></h3>
                <p class="text-muted small m-0">
                    <strong>Phương thức đánh giá:</strong> <?= e($selectedCourse['phuong_thuc_danh_gia']) ?> | 
                    <strong>Ngưỡng đạt:</strong> <span class="badge bg-success"><?= e($selectedCourse['nguong_dat']) ?></span>
                </p>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#enrollModal">
                + Ghi danh cán bộ mới
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th style="width: 220px;">Họ tên cán bộ</th>
                            <th style="width: 180px;">Đơn vị / Phòng ban</th>
                            <th style="width: 130px;" class="text-center">Số hồ sơ mẫu</th>
                            <th style="width: 110px;" class="text-center">Điểm số</th>
                            <th style="width: 130px;" class="text-center">Kết quả sát hạch</th>
                            <th>Nhận xét & Đánh giá năng lực</th>
                            <th style="width: 90px;" class="text-center">Chấm điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Chưa có học viên nào được ghi danh vào khóa này.</td>
                            </tr>
                        <?php else: ?>
                            <?php $stt = 1; foreach ($students as $stu): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $stt++ ?></td>
                                    <td>
                                        <div class="fw-bold"><?= e($stu['ho_ten']) ?></div>
                                        <small class="text-muted"><?= e($stu['ten_dang_nhap']) ?> | <?= e($stu['vai_tro']) ?></small>
                                    </td>
                                    <td class="small">
                                        <div><?= e($stu['ten_don_vi'] ?? 'UBND TP') ?></div>
                                        <small class="text-muted"><?= e($stu['ten_phong_ban'] ?? '') ?></small>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= $stu['so_ho_so_thuc_hanh'] ?> hồ sơ
                                    </td>
                                    <td class="text-center fw-bold fs-6">
                                        <?= ($stu['diem_so'] !== null) ? $stu['diem_so'] : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($stu['ket_qua'] === 'DAT'): ?>
                                            <span class="badge bg-success">ĐẠT CHUẨN</span>
                                        <?php elseif ($stu['ket_qua'] === 'KHONG_DAT'): ?>
                                            <span class="badge bg-danger">CHƯA ĐẠT</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">CHƯA THI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= e($stu['nhan_xet'] ?? 'Chưa có nhận xét sát hạch') ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick="gradeStudentModal(<?= htmlspecialchars(json_encode($stu), ENT_QUOTES, 'UTF-8') ?>)">
                                            ✏️ Chấm
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
<?php endif; ?>

<!-- Modal Ghi danh cán bộ -->
<div class="modal fade" id="enrollModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/admin/he-thong/dao-tao/ghi-danh') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="khoa_id" value="<?= $selectedCourse['id'] ?? 0 ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Ghi danh cán bộ tham gia khóa đào tạo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn cán bộ công chức / viên chức:</label>
                        <select name="nguoi_dung_id" class="form-select" required>
                            <option value="">-- Chọn cán bộ --</option>
                            <?php foreach ($availableUsers as $au): ?>
                                <option value="<?= $au['id'] ?>">
                                    <?= e($au['ho_ten']) ?> (<?= e($au['ten_dang_nhap']) ?> - <?= e($au['vai_tro']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Xác nhận ghi danh</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Chấm điểm sát hạch thực hành -->
<div class="modal fade" id="gradeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/admin/he-thong/dao-tao/nhap-diem') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="khoa_id" value="<?= $selectedCourse['id'] ?? 0 ?>">
                <input type="hidden" name="id" id="grade-student-id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradeModalTitle">Đánh giá Kết quả Sát hạch Cán bộ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <strong>Tiêu chuẩn ĐẠT nhóm <?= e($selectedCourse['nhom_doi_tuong'] ?? '') ?>:</strong><br>
                        <?= e($selectedCourse['nguong_dat'] ?? '') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Điểm số lý thuyết / trắc nghiệm (thang 100):</label>
                        <input type="number" step="0.5" min="0" max="100" name="diem_so" id="grade-score" class="form-control" placeholder="Nhập điểm số (ví dụ: 92.5)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Số lượng hồ sơ mẫu đã thực hành xử lý:</label>
                        <input type="number" min="0" name="so_ho_so_thuc_hanh" id="grade-records" class="form-control" placeholder="Số hồ sơ hoàn thành">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhận xét & Đánh giá năng lực thao tác:</label>
                        <textarea name="nhan_xet" id="grade-notes" class="form-control" rows="3" placeholder="Nhận xét sự thành thạo thao tác, xử lý quy trình..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">Lưu kết quả sát hạch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function gradeStudentModal(stu) {
    document.getElementById('grade-student-id').value = stu.id;
    document.getElementById('gradeModalTitle').innerText = 'Chấm điểm cán bộ: ' + stu.ho_ten;
    document.getElementById('grade-score').value = stu.diem_so !== null ? stu.diem_so : '';
    document.getElementById('grade-records').value = stu.so_ho_so_thuc_hanh || 0;
    document.getElementById('grade-notes').value = stu.nhan_xet || '';

    const modal = new bootstrap.Modal(document.getElementById('gradeModal'));
    modal.show();
}
</script>
