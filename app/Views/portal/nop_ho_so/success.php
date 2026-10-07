<?php $this->layout('layouts/portal', ['title' => 'Nộp hồ sơ trực tuyến thành công']); ?>

<?php $this->startSection('content'); ?>
<div style="max-width: 800px; margin: 0 auto;">
    <!-- THẺ CHÚC MỪNG -->
    <div class="card p-4 text-center mb-4" style="background: #fff; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 6px solid #16a34a;">
        <div style="font-size: 3.5rem; margin-bottom: 0.5rem;">🎉</div>
        <h1 style="font-size: 1.6rem; color: #166534; font-weight: 800; margin: 0 0 0.5rem 0;">
            NỘP HỒ SƠ TRỰC TUYẾN THÀNH CÔNG!
        </h1>
        <p class="text-muted" style="margin: 0 0 1.5rem 0; font-size: 0.95rem;">
            Hồ sơ đã được mã hóa, ký số điện tử và chuyển thẳng tới Bộ phận Một cửa để thẩm định.
        </p>

        <!-- KHUNG MÃ HỒ SƠ QCVN 102 -->
        <div style="background: #f8fafc; border: 2px dashed #0b2e59; border-radius: 10px; padding: 1.25rem; display: inline-block; margin-bottom: 1.5rem;">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase;">
                MÃ HỒ SƠ ĐỊNH DANH (QCVN 102:2016/BTTTT)
            </div>
            <div style="font-size: 1.6rem; font-weight: 800; color: #0b2e59; font-family: monospace; letter-spacing: 1px; margin-top: 0.25rem;">
                <?= e($hoSo['ma_ho_so']) ?>
            </div>
        </div>

        <!-- MÃ QR TRA CỨU TIẾN ĐỘ -->
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <div id="qrcode-container" style="background: #fff; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: var(--shadow-sm);"></div>
            <small class="text-muted" style="font-size: 0.75rem; margin-top: 0.5rem;">Quét mã QR bằng điện thoại để tra cứu tiến độ mọi lúc mọi nơi</small>
        </div>

        <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= e(url('/cong-dan/ho-so/' . $hoSo['ma_ho_so'])) ?>" class="btn btn-primary" style="font-weight: 600;">
                📊 Theo dõi tiến trình hồ sơ
            </a>
            <a href="<?= e(url('/tra-cuu?ma_ho_so=' . $hoSo['ma_ho_so'])) ?>" class="btn btn-outline-info">
                🔍 Trang tra cứu công khai
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                🖨️ In giấy biên nhận
            </button>
        </div>
    </div>

    <!-- GIẤY BIÊN NHẬN TẠM THỜI -->
    <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <div style="text-align: center; border-bottom: 2px solid #0b2e59; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <div style="font-size: 0.85rem; font-weight: 700; color: #1e293b;">ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: #0b2e59; margin-top: 0.25rem;">GIẤY TIẾP NHẬN HỒ SƠ & HẸN TRẢ KẾT QUẢ ĐIỆN TỬ</div>
            <div style="font-size: 0.75rem; color: #64748b;">(Mẫu ban hành theo Thông tư số 01/2018/TT-VPCP của Văn phòng Chính phủ)</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.85rem; margin-bottom: 1rem;">
            <div><strong>Người nộp hồ sơ:</strong> <?= e($hoSo['nguoi_nop_ho_ten']) ?></div>
            <div><strong>Số CCCD / ĐDCN:</strong> <?= e($hoSo['nguoi_nop_cccd']) ?></div>
            <div><strong>Số điện thoại:</strong> <?= e($hoSo['nguoi_nop_sdt'] ?: 'Theo VNeID') ?></div>
            <div><strong>Thời điểm nộp:</strong> <?= date('d/m/Y H:i', strtotime($hoSo['ngay_nop'])) ?></div>
            <div style="grid-column: 1 / -1;"><strong>Thủ tục hành chính:</strong> <?= e($hoSo['ten_tthc']) ?></div>
            <div style="grid-column: 1 / -1;"><strong>Cơ quan tiếp nhận:</strong> <?= e($hoSo['ten_don_vi']) ?></div>
            <div style="grid-column: 1 / -1; color: #166534;"><strong>Ngày hẹn trả kết quả:</strong> <?= date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) ?> (hoặc sớm hơn)</div>
        </div>

        <div class="p-2" style="background: #f8fafc; border-radius: 6px; font-size: 0.8rem; color: #475569;">
            📌 <em>Lưu ý: Hệ thống đã gửi tin nhắn SMS Brandname "UBND TP HP" và thông báo Zalo ZNS xác nhận tiếp nhận. Vui lòng giữ mã hồ sơ để đối chiếu khi cần thiết.</em>
        </div>
    </div>
</div>

<script src="<?= e(asset('vendor/qrcode/qrcode.min.js')) ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qrContainer = document.getElementById('qrcode-container');
    if (qrContainer && typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
            text: "<?= $lookupUrl ?>",
            width: 130,
            height: 130,
            colorDark: "#0b2e59",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
<?php $this->endSection(); ?>
