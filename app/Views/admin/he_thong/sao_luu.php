<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="row mb-4">
    <!-- Thẻ tóm tắt chỉ số -->
    <div class="col-md-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">BẢN FULL BACKUP</div>
                <h3 class="fw-bold text-primary my-1"><?= $stats['full_count'] ?> bản</h3>
                <div class="small text-muted">Lần cuối: <?= $stats['last_full'] ? date('d/m/Y H:i', strtotime($stats['last_full'])) : 'Chưa có' ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">DIFFERENTIAL (NGÀY)</div>
                <h3 class="fw-bold text-info my-1"><?= $stats['diff_count'] ?> bản</h3>
                <div class="small text-muted">Lưu trữ tối đa 30 ngày gần nhất</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">TRANSACTION LOG (2H)</div>
                <h3 class="fw-bold text-success my-1"><?= $stats['log_count'] ?> bản</h3>
                <div class="small text-muted">Cam kết RPO ≤ 2 giờ</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning h-100">
            <div class="card-body">
                <div class="text-muted small fw-bold">CAM KẾT RTO / DRP</div>
                <h3 class="fw-bold text-warning my-1">≤ 4 giờ</h3>
                <div class="small text-muted">Phục hồi thử nghiệm tự động</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="card-title m-0">Quản trị Sao lưu & Phục hồi CSDL (PH13)</h2>
            <p class="text-muted small m-0">Sao lưu an toàn tuần hoàn: Full (Chủ nhật 00:00) + Differential (23:00) + Transaction Log (2h/lần)</p>
        </div>
        <!-- Các nút kích hoạt chạy sao lưu tức thì -->
        <div class="d-flex gap-2">
            <form method="POST" action="<?= url('/admin/he-thong/sao-luu/run') ?>" class="d-inline" onsubmit="return confirm('Khởi động chạy Full Backup ngay bây giờ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="FULL">
                <button type="submit" class="btn btn-primary btn-sm">
                    💾 Full Backup ngay
                </button>
            </form>
            <form method="POST" action="<?= url('/admin/he-thong/sao-luu/run') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="DIFFERENTIAL">
                <button type="submit" class="btn btn-info btn-sm text-white">
                    📑 Diff Backup ngay
                </button>
            </form>
            <form method="POST" action="<?= url('/admin/he-thong/sao-luu/run') ?>" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="LOG">
                <button type="submit" class="btn btn-success btn-sm">
                    ⏱️ Log Backup ngay
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 120px;">Loại</th>
                        <th>Tên tệp tin sao lưu</th>
                        <th style="width: 110px;">Dung lượng</th>
                        <th>Checksum SHA-256 đối soát</th>
                        <th style="width: 140px;">Vị trí lưu</th>
                        <th style="width: 150px;">Thời gian tạo</th>
                        <th style="width: 110px;" class="text-center">Trạng thái</th>
                        <th style="width: 190px;" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Chưa có bản ghi sao lưu nào trong lịch sử. Bấm nút phía trên để tạo bản sao lưu đầu tiên.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $item['id'] ?></td>
                                <td>
                                    <?php
                                    $badgeType = 'bg-secondary';
                                    if ($item['loai_sao_luu'] === 'FULL') $badgeType = 'bg-primary';
                                    elseif ($item['loai_sao_luu'] === 'DIFFERENTIAL') $badgeType = 'bg-info text-white';
                                    elseif ($item['loai_sao_luu'] === 'LOG') $badgeType = 'bg-success';
                                    ?>
                                    <span class="badge <?= $badgeType ?>"><?= e($item['loai_sao_luu']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold font-monospace small"><?= e($item['ten_file']) ?></div>
                                    <small class="text-muted"><?= e($item['ghi_chu'] ?? '') ?></small>
                                </td>
                                <td class="small">
                                    <?php
                                    $bytes = (int)$item['dung_luong_byte'];
                                    if ($bytes >= 1048576) {
                                        echo round($bytes / 1048576, 2) . ' MB';
                                    } elseif ($bytes >= 1024) {
                                        echo round($bytes / 1024, 2) . ' KB';
                                    } else {
                                        echo $bytes . ' B';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="font-monospace small text-truncate" style="max-width: 200px;" title="<?= e($item['sha256']) ?>">
                                        <?= !empty($item['sha256']) ? substr($item['sha256'], 0, 16) . '...' : 'N/A' ?>
                                    </div>
                                </td>
                                <td class="small">
                                    <span class="badge bg-light text-dark border"><?= e($item['vi_tri_luu']) ?></span>
                                </td>
                                <td class="small">
                                    <?= date('d/m/Y H:i:s', strtotime($item['thoi_gian_bat_dau'])) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item['trang_thai'] === 'THANH_CONG'): ?>
                                        <span class="badge bg-success">Thành công</span>
                                    <?php elseif ($item['trang_thai'] === 'DANG_CHAY'): ?>
                                        <span class="badge bg-warning text-dark">Đang chạy</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Thất bại</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Tải về -->
                                        <a href="<?= url('/admin/he-thong/sao-luu/download/' . $item['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Tải tệp tin">
                                            📥
                                        </a>

                                        <!-- Phục hồi thử nghiệm (Test Restore) -->
                                        <form method="POST" action="<?= url('/admin/he-thong/sao-luu/test-restore') ?>" class="d-inline" onsubmit="return confirm('Thực hiện PHỤC HỒI THỬ NGHIỆM bản lưu #<?= $item['id'] ?> vào CSDL kiểm thử hp_egov_restore_test và đo thời gian RTO?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="backup_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Phục hồi thử nghiệm vào hp_egov_restore_test">
                                                🔄 Phục hồi thử (RTO)
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hướng dẫn cấu hình Tự động hóa Windows Task Scheduler & MySQL Binlog -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h4 class="card-title m-0 fs-6 fw-bold">🕒 Lệnh Windows Task Scheduler mẫu (schtasks)</h4>
            </div>
            <div class="card-body">
                <p class="small text-muted">Cấu hình tự động chạy lịch sao lưu bằng Task Scheduler trên máy chủ Windows XAMPP:</p>
                
                <label class="form-label small fw-bold">1. Full Backup (00:00 Chủ nhật hàng tuần):</label>
                <pre class="bg-dark text-light p-2 rounded small font-monospace">schtasks /Create /SC WEEKLY /D SUN /TN "HP-eGov\FullBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_full.php" /ST 00:00 /RU SYSTEM</pre>

                <label class="form-label small fw-bold mt-2">2. Differential Backup (23:00 hằng ngày):</label>
                <pre class="bg-dark text-light p-2 rounded small font-monospace">schtasks /Create /SC DAILY /TN "HP-eGov\DiffBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_diff.php" /ST 23:00 /RU SYSTEM</pre>

                <label class="form-label small fw-bold mt-2">3. Transaction Log Backup (Mỗi 2 giờ):</label>
                <pre class="bg-dark text-light p-2 rounded small font-monospace">schtasks /Create /SC HOURLY /MO 2 /TN "HP-eGov\LogBackup" /TR "C:\xampp\php\php.exe c:\xampp\htdocs\HP-eGov\cron\backup_log.php" /RU SYSTEM</pre>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h4 class="card-title m-0 fs-6 fw-bold">⚙️ Hướng dẫn Kích hoạt Binary Log trong my.ini (RPO ≤ 2h)</h4>
            </div>
            <div class="card-body">
                <p class="small text-muted">Để tối ưu hóa Transaction Log bằng <code>mysqlbinlog.exe</code> chuẩn xác đến từng giao dịch, mở tệp <code>C:\xampp\mysql\bin\my.ini</code> và bổ sung khối sau dưới thẻ <code>[mysqld]</code>:</p>
                <pre class="bg-dark text-light p-2 rounded small font-monospace"># Kích hoạt MySQL Binary Logging cho HP-eGov
server-id = 1
log-bin = mysql-bin
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 100M</pre>
                <p class="small text-muted mt-2">
                    Khởi động lại dịch vụ MySQL trong XAMPP Control Panel sau khi sửa đổi. Lệnh trích xuất binlog thủ công:
                </p>
                <pre class="bg-dark text-light p-2 rounded small font-monospace">C:\xampp\mysql\bin\mysqlbinlog.exe --start-datetime="2026-10-07 00:00:00" C:\xampp\mysql\data\mysql-bin.000001 &gt; binlog_recovery.sql</pre>
            </div>
        </div>
    </div>
</div>
