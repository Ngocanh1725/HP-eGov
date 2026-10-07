<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="card-title m-0">Nhật ký Đăng nhập & An toàn Phiên làm việc</h2>
            <p class="text-muted small m-0">Theo dõi vết đăng nhập cán bộ và công dân, phát hiện kịp thời các hành vi dò quét mật khẩu Brute-force</p>
        </div>
    </div>
    <div class="card-body">
        <!-- Bộ lọc -->
        <form method="GET" action="<?= url('/admin/he-thong/nhat-ky-dang-nhap') ?>" class="row g-3 mb-4 filter-form">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control form-control-sm" placeholder="Tìm tài khoản..." value="<?= e($filters['username'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Địa chỉ IP</label>
                <input type="text" name="ip" class="form-control form-control-sm" placeholder="Tìm IP..." value="<?= e($filters['ip'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Trạng thái</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="THANH_CONG" <?= ($filters['status'] === 'THANH_CONG') ? 'selected' : '' ?>>Thành công</option>
                    <option value="THAT_BAI" <?= ($filters['status'] === 'THAT_BAI') ? 'selected' : '' ?>>Thất bại</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Lọc nhật ký</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 160px;">Thời gian</th>
                        <th style="width: 180px;">Tài khoản / Vai trò</th>
                        <th style="width: 140px;">Địa chỉ IP</th>
                        <th>Thiết bị & Trình duyệt (User Agent)</th>
                        <th style="width: 130px;" class="text-center">Trạng thái</th>
                        <th>Ghi chú / Lý do</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Không có bản ghi nhật ký đăng nhập nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $log['id'] ?></td>
                                <td class="small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <div class="fw-bold"><?= e($log['ten_dang_nhap']) ?></div>
                                    <span class="badge bg-light text-dark border small"><?= e($log['vai_tro'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <code><?= e($log['dia_chi_ip']) ?></code>
                                </td>
                                <td class="small text-muted text-truncate" style="max-width: 250px;" title="<?= e($log['user_agent']) ?>">
                                    <?= e($log['user_agent']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($log['trang_thai'] === 'THANH_CONG'): ?>
                                        <span class="badge bg-success">Thành công</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Thất bại</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small <?= ($log['trang_thai'] === 'THAT_BAI') ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= e($log['ly_do'] ?? 'Đăng nhập bình thường') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-between align-items-center mt-3">
                <span class="small text-muted">Trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= number_format($total) ?> lượt)</span>
                <ul class="pagination pagination-sm m-0">
                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/he-thong/nhat-ky-dang-nhap?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
