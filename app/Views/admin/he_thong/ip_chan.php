<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="row">
    <!-- Cột trái: Quản lý IP bị chặn -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title m-0">Danh sách IP Bị Chặn (Blacklist)</h3>
                    <p class="text-muted small m-0">Các địa chỉ IP cố tình tấn công hoặc vi phạm an toàn thông tin bị hệ thống từ chối hoàn toàn</p>
                </div>
                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#blockIpModal">
                    + Chặn địa chỉ IP
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 140px;">Địa chỉ IP</th>
                                <th>Lý do vi phạm</th>
                                <th style="width: 140px;">Khóa đến</th>
                                <th style="width: 90px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($blockedIps)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Hiện không có địa chỉ IP nào bị khóa.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($blockedIps as $b): ?>
                                    <tr>
                                        <td><code><?= e($b['ip_address']) ?></code></td>
                                        <td class="small"><?= e($b['ly_do']) ?></td>
                                        <td class="small">
                                            <?= $b['chan_den'] ? date('d/m/Y H:i', strtotime($b['chan_den'])) : '<span class="badge bg-danger">Vĩnh viễn</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <form method="POST" action="<?= url('/admin/he-thong/ip-chan/unblock') ?>" onsubmit="return confirm('Mở khóa cho địa chỉ IP này?');">
                                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-outline-success btn-sm">Mở</button>
                                            </form>
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

    <!-- Cột phải: IP đang chịu Rate Limit -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title m-0">IP Đang Bị Rate Limit</h3>
                    <p class="text-muted small m-0">Vượt ngưỡng 60 req/phút</p>
                </div>
                <form method="POST" action="<?= url('/admin/he-thong/ip-chan/clear-rate-limit') ?>" onsubmit="return confirm('Làm sạch toàn bộ bộ đếm Rate limit?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Xóa tất cả</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address & Endpoint</th>
                                <th style="width: 80px;" class="text-center">Số req</th>
                                <th style="width: 120px;" class="text-center">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rateLimits)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted small">Không có IP nào bị Rate-limit.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rateLimits as $r): ?>
                                    <tr>
                                        <td class="small">
                                            <code><?= e($r['ip_address']) ?></code>
                                            <div class="text-muted font-monospace small"><?= e($r['endpoint']) ?></div>
                                        </td>
                                        <td class="text-center fw-bold text-danger"><?= $r['so_request'] ?></td>
                                        <td class="text-center small"><?= date('H:i:s', (int)$r['thoi_gian_bat_dau']) ?></td>
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

<!-- Modal Thêm IP Chặn -->
<div class="modal fade" id="blockIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/admin/he-thong/ip-chan/block') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Khóa Địa chỉ IP Vi phạm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Địa chỉ IP:</label>
                        <input type="text" name="ip_address" class="form-control" placeholder="VD: 113.190.23.45" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Lý do khóa:</label>
                        <textarea name="ly_do" class="form-control" rows="2" placeholder="VD: Dò quét lỗ hổng SQLi, DDoS Layer 7..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thời hạn khóa (ngày):</label>
                        <select name="days" class="form-select">
                            <option value="1">1 Ngày</option>
                            <option value="7">7 Ngày</option>
                            <option value="30">30 Ngày</option>
                            <option value="0">Khóa vĩnh viễn</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận khóa IP</button>
                </div>
            </form>
        </div>
    </div>
</div>
