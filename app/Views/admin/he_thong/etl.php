<?php
/**
 * View: Quản trị Chuyển đổi dữ liệu ETL 4 bước (§1.1, §1.3.A)
 * Layout: layouts/admin.php
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="page-title">
                <span class="icon-box bg-primary-subtle text-primary">🔄</span>
                Chuyển đổi dữ liệu lịch sử ETL 4 bước (§1.3.A)
            </h1>
            <p class="text-muted mb-0">Pipeline chuyển đổi hồ sơ Một cửa cũ 5 năm gần nhất: Extract → Staging & Profiling → Transform → Load & Verify (SHA-256)</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline" id="btnManageMappings">
                ⚙️ Bảng ánh xạ TTHC & ĐVHC
            </button>
            <button type="button" class="btn btn-primary" id="btnOpenWizard">
                🚀 Khởi tạo phiên ETL mới
            </button>
        </div>
    </div>
</div>

<!-- 4 Thẻ KPI Tổng thể -->
<div class="grid grid-4 gap-3 mb-4">
    <div class="card stat-card">
        <div class="stat-icon bg-primary-subtle text-primary">📦</div>
        <div class="stat-content">
            <div class="stat-label">Tổng số lô đã chạy</div>
            <div class="stat-value"><?= number_format((int)($thongKe['tong_so_lo'] ?? 0)) ?></div>
            <div class="stat-desc text-muted">Lịch sử chuyển đổi Một cửa cũ</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon bg-info-subtle text-info">📑</div>
        <div class="stat-content">
            <div class="stat-label">Tổng bản ghi đã trích xuất</div>
            <div class="stat-value"><?= number_format((int)($thongKe['tong_ban_ghi'] ?? 0)) ?></div>
            <div class="stat-desc text-info">Từ nguồn MS SQL & MySQL cũ</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon bg-success-subtle text-success">✅</div>
        <div class="stat-content">
            <div class="stat-label">Đã nạp vào CSDL lõi</div>
            <div class="stat-value text-success"><?= number_format((int)($thongKe['tong_thanh_cong'] ?? 0)) ?></div>
            <div class="stat-desc text-success">Khớp 100% SHA-256 tệp đính kèm</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon bg-danger-subtle text-danger">⚠️</div>
        <div class="stat-content">
            <div class="stat-label">Bản ghi phát hiện lỗi/rác</div>
            <div class="stat-value text-danger"><?= number_format((int)($thongKe['tong_loi'] ?? 0)) ?></div>
            <div class="stat-desc text-muted">Được ghi nhận tại etl_loi</div>
        </div>
    </div>
</div>

<!-- KHUNG WIZARD ETL 4 BƯỚC -->
<div class="card mb-4" id="wizardCard">
    <div class="card-header border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">🛠️ Quy trình thực hiện ETL Pipeline 4 bước (§1.3.A)</h3>
            <span class="badge badge-info" id="currentBatchBadge">Sẵn sàng khởi chạy</span>
        </div>
    </div>
    <div class="card-body">
        <!-- Stepper Visual -->
        <div class="stepper mb-4">
            <div class="step active" id="stepIndicator1">
                <div class="step-circle">1</div>
                <div class="step-title">Bước 1: Extract</div>
                <div class="step-subtitle">Trích xuất CSV nguồn</div>
            </div>
            <div class="step" id="stepIndicator2">
                <div class="step-circle">2</div>
                <div class="step-title">Bước 2: Staging & Profiling</div>
                <div class="step-subtitle">Đánh giá chất lượng dữ liệu</div>
            </div>
            <div class="step" id="stepIndicator3">
                <div class="step-circle">3</div>
                <div class="step-title">Bước 3: Transform</div>
                <div class="step-subtitle">Ánh xạ & Gắn cờ PDF/A</div>
            </div>
            <div class="step" id="stepIndicator4">
                <div class="step-circle">4</div>
                <div class="step-title">Bước 4: Load & Verify</div>
                <div class="step-subtitle">Đối soát SHA-256 theo lô</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress mb-4" style="height: 8px; display: none;" id="etlProgressBarContainer">
            <div class="progress-bar progress-bar-striped progress-bar-animated" id="etlProgressBar" style="width: 0%"></div>
        </div>

        <!-- MÀN HÌNH BƯỚC 1: EXTRACT -->
        <div class="wizard-step-content" id="stepContent1">
            <h4 class="mb-3">Bước 1: Chọn nguồn dữ liệu Một cửa cũ (5 năm gần nhất)</h4>
            <div class="grid grid-3 gap-3 mb-4">
                <label class="card card-radio p-3 border cursor-pointer hover-shadow" id="optSampleMssql">
                    <input type="radio" name="loai_nguon" value="SAMPLE_MSSQL" checked>
                    <div class="mt-2">
                        <strong>📊 Mẫu MS SQL Server cũ</strong>
                        <p class="text-muted small mb-1">Mô phỏng cơ sở dữ liệu Một cửa cấp Huyện cũ (15 bản ghi mẫu, có hồ sơ trùng, thiếu CCCD, sai checksum file, mã lạ).</p>
                        <span class="badge badge-primary">Tệp: database/etl_samples/sample_mssql_cu.csv</span>
                    </div>
                </label>
                <label class="card card-radio p-3 border cursor-pointer hover-shadow" id="optSampleMysql">
                    <input type="radio" name="loai_nguon" value="SAMPLE_MYSQL">
                    <div class="mt-2">
                        <strong>🐬 Mẫu MySQL Một cửa cũ</strong>
                        <p class="text-muted small mb-1">Mô phỏng hệ thống Một cửa cấp Xã/Phường cũ (15 bản ghi mẫu, định dạng ngày d/m/Y, tên viết hoa rác, chuỗi mã độc XSS).</p>
                        <span class="badge badge-success">Tệp: database/etl_samples/sample_mysql_cu.csv</span>
                    </div>
                </label>
                <label class="card card-radio p-3 border cursor-pointer hover-shadow" id="optUploadCustom">
                    <input type="radio" name="loai_nguon" value="UPLOAD">
                    <div class="mt-2">
                        <strong>📁 Tải lên tệp CSV tùy chỉnh</strong>
                        <p class="text-muted small mb-1">Tải tệp CSV từ máy tính quản trị viên để tiến hành trích xuất vào phân hệ Staging.</p>
                        <span class="badge badge-secondary">Tối đa 20MB, định dạng UTF-8 CSV</span>
                    </div>
                </label>
            </div>

            <div id="uploadInputSection" style="display: none;" class="mb-3">
                <label class="form-label font-weight-bold">Chọn tệp CSV từ máy tính:</label>
                <input type="file" class="form-control" id="customCsvInput" accept=".csv">
            </div>

            <div class="mb-3">
                <label class="form-label font-weight-bold">Tên gợi nhớ của lô dữ liệu:</label>
                <input type="text" class="form-control" id="tenLoInput" placeholder="Ví dụ: Chuyển đổi dữ liệu Một cửa Quận Hồng Bàng 2021-2025" value="Lô chuyển đổi dữ liệu Một cửa cũ (Thực nghiệm <?= date('d/m/Y H:i') ?>)">
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary" id="btnRunExtract">
                    ▶️ Bắt đầu Bước 1 (Trích xuất vào Staging)
                </button>
            </div>
        </div>

        <!-- MÀN HÌNH BƯỚC 2: PROFILING -->
        <div class="wizard-step-content" id="stepContent2" style="display: none;">
            <h4 class="mb-2">Bước 2: Kết quả đánh giá chất lượng dữ liệu (Data Profiling)</h4>
            <p class="text-muted small mb-3">Tự động phát hiện trường dữ liệu rác, hồ sơ trùng lặp, thiếu số định danh cá nhân và mã danh mục sai lệch.</p>

            <div class="grid grid-4 gap-3 mb-4" id="profilingStatsCards">
                <div class="card p-3 bg-light text-center">
                    <div class="text-muted small">Tổng số bản ghi Staging</div>
                    <div class="h3 mb-0 text-primary" id="pTotal">0</div>
                </div>
                <div class="card p-3 bg-light text-center">
                    <div class="text-muted small">Bản ghi hợp lệ</div>
                    <div class="h3 mb-0 text-success" id="pValid">0</div>
                </div>
                <div class="card p-3 bg-light text-center">
                    <div class="text-muted small">Bản ghi phát hiện lỗi</div>
                    <div class="h3 mb-0 text-danger" id="pErrors">0</div>
                </div>
                <div class="card p-3 bg-light text-center">
                    <div class="text-muted small">Tỷ lệ dữ liệu sạch</div>
                    <div class="h3 mb-0 text-info" id="pRate">0%</div>
                </div>
            </div>

            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning-subtle py-2">
                    <strong class="text-warning-emphasis">⚠️ Chi tiết phân loại lỗi phát hiện (§1.3.A):</strong>
                </div>
                <div class="card-body p-3">
                    <div class="grid grid-3 gap-2 small" id="profilingErrorCategories">
                        <div>🔄 Hồ sơ trùng mã: <strong id="errDupl" class="text-danger">0</strong></div>
                        <div>🪪 Thiếu CCCD/CMND: <strong id="errMissingCccd" class="text-danger">0</strong></div>
                        <div>🔢 Số CCCD sai độ dài: <strong id="errWrongCccd" class="text-danger">0</strong></div>
                        <div>📋 Mã TTHC chưa ánh xạ: <strong id="errTthc" class="text-danger">0</strong></div>
                        <div>🏛️ Mã ĐVHC chưa ánh xạ: <strong id="errDvhc" class="text-danger">0</strong></div>
                        <div>🛡️ Dữ liệu rác / XSS script: <strong id="errJunk" class="text-danger">0</strong></div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline" id="btnBackToStep1">⬅️ Quay lại</button>
                <button type="button" class="btn btn-primary" id="btnRunTransform">
                    ▶️ Tiếp tục Bước 3 (Transform & Chuẩn hóa)
                </button>
            </div>
        </div>

        <!-- MÀN HÌNH BƯỚC 3: TRANSFORM -->
        <div class="wizard-step-content" id="stepContent3" style="display: none;">
            <h4 class="mb-2">Bước 3: Chuẩn hóa dữ liệu & Ánh xạ danh mục Quốc gia</h4>
            <p class="text-muted small mb-3">Chuyển đổi mã TTHC cũ sang mã TTHC quốc gia, mã ĐVHC sang mã TCTK, định dạng ngày tháng chuẩn ISO 8601, chuẩn hóa họ tên Title Case và kiểm tra tệp PDF/A-1b.</p>

            <div class="alert alert-info mb-4" id="transformSummaryAlert">
                <div class="d-flex align-items-center gap-3">
                    <div style="font-size: 2rem;">✨</div>
                    <div>
                        <strong>Đã chuẩn hóa và ánh xạ thành công các bản ghi hợp lệ!</strong>
                        <div class="small mt-1" id="transformDetailsText">
                            • TTHC: Đã gắn mã thủ tục Quốc gia <code>1.000xxx.000.00.00.H21</code><br>
                            • ĐVHC: Đã ánh xạ đơn vị Một cửa sang mã TCTK <code>303, 304, 305...</code><br>
                            • Tệp đính kèm: Đã phân tích và đánh dấu các tệp cần số hóa chuyển đổi PDF/A-1b bảo quản lâu dài.
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline" id="btnBackToStep2">⬅️ Quay lại</button>
                <button type="button" class="btn btn-success" id="btnRunLoad">
                    🚀 Tiến hành Bước 4 (Load & Verify SHA-256)
                </button>
            </div>
        </div>

        <!-- MÀN HÌNH BƯỚC 4: LOAD & VERIFY -->
        <div class="wizard-step-content" id="stepContent4" style="display: none;">
            <h4 class="mb-2">Bước 4: Nạp hợp nhất vào CSDL & Đối soát SHA-256 100% tệp đính kèm</h4>
            <p class="text-muted small mb-3">Nạp dữ liệu vào bảng <code>ho_so</code>, <code>ho_so_tai_lieu</code> trong Transaction theo từng lô và kiểm chứng mã băm tệp vật lý.</p>

            <div class="card mb-4 bg-light p-4 text-center" id="loadResultCard">
                <div style="font-size: 3rem;" class="mb-2" id="loadResultIcon">🎉</div>
                <h3 class="text-success mb-2" id="loadResultTitle">Chuyển đổi dữ liệu ETL hoàn tất!</h3>
                <p class="text-muted mb-3" id="loadResultDesc">Dữ liệu đã được nạp an toàn vào hệ thống hành chính HP-eGov.</p>

                <div class="grid grid-3 gap-3 text-start max-w-600 mx-auto mb-3">
                    <div class="card p-2">
                        <small class="text-muted">Hồ sơ nạp thành công:</small>
                        <strong class="text-success h4 mb-0" id="resSuccessCount">0</strong>
                    </div>
                    <div class="card p-2">
                        <small class="text-muted">Tệp khớp SHA-256 100%:</small>
                        <strong class="text-info h4 mb-0" id="resFileShaCount">0</strong>
                    </div>
                    <div class="card p-2">
                        <small class="text-muted">Bản ghi thất bại/lỗi:</small>
                        <strong class="text-danger h4 mb-0" id="resFailCount">0</strong>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-outline" id="btnDownloadErrorsCsv">
                        📥 Tải báo cáo danh sách lỗi (CSV UTF-8)
                    </a>
                    <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                        🔄 Hoàn tất & Làm mới màn hình
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BẢNG LỊCH SỬ CÁC LÔ ETL ĐÃ THỰC HIỆN -->
<div class="card mb-4">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">📜 Lịch sử các phiên chuyển đổi dữ liệu (ETL Lô)</h3>
        <span class="text-muted small">Cập nhật tự động từ bảng etl_lo</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Tên lô dữ liệu</th>
                        <th>Hệ thống nguồn</th>
                        <th class="text-center">Tổng số</th>
                        <th class="text-center">Thành công</th>
                        <th class="text-center">Số lỗi</th>
                        <th>Trạng thái</th>
                        <th>Thời gian chạy</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachLo)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Chưa có phiên ETL nào được thực hiện. Hãy nhấn "Khởi tạo phiên ETL mới" để bắt đầu.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($danhSachLo as $lo): ?>
                    <tr>
                        <td><strong>#<?= (int)$lo['id'] ?></strong></td>
                        <td>
                            <strong><?= e($lo['ten_lo']) ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= e($lo['he_thong_nguon']) ?></span>
                        </td>
                        <td class="text-center"><strong><?= number_format((int)$lo['tong_so_ban_ghi']) ?></strong></td>
                        <td class="text-center text-success font-weight-bold"><?= number_format((int)$lo['so_thanh_cong']) ?></td>
                        <td class="text-center text-danger font-weight-bold"><?= number_format((int)$lo['so_loi']) ?></td>
                        <td>
                            <?php if ($lo['trang_thai'] === 'LOADED'): ?>
                                <span class="badge badge-success">HOÀN TẤT (LOADED)</span>
                            <?php elseif ($lo['trang_thai'] === 'TRANSFORMING'): ?>
                                <span class="badge badge-warning">ĐANG CHUẨN HÓA</span>
                            <?php else: ?>
                                <span class="badge badge-info"><?= e($lo['trang_thai']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($lo['created_at'])) ?></small></td>
                        <td class="text-end">
                            <?php if ((int)$lo['so_loi'] > 0): ?>
                            <a href="<?= e(url('/admin/he-thong/etl/export-errors/' . $lo['id'])) ?>" class="btn btn-sm btn-outline text-danger" title="Tải danh sách lỗi">
                                📥 Tải file lỗi
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">Không có lỗi</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL QUẢN LÝ BẢNG ÁNH XẠ TTHC & ĐVHC -->
<div class="modal-backdrop" id="modalMappings" style="display: none;">
    <div class="modal-card modal-lg">
        <div class="modal-header border-bottom">
            <h3 class="modal-title">⚙️ Cấu hình Bảng ánh xạ chuyển đổi ETL (§1.3.A)</h3>
            <button type="button" class="btn-close" data-modal-close="modalMappings">&times;</button>
        </div>
        <div class="modal-body p-3">
            <ul class="nav-tabs mb-3">
                <li class="nav-tab-item active" data-tab-target="tabMappingTthc">📋 Ánh xạ Thủ tục hành chính (<?= count($anhXaTthc) ?>)</li>
                <li class="nav-tab-item" data-tab-target="tabMappingDvhc">🏛️ Ánh xạ Đơn vị hành chính (<?= count($anhXaDvhc) ?>)</li>
            </ul>

            <!-- Tab TTHC -->
            <div class="tab-pane active" id="tabMappingTthc">
                <div class="table-responsive mb-3" style="max-height: 280px;">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã TTHC cũ</th>
                                <th>Tên thủ tục cũ</th>
                                <th>Mã TTHC mới (Quốc gia)</th>
                                <th>Thủ tục chuẩn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anhXaTthc as $m): ?>
                            <tr>
                                <td><code><?= e($m['ma_tthc_cu']) ?></code></td>
                                <td><?= e($m['ten_tthc_cu']) ?></td>
                                <td><span class="badge badge-primary"><?= e($m['ma_tthc_moi']) ?></span></td>
                                <td><?= e($m['ten_tthc'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card p-3 bg-light">
                    <h6>➕ Thêm mới / Cập nhật ánh xạ TTHC</h6>
                    <form id="formAddMappingTthc" class="grid grid-3 gap-2 align-items-end">
                        <?= csrf_field() ?>
                        <div>
                            <label class="form-label small">Mã TTHC cũ *</label>
                            <input type="text" name="ma_tthc_cu" class="form-control form-control-sm" placeholder="VD: MC-XD-99" required>
                        </div>
                        <div>
                            <label class="form-label small">Tên TTHC cũ</label>
                            <input type="text" name="ten_tthc_cu" class="form-control form-control-sm" placeholder="VD: Cấp phép XD cũ">
                        </div>
                        <div>
                            <label class="form-label small">Mã TTHC Quốc gia mới *</label>
                            <input type="text" name="ma_tthc_moi" class="form-control form-control-sm" placeholder="1.000456.000.00.00.H21" required>
                        </div>
                        <div class="grid-span-3 text-end mt-2">
                            <button type="submit" class="btn btn-sm btn-primary">Lưu ánh xạ TTHC</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab ĐVHC -->
            <div class="tab-pane" id="tabMappingDvhc" style="display: none;">
                <div class="table-responsive mb-3" style="max-height: 280px;">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã ĐVHC cũ</th>
                                <th>Tên đơn vị cũ</th>
                                <th>Mã chuẩn TCTK</th>
                                <th>Đơn vị liên kết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anhXaDvhc as $m): ?>
                            <tr>
                                <td><code><?= e($m['ma_dvhc_cu']) ?></code></td>
                                <td><?= e($m['ten_dvhc_cu']) ?></td>
                                <td><span class="badge badge-success"><?= e($m['ma_tctk']) ?></span></td>
                                <td><?= e($m['ten_don_vi'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card p-3 bg-light">
                    <h6>➕ Thêm mới / Cập nhật ánh xạ ĐVHC</h6>
                    <form id="formAddMappingDvhc" class="grid grid-3 gap-2 align-items-end">
                        <?= csrf_field() ?>
                        <div>
                            <label class="form-label small">Mã ĐVHC cũ *</label>
                            <input type="text" name="ma_dvhc_cu" class="form-control form-control-sm" placeholder="VD: TH-09" required>
                        </div>
                        <div>
                            <label class="form-label small">Tên ĐVHC cũ</label>
                            <input type="text" name="ten_dvhc_cu" class="form-control form-control-sm" placeholder="VD: UBND Huyện Tiên Lãng cũ">
                        </div>
                        <div>
                            <label class="form-label small">Mã TCTK (3 chữ số) *</label>
                            <input type="text" name="ma_tctk" class="form-control form-control-sm" placeholder="VD: 315" required>
                        </div>
                        <div class="grid-span-3 text-end mt-2">
                            <button type="submit" class="btn btn-sm btn-primary">Lưu ánh xạ ĐVHC</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top">
            <button type="button" class="btn btn-outline" data-modal-close="modalMappings">Đóng</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let currentLoId = null;

    // Quản lý Modal & Tabs
    document.getElementById('btnManageMappings')?.addEventListener('click', function () {
        App.modal.open('modalMappings');
    });

    document.querySelectorAll('[data-tab-target]').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.nav-tab-item').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
            this.classList.add('active');
            const targetId = this.getAttribute('data-tab-target');
            const pane = document.getElementById(targetId);
            if (pane) pane.style.display = 'block';
        });
    });

    // Xử lý radio chọn nguồn CSV
    document.querySelectorAll('input[name="loai_nguon"]').forEach(r => {
        r.addEventListener('change', function () {
            const uploadSec = document.getElementById('uploadInputSection');
            if (this.value === 'UPLOAD') {
                uploadSec.style.display = 'block';
            } else {
                uploadSec.style.display = 'none';
            }
        });
    });

    // Helper cập nhật thanh tiến trình
    function setProgress(percent, label) {
        const container = document.getElementById('etlProgressBarContainer');
        const bar = document.getElementById('etlProgressBar');
        container.style.display = 'block';
        bar.style.width = percent + '%';
        bar.textContent = label || (percent + '%');
    }

    // Helper chuyển bước
    function goToStep(stepNum) {
        for (let i = 1; i <= 4; i++) {
            const sc = document.getElementById('stepContent' + i);
            const si = document.getElementById('stepIndicator' + i);
            if (sc) sc.style.display = (i === stepNum) ? 'block' : 'none';
            if (si) {
                if (i <= stepNum) {
                    si.classList.add('active');
                } else {
                    si.classList.remove('active');
                }
            }
        }
    }

    // BƯỚC 1: EXTRACT
    document.getElementById('btnRunExtract')?.addEventListener('click', async function () {
        const loaiNguon = document.querySelector('input[name="loai_nguon"]:checked')?.value || 'SAMPLE_MSSQL';
        const tenLo = document.getElementById('tenLoInput')?.value || '';

        const formData = new FormData();
        formData.append('loai_nguon', loaiNguon);
        formData.append('ten_lo', tenLo);
        formData.append('_csrf_token', '<?= csrf_token() ?>');

        if (loaiNguon === 'UPLOAD') {
            const file = document.getElementById('customCsvInput')?.files[0];
            if (!file) {
                App.toast.error('Vui lòng chọn tệp CSV tải lên từ máy tính.');
                return;
            }
            formData.append('file_csv', file);
        }

        this.disabled = true;
        this.textContent = '⏳ Đang trích xuất CSV...';
        setProgress(25, 'Đang đọc CSV...');

        try {
            const res = await fetch('<?= url("/admin/he-thong/etl/extract") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (!data.success) {
                throw new Error(data.message || 'Lỗi trích xuất');
            }

            currentLoId = data.data.lo_id;
            App.toast.success(data.message);
            document.getElementById('currentBatchBadge').textContent = 'Lô #' + currentLoId + ' (Staging)';

            // Tự động gọi Bước 2: Profiling
            await runProfiling(currentLoId);

        } catch (err) {
            App.toast.error('Thất bại: ' + err.message);
        } finally {
            this.disabled = false;
            this.textContent = '▶️ Bắt đầu Bước 1 (Trích xuất vào Staging)';
        }
    });

    // BƯỚC 2: PROFILING
    async function runProfiling(loId) {
        setProgress(50, 'Đang đánh giá chất lượng...');
        try {
            const res = await fetch('<?= url("/admin/he-thong/etl/profile") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf_token=<?= csrf_token() ?>&lo_id=' + loId
            });
            const result = await res.json();
            if (!result.success) throw new Error(result.message);

            const p = result.data;
            document.getElementById('pTotal').textContent = p.tong_so;
            document.getElementById('pValid').textContent = p.so_hop_le;
            document.getElementById('pErrors').textContent = p.so_loi;
            document.getElementById('pRate').textContent = p.ty_le_hop_le_phan_tram + '%';

            // Chi tiết lỗi
            const errs = p.chi_tiet_loi;
            document.getElementById('errDupl').textContent = errs.TRUNG_LAP || 0;
            document.getElementById('errMissingCccd').textContent = errs.THIEU_CCCD || 0;
            document.getElementById('errWrongCccd').textContent = errs.SAI_CCCD || 0;
            document.getElementById('errTthc').textContent = errs.MA_TTHC_SAI || 0;
            document.getElementById('errDvhc').textContent = errs.MA_DVHC_SAI || 0;
            document.getElementById('errJunk').textContent = errs.DU_LIEU_RAC || 0;

            goToStep(2);
        } catch (err) {
            App.toast.error('Lỗi Profiling: ' + err.message);
        }
    }

    // Nút quay lại
    document.getElementById('btnBackToStep1')?.addEventListener('click', () => goToStep(1));
    document.getElementById('btnBackToStep2')?.addEventListener('click', () => goToStep(2));

    // BƯỚC 3: TRANSFORM
    document.getElementById('btnRunTransform')?.addEventListener('click', async function () {
        if (!currentLoId) return;

        this.disabled = true;
        this.textContent = '⏳ Đang chuẩn hóa...';
        setProgress(75, 'Đang transform dữ liệu...');

        try {
            const res = await fetch('<?= url("/admin/he-thong/etl/transform") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf_token=<?= csrf_token() ?>&lo_id=' + currentLoId
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            App.toast.success(data.message);
            goToStep(3);
        } catch (err) {
            App.toast.error('Lỗi Transform: ' + err.message);
        } finally {
            this.disabled = false;
            this.textContent = '▶️ Tiếp tục Bước 3 (Transform & Chuẩn hóa)';
        }
    });

    // BƯỚC 4: LOAD & VERIFY
    document.getElementById('btnRunLoad')?.addEventListener('click', async function () {
        if (!currentLoId) return;

        this.disabled = true;
        this.textContent = '⏳ Đang nạp vào CSDL lõi...';
        setProgress(95, 'Đối soát SHA-256...');

        try {
            const res = await fetch('<?= url("/admin/he-thong/etl/load") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_csrf_token=<?= csrf_token() ?>&lo_id=' + currentLoId + '&chunk_size=50'
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message);

            const r = data.data;
            document.getElementById('resSuccessCount').textContent = r.so_thanh_cong;
            document.getElementById('resFileShaCount').textContent = r.doi_soat_file.khop_sha256;
            document.getElementById('resFailCount').textContent = r.so_that_bai;

            const btnErr = document.getElementById('btnDownloadErrorsCsv');
            if (btnErr) {
                btnErr.href = '<?= url("/admin/he-thong/etl/export-errors/") ?>' + currentLoId;
            }

            setProgress(100, 'Hoàn tất 100%!');
            App.toast.success(data.message);
            goToStep(4);
        } catch (err) {
            App.toast.error('Lỗi Load: ' + err.message);
        } finally {
            this.disabled = false;
            this.textContent = '🚀 Tiến hành Bước 4 (Load & Verify SHA-256)';
        }
    });

    // Lưu Ánh xạ TTHC
    document.getElementById('formAddMappingTthc')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const res = await fetch('<?= url("/admin/he-thong/etl/mapping-tthc") ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            App.toast.success(data.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            App.toast.error(data.message);
        }
    });

    // Lưu Ánh xạ ĐVHC
    document.getElementById('formAddMappingDvhc')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const res = await fetch('<?= url("/admin/he-thong/etl/mapping-dvhc") ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            App.toast.success(data.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            App.toast.error(data.message);
        }
    });
});
</script>
