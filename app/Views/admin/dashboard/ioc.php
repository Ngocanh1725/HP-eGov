<?php $this->layout('layouts/admin', ['title' => $title ?? 'Trung tâm IOC Hải Phòng']) ?>

<style>
/* IOC Dark / High-tech Theme Custom Styles */
.ioc-container {
    background: #0f172a;
    color: #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    margin-bottom: 2rem;
}
.ioc-header {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 16px;
    margin-bottom: 24px;
}
.ioc-kpi-card {
    background: rgba(30, 41, 59, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.ioc-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.4);
    border-color: rgba(255, 255, 255, 0.25);
}
.ioc-kpi-card.passed {
    border-left: 5px solid #10b981;
}
.ioc-kpi-card.failed {
    border-left: 5px solid #ef4444;
}
.ioc-panel {
    background: rgba(30, 41, 59, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 20px;
    height: 100%;
}
.ioc-panel-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #38bdf8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
/* Bản đồ SVG */
.map-district {
    stroke: #0f172a;
    stroke-width: 2;
    cursor: pointer;
    transition: all 0.25s ease;
}
.map-district:hover {
    stroke: #ffffff;
    stroke-width: 3.5;
    filter: brightness(1.25);
}
.map-label {
    fill: #ffffff;
    font-size: 11px;
    font-weight: 700;
    text-anchor: middle;
    pointer-events: none;
    text-shadow: 0 1px 3px rgba(0,0,0,0.9);
}
/* Heatmap cell */
.heat-cell {
    width: 100%;
    height: 28px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    color: #ffffff;
    transition: transform 0.15s ease;
    cursor: default;
}
.heat-cell:hover {
    transform: scale(1.15);
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}
/* TV Wall Fullscreen Styling */
:fullscreen .ioc-container {
    border-radius: 0;
    min-height: 100vh;
    padding: 30px;
}
.refresh-pulse {
    display: inline-block;
    width: 10px;
    height: 10px;
    background-color: #10b981;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>

<div class="ioc-container" id="iocContainer">
    <!-- Header IOC -->
    <div class="ioc-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="refresh-pulse"></span>
                <span class="badge bg-danger text-uppercase fw-bold">IOC LIVE</span>
                <span class="text-info small">Cập nhật lúc: <span id="iocUpdateTime"><?= e($cap_nhat_luc) ?></span></span>
                <?php if (!empty($tu_cache)): ?>
                    <span class="badge bg-secondary" title="Dữ liệu tổng hợp từ bộ đệm 5 phút">Cache HIT (<?= $thoi_gian_truy_van ?>s)</span>
                <?php else: ?>
                    <span class="badge bg-primary" title="Truy vấn dữ liệu thời gian thực">Realtime (<?= $thoi_gian_truy_van ?>s)</span>
                <?php endif; ?>
            </div>
            <h1 class="h3 fw-bold text-white mb-0">
                TRUNG TÂM CHỈ ĐẠO & ĐIỀU HÀNH DỊCH VỤ CÔNG TP. HẢI PHÒNG
            </h1>
            <p class="text-secondary small mb-0 mt-1">
                Giám sát thời gian thực chu trình giải quyết TTHC tại 15 Quận/Huyện và các Sở, Ban, Ngành
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end text-light small d-none d-md-block">
                Tự làm mới sau: <strong class="text-warning fs-6" id="countdownTimer">60s</strong>
            </div>
            <button class="btn btn-sm btn-outline-info" onclick="manualRefreshIoc()">
                🔄 Làm mới
            </button>
            <button class="btn btn-sm btn-primary" onclick="toggleTvWall()">
                📺 Chế độ TV Wall
            </button>
            <a href="<?= e(url('/admin/dashboard')) ?>" class="btn btn-sm btn-outline-light">
                ✕ Trở về
            </a>
        </div>
    </div>

    <!-- 4 Thẻ KPI Chỉ tiêu Báo cáo Kiến trúc (§2) -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Đúng hạn >= 98% -->
        <div class="col-xl-3 col-md-6">
            <div class="ioc-kpi-card <?= $kpi_cards['dung_han']['dat'] ? 'passed' : 'failed' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">
                        <?= e($kpi_cards['dung_han']['tieu_de']) ?>
                    </span>
                    <span class="badge <?= $kpi_cards['dung_han']['dat'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $kpi_cards['dung_han']['dat'] ? '✓ ĐẠT CHỈ TIÊU' : '✗ CHƯA ĐẠT' ?>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-bold <?= $kpi_cards['dung_han']['dat'] ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($kpi_cards['dung_han']['gia_tri'], 1) ?>%
                    </span>
                    <span class="text-secondary small">/ Chỉ tiêu <?= $kpi_cards['dung_han']['chi_tieu'] ?></span>
                </div>
                <div class="text-secondary small mt-2">
                    <?= e($kpi_cards['dung_han']['so_lieu_phu']) ?>
                </div>
            </div>
        </div>

        <!-- KPI 2: Toàn trình >= 80% -->
        <div class="col-xl-3 col-md-6">
            <div class="ioc-kpi-card <?= $kpi_cards['toan_trinh']['dat'] ? 'passed' : 'failed' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">
                        <?= e($kpi_cards['toan_trinh']['tieu_de']) ?>
                    </span>
                    <span class="badge <?= $kpi_cards['toan_trinh']['dat'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $kpi_cards['toan_trinh']['dat'] ? '✓ ĐẠT CHỈ TIÊU' : '⚠ CẦN ĐẨY MẠNH' ?>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-bold <?= $kpi_cards['toan_trinh']['dat'] ? 'text-success' : 'text-warning' ?>">
                        <?= number_format($kpi_cards['toan_trinh']['gia_tri'], 1) ?>%
                    </span>
                    <span class="text-secondary small">/ Chỉ tiêu <?= $kpi_cards['toan_trinh']['chi_tieu'] ?></span>
                </div>
                <div class="text-secondary small mt-2">
                    <?= e($kpi_cards['toan_trinh']['so_lieu_phu']) ?>
                </div>
            </div>
        </div>

        <!-- KPI 3: Số hóa 100% -->
        <div class="col-xl-3 col-md-6">
            <div class="ioc-kpi-card <?= $kpi_cards['so_hoa']['dat'] ? 'passed' : 'failed' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">
                        <?= e($kpi_cards['so_hoa']['tieu_de']) ?>
                    </span>
                    <span class="badge <?= $kpi_cards['so_hoa']['dat'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                        <?= $kpi_cards['so_hoa']['dat'] ? '✓ ĐẠT 100%' : 'TIẾN ĐỘ TỐT' ?>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-bold <?= $kpi_cards['so_hoa']['dat'] ? 'text-success' : 'text-info' ?>">
                        <?= number_format($kpi_cards['so_hoa']['gia_tri'], 1) ?>%
                    </span>
                    <span class="text-secondary small">/ Chỉ tiêu <?= $kpi_cards['so_hoa']['chi_tieu'] ?></span>
                </div>
                <div class="text-secondary small mt-2">
                    <?= e($kpi_cards['so_hoa']['so_lieu_phu']) ?>
                </div>
            </div>
        </div>

        <!-- KPI 4: Hài lòng SIPAS >= 92% -->
        <div class="col-xl-3 col-md-6">
            <div class="ioc-kpi-card <?= $kpi_cards['hai_long']['dat'] ? 'passed' : 'failed' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">
                        <?= e($kpi_cards['hai_long']['tieu_de']) ?>
                    </span>
                    <span class="badge <?= $kpi_cards['hai_long']['dat'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $kpi_cards['hai_long']['dat'] ? '✓ RẤT HÀI LÒNG' : 'CHƯA ĐẠT' ?>
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-bold <?= $kpi_cards['hai_long']['dat'] ? 'text-success' : 'text-warning' ?>">
                        <?= number_format($kpi_cards['hai_long']['gia_tri'], 1) ?>%
                    </span>
                    <span class="text-secondary small">/ Chỉ tiêu <?= $kpi_cards['hai_long']['chi_tieu'] ?></span>
                </div>
                <div class="text-secondary small mt-2">
                    <?= e($kpi_cards['hai_long']['so_lieu_phu']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hàng 2: Bản đồ 15 Quận/Huyện SVG & Bảng xếp hạng Thi đua -->
    <div class="row g-4 mb-4">
        <!-- Cột trái (7 cols): Bản đồ số 15 quận/huyện TP Hải Phòng -->
        <div class="col-lg-7">
            <div class="ioc-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="ioc-panel-title mb-0">
                        🗺️ BẢN ĐỒ SỐ ĐIỀU HÀNH 15 QUẬN / HUYỆN HẢI PHÒNG
                    </div>
                    <div class="d-flex gap-2 small">
                        <span class="badge" style="background:#10b981;">Xanh: ≥98%</span>
                        <span class="badge" style="background:#f59e0b;">Vàng: 95-98%</span>
                        <span class="badge" style="background:#ef4444;">Đỏ: &lt;95%</span>
                    </div>
                </div>

                <div class="position-relative text-center" style="min-height: 440px;">
                    <!-- SVG BẢN ĐỒ 15 QUẬN/HUYỆN HẢI PHÒNG TỰ VẼ CHUẨN XÁC VỊ TRÍ TƯƠNG ĐỐI -->
                    <svg viewBox="0 0 850 560" style="width: 100%; height: 100%; max-height: 480px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));">
                        <defs>
                            <radialGradient id="oceanGlow" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" stop-color="#1e293b" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#0f172a" stop-opacity="0.9"/>
                            </radialGradient>
                        </defs>

                        <!-- Biển Đông / Vịnh Bắc Bộ Background -->
                        <rect width="850" height="560" fill="url(#oceanGlow)" rx="10"/>

                        <!-- Đường sóng biển trang trí -->
                        <path d="M 520,380 Q 560,370 600,390 T 700,380" fill="none" stroke="rgba(56,189,248,0.15)" stroke-width="1.5" stroke-dasharray="4,4"/>
                        <path d="M 540,430 Q 590,420 640,440 T 750,420" fill="none" stroke="rgba(56,189,248,0.15)" stroke-width="1.5" stroke-dasharray="4,4"/>

                        <!-- 1. THỦY NGUYÊN (id: 13) - Cực Bắc, diện tích rộng lớn -->
                        <polygon class="map-district" id="district-13" points="260,30 460,30 490,130 420,180 310,170 230,120"
                                 fill="<?= $district_map[13]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(13)">
                            <title>TP. Thủy Nguyên</title>
                        </polygon>
                        <text x="360" y="105" class="map-label">THỦY NGUYÊN</text>

                        <!-- 2. AN DƯƠNG (id: 14) - Phía Tây -->
                        <polygon class="map-district" id="district-14" points="160,110 240,115 280,180 250,260 140,240 120,170"
                                 fill="<?= $district_map[14]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(14)">
                            <title>Quận An Dương</title>
                        </polygon>
                        <text x="190" y="185" class="map-label">AN DƯƠNG</text>

                        <!-- 3. HỒNG BÀNG (id: 10) - Trung tâm ven sông Cấm -->
                        <polygon class="map-district" id="district-10" points="285,175 345,175 350,225 285,225"
                                 fill="<?= $district_map[10]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(10)">
                            <title>Quận Hồng Bàng</title>
                        </polygon>
                        <text x="317" y="202" class="map-label" style="font-size: 10px;">H.BÀNG</text>

                        <!-- 4. NGÔ QUYỀN (id: 11) - Phía Đông Hồng Bàng -->
                        <polygon class="map-district" id="district-11" points="350,175 410,180 415,230 350,225"
                                 fill="<?= $district_map[11]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(11)">
                            <title>Quận Ngô Quyền</title>
                        </polygon>
                        <text x="380" y="205" class="map-label" style="font-size: 10px;">N.QUYỀN</text>

                        <!-- 5. LÊ CHÂN (id: 12) - Nam Hồng Bàng / Ngô Quyền -->
                        <polygon class="map-district" id="district-12" points="285,230 370,230 360,285 285,275"
                                 fill="<?= $district_map[12]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(12)">
                            <title>Quận Lê Chân</title>
                        </polygon>
                        <text x="325" y="258" class="map-label" style="font-size: 10px;">L.CHÂN</text>

                        <!-- 6. HẢI AN (id: 15) - Đông Nam, Cảng Đình Vũ -->
                        <polygon class="map-district" id="district-15" points="415,185 510,190 535,270 425,275 375,230"
                                 fill="<?= $district_map[15]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(15)">
                            <title>Quận Hải An</title>
                        </polygon>
                        <text x="455" y="235" class="map-label">HẢI AN</text>

                        <!-- 7. KIẾN AN (id: 16) - Tây Nam sông Lạch Tray -->
                        <polygon class="map-district" id="district-16" points="220,265 290,270 280,340 200,335 210,285"
                                 fill="<?= $district_map[16]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(16)">
                            <title>Quận Kiến An</title>
                        </polygon>
                        <text x="245" y="305" class="map-label">KIẾN AN</text>

                        <!-- 8. DƯƠNG KINH (id: 18) - Giữa Hải An, Kiến An, Đồ Sơn -->
                        <polygon class="map-district" id="district-18" points="295,280 415,280 435,350 330,355 285,335"
                                 fill="<?= $district_map[18]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(18)">
                            <title>Quận Dương Kinh</title>
                        </polygon>
                        <text x="355" y="320" class="map-label">D.KINH</text>

                        <!-- 9. ĐỒ SƠN (id: 17) - Bán đảo Đồ Sơn vươn ra biển -->
                        <polygon class="map-district" id="district-17" points="390,355 450,350 480,440 430,460 380,400"
                                 fill="<?= $district_map[17]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(17)">
                            <title>Quận Đồ Sơn</title>
                        </polygon>
                        <text x="430" y="405" class="map-label">ĐỒ SƠN</text>

                        <!-- 10. AN LÃO (id: 19) - Tây Nam Kiến An -->
                        <polygon class="map-district" id="district-19" points="105,250 205,270 195,370 100,345"
                                 fill="<?= $district_map[19]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(19)">
                            <title>Huyện An Lão</title>
                        </polygon>
                        <text x="150" y="315" class="map-label">AN LÃO</text>

                        <!-- 11. KIẾN THỤY (id: 20) - Nam sông Đa Độ -->
                        <polygon class="map-district" id="district-20" points="275,350 375,360 370,445 250,430 200,375"
                                 fill="<?= $district_map[20]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(20)">
                            <title>Huyện Kiến Thụy</title>
                        </polygon>
                        <text x="295" y="400" class="map-label">KIẾN THỤY</text>

                        <!-- 12. TIÊN LÃNG (id: 21) - Tây Nam, giữa hai con sông lớn -->
                        <polygon class="map-district" id="district-21" points="110,360 215,385 250,480 120,490 85,420"
                                 fill="<?= $district_map[21]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(21)">
                            <title>Huyện Tiên Lãng</title>
                        </polygon>
                        <text x="165" y="440" class="map-label">TIÊN LÃNG</text>

                        <!-- 13. VĨNH BẢO (id: 22) - Cực Nam thành phố -->
                        <polygon class="map-district" id="district-22" points="60,450 145,465 170,545 40,540"
                                 fill="<?= $district_map[22]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(22)">
                            <title>Huyện Vĩnh Bảo</title>
                        </polygon>
                        <text x="105" y="505" class="map-label">VĨNH BẢO</text>

                        <!-- 14. CÁT HẢI (id: 23) - Đảo Cát Bà ngoài khơi vịnh Lan Hạ -->
                        <polygon class="map-district" id="district-23" points="560,170 690,165 725,260 635,280 550,225"
                                 fill="<?= $district_map[23]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(23)">
                            <title>Huyện đảo Cát Hải</title>
                        </polygon>
                        <text x="635" y="225" class="map-label">CÁT HẢI (CÁT BÀ)</text>

                        <!-- 15. BẠCH LONG VĨ (id: 24) - Huyện đảo tiền tiêu xa bờ -->
                        <circle class="map-district" id="district-24" cx="740" cy="450" r="32"
                                fill="<?= $district_map[24]['color'] ?? '#10b981' ?>" onclick="showDistrictModal(24)">
                            <title>Huyện đảo Bạch Long Vĩ</title>
                        </circle>
                        <text x="740" y="454" class="map-label" style="font-size: 9.5px;">B.L.VĨ</text>
                    </svg>

                    <div class="text-secondary small mt-2">
                        💡 <em>Bấm vào từng quận / huyện trên bản đồ để xem chi tiết số liệu và hồ sơ trễ hạn.</em>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải (5 cols): Bảng xếp hạng Thi đua Sở & Quận -->
        <div class="col-lg-5">
            <div class="ioc-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="ioc-panel-title mb-0">
                        🏆 XẾP HẠNG THI ĐUA ĐƠN VỊ
                    </div>
                    <!-- Tab chuyển đổi Sở / Huyện -->
                    <ul class="nav nav-pills nav-fill small" id="emulationTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-3" id="tab-quan-btn" data-bs-toggle="pill" data-bs-target="#tab-quan" type="button">
                                15 Quận / Huyện
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3" id="tab-so-btn" data-bs-toggle="pill" data-bs-target="#tab-so" type="button">
                                Khối Sở Ngành
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="emulationTabContent" style="max-height: 440px; overflow-y: auto;">
                    <!-- Tab 1: 15 Quận/Huyện -->
                    <div class="tab-pane fade show active" id="tab-quan">
                        <table class="table table-dark table-hover table-sm align-middle mb-0" style="background: transparent;">
                            <thead class="text-secondary small text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Quận / Huyện</th>
                                    <th class="text-center">Hồ sơ</th>
                                    <th class="text-center">Đúng hạn</th>
                                    <th class="text-end">Điểm thi đua</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emulation['quan_huyen'] as $idx => $qh): ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $idx < 3 ? 'bg-warning text-dark fw-bold' : 'bg-secondary' ?>">
                                                <?= $idx + 1 ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-light"><?= e($qh['ten_don_vi']) ?></span>
                                        </td>
                                        <td class="text-center"><?= number_format($qh['tong_tiep_nhan']) ?></td>
                                        <td class="text-center fw-bold <?= ($qh['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($qh['ty_le_dung_han'], 1) ?>%
                                        </td>
                                        <td class="text-end fw-bold text-info">
                                            <?= number_format($qh['diem_thi_dua'], 1) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab 2: Khối Sở Ngành -->
                    <div class="tab-pane fade" id="tab-so">
                        <table class="table table-dark table-hover table-sm align-middle mb-0" style="background: transparent;">
                            <thead class="text-secondary small text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Sở / Ban / Ngành</th>
                                    <th class="text-center">Hồ sơ</th>
                                    <th class="text-center">Đúng hạn</th>
                                    <th class="text-end">Điểm thi đua</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emulation['so_nganh'] as $idx => $sn): ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $idx < 3 ? 'bg-warning text-dark fw-bold' : 'bg-secondary' ?>">
                                                <?= $idx + 1 ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-light"><?= e($sn['ten_don_vi']) ?></span>
                                        </td>
                                        <td class="text-center"><?= number_format($sn['tong_tiep_nhan']) ?></td>
                                        <td class="text-center fw-bold <?= ($sn['ty_le_dung_han'] >= 98.0) ? 'text-success' : 'text-danger' ?>">
                                            <?= number_format($sn['ty_le_dung_han'], 1) ?>%
                                        </td>
                                        <td class="text-end fw-bold text-info">
                                            <?= number_format($sn['diem_thi_dua'], 1) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hàng 3: Heatmap Tiếp nhận Giờ x Ngày & Phát hiện Thắt cổ chai -->
    <div class="row g-4">
        <!-- Cột trái (7 cols): Heatmap Hồ sơ tiếp nhận theo giờ x ngày -->
        <div class="col-lg-7">
            <div class="ioc-panel">
                <div class="ioc-panel-title">
                    🔥 HEATMAP TIẾP NHẬN HỒ SƠ THEO GIỜ × NGÀY TRONG TUẦN
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless text-light align-middle mb-0" style="table-layout: fixed;">
                        <thead>
                            <tr class="text-secondary small text-center">
                                <th style="width: 80px;">Thứ / Giờ</th>
                                <?php foreach ($heatmap['hours'] as $h): ?>
                                    <th><?= $h ?>h</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($heatmap['days'] as $dNum => $dName): ?>
                                <tr>
                                    <td class="small fw-bold text-secondary"><?= $dName ?></td>
                                    <?php foreach ($heatmap['hours'] as $h): 
                                        $cnt = $heatmap['matrix'][$dNum]['gio'][$h] ?? 0;
                                        // Tính màu gradient xanh ngọc theo cường độ
                                        $ratio = $heatmap['max_val'] > 0 ? ($cnt / $heatmap['max_val']) : 0;
                                        if ($cnt === 0) {
                                            $bg = 'rgba(51, 65, 85, 0.4)';
                                        } elseif ($ratio < 0.3) {
                                            $bg = 'rgba(16, 185, 129, 0.35)';
                                        } elseif ($ratio < 0.7) {
                                            $bg = 'rgba(16, 185, 129, 0.7)';
                                        } else {
                                            $bg = '#10b981';
                                        }
                                    ?>
                                        <td class="p-1">
                                            <div class="heat-cell" style="background: <?= $bg ?>" title="<?= $dName ?> lúc <?= $h ?>:00 – <?= $cnt ?> hồ sơ">
                                                <?= $cnt > 0 ? $cnt : '' ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột phải (5 cols): Bottleneck Detector (Điểm thắt cổ chai) -->
        <div class="col-lg-5">
            <div class="ioc-panel">
                <div class="ioc-panel-title text-warning">
                    ⚠️ PHÁT HIỆN ĐIỂM NGHẼN QUY TRÌNH (BOTTLENECK DETECTOR)
                </div>

                <?php if (!empty($bottleneck['alert'])): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-25 border-danger text-light p-3 rounded-3 mb-3">
                        <div class="fw-bold mb-1">🚨 Cảnh báo Thắt cổ chai nghiêm trọng:</div>
                        <div class="small"><?= e($bottleneck['alert']['khuyen_nghi']) ?></div>
                    </div>
                <?php endif; ?>

                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-dark table-sm align-middle mb-0" style="background: transparent;">
                        <thead class="text-secondary small text-uppercase">
                            <tr>
                                <th>Bước quy trình</th>
                                <th class="text-center">Tồn đọng</th>
                                <th class="text-center">Quá hạn</th>
                                <th class="text-end">TG TB (Giờ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bottleneck['steps'] as $st): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-light"><?= e($st['ten_buoc']) ?></div>
                                        <small class="text-secondary">Vai trò: <?= e($st['vai_tro']) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $st['so_luong_ton'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($st['so_qua_han'] > 0): ?>
                                            <span class="badge bg-danger"><?= $st['so_qua_han'] ?></span>
                                        <?php else: ?>
                                            <span class="text-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold <?= ($st['thoi_gian_tb'] > $st['thoi_han_gio']) ? 'text-danger' : 'text-info' ?>">
                                        <?= number_format($st['thoi_gian_tb'], 1) ?>h
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết Quận / Huyện khi Click trên Bản đồ -->
<div class="modal fade" id="districtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info" id="districtModalTitle">
                    Thông tin đơn vị
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="districtModalBody">
                <div class="text-center py-4">Đang tải dữ liệu...</div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
let countdown = 60;
let refreshInterval = null;

// Tự làm mới đếm ngược 60 giây
function startCountdown() {
    if (refreshInterval) clearInterval(refreshInterval);
    countdown = 60;
    const timerEl = document.getElementById('countdownTimer');
    
    refreshInterval = setInterval(() => {
        countdown--;
        if (timerEl) timerEl.textContent = countdown + 's';
        if (countdown <= 0) {
            countdown = 60;
            reloadIocData();
        }
    }, 1000);
}

// Gọi API lấy dữ liệu mới ngầm (không nhấp nháy màn hình)
function reloadIocData() {
    fetch('<?= e(url('/admin/api/ioc-stats')) ?>?refresh=1')
        .then(res => res.json())
        .then(data => {
            const timeEl = document.getElementById('iocUpdateTime');
            if (timeEl && data.cap_nhat_luc) {
                timeEl.textContent = data.cap_nhat_luc;
            }
            // Nếu muốn cập nhật toàn trang mượt mà
            window.location.reload();
        })
        .catch(err => {
            console.error('Lỗi tự động cập nhật IOC:', err);
        });
}

function manualRefreshIoc() {
    window.location.href = '<?= e(url('/admin/ioc')) ?>?refresh=1';
}

// Bật/tắt chế độ TV Wall Fullscreen
function toggleTvWall() {
    const elem = document.getElementById('iocContainer');
    if (!document.fullscreenElement) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Hiển thị chi tiết Quận/Huyện khi click bản đồ SVG
function showDistrictModal(districtId) {
    const modalEl = document.getElementById('districtModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const titleEl = document.getElementById('districtModalTitle');
    const bodyEl = document.getElementById('districtModalBody');
    bodyEl.innerHTML = '<div class="text-center py-4">Đang nạp số liệu từ CSDL...</div>';

    fetch('<?= e(url('/admin/api/ioc-district/')) ?>' + districtId)
        .then(res => res.json())
        .then(res => {
            if (res.error) {
                bodyEl.innerHTML = '<div class="alert alert-danger">' + res.error + '</div>';
                return;
            }
            const u = res.unit;
            titleEl.textContent = '🏛️ ' + u.ten_don_vi;

            let overdueHtml = '';
            if (res.overdue_samples && res.overdue_samples.length > 0) {
                overdueHtml = '<h6 class="text-danger fw-bold mt-3 mb-2">🚨 Hồ sơ đang trễ hạn:</h6><ul class="small ps-3">';
                res.overdue_samples.forEach(item => {
                    overdueHtml += '<li><strong>' + item.ma_ho_so + '</strong>: ' + item.nguoi_nop_ho_ten + ' (Hạn: ' + item.ngay_hen_tra + ')</li>';
                });
                overdueHtml += '</ul>';
            } else {
                overdueHtml = '<div class="text-success small mt-3">✓ Không có hồ sơ nào bị quá hạn tại đơn vị này.</div>';
            }

            bodyEl.innerHTML = `
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded bg-secondary bg-opacity-25">
                            <small class="text-secondary d-block">Tổng tiếp nhận</small>
                            <span class="fs-5 fw-bold text-light">${u.tong_ho_so_tiep_nhan}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-secondary bg-opacity-25">
                            <small class="text-secondary d-block">Đã giải quyết</small>
                            <span class="fs-5 fw-bold text-success">${u.so_ho_so_da_giai_quyet}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-secondary bg-opacity-25">
                            <small class="text-secondary d-block">Tỷ lệ Đúng hạn</small>
                            <span class="fs-5 fw-bold ${u.ty_le_dung_han_pt >= 98 ? 'text-success' : 'text-danger'}">${u.ty_le_dung_han_pt || 100}%</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-secondary bg-opacity-25">
                            <small class="text-secondary d-block">Hồ sơ quá hạn</small>
                            <span class="fs-5 fw-bold ${u.so_dang_qua_han > 0 ? 'text-danger' : 'text-light'}">${u.so_dang_qua_han}</span>
                        </div>
                    </div>
                </div>
                ${overdueHtml}
            `;
        })
        .catch(err => {
            bodyEl.innerHTML = '<div class="alert alert-danger">Lỗi kết nối máy chủ.</div>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    startCountdown();
});
</script>
