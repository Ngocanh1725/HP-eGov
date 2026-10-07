<?php
/**
 * View: Hộp thư Mô phỏng SMS Brandname & Zalo ZNS Gateway (§2.2 Báo cáo Kiến trúc)
 * HP-eGov - Trung tâm Phục vụ Hành chính công TP. Hải Phòng
 */
$this->layout('layouts/admin', ['title' => $title ?? 'Hộp thư Mô phỏng']);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-navy fw-bold mb-1">
                <i class="bi bi-phone-fill text-primary me-2"></i>Hộp thư Mô phỏng SMS Brandname & Zalo ZNS
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/admin/dashboard">Bàn làm việc</a></li>
                    <li class="breadcrumb-item"><a href="/admin/he-thong/mau-thong-bao">Mẫu thông báo</a></li>
                    <li class="breadcrumb-item active">Hộp thư mô phỏng Gateway</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Làm mới
            </button>
        </div>
    </div>

    <!-- 4 Thẻ thống kê Outbox -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">SMS Brandname (UBND TP HP)</div>
                    <div class="fs-2 fw-bold text-success mt-1"><?= number_format($totalSms) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Zalo ZNS (Trung tâm Phục vụ HCC)</div>
                    <div class="fs-2 fw-bold text-primary mt-1"><?= number_format($totalZalo) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Email điện tử</div>
                    <div class="fs-2 fw-bold text-info mt-1"><?= number_format($totalEmail) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Chờ gửi trong Outbox</div>
                    <div class="fs-2 fw-bold text-warning mt-1"><?= number_format($totalChoGui) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Bảng danh sách tin nhắn Outbox -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fs-6 fw-bold text-navy">
                        <i class="bi bi-list-columns-reverse me-2"></i>Nhật ký Hàng đợi Thông báo (hang_doi_thong_bao)
                    </h5>
                    <span class="badge bg-light text-secondary border">Tối đa 100 tin gần nhất</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase text-secondary">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Kênh</th>
                                    <th>Người nhận</th>
                                    <th>Hồ sơ / Tiêu đề</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                    <th class="text-end pe-3">Xem mẫu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Chưa có tin nhắn nào trong hàng đợi Outbox.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $m): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-muted">#<?= $m['id'] ?></td>
                                            <td>
                                                <?php if ($m['kenh'] === 'SMS'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-chat-dots me-1"></i>SMS</span>
                                                <?php elseif ($m['kenh'] === 'ZALO'): ?>
                                                    <span class="badge bg-primary"><i class="bi bi-chat-square-text me-1"></i>Zalo ZNS</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info"><i class="bi bi-envelope me-1"></i>Email</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-navy"><?= e($m['nguoi_nhan']) ?></div>
                                                <?php if (!empty($m['nguoi_nop_ho_ten'])): ?>
                                                    <div class="small text-muted"><?= e($m['nguoi_nop_ho_ten']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($m['ma_ho_so'])): ?>
                                                    <span class="badge bg-light text-dark border"><?= e($m['ma_ho_so']) ?></span>
                                                <?php endif; ?>
                                                <div class="small text-truncate" style="max-width: 260px;" title="<?= e($m['noi_dung']) ?>">
                                                    <?= e($m['noi_dung']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($m['trang_thai'] === 'THANH_CONG'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Đã gửi</span>
                                                <?php elseif ($m['trang_thai'] === 'CHO_GUI'): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Chờ gửi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Thất bại</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="small text-muted"><?= date('H:i d/m', strtotime($m['thoi_gian_tao'])) ?></div>
                                            </td>
                                            <td class="text-end pe-3">
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        onclick="previewMessage(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <i class="bi bi-eye"></i>
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
        </div>

        <!-- Cột phải: Khung mô phỏng Smartphone nhận tin -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-navy text-white py-3 text-center">
                    <h6 class="card-title mb-0 fs-6 fw-bold">
                        <i class="bi bi-phone me-1"></i>Mô phỏng Giao diện Điện thoại Công dân
                    </h6>
                </div>
                <div class="card-body p-3 bg-light d-flex justify-content-center">
                    <!-- Mô phỏng màn hình điện thoại iPhone/Android -->
                    <div style="width: 300px; height: 560px; background: #ffffff; border-radius: 36px; border: 8px solid #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; position: relative;">
                        <!-- Tai thỏ / Notch -->
                        <div style="height: 24px; background: #1e293b; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px; width: 140px; margin: 0 auto;"></div>

                        <!-- Header app tin nhắn -->
                        <div class="p-2 border-bottom text-center bg-light" id="phoneHeader">
                            <div class="fw-bold small text-navy" id="phoneSender">UBND TP HP</div>
                            <div class="text-muted" style="font-size: 10px;">SMS Brandname Chính thức</div>
                        </div>

                        <!-- Màn hình chat -->
                        <div class="p-3 flex-grow-1 overflow-auto bg-slate-50" id="phoneChatBody" style="font-size: 12px; background: #f8fafc;">
                            <div class="text-center text-muted my-2" style="font-size: 10px;">Hôm nay, <?= date('H:i') ?></div>

                            <!-- Bubble tin nhắn -->
                            <div class="p-2 rounded shadow-sm mb-2" id="phoneBubble" style="background: #e2e8f0; color: #1e293b; border-radius: 12px; border-top-left-radius: 2px; line-height: 1.4;">
                                Chọn một tin nhắn bên trái để xem giao diện hiển thị trên điện thoại.
                            </div>

                            <!-- Link / Action button mô phỏng nếu là Zalo -->
                            <div id="phoneZaloCard" class="card border p-2 mb-2 d-none" style="background: #ffffff; border-radius: 8px; font-size: 11px;">
                                <div class="fw-bold text-primary mb-1">Cổng DVC TP. Hải Phòng</div>
                                <div class="text-muted" style="font-size: 10px;">Xác thực thông báo thủ tục hành chính điện tử</div>
                                <div class="text-center my-2">
                                    <div class="p-2 bg-light border d-inline-block rounded">
                                        <i class="bi bi-qr-code fs-1 text-dark"></i>
                                    </div>
                                    <div class="text-muted" style="font-size: 9px;">Quét QR để tra cứu hồ sơ</div>
                                </div>
                                <a href="#" class="btn btn-sm btn-primary py-0" style="font-size: 11px;">Tra cứu hồ sơ</a>
                            </div>
                        </div>

                        <!-- Footer bàn phím / thanh điều hướng -->
                        <div class="p-2 border-top bg-white text-center">
                            <div style="width: 100px; height: 4px; background: #94a3b8; border-radius: 2px; margin: 6px auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewMessage(m) {
    const sender = document.getElementById('phoneSender');
    const header = document.getElementById('phoneHeader');
    const bubble = document.getElementById('phoneBubble');
    const zaloCard = document.getElementById('phoneZaloCard');

    if (m.kenh === 'SMS') {
        sender.innerText = 'UBND TP HP';
        header.querySelector('div:last-child').innerText = 'SMS Brandname Đã Xác Thực';
        bubble.style.background = '#e2e8f0';
        bubble.style.color = '#1e293b';
        bubble.innerText = m.noi_dung;
        zaloCard.classList.add('d-none');
    } else if (m.kenh === 'ZALO') {
        sender.innerText = 'Phục vụ HCC Hải Phòng';
        header.querySelector('div:last-child').innerText = 'Zalo ZNS Official Account (Đã xác minh)';
        bubble.style.background = '#0068ff';
        bubble.style.color = '#ffffff';
        bubble.innerText = m.noi_dung;
        zaloCard.classList.remove('d-none');
    } else {
        sender.innerText = 'DVC TP Hải Phòng (Email)';
        header.querySelector('div:last-child').innerText = m.tieu_de || 'Thông báo điện tử';
        bubble.style.background = '#f1f5f9';
        bubble.style.color = '#0f172a';
        bubble.innerText = m.noi_dung;
        zaloCard.classList.add('d-none');
    }
}
</script>
