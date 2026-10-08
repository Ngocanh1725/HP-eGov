<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '500 - Lỗi máy chủ nội bộ') ?> | Cổng DVC Hải Phòng</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(11, 46, 89, 0.12), 0 8px 10px -6px rgba(11, 46, 89, 0.08);
            max-width: 650px;
            width: 100%;
            padding: 44px 36px;
            text-align: center;
            border-top: 6px solid #b91c1c;
        }
        .badge {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 9999px;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }
        h1 { font-size: 26px; color: #0B2E59; margin-bottom: 12px; font-weight: 700; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 28px; }
        .btn-group { display: flex; gap: 12px; justify-content: center; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 11px 22px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .btn-primary { background: #0B2E59; color: #ffffff; }
        .btn-primary:hover { background: #082242; }
        .btn-outline { background: #ffffff; color: #475569; border: 1px solid #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; }
        .debug-panel {
            margin-top: 24px;
            text-align: left;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-family: Consolas, monospace;
            font-size: 13px;
            max-height: 280px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <span class="badge">500 INTERNAL SERVER ERROR</span>
        <h1>Lỗi Hệ Thống Nội Bộ</h1>
        <p>
            <?php if (!empty($isDebug) && !empty($message)): ?>
                <?= htmlspecialchars($message) ?>
            <?php else: ?>
                Đã xảy ra sự cố kỹ thuật trong quá trình xử lý yêu cầu. Sự cố đã được tự động ghi nhận vào nhật ký kiểm toán hệ thống để bộ phận kỹ thuật khắc phục.
            <?php endif; ?>
        </p>
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-outline">Quay lại</a>
            <a href="/HP-eGov/" class="btn btn-primary">Trang chủ Hệ thống</a>
        </div>

        <?php if (!empty($isDebug) && !empty($exception)): ?>
            <div class="debug-panel">
                <strong>Ngoại lệ:</strong> <?= htmlspecialchars(get_class($exception)) ?><br>
                <strong>Tệp tin:</strong> <?= htmlspecialchars($exception->getFile()) ?> : <strong>Dòng <?= $exception->getLine() ?></strong><br><br>
                <strong>Chi tiết Stack Trace:</strong>
                <pre style="margin: 0; white-space: pre-wrap; font-size: 12px;"><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
