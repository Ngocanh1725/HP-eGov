<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? '403 - Truy cập bị từ chối') ?> | Cổng DVC Hải Phòng</title>
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
            max-width: 600px;
            width: 100%;
            padding: 44px 36px;
            text-align: center;
            border-top: 6px solid #e11d48;
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
    </style>
</head>
<body>
    <div class="error-card">
        <span class="badge">403 FORBIDDEN</span>
        <h1>Truy Cập Bị Từ Chối</h1>
        <p><?= htmlspecialchars($message ?? 'Bạn không có quyền truy cập vào tài nguyên này theo Quy chế An toàn thông tin HP-eGov.') ?></p>
        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-outline">Quay lại</a>
            <a href="/HP-eGov/" class="btn btn-primary">Trang chủ Hệ thống</a>
        </div>
    </div>
</body>
</html>
