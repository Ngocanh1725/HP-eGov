<?php $this->layout('layouts/portal', ['title' => 'Tra cứu Tiến độ Hồ sơ & Quét mã QR']); ?>

<?php $this->startSection('content'); ?>
<div style="max-width: 850px; margin: 0 auto;">
    <div class="text-center mb-4">
        <h1 style="font-size: 1.6rem; color: #0b2e59; margin: 0 0 0.25rem 0; font-weight: 800;">
            TRA CỨU TIẾN ĐỘ HỒ SƠ HÀNH CHÍNH
        </h1>
        <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
            Nhập mã định danh hồ sơ QCVN 102 hoặc quét mã QR trên giấy hẹn tiếp nhận
        </p>
    </div>

    <!-- KHUNG TRA CỨU: 2 TAB (NHẬP MÃ / QUÉT QR) -->
    <div class="card p-4 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <button type="button" id="tab-btn-code" class="btn btn-sm btn-primary" onclick="switchLookupTab('code')">
                ⌨️ Tra cứu bằng Mã hồ sơ
            </button>
            <button type="button" id="tab-btn-qr" class="btn btn-sm btn-outline-secondary" onclick="switchLookupTab('qr')">
                📷 Quét mã QR bằng Camera
            </button>
        </div>

        <!-- TAB 1: NHẬP MÃ HỒ SƠ -->
        <div id="tab-content-code">
            <form action="<?= e(url('/tra-cuu')) ?>" method="GET" style="display: flex; gap: 0.75rem;">
                <input type="text" name="ma_ho_so" class="form-control" placeholder="Nhập mã hồ sơ (VD: 000.04.15.H21-261007-4123-000001)" 
                       value="<?= e($maHoSo) ?>" required style="font-family: monospace; font-size: 0.95rem; font-weight: 600;">
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.75rem; font-weight: 600; white-space: nowrap;">
                    🔍 Tra cứu ngay
                </button>
            </form>
        </div>

        <!-- TAB 2: QUÉT QR CAMERA -->
        <div id="tab-content-qr" style="display: none; text-align: center;">
            <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto; border: 1px dashed var(--border-color); border-radius: 8px;"></div>
            <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.5rem;">
                Đặt mã QR trên Giấy hẹn trước camera để hệ thống tự động nhận diện
            </p>
        </div>
    </div>

    <!-- KẾT QUẢ TRA CỨU (NẾU CÓ) -->
    <?php if (!empty($hoSo)): ?>
        <div class="card p-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-top: 5px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.75rem;">
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase;">MÃ HỒ SƠ</span>
                    <div style="font-family: monospace; font-size: 1.35rem; font-weight: 800; color: #0b2e59;">
                        <?= e($hoSo['ma_ho_so']) ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; display: block;">TRẠNG THÁI HIỆN TẠI</span>
                    <?php 
                    $badgeClass = match($hoSo['trang_thai']) {
                        'CHO_TIEP_NHAN', 'MOI_TIEP_NHAN' => 'badge-info',
                        'DANG_XU_LY' => 'badge-warning',
                        'CHO_BO_SUNG' => 'badge-danger',
                        'HOAN_THANH', 'DA_TRA_KET_QUA' => 'badge-success',
                        default => 'badge-secondary'
                    };
                    ?>
                    <span class="badge <?= $badgeClass ?>" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">
                        <?= e($hoSo['trang_thai']) ?>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; margin-bottom: 1rem;">
                <div><strong>Thủ tục:</strong> <?= e($hoSo['ten_tthc']) ?></div>
                <div><strong>Cơ quan giải quyết:</strong> <?= e($hoSo['ten_don_vi']) ?></div>
                <div><strong>Ngày tiếp nhận:</strong> <?= date('d/m/Y H:i', strtotime($hoSo['ngay_nop'])) ?></div>
                <div style="color: #166534;"><strong>Ngày hẹn trả:</strong> <?= !empty($hoSo['ngay_hen_tra']) ? date('d/m/Y H:i', strtotime($hoSo['ngay_hen_tra'])) : 'Đang xử lý' ?></div>
            </div>

            <div class="p-3" style="background: #f8fafc; border-radius: 6px; font-size: 0.8rem; color: #475569;">
                🔒 <em>Lưu ý bảo mật: Nhằm tuân thủ quy định bảo vệ dữ liệu cá nhân theo Nghị định 13/2023/NĐ-CP, Cổng DVC chỉ công khai các thông tin tiến độ cơ bản. Để xem toàn bộ chi tiết thành phần hồ sơ và ý kiến xử lý, Quý khách vui lòng đăng nhập tài khoản định danh VNeID.</em>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= e(asset('vendor/html5-qrcode/html5-qrcode.min.js')) ?>"></script>
<script>
let html5QrCode = null;

function switchLookupTab(tab) {
    if (tab === 'code') {
        document.getElementById('tab-content-code').style.display = 'block';
        document.getElementById('tab-content-qr').style.display = 'none';
        document.getElementById('tab-btn-code').className = 'btn btn-sm btn-primary';
        document.getElementById('tab-btn-qr').className = 'btn btn-sm btn-outline-secondary';
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop();
        }
    } else {
        document.getElementById('tab-content-code').style.display = 'none';
        document.getElementById('tab-content-qr').style.display = 'block';
        document.getElementById('tab-btn-code').className = 'btn btn-sm btn-outline-secondary';
        document.getElementById('tab-btn-qr').className = 'btn btn-sm btn-primary';
        startQrScanner();
    }
}

function startQrScanner() {
    if (typeof Html5Qrcode === 'undefined') return;
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText) => {
            html5QrCode.stop().then(() => {
                // Kiểm tra xem decoded text có phải URL hoặc chứa mã hồ sơ
                let ma = decodedText;
                if (decodedText.includes('ma_ho_so=')) {
                    const url = new URL(decodedText, window.location.origin);
                    ma = url.searchParams.get('ma_ho_so') || decodedText;
                }
                window.location.href = "<?= url('/tra-cuu?ma_ho_so=') ?>" + encodeURIComponent(ma);
            });
        },
        (errorMessage) => {}
    ).catch(err => {
        document.getElementById('tab-content-qr').innerHTML += '<p class="text-danger mt-2">Không thể truy cập Camera: ' + err + '</p>';
    });
}
</script>
<?php $this->endSection(); ?>
