<?php $this->layout('layouts/admin', ['title' => $pageTitle]); ?>

<?php $this->startSection('content'); ?>
<div class="mb-4">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
        <a href="<?= e(url('/admin/mot-cua')) ?>" style="color: #64748b; text-decoration: none; font-size: 0.85rem;">⬅ Bàn làm việc Một cửa</a>
        <span class="text-muted">/</span>
        <span style="font-size: 0.85rem; font-weight: 600; color: #1e293b;">Tiếp nhận trực tiếp</span>
    </div>
    <h1 style="font-size: 1.5rem; color: #0b2e59; margin: 0; font-weight: 800;">
        📥 TIẾP NHẬN HỒ SƠ TRỰC TIẾP TẠI BỘ PHẬN MỘT CỬA
    </h1>
    <p class="text-muted" style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">
        Hỗ trợ quét QR CCCD gắn chip tự động điền, đối soát C06 qua LGSP & Số hóa đầu vào theo Nghị định 107/2021/NĐ-CP
    </p>
</div>

<!-- KHUNG 1: QUÉT QR CCCD GẮN CHIP HOẶC NHẬP CHUỖI QR -->
<div class="card p-3 mb-4" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
        <div style="font-weight: 700; color: #1d4ed8; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
            <span>📷</span> QUÉT MÃ QR THẺ CCCD GẮN CHIP (BỘ CÔNG AN)
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" id="btn-toggle-camera" class="btn btn-sm btn-primary" onclick="toggleCameraQr()">
                📸 Bật Camera quét thẻ CCCD
            </button>
        </div>
    </div>

    <!-- Khung camera ẩn/hiện -->
    <div id="camera-scanner-container" style="display: none; margin-bottom: 1rem; text-align: center;">
        <div id="reader" style="width: 100%; max-width: 360px; margin: 0 auto; border: 2px dashed #3b82f6; border-radius: 8px; background: #000;"></div>
        <small class="text-muted" style="display: block; margin-top: 0.25rem;">Đặt mã QR mặt sau thẻ CCCD trước ống kính camera</small>
    </div>

    <!-- Ô dán chuỗi QR quét bằng súng quét mã vạch hoặc nhập tay -->
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <input type="text" id="raw-qr-input" class="form-control" 
               placeholder="Dán chuỗi quét QR từ máy quét (Định dạng: CCCD|CMND|Họ tên|ddMMyyyy|Giới tính|Địa chỉ|ddMMyyyy)..."
               style="font-size: 0.85rem; font-family: monospace;">
        <button type="button" class="btn btn-primary" onclick="parseRawQrString()" style="white-space: nowrap; font-weight: 600;">
            ⚡ Phân tích & Tự điền
        </button>
    </div>

    <!-- Thông báo đối soát C06 -->
    <div id="c06-status-box" style="display: none; margin-top: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.85rem;"></div>
</div>

<!-- FORM TIẾP NHẬN HỒ SƠ CHÍNH -->
<form action="<?= e(url('/admin/mot-cua/tiep-nhan')) ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- KHUNG 2: CHỌN THỦ TỤC HÀNH CHÍNH -->
    <div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0 0 0.75rem 0; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            🏛️ 1. CHỌN THỦ TỤC HÀNH CHÍNH YÊU CẦU
        </h2>

        <div class="row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: flex-end;">
            <div class="form-group mb-0">
                <label class="form-label" style="font-weight: 600;">Thủ tục hành chính áp dụng: <span style="color: red;">*</span></label>
                <select name="tthc_id" id="tthc_id" class="form-control" onchange="onSelectTthc(this.value)" required>
                    <option value="">-- Chọn thủ tục hành chính --</option>
                    <?php foreach ($tthcList as $tt): ?>
                        <option value="<?= $tt['id'] ?>" <?= ($selectedTthc && $selectedTthc['id'] == $tt['id']) ? 'selected' : '' ?>>
                            [<?= e($tt['ma_rut_gon']) ?>] <?= e($tt['ten_tthc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedTthc): ?>
                <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.85rem;">
                    <div>Thời hạn: <strong><?= e($selectedTthc['thoi_han_giai_quyet_ngay']) ?> ngày làm việc</strong></div>
                    <div>Lệ phí: <strong><?= number_format((float)($selectedTthc['phi_le_phi'] ?? 0), 0, ',', '.') ?> đ</strong></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KHUNG 3: THÔNG TIN NGƯỜI NỘP HỒ SƠ -->
    <div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
            <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0;">
                👤 2. THÔNG TIN CHỦ HỒ SƠ / NGƯỜI NỘP
            </h2>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="verifyCccdOnline()">
                🛡️ Đối chiếu C06 qua LGSP
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Số CCCD / Định danh cá nhân: <span style="color: red;">*</span></label>
                <input type="text" name="so_cccd" id="so_cccd" class="form-control" required maxlength="12" placeholder="Ví dụ: 031090001234">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Họ và tên người nộp: <span style="color: red;">*</span></label>
                <input type="text" name="ho_ten" id="ho_ten" class="form-control" required placeholder="NGUYỄN VĂN AN">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Ngày sinh (dd/mm/yyyy):</label>
                <input type="text" name="ngay_sinh" id="ngay_sinh" class="form-control" placeholder="15/05/1990">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Giới tính:</label>
                <select name="gioi_tinh" id="gioi_tinh" class="form-control">
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Số điện thoại:</label>
                <input type="tel" name="so_dien_thoai" id="so_dien_thoai" class="form-control" placeholder="0912345678">
            </div>
            <div class="form-group">
                <label class="form-label" style="font-weight: 600;">Email:</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="congdan@gmail.com">
            </div>
        </div>

        <div class="form-group mt-2">
            <label class="form-label" style="font-weight: 600;">Địa chỉ thường trú / Nơi ở hiện tại:</label>
            <input type="text" name="dia_chi" id="dia_chi" class="form-control" placeholder="Số nhà, đường phố, Phường/Xã, Quận/Huyện...">
        </div>
    </div>

    <!-- KHUNG 4: SỐ HÓA ĐẦU VÀO THEO NGHỊ ĐỊNH 107/2021/NĐ-CP (4 BƯỚC) -->
    <?php if ($selectedTthc): ?>
        <div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border-left: 5px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0;">
                        📁 3. SỐ HÓA HỒ SƠ ĐẦU VÀO THEO NGHỊ ĐỊNH 107/2021/NĐ-CP
                    </h2>
                    <small class="text-muted">Thực hiện 4 bước: Pháp lý bản chính ➔ Quét scan ➔ OCR trích xuất ➔ Ký số cán bộ Một cửa</small>
                </div>
                <span class="badge badge-primary" style="font-size: 0.75rem;">Chỉ tiêu số hóa: 100%</span>
            </div>

            <?php if (empty($thanhPhanHoSo)): ?>
                <div class="text-muted p-2">Thủ tục này không yêu cầu thành phần giấy tờ kèm theo.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($thanhPhanHoSo as $tp): ?>
                        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; background: #fafafa;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <strong style="color: #1e293b; font-size: 0.9rem;"><?= e($tp['ten_giay_to']) ?></strong>
                                    <?php if ($tp['bat_buoc']): ?>
                                        <span style="color: red;">*</span>
                                        <span class="badge badge-danger" style="font-size: 0.65rem; margin-left: 0.25rem;">Bắt buộc</span>
                                    <?php endif; ?>
                                </div>
                                <div id="ocr-badge-<?= $tp['id'] ?>"></div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center;">
                                <!-- Bước 1: Checkbox xác nhận bản chính -->
                                <div>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; color: #166534; font-weight: 600;">
                                        <input type="checkbox" name="xac_nhan_ban_chinh[<?= $tp['id'] ?>]" value="1" <?= $tp['bat_buoc'] ? 'checked' : '' ?>>
                                        <span>✔ Bước 1: Đã đối chiếu bản chính / bản sao chứng thực</span>
                                    </label>
                                </div>

                                <!-- Bước 2: Tải file scan -->
                                <div>
                                    <input type="file" name="tep_so_hoa[<?= $tp['id'] ?>]" class="form-control" 
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           onchange="simulateOcrOnSelect(this, <?= $tp['id'] ?>, '<?= addslashes(e($tp['ten_giay_to'])) ?>')"
                                           style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                    <small class="text-muted" style="font-size: 0.75rem;">Khuyến nghị file PDF/Scan độ phân giải ≥ 300 DPI (Nghị định 107/2021/NĐ-CP)</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- KHUNG 5: HÌNH THỨC NHẬN KẾT QUẢ -->
    <div class="card p-3 mb-4" style="background: #fff; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.05rem; color: #0b2e59; font-weight: 700; margin: 0 0 0.75rem 0; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
            🎁 4. HÌNH THỨC TRẢ KẾT QUẢ CHO CÔNG DÂN
        </h2>

        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; cursor: pointer;">
                <input type="radio" name="hinh_thuc_nhan_kq" value="TRUC_TIEP" checked onchange="toggleDiaChiNhan(false)">
                <span><strong>Trực tiếp tại Bộ phận Một cửa</strong></span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; cursor: pointer;">
                <input type="radio" name="hinh_thuc_nhan_kq" value="BUU_DIEN" onchange="toggleDiaChiNhan(true)">
                <span><strong>Chuyển phát bưu chính công ích (VNPost)</strong></span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; cursor: pointer;">
                <input type="radio" name="hinh_thuc_nhan_kq" value="DIEN_TU" onchange="toggleDiaChiNhan(false)">
                <span><strong>Bản điện tử (Kho dữ liệu Cổng DVC)</strong></span>
            </label>
        </div>

        <div id="box-dia-chi-nhan" style="display: none; margin-top: 1rem;">
            <label class="form-label" style="font-weight: 600;">Địa chỉ giao phát tận nơi (VNPost):</label>
            <input type="text" name="dia_chi_nhan_kq" class="form-control" placeholder="Số nhà, đường phố, Xã/Phường, Quận/Huyện...">
        </div>
    </div>

    <!-- NÚT GỬI & HOÀN TẤT TIẾP NHẬN -->
    <div style="display: flex; justify-content: flex-end; gap: 1rem;" class="mb-5">
        <a href="<?= e(url('/admin/mot-cua')) ?>" class="btn btn-secondary" style="font-weight: 600;">Hủy bỏ</a>
        <button type="submit" class="btn btn-primary" style="font-size: 1rem; font-weight: 700; padding: 0.75rem 2rem; box-shadow: var(--shadow-md);">
            ✍️ Hoàn tất tiếp nhận, Cấp mã QCVN 102 & In Giấy hẹn ➔
        </button>
    </div>
</form>

<!-- SCRIPT XỬ LÝ SCANNER & PARSER QR CCCD -->
<script src="<?= e(url('/assets/vendor/html5-qrcode/html5-qrcode.min.js')) ?>"></script>
<script>
let html5QrScanner = null;
let isCameraRunning = false;

function toggleCameraQr() {
    const container = document.getElementById('camera-scanner-container');
    const btn = document.getElementById('btn-toggle-camera');

    if (!isCameraRunning) {
        container.style.display = 'block';
        btn.innerText = '⏹ Tắt Camera quét';
        btn.className = 'btn btn-sm btn-danger';

        html5QrScanner = new Html5Qrcode("reader");
        html5QrScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            (decodedText) => {
                document.getElementById('raw-qr-input').value = decodedText;
                parseRawQrString();
                toggleCameraQr(); // Dừng camera sau khi quét thành công
            },
            (errorMessage) => {}
        ).catch(err => {
            alert('Không thể khởi động camera: ' + err);
        });
        isCameraRunning = true;
    } else {
        if (html5QrScanner) {
            html5QrScanner.stop().then(() => {
                container.style.display = 'none';
                btn.innerText = '📸 Bật Camera quét thẻ CCCD';
                btn.className = 'btn btn-sm btn-primary';
                isCameraRunning = false;
            });
        }
    }
}

/**
 * Phân tích chuỗi QR chuẩn C06 Bộ Công an:
 * Định dạng: CCCD|CMND|Họ tên|ddMMyyyy|Giới tính|Địa chỉ|ddMMyyyy
 */
function parseRawQrString() {
    const raw = document.getElementById('raw-qr-input').value.trim();
    if (!raw) return;

    const parts = raw.split('|');
    if (parts.length >= 6) {
        const cccd = parts[0].trim();
        const hoTen = parts[2].trim();
        const dobRaw = parts[3].trim(); // ddMMyyyy
        const gioiTinh = parts[4].trim();
        const diaChi = parts[5].trim();

        document.getElementById('so_cccd').value = cccd;
        document.getElementById('ho_ten').value = hoTen;
        if (dobRaw.length === 8) {
            document.getElementById('ngay_sinh').value = dobRaw.substring(0, 2) + '/' + dobRaw.substring(2, 4) + '/' + dobRaw.substring(4, 8);
        }
        if (gioiTinh.toLowerCase().includes('nữ') || gioiTinh.toLowerCase().includes('nu')) {
            document.getElementById('gioi_tinh').value = 'Nữ';
        } else {
            document.getElementById('gioi_tinh').value = 'Nam';
        }
        document.getElementById('dia_chi').value = diaChi;

        // Tự động gọi đối soát C06
        verifyCccdOnline();
    } else {
        // Nếu chỉ quét ra số CCCD
        if (/^\d{12}$/.test(raw)) {
            document.getElementById('so_cccd').value = raw;
            verifyCccdOnline();
        } else {
            alert('Định dạng chuỗi QR không đúng chuẩn C06 (CCCD|CMND|Họ tên|ddMMyyyy|Giới tính|Địa chỉ|ddMMyyyy).');
        }
    }
}

function verifyCccdOnline() {
    const cccd = document.getElementById('so_cccd').value.trim();
    const hoTen = document.getElementById('ho_ten').value.trim();
    const ngaySinh = document.getElementById('ngay_sinh').value.trim();
    const box = document.getElementById('c06-status-box');

    if (!cccd) {
        alert('Vui lòng nhập số CCCD trước khi đối soát C06.');
        return;
    }

    box.style.display = 'block';
    box.style.background = '#fef3c7';
    box.style.color = '#92400e';
    box.innerText = '⏳ Đang kết nối LGSP đối soát với CSDL Quốc gia về Dân cư (C06)...';

    fetch('<?= e(url('/admin/mot-cua/api/verify-cccd')) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        },
        body: new URLSearchParams({
            '_csrf': '<?= csrf_token() ?>',
            'so_cccd': cccd,
            'ho_ten': hoTen,
            'ngay_sinh': ngaySinh
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            box.style.background = '#dcfce7';
            box.style.color = '#15803d';
            box.innerHTML = '✔ <strong>ĐỐI SOÁT C06 THÀNH CÔNG:</strong> ' + res.message + ' (Mã LGSP: <code>' + (res.data.ma_xac_thuc_lgsp || '') + '</code>)';
        } else {
            box.style.background = '#fee2e2';
            box.style.color = '#b91c1c';
            box.innerText = '✖ ' + res.message;
        }
    })
    .catch(err => {
        box.style.background = '#fee2e2';
        box.style.color = '#b91c1c';
        box.innerText = 'Lỗi kết nối đối soát C06: ' + err;
    });
}

function onSelectTthc(id) {
    if (id) {
        window.location.href = '<?= e(url('/admin/mot-cua/tiep-nhan?tthc_id=')) ?>' + id;
    }
}

function toggleDiaChiNhan(show) {
    document.getElementById('box-dia-chi-nhan').style.display = show ? 'block' : 'none';
}

function simulateOcrOnSelect(input, id, name) {
    if (input.files && input.files[0]) {
        const badge = document.getElementById('ocr-badge-' + id);
        badge.innerHTML = '<span class="badge badge-success" style="font-size: 0.7rem;">⚡ Đã OCR & Sẵn sàng ký số NĐ 107</span>';
    }
}
</script>
<?php $this->endSection(); ?>
