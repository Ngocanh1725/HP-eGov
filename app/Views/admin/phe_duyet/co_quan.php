<?php $this->layout('layouts/admin', ['title' => $title ?? 'Lãnh đạo cơ quan - Phê duyệt & Ký số phát hành']) ?>

<?php $this->section('content') ?>
<div class="page-header" style="margin-bottom: 20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: #0B2E59; margin: 0 0 6px 0;">
                <span style="color:#C8102E;">🏛️</span> Phê duyệt & Ký số Phát hành (Lãnh đạo Cơ quan)
            </h1>
            <p style="color: #64748B; margin: 0; font-size: 14px;">
                Phê duyệt ban hành kết quả TTHC chính thức bằng Chữ ký số SmartCA / Token Ban Cơ yếu Chính phủ
            </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="<?= e(url('/admin/phe-duyet/cho-huy')) ?>" class="btn btn-secondary" style="border-color:#DC2626; color:#DC2626;">
                ⚠️ Đề xuất hủy hồ sơ
            </a>
            <button type="button" class="btn btn-primary" id="btn-batch-sign-trigger" onclick="openBatchSignModal()" style="background:#0284C7; border-color:#0284C7;">
                ⚡ Ký số hàng loạt (<span id="selected-count">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card" style="margin-bottom: 20px; padding: 14px;">
    <form method="GET" action="<?= e(url('/admin/phe-duyet/co-quan')) ?>" style="display:flex; gap:12px; flex-wrap:wrap;">
        <input type="text" name="q" value="<?= e($q ?? '') ?>" class="form-control" placeholder="Tìm theo mã hồ sơ, tên người nộp, thủ tục..." style="flex:1; min-width:240px;">
        <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
        <?php if (!empty($q)): ?>
            <a href="<?= e(url('/admin/phe-duyet/co-quan')) ?>" class="btn btn-secondary">Đặt lại</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bảng hồ sơ với Checkbox hỗ trợ Ký hàng loạt & Mobile responsive -->
<div class="card" style="padding:0; overflow:hidden;">
    <div style="padding:14px 20px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <label style="display:flex; align-items:center; gap:6px; font-weight:600; cursor:pointer; font-size:14px; margin:0;">
                <input type="checkbox" id="check-all" onchange="toggleSelectAll(this)" style="width:18px; height:18px;">
                Chọn tất cả hồ sơ hợp lệ
            </label>
            <span style="color:#64748B; font-size:13px;">| Đã chọn: <strong id="lbl-selected-count" style="color:#0284C7;">0</strong></span>
        </div>
        <div style="font-size:12px; color:#16A34A; font-weight:600;">
            ⚡ Tối ưu di động · Tốc độ ký < 2 giây/hồ sơ
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" style="margin-bottom:0;" id="table-ho-so-ky">
            <thead>
                <tr>
                    <th style="width: 40px; text-align:center;">#</th>
                    <th style="width: 170px;">Mã hồ sơ</th>
                    <th>Thủ tục hành chính</th>
                    <th style="width: 150px;">Người nộp</th>
                    <th style="width: 140px;">Chuyên viên</th>
                    <th style="width: 120px; text-align:center;">Duyệt bước 1</th>
                    <th style="width: 120px;">Hạn cam kết</th>
                    <th style="width: 110px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoSoList)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px 20px; color:#64748B;">
                            <div style="font-size:36px; margin-bottom:10px;">🎉</div>
                            Hiện không có hồ sơ nào đang chờ Lãnh đạo cơ quan ký phát hành.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoSoList as $hs): ?>
                        <tr id="row-hs-<?= e($hs['id']) ?>">
                            <td style="text-align:center;">
                                <input type="checkbox" class="hs-checkbox" value="<?= e($hs['id']) ?>" onchange="updateSelectedCount()" style="width:18px; height:18px; cursor:pointer;">
                            </td>
                            <td>
                                <a href="<?= e(url('/admin/phe-duyet/co-quan/' . $hs['id'])) ?>" style="font-weight:700; color:#0B2E59; text-decoration:none;">
                                    <?= e($hs['ma_ho_so']) ?>
                                </a>
                                <div style="font-size:11px; color:#64748B; margin-top:2px;">
                                    <?= e($hs['ten_don_vi'] ?? '') ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:#1E293B;">
                                    <?= e($hs['ten_tthc']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:500;"><?= e($hs['ho_ten_nguoi_nop']) ?></div>
                                <div style="font-size:11px; color:#64748B;"><?= e($hs['so_dien_thoai_nguoi_nop'] ?? '') ?></div>
                            </td>
                            <td>
                                <div style="font-size:13px; color:#1E293B;">
                                    👤 <?= e($hs['ten_chuyen_vien'] ?? '---') ?>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <?php if ((int)$hs['phong_da_duyet'] > 0): ?>
                                    <span class="badge" style="background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; font-size:11px;">
                                        ✓ Phòng đã duyệt
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#FEF3C7; color:#D97706; font-size:11px;">
                                        Chờ ký tờ trình
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:13px; font-weight:600; color:#0B2E59;">
                                    <?= date('d/m/Y', strtotime($hs['ngay_hen_tra'])) ?>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= e(url('/admin/phe-duyet/co-quan/' . $hs['id'])) ?>" class="btn btn-sm btn-primary" id="btn-ky-hs-<?= e($hs['id']) ?>">
                                    Ký phát hành →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ký số hàng loạt (Batch Signing) -->
<div id="modal-batch-sign" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:540px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
            <h3 style="margin:0; font-size:18px; color:#0B2E59; display:flex; align-items:center; gap:8px;">
                <span style="color:#0284C7;">⚡</span> Ký số hàng loạt qua SmartCA
            </h3>
            <button type="button" onclick="closeBatchSignModal()" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <div id="batch-form-area">
            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#166534;">
                Đã chọn: <strong id="modal-count-display">0</strong> hồ sơ hợp lệ.
                Chỉ cần nhập mã PIN SmartCA duy nhất <strong>01 lần</strong> để phê duyệt toàn bộ.
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:6px;">Ý kiến chỉ đạo chung:</label>
                <input type="text" id="batch-y-kien" class="form-control" value="Đồng ý phê duyệt ban hành kết quả giải quyết TTHC theo quy định." style="width:100%;">
            </div>

            <div style="margin-bottom:20px; background:#F0F9FF; border:1px solid #BAE6FD; padding:16px; border-radius:8px;">
                <label style="font-weight:700; font-size:13px; color:#0369A1; display:block; margin-bottom:6px;">
                    Mã PIN SmartCA Lãnh đạo cơ quan: <span style="color:#C8102E;">*</span>
                </label>
                <input type="password" id="batch-pin" class="form-control" placeholder="Nhập PIN (Mẫu: 123456)" maxlength="6" style="font-size:20px; letter-spacing:4px; text-align:center;">
                <div style="font-size:11px; color:#64748B; margin-top:6px; text-align:center;">
                    Hệ thống sẽ ký tuần tự bằng OpenSSL và ghi nhật ký chu_ky_so cho từng hồ sơ
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeBatchSignModal()">Hủy</button>
                <button type="button" class="btn btn-primary" id="btn-submit-batch" onclick="executeBatchSign()">
                    🚀 Bắt đầu ký số hàng loạt
                </button>
            </div>
        </div>

        <!-- Vùng hiển thị tiến trình & kết quả batch -->
        <div id="batch-progress-area" style="display:none; text-align:center; padding:10px 0;">
            <div style="font-size:14px; font-weight:600; color:#0B2E59; margin-bottom:10px;">
                Đang xử lý ký số hàng loạt qua OpenSSL...
            </div>
            <div style="width:100%; height:10px; background:#E2E8F0; border-radius:5px; overflow:hidden; margin-bottom:16px;">
                <div id="batch-progress-bar" style="width:100%; height:100%; background:#0284C7; animation:pulse 1.5s infinite;"></div>
            </div>
        </div>

        <div id="batch-result-area" style="display:none; max-height:260px; overflow-y:auto; margin-top:10px;">
            <div id="batch-result-summary" style="font-weight:700; font-size:14px; margin-bottom:10px; padding:10px; border-radius:6px;"></div>
            <table class="table" style="font-size:12px;">
                <thead>
                    <tr><th>Mã hồ sơ</th><th>Trạng thái</th><th>Thời gian</th></tr>
                </thead>
                <tbody id="batch-result-tbody"></tbody>
            </table>
            <div style="text-align:right; margin-top:14px;">
                <button type="button" class="btn btn-primary" onclick="window.location.reload();">Đóng & Làm mới</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.hs-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.hs-checkbox:checked');
    const count = selected.length;
    document.getElementById('selected-count').innerText = count;
    document.getElementById('lbl-selected-count').innerText = count;
    document.getElementById('modal-count-display').innerText = count;
}

function openBatchSignModal() {
    const selected = document.querySelectorAll('.hs-checkbox:checked');
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một hồ sơ trong danh sách để ký số hàng loạt.');
        return;
    }
    document.getElementById('batch-form-area').style.display = 'block';
    document.getElementById('batch-progress-area').style.display = 'none';
    document.getElementById('batch-result-area').style.display = 'none';
    document.getElementById('batch-pin').value = '';
    document.getElementById('modal-batch-sign').style.display = 'flex';
}

function closeBatchSignModal() {
    document.getElementById('modal-batch-sign').style.display = 'none';
}

function executeBatchSign() {
    const selected = document.querySelectorAll('.hs-checkbox:checked');
    const hoSoIds = Array.from(selected).map(cb => cb.value);
    const pin = document.getElementById('batch-pin').value.trim();
    const yKien = document.getElementById('batch-y-kien').value.trim();

    if (!pin) {
        alert('Vui lòng nhập mã PIN SmartCA.');
        return;
    }

    document.getElementById('batch-form-area').style.display = 'none';
    document.getElementById('batch-progress-area').style.display = 'block';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('pin', pin);
    formData.append('y_kien', yKien);
    hoSoIds.forEach(id => formData.append('ho_so_ids[]', id));

    fetch('<?= e(url('/admin/phe-duyet/co-quan/batch-sign')) ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('batch-progress-area').style.display = 'none';
        document.getElementById('batch-result-area').style.display = 'block';

        if (data.success && data.data) {
            const resData = data.data;
            const summaryDiv = document.getElementById('batch-result-summary');
            summaryDiv.className = 'bg-success-light text-success';
            summaryDiv.style.background = '#ECFDF5';
            summaryDiv.style.color = '#065F46';
            summaryDiv.innerHTML = `✓ ${data.message} | Tổng thời gian: ${resData.duration_total}s (TB ${resData.duration_avg}s/lần ký)`;

            const tbody = document.getElementById('batch-result-tbody');
            tbody.innerHTML = '';
            resData.results.forEach(item => {
                const tr = document.createElement('tr');
                const isSuccess = item.status === 'SUCCESS';
                tr.innerHTML = `
                    <td style="font-weight:600;">${item.ma_ho_so}</td>
                    <td><span style="color:${isSuccess ? '#16A34A' : '#DC2626'}; font-weight:bold;">${isSuccess ? '✓ Đã ký' : '✖ Thất bại'}</span></td>
                    <td>${item.duration}s</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            alert(data.message || 'Lỗi xử lý ký số hàng loạt');
            closeBatchSignModal();
        }
    })
    .catch(err => {
        alert('Lỗi kết nối máy chủ: ' + err);
        closeBatchSignModal();
    });
}
</script>

<?php $this->endSection() ?>
