<?php $this->layout('layouts/admin', ['title' => $pageTitle]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
        <a href="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Danh sách hồ sơ trực tuyến</a>
        <span class="text-muted">/</span>
        <span style="font-family: monospace; font-size: 0.85rem; font-weight: 700; color: #1e40af;"><?= e($hoSo['ma_ho_so']) ?></span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; color: #0b2e59; margin: 0; font-weight: 800;">
                THẨM TRA HỒ SƠ TRỰC TUYẾN: <?= e($hoSo['ma_ho_so']) ?>
            </h1>
            <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">
                Thủ tục: <strong><?= e($hoSo['ten_tthc']) ?></strong> · Tiếp nhận lúc: <?= date('d/m/Y H:i', strtotime($hoSo['ngay_nop'])) ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <!-- Nút 1: Tiếp nhận -->
            <form action="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hoSo['id'] . '/tiep-nhan')) ?>" method="POST" onsubmit="return confirm('Xác nhận TIẾP NHẬN hồ sơ trực tuyến này và chuyển thụ lý theo quy trình?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success" style="font-weight: 700;">
                    ✔ Tiếp nhận hồ sơ
                </button>
            </form>
            <!-- Nút 2: Yêu cầu bổ sung -->
            <button type="button" class="btn btn-warning" onclick="openModal('modal-yeu-cau-bo-sung')" style="font-weight: 600;">
                ⚠️ Yêu cầu bổ sung
            </button>
            <!-- Nút 3: Từ chối tiếp nhận -->
            <button type="button" class="btn btn-danger" onclick="openModal('modal-tu-choi')" style="font-weight: 600;">
                ✖ Từ chối tiếp nhận
            </button>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- CỘT TRÁI: E-FORM & TÀI LIỆU ĐÍNH KÈM -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- 1. Tờ khai điện tử E-Form -->
        <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0;">
                    📝 TỜ KHAI ĐIỆN TỬ E-FORM (CÔNG DÂN KÝ SỐ)
                </h2>
                <?php if (!empty($signature)): ?>
                    <span class="badge badge-success" style="font-size: 0.75rem;">
                        🔒 Đã ký số định danh SmartCA
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($eformData)): ?>
                <p class="text-muted">Không có dữ liệu tờ khai điện tử kèm theo.</p>
            <?php else: ?>
                <table class="table mb-0" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <?php foreach ($eformData as $k => $val): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="width: 35%; padding: 0.5rem 0.75rem; font-weight: 600; color: #475569; background: #f8fafc;">
                                    <?= e(ucfirst(str_replace('_', ' ', (string)$k))) ?>
                                </td>
                                <td style="padding: 0.5rem 0.75rem; color: #1e293b; font-weight: 500;">
                                    <?= nl2br(e((string)$val)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($signature)): ?>
                <div class="mt-3 p-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; font-size: 0.8rem;">
                    <div style="font-weight: 700; color: #166534; margin-bottom: 0.25rem;">THÔNG TIN CHỨNG THƯ SỐ ĐÃ KÝ:</div>
                    <div>Chủ thể ký: <strong><?= e($signature['signer_name'] ?? '') ?></strong> (CCCD: <?= e($signature['signer_id'] ?? '') ?>)</div>
                    <div>Thời điểm ký: <?= e($signature['signed_at'] ?? '') ?> · Thuật toán: <?= e($signature['algorithm'] ?? 'SHA256withRSA') ?></div>
                    <div style="font-family: monospace; font-size: 0.7rem; color: #64748b; margin-top: 2px;">
                        Hash: <?= e($signature['signature_hash'] ?? '') ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Danh sách tài liệu đính kèm -->
        <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0 0 1rem 0; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
                📎 THÀNH PHẦN HỒ SƠ & GIẤY TỜ ĐÍNH KÈM
            </h2>

            <?php if (empty($taiLieuList)): ?>
                <p class="text-muted">Không có tài liệu đính kèm nào.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($taiLieuList as $tl): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; background: #fafafa;">
                            <div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.9rem;">
                                    <?= e($tl['ten_giay_to'] ?: $tl['ten_goc']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                    Tên tệp: <?= e($tl['ten_goc']) ?> · Dung lượng: <?= number_format($tl['kich_thuoc_byte'] / 1024, 1) ?> KB · Định dạng: <?= e($tl['mime_type']) ?>
                                </div>
                                <div style="font-size: 0.7rem; font-family: monospace; color: #94a3b8;">
                                    SHA-256: <?= e($tl['sha256']) ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge badge-success" style="font-size: 0.75rem;">Đã kiểm tra an toàn</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CỘT PHẢI: THÔNG TIN NGƯỜI NỘP & HÌNH THỨC NHẬN -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <!-- Thẻ Người nộp -->
        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 4px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">NGƯỜI NỘP HỒ SƠ</span>
                <span class="badge badge-success" style="font-size: 0.7rem;">✔ VNeID Định danh</span>
            </div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #0b2e59;">
                <?= e($hoSo['nguoi_nop_ho_ten']) ?>
            </div>
            <div style="font-size: 0.85rem; color: #334155; margin-top: 0.5rem;">
                CCCD: <strong><?= e($hoSo['nguoi_nop_cccd']) ?></strong><br>
                Điện thoại: <strong><?= e($hoSo['nguoi_nop_sdt'] ?: 'Chưa cập nhật') ?></strong><br>
                Email: <?= e($hoSo['nguoi_nop_email'] ?: 'Chưa cập nhật') ?>
            </div>
            <div style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; border-top: 1px dashed #e2e8f0; padding-top: 0.5rem;">
                Địa chỉ: <?= e($hoSo['nguoi_nop_dia_chi']) ?>
            </div>
        </div>

        <!-- Thẻ Hình thức nhận kết quả -->
        <div class="card p-3" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem;">
                HÌNH THỨC NHẬN KẾT QUẢ
            </div>
            <div style="font-size: 1rem; font-weight: 700; color: #1e3a8a;">
                <?php 
                echo match($hoSo['hinh_thuc_nhan_kq']) {
                    'TRUC_TIEP' => '🏢 Nhận trực tiếp tại Một cửa',
                    'BUU_DIEN' => '🚚 Bưu chính công ích VNPost',
                    'DIEN_TU' => '🌐 Bản điện tử (Kho dữ liệu)',
                    default => 'Trực tiếp'
                };
                ?>
            </div>
            <?php if (!empty($hoSo['dia_chi_nhan_kq'])): ?>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 0.5rem;">
                    Địa chỉ chuyển phát: <?= e($hoSo['dia_chi_nhan_kq']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Thẻ Thời hạn cam kết -->
        <div class="card p-3" style="background: #f8fafc; border-left: 4px solid #16a34a; border-radius: var(--radius-md);">
            <div style="font-weight: 700; color: #166534; font-size: 0.85rem;">THỜI HẠN GIẢI QUYẾT CAM KẾT</div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #15803d; margin-top: 0.25rem;">
                <?= e($hoSo['thoi_han_giai_quyet_ngay']) ?> ngày làm việc
            </div>
            <small class="text-muted">Tính từ ngày Bộ phận Một cửa chính thức tiếp nhận</small>
        </div>
    </div>
</div>

<!-- MODAL YÊU CẦU BỔ SUNG -->
<div id="modal-yeu-cau-bo-sung" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div class="card p-4" style="background: #fff; width: 100%; max-width: 520px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg);">
        <h3 style="font-size: 1.15rem; color: #b45309; margin: 0 0 0.5rem 0; font-weight: 700;">
            ⚠️ YÊU CẦU BỔ SUNG HOÀN THIỆN HỒ SƠ
        </h3>
        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">
            Theo quy định NĐ 61/2018/NĐ-CP, cán bộ Một cửa chỉ được yêu cầu bổ sung hồ sơ <strong>MỘT LẦN DUY NHẤT</strong>. Vui lòng liệt kê rõ ràng toàn bộ nội dung cần bổ sung.
        </p>

        <form action="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hoSo['id'] . '/yeu-cau-bo-sung')) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Nội dung chi tiết yêu cầu bổ sung: <span style="color: red;">*</span></label>
                <textarea name="noi_dung_yeu_cau" class="form-control" rows="4" required placeholder="Nêu rõ các giấy tờ, thông tin còn thiếu hoặc chưa đúng quy định..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-yeu-cau-bo-sung')">Hủy</button>
                <button type="submit" class="btn btn-warning" style="font-weight: 700;">Gửi yêu cầu bổ sung</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TỪ CHỐI TIẾP NHẬN -->
<div id="modal-tu-choi" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div class="card p-4" style="background: #fff; width: 100%; max-width: 520px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg);">
        <h3 style="font-size: 1.15rem; color: #b91c1c; margin: 0 0 0.5rem 0; font-weight: 700;">
            ✖ TỪ CHỐI TIẾP NHẬN HỒ SƠ
        </h3>
        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">
            Hồ sơ sẽ bị từ chối và phát hành Phiếu từ chối giải quyết (Mẫu số 03). Bắt buộc phải nêu rõ căn cứ và lý do từ chối.
        </p>

        <form action="<?= e(url('/admin/mot-cua/ho-so-truc-tuyen/' . $hoSo['id'] . '/tu-choi')) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="form-group mb-3">
                <label class="form-label" style="font-weight: 600;">Lý do từ chối tiếp nhận: <span style="color: red;">*</span></label>
                <textarea name="ly_do_tu_choi" class="form-control" rows="4" required placeholder="Nêu rõ lý do không đủ điều kiện theo quy định của pháp luật..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-tu-choi')">Hủy</button>
                <button type="submit" class="btn btn-danger" style="font-weight: 700;">Xác nhận từ chối</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
</script>
<?php $this->endSection(); ?>
