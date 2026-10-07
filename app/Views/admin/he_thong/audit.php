<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="card-title m-0">Tra cứu Nhật ký kiểm toán WORM (Audit Trail)</h2>
            <p class="text-muted small m-0">Ghi vết bất biến mọi thao tác cơ sở dữ liệu với chuỗi hàm băm SHA-256 nối tiếp (WORM - Write Once, Read Many)</p>
        </div>
        <div class="d-flex gap-2">
            <button id="btn-verify-worm" class="btn btn-warning d-flex align-items-center gap-1" onclick="verifyWormChain()">
                <span class="icon">🛡️</span> <strong>Kiểm tra toàn vẹn chuỗi băm</strong>
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Bộ lọc -->
        <form method="GET" action="<?= url('/admin/he-thong/audit') ?>" class="row g-3 mb-4 filter-form">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Người thực hiện</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả người dùng --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filters['user_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= e($u['ho_ten']) ?> (<?= e($u['ten_dang_nhap']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Hành động</label>
                <input type="text" name="action" class="form-control form-control-sm" placeholder="VD: INSERT, UPDATE..." value="<?= e($filters['action'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bảng tác động</label>
                <input type="text" name="table" class="form-control form-control-sm" placeholder="VD: ho_so, chu_ky_so..." value="<?= e($filters['table'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Từ ngày</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= e($filters['from_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Đến ngày</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= e($filters['to_date'] ?? '') ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">Lọc</button>
            </div>
        </form>

        <!-- Khung hiển thị kết quả kiểm tra WORM -->
        <div id="worm-verify-result" class="alert d-none mb-3"></div>

        <!-- Bảng danh sách nhật ký -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 150px;">Thời gian</th>
                        <th style="width: 160px;">Người thực hiện</th>
                        <th style="width: 110px;">Hành động</th>
                        <th style="width: 140px;">Bảng / Bản ghi</th>
                        <th style="width: 120px;">IP / Client</th>
                        <th>Chuỗi băm SHA-256 (Hash / Prev Hash)</th>
                        <th style="width: 90px;" class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Không tìm thấy dữ liệu nhật ký kiểm toán phù hợp.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $log['id'] ?></td>
                                <td class="small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <?php if (!empty($log['ho_ten'])): ?>
                                        <div class="fw-bold"><?= e($log['ho_ten']) ?></div>
                                        <div class="small text-muted"><?= e($log['vai_tro']) ?> (<?= e($log['ten_dang_nhap']) ?>)</div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Hệ thống / Khách</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $actionClass = 'bg-secondary';
                                    if (str_contains($log['hanh_dong'], 'INSERT') || str_contains($log['hanh_dong'], 'TAO')) $actionClass = 'bg-success';
                                    elseif (str_contains($log['hanh_dong'], 'UPDATE') || str_contains($log['hanh_dong'], 'SUA')) $actionClass = 'bg-primary';
                                    elseif (str_contains($log['hanh_dong'], 'DELETE') || str_contains($log['hanh_dong'], 'XOA')) $actionClass = 'bg-danger';
                                    elseif (str_contains($log['hanh_dong'], 'KY_SO')) $actionClass = 'bg-info text-dark';
                                    ?>
                                    <span class="badge <?= $actionClass ?>"><?= e($log['hanh_dong']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($log['bang_tac_dong'] ?? 'N/A') ?></span>
                                    <?php if ($log['ban_ghi_id']): ?>
                                        <span class="small text-muted">#<?= $log['ban_ghi_id'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <div><code><?= e($log['dia_chi_ip'] ?? '127.0.0.1') ?></code></div>
                                </td>
                                <td>
                                    <div class="font-monospace small text-truncate" style="max-width: 280px;" title="Hash: <?= e($log['hash']) ?>">
                                        <span class="text-success fw-bold">H:</span> <?= substr($log['hash'], 0, 16) ?>...
                                    </div>
                                    <div class="font-monospace text-muted small text-truncate" style="max-width: 280px;" title="Prev Hash: <?= e($log['prev_hash']) ?>">
                                        <span class="text-muted">P:</span> <?= substr($log['prev_hash'] ?? '0', 0, 16) ?>...
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="showDiffModal(<?= htmlspecialchars(json_encode([
                                                'id' => $log['id'],
                                                'action' => $log['hanh_dong'],
                                                'table' => $log['bang_tac_dong'],
                                                'old' => json_decode($log['du_lieu_cu'] ?? 'null', true),
                                                'new' => json_decode($log['du_lieu_moi'] ?? 'null', true),
                                                'hash' => $log['hash'],
                                                'prev_hash' => $log['prev_hash']
                                            ]), ENT_QUOTES, 'UTF-8') ?>)">
                                        Xem Diff
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang -->
        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-between align-items-center mt-3">
                <span class="small text-muted">Hiển thị trang <?= $page ?> / <?= $totalPages ?> (Tổng <?= number_format($total) ?> bản ghi)</span>
                <ul class="pagination pagination-sm m-0">
                    <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/he-thong/audit?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal xem Diff dữ liệu cũ / mới -->
<div class="modal fade" id="diffModal" tabindex="-1" aria-labelledby="diffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="diffModalLabel">Chi tiết Thay đổi Dữ liệu & Đối soát WORM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="badge bg-primary" id="diff-action"></span>
                    <span class="badge bg-secondary" id="diff-table"></span>
                    <span class="small text-muted ms-2">Mã kiểm toán: <strong id="diff-id"></strong></span>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger">Dữ liệu trước thay đổi (Cũ):</label>
                        <pre id="diff-old" class="bg-light p-2 border rounded font-monospace small" style="max-height: 250px; overflow: auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success">Dữ liệu sau thay đổi (Mới):</label>
                        <pre id="diff-new" class="bg-light p-2 border rounded font-monospace small" style="max-height: 250px; overflow: auto;"></pre>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold">Chuỗi băm toàn vẹn SHA-256 WORM:</label>
                    <div class="small bg-light p-2 border rounded font-monospace">
                        <div><strong>Block Hash:</strong> <code id="diff-hash"></code></div>
                        <div class="text-muted"><strong>Prev Block Hash:</strong> <code id="diff-prev-hash"></code></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
function verifyWormChain() {
    const btn = document.getElementById('btn-verify-worm');
    const resBox = document.getElementById('worm-verify-result');
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang quét chuỗi băm...';

    fetch('<?= url('/admin/he-thong/audit/verify-chain') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= csrf_token() ?>',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span class="icon">🛡️</span> <strong>Kiểm tra toàn vẹn chuỗi băm</strong>';
        resBox.classList.remove('d-none', 'alert-success', 'alert-danger');

        if (data.valid) {
            resBox.classList.add('alert-success');
            resBox.innerHTML = `<strong>✅ KẾT QUẢ: TOÀN VẸN 100%</strong><br>
                Đã kiểm tra liên kết chuỗi băm WORM thành công trên <strong>${data.total_verified}</strong> bản ghi. Không phát hiện sự cố can thiệp hay sửa đổi dữ liệu trái phép.`;
        } else {
            resBox.classList.add('alert-danger');
            resBox.innerHTML = `<strong>⚠️ CẢNH BÁO: PHÁT HIỆN LỖI TOÀN VẸN CHUỖI BĂM!</strong><br>
                Phát hiện sai lệch tại bản ghi ID <strong>#${data.broken_at_id}</strong>: ${data.reason}`;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Kiểm tra thất bại';
        alert('Lỗi kết nối khi kiểm tra chuỗi băm WORM.');
    });
}

function showDiffModal(data) {
    document.getElementById('diff-id').innerText = '#' + data.id;
    document.getElementById('diff-action').innerText = data.action;
    document.getElementById('diff-table').innerText = data.table;
    document.getElementById('diff-old').innerText = data.old ? JSON.stringify(data.old, null, 2) : '(Không có dữ liệu cũ)';
    document.getElementById('diff-new').innerText = data.new ? JSON.stringify(data.new, null, 2) : '(Không có dữ liệu mới)';
    document.getElementById('diff-hash').innerText = data.hash;
    document.getElementById('diff-prev-hash').innerText = data.prev_hash;

    const modal = new bootstrap.Modal(document.getElementById('diffModal'));
    modal.show();
}
</script>
