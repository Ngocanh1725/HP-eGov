<?php $this->layout('layouts/admin', ['title' => "Thiết kế Quy trình: {$workflow['ten_quy_trinh']}"]); ?>

<?php $this->startSection('content'); ?>
<div class="content-header mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="<?= e(url('/admin/quy-trinh')) ?>" class="text-muted" style="text-decoration: none; font-size: 0.85rem;">⬅ Danh sách quy trình</a>
            <span class="text-muted">/</span>
            <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 700;">v<?= e($workflow['phien_ban']) ?></span>
            <?php if ($workflow['is_active']): ?>
                <span class="badge badge-success" style="font-size: 0.75rem;">Đang áp dụng</span>
            <?php endif; ?>
        </div>
        <h1 style="font-size: 1.5rem; color: var(--color-primary); margin: 0;">
            <?= e($workflow['ten_quy_trinh']) ?>
        </h1>
        <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">
            Thủ tục áp dụng: <strong>[<?= e($workflow['ma_rut_gon'] ?? 'N/A') ?>] <?= e($workflow['ten_tthc'] ?? 'TTHC chung') ?></strong>
            · SLA quy định: <strong><?= e($workflow['thoi_han_giai_quyet_ngay'] ?? 0) ?> ngày (<?= ((int)($workflow['thoi_han_giai_quyet_ngay'] ?? 0)) * 8 ?> giờ)</strong>
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('modal-add-step').style.display='flex'">
            ➕ Thêm bước
        </button>
        <button type="button" class="btn btn-outline-info" onclick="document.getElementById('modal-add-transition').style.display='flex'">
            🔗 Thêm luồng chuyển tiếp
        </button>
        <form action="<?= e(url('/admin/quy-trinh/' . $workflow['id'] . '/nhan-ban-phien-ban')) ?>" method="POST" onsubmit="return confirm('Tạo phiên bản mới (v<?= ((int)$workflow['phien_ban']) + 1 ?>)? Các hồ sơ đang giải quyết sẽ giữ nguyên phiên bản cũ an toàn.');" style="display: inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-warning">
                📋 Tạo phiên bản mới (v<?= ((int)$workflow['phien_ban']) + 1 ?>)
            </button>
        </form>
    </div>
</div>

<!-- Thẩm định quy trình (Workflow Validation & SLA Checker) -->
<div class="mb-4">
    <?php if (!$validation['is_valid']): ?>
        <div class="card p-3" style="background: #fef2f2; border-left: 4px solid #dc2626; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                <span>⚠️</span> QUY TRÌNH CHƯA HỢP LỆ THEO CHUẨN BPMN-LITE
            </div>
            <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0; color: #b91c1c; font-size: 0.85rem;">
                <?php foreach ($validation['errors'] as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="card p-3" style="background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #166534; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                <span>✅</span> QUY TRÌNH HỢP LỆ CHUẨN BPMN-LITE
            </div>
            <div style="font-size: 0.85rem; color: #15803d; margin-top: 0.25rem;">
                Quy trình có đầy đủ điểm bắt đầu (Tiếp nhận Một cửa) và kết thúc (Trả kết quả), không có bước mồ côi. 
                Tổng thời hạn thiết kế: <strong><?= e($validation['total_step_hours']) ?> giờ</strong> 
                (Thời hạn TTHC: <strong><?= e($validation['expected_tthc_hours']) ?> giờ</strong>).
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($validation['warnings'])): ?>
        <div class="card p-3 mt-2" style="background: #fffbeb; border-left: 4px solid #d97706; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #92400e; font-size: 0.9rem;">
                <span>⚠️</span> CẢNH BÁO THỜI HẠN XỬ LÝ (SLA):
            </div>
            <ul style="margin: 0.25rem 0 0 1.25rem; padding: 0; color: #b45309; font-size: 0.85rem;">
                <?php foreach ($validation['warnings'] as $warn): ?>
                    <li><?= e($warn) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- SƠ ĐỒ QUY TRÌNH TRỰC QUAN -->
<div class="card mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow: hidden;">
    <div class="card-header p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 1rem; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
            <span>🗺️</span> Sơ đồ luồng xử lý trực quan (BPMN-lite Flow)
        </h3>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleMermaidRaw()">
                📜 Mã Mermaid
            </button>
        </div>
    </div>
    <div class="p-4" style="background: #fafafa; overflow-x: auto;">
        <!-- Visual Flow Nodes & Arrows -->
        <div style="display: flex; align-items: center; min-width: 800px; gap: 0.75rem; padding: 1rem 0;">
            <?php foreach ($steps as $index => $step): ?>
                <!-- Step Node Card -->
                <div style="flex: 1; min-width: 170px; background: #fff; border: 2px solid <?= ($index === 0 ? '#10b981' : ($index === count($steps) - 1 ? '#0ea5e9' : '#cbd5e1')) ?>; border-radius: 8px; padding: 0.85rem; box-shadow: var(--shadow-sm); position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <span style="background: #f1f5f9; color: #334155; font-size: 0.75rem; font-weight: 700; width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                            <?= e($step['stt_buoc']) ?>
                        </span>
                        <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.7rem;">
                            <?= e($step['loai_buoc']) ?>
                        </span>
                    </div>
                    <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b; min-height: 38px; line-height: 1.25;">
                        <?= e($step['ten_buoc']) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem; border-top: 1px dashed #e2e8f0; padding-top: 0.4rem;">
                        <div>👤 <strong><?= e($step['vai_tro_xu_ly']) ?></strong></div>
                        <div style="margin-top: 2px; color: var(--color-primary); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($step['ten_don_vi'] ?? 'Chưa gán đơn vị') ?>">
                            🏢 <?= e($step['ten_don_vi'] ?? 'Nội bộ') ?>
                        </div>
                        <div style="margin-top: 2px; color: #ea580c; font-weight: 600;">
                            ⏱️ <?= e($step['thoi_han_gio']) ?> giờ
                        </div>
                    </div>
                </div>

                <!-- Arrow between nodes -->
                <?php if ($index < count($steps) - 1): ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; font-size: 1.25rem;">
                        <span>➔</span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Khối Mermaid Raw Code ẩn/hiện -->
        <div id="mermaid-raw-box" style="display: none; margin-top: 1rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
            <label style="font-size: 0.8rem; font-weight: 600; color: #475569;">Mermaid BPMN Syntax:</label>
            <pre style="background: #1e293b; color: #f8fafc; padding: 1rem; border-radius: 6px; font-size: 0.8rem; overflow-x: auto;"><?= e($mermaid) ?></pre>
        </div>
    </div>
</div>

<!-- 2 Cột: Bảng các bước & Bảng luồng chuyển tiếp -->
<div class="row" style="display: grid; grid-template-columns: 3fr 2fr; gap: 1.5rem;">
    <!-- Cột trái: Danh sách các bước -->
    <div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
        <div class="p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 0.95rem; margin: 0; color: #1e293b; font-weight: 700;">Danh sách các bước trong quy trình (<?= count($steps) ?> bước)</h3>
        </div>
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.65rem 0.75rem; text-align: center; width: 45px;">STT</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: left;">Tên bước</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center;">Loại bước</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: left;">Vai trò</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: left;">Đơn vị xử lý</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: right;">Hạn (h)</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center;">Trả lại</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($steps as $s): ?>
                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                        <td style="padding: 0.65rem 0.75rem; text-align: center; font-weight: 700; color: #64748b;">
                            <?= e($s['stt_buoc']) ?>
                        </td>
                        <td style="padding: 0.65rem 0.75rem; font-weight: 600; color: #1e293b;">
                            <?= e($s['ten_buoc']) ?>
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            <span class="badge" style="background: #e2e8f0; font-size: 0.75rem;">
                                <?= e($s['loai_buoc']) ?>
                            </span>
                        </td>
                        <td style="padding: 0.65rem 0.75rem; font-size: 0.8rem; color: #475569;">
                            <?= e($s['vai_tro_xu_ly']) ?>
                        </td>
                        <td style="padding: 0.65rem 0.75rem; font-size: 0.8rem; color: var(--color-primary); font-weight: 500;">
                            <?= e($s['ten_don_vi'] ?? 'Mặc định') ?>
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: right; font-weight: 700; color: #ea580c;">
                            <?= e($s['thoi_han_gio']) ?>h
                        </td>
                        <td style="padding: 0.65rem 0.75rem; text-align: center;">
                            <?= $s['cho_phep_tra_lai'] ? '✅' : '❌' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cột phải: Danh sách luồng chuyển tiếp -->
    <div class="card p-0" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); overflow-x: auto;">
        <div class="p-3" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 0.95rem; margin: 0; color: #1e293b; font-weight: 700;">Luồng chuyển tiếp (<?= count($transitions) ?> luồng)</h3>
        </div>
        <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
            <thead style="background: #fafafa; font-size: 0.8rem; border-bottom: 1px solid var(--border-color);">
                <tr>
                    <th style="padding: 0.65rem 0.75rem; text-align: left;">Từ bước ➔ Đến bước</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: center;">Hành động</th>
                    <th style="padding: 0.65rem 0.75rem; text-align: left;">Điều kiện</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transitions)): ?>
                    <tr><td colspan="3" style="padding: 1rem; text-align: center; color: #64748b;">Chưa có luồng chuyển tiếp.</td></tr>
                <?php else: ?>
                    <?php foreach ($transitions as $tr): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                            <td style="padding: 0.65rem 0.75rem;">
                                <div style="font-weight: 600; color: #334155;"><?= e($tr['tu_ten'] ?? ('Bước ID ' . $tr['tu_buoc_id'])) ?></div>
                                <div style="color: #64748b; font-size: 0.75rem;">➔ <?= e($tr['den_ten'] ?? ('Bước ID ' . $tr['den_buoc_id'])) ?></div>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; text-align: center;">
                                <?php 
                                $actBadge = match($tr['hanh_dong']) {
                                    'CHUYEN' => 'badge-primary',
                                    'TRA_LAI' => 'badge-warning',
                                    'YEU_CAU_BO_SUNG' => 'badge-secondary',
                                    'TU_CHOI' => 'badge-danger',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?= $actBadge ?>" style="font-size: 0.75rem;">
                                    <?= e($tr['hanh_dong']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.65rem 0.75rem; font-size: 0.8rem; color: #64748b;">
                                <?= e($tr['dieu_kien'] ?? 'Mặc định') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm Bước Mới -->
<div id="modal-add-step" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 100%; max-width: 600px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-primary); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #fff;">Thêm bước xử lý mới</h3>
            <button type="button" onclick="document.getElementById('modal-add-step').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= e(url('/admin/quy-trinh/' . $workflow['id'] . '/buoc')) ?>" method="POST" style="padding: 1.5rem;">
            <?= csrf_field() ?>
            <div style="display: grid; grid-template-columns: 1fr 3fr; gap: 0.75rem;" class="mb-3">
                <div>
                    <label class="form-label" style="font-weight: 600;">STT bước:</label>
                    <input type="number" name="stt_buoc" class="form-control" value="<?= count($steps) + 1 ?>" required min="1">
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600;">Tên bước: <span style="color: red;">*</span></label>
                    <input type="text" name="ten_buoc" class="form-control" placeholder="VD: Thẩm định hồ sơ kỹ thuật..." required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;" class="mb-3">
                <div>
                    <label class="form-label" style="font-weight: 600;">Loại bước:</label>
                    <select name="loai_buoc" class="form-control">
                        <option value="TIEP_NHAN">TIEP_NHAN (Tiếp nhận)</option>
                        <option value="PHAN_CONG">PHAN_CONG (Phân công)</option>
                        <option value="THU_LY" selected>THU_LY (Thụ lý hồ sơ)</option>
                        <option value="LAY_Y_KIEN">LAY_Y_KIEN (Lấy ý kiến liên ngành)</option>
                        <option value="THAM_TRA">THAM_TRA (Thẩm tra)</option>
                        <option value="PHE_DUYET">PHE_DUYET (Phê duyệt)</option>
                        <option value="DONG_DAU">DONG_DAU (Đóng dấu/Ký số)</option>
                        <option value="TRA_KET_QUA">TRA_KET_QUA (Trả kết quả)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600;">Vai trò xử lý:</label>
                    <select name="vai_tro_xu_ly" class="form-control">
                        <option value="CHUYEN_VIEN_MOT_CUA">Chuyên viên Một cửa</option>
                        <option value="CHUYEN_VIEN" selected>Chuyên viên Thụ lý</option>
                        <option value="TRUONG_PHONG">Trưởng phòng</option>
                        <option value="LANH_DAO_DON_VI">Lãnh đạo Cơ quan / Đơn vị</option>
                        <option value="LANH_DAO_UBND_TP">Lãnh đạo UBND Thành phố</option>
                        <option value="VAN_THU">Văn thư đóng dấu</option>
                    </select>
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Đơn vị cơ quan xử lý (Hỗ trợ liên thông 5 đơn vị):</label>
                <select name="don_vi_xu_ly_id" class="form-control">
                    <?php foreach ($donViList as $dv): ?>
                        <option value="<?= $dv['id'] ?>">
                            <?= e($dv['ten_don_vi']) ?> (<?= e($dv['loai_don_vi']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;" class="mb-3">
                <div>
                    <label class="form-label" style="font-weight: 600;">Thời hạn thực hiện (giờ):</label>
                    <input type="number" name="thoi_han_gio" class="form-control" value="8" min="1" required>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
                    <input type="checkbox" name="cho_phep_tra_lai" value="1" id="cptl" checked>
                    <label for="cptl" style="font-size: 0.85rem; font-weight: 600;">Cho phép trả lại hồ sơ</label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add-step').style.display='none'">Hủy</button>
                <button type="submit" class="btn btn-primary">Thêm bước</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thêm Luồng Chuyển Tiếp -->
<div id="modal-add-transition" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div style="background: #fff; width: 100%; max-width: 550px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-primary); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #fff;">Thêm luồng chuyển tiếp giữa các bước</h3>
            <button type="button" onclick="document.getElementById('modal-add-transition').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="<?= e(url('/admin/quy-trinh/' . $workflow['id'] . '/chuyen-tiep')) ?>" method="POST" style="padding: 1.5rem;">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Từ bước:</label>
                <select name="tu_buoc_id" class="form-control" required>
                    <option value="">-- Chọn bước bắt đầu chuyển --</option>
                    <?php foreach ($steps as $s): ?>
                        <option value="<?= $s['id'] ?>">Bước <?= e($s['stt_buoc']) ?>: <?= e($s['ten_buoc']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Đến bước:</label>
                <select name="den_buoc_id" class="form-control" required>
                    <option value="">-- Chọn bước nhận tiếp theo --</option>
                    <?php foreach ($steps as $s): ?>
                        <option value="<?= $s['id'] ?>">Bước <?= e($s['stt_buoc']) ?>: <?= e($s['ten_buoc']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Hành động chuyển:</label>
                <select name="hanh_dong" class="form-control">
                    <option value="CHUYEN">Chuyển tiếp (Bình thường)</option>
                    <option value="TRA_LAI">Trả lại bước trước</option>
                    <option value="YEU_CAU_BO_SUNG">Yêu cầu bổ sung hồ sơ</option>
                    <option value="TU_CHOI">Từ chối giải quyết</option>
                </select>
            </div>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Điều kiện chuyển tiếp:</label>
                <input type="text" name="dieu_kien" class="form-control" placeholder="VD: Đạt yêu cầu, Có ý kiến đồng thuận...">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-add-transition').style.display='none'">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu luồng chuyển tiếp</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMermaidRaw() {
    const box = document.getElementById('mermaid-raw-box');
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php $this->endSection(); ?>
