<?php $this->layout('layouts/admin', ['title' => $title, 'breadcrumbs' => $breadcrumbs]); ?>

<div class="row mb-4">
    <!-- Thẻ trạng thái tổng quát -->
    <div class="col-md-3">
        <div class="card h-100 <?= ($health['status'] === 'UP') ? 'border-success' : 'border-danger' ?>">
            <div class="card-body text-center">
                <span class="fs-1"><?= ($health['status'] === 'UP') ? '🟢' : '🔴' ?></span>
                <h4 class="fw-bold mt-2 <?= ($health['status'] === 'UP') ? 'text-success' : 'text-danger' ?>">
                    <?= $health['status'] === 'UP' ? 'HỆ THỐNG KHỎE MẠNH' : 'CẢNH BÁO GIẢM HIỆU NĂNG' ?>
                </h4>
                <p class="small text-muted m-0">Kiểm tra lúc: <?= date('d/m/Y H:i:s', strtotime($health['timestamp'])) ?></p>
            </div>
        </div>
    </div>

    <!-- DB Ping & Latency -->
    <div class="col-md-3">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <div class="text-muted small fw-bold">CSDL MASTER (MARIADB)</div>
                <h3 class="fw-bold text-primary my-2">
                    <?= $health['database']['latency_ms'] ?> ms
                </h3>
                <span class="badge <?= ($health['database']['status'] === 'UP') ? 'bg-success' : 'bg-danger' ?>">
                    Kết nối: <?= $health['database']['status'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Ổ đĩa -->
    <div class="col-md-3">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <div class="text-muted small fw-bold">DUNG LƯỢNG Ổ ĐĨA HỆ THỐNG</div>
                <h3 class="fw-bold text-warning-emphasis my-2">
                    <?= $health['disk']['used_percent'] ?>%
                </h3>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $health['disk']['used_percent'] ?>%;"></div>
                </div>
                <small class="text-muted">Trống: <?= $health['disk']['free_gb'] ?> GB / <?= $health['disk']['total_gb'] ?> GB</small>
            </div>
        </div>
    </div>

    <!-- Active Sessions -->
    <div class="col-md-3">
        <div class="card h-100 border-info">
            <div class="card-body">
                <div class="text-muted small fw-bold">PHIÊN HOẠT ĐỘNG (30 PHÚT)</div>
                <h3 class="fw-bold text-info my-2">
                    <?= $health['active_sessions'] ?> phiên
                </h3>
                <small class="text-muted">Cán bộ và công dân trực tuyến</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Cột trái: Chi tiết thành phần hạ tầng -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h4 class="card-title m-0 fs-6 fw-bold">🔍 Chi tiết Các Thành phần Phụ trợ (Subsystems)</h4>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Kích thước Thư mục Storage:</strong>
                            <div class="small text-muted">Bao gồm hồ sơ đính kèm, tệp chữ ký số (.sig), sao lưu</div>
                        </div>
                        <span class="badge bg-secondary fs-6"><?= $health['storage']['formatted'] ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Hàng đợi Thông báo Chờ gửi:</strong>
                            <div class="small text-muted">Hàng đợi SMS, Email, Zalo ZNS công dân</div>
                        </div>
                        <span class="badge <?= $health['queue']['pending_count'] > 0 ? 'bg-warning text-dark' : 'bg-success' ?> fs-6">
                            <?= $health['queue']['pending_count'] ?> tin chờ
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Lần quét SLA Cron gần nhất:</strong>
                            <div class="small text-muted">Tiến trình quét cảnh báo vi phạm hạn giải quyết</div>
                        </div>
                        <span class="small font-monospace text-primary"><?= e($health['cron']['last_sla_scan']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Lần sao lưu gần nhất:</strong>
                            <div class="small text-muted">Sao lưu CSDL Full / Differential</div>
                        </div>
                        <span class="small font-monospace text-success"><?= e($health['cron']['last_backup']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Cột phải: Thông số Môi trường & REST API Endpoint -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <h4 class="card-title m-0 fs-6 fw-bold">💻 Thông tin Môi trường Máy chủ & Endpoint Giám sát</h4>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <tbody>
                        <tr>
                            <td class="fw-bold bg-light" style="width: 160px;">Phiên bản PHP:</td>
                            <td><code><?= e($health['environment']['php_version']) ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">Phiên bản MariaDB:</td>
                            <td><code><?= e($health['environment']['mysql_version']) ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">Máy chủ Web:</td>
                            <td><?= e($health['environment']['web_server']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">Hệ điều hành:</td>
                            <td><?= e($health['environment']['os']) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold bg-light">Môi trường (APP_ENV):</td>
                            <td>
                                <span class="badge <?= ($health['app_env'] === 'production') ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= e($health['app_env']) ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    <label class="form-label fw-bold small">REST API Health Endpoint (dành cho Prometheus, Zabbix, Uptime Kuma):</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control font-monospace" value="<?= url('/api/v1/health') ?>" readonly>
                        <a href="<?= url('/api/v1/health') ?>" target="_blank" class="btn btn-outline-primary">Xem JSON</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
