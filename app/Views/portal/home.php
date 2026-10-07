<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'HP-eGov Hải Phòng') ?> | UBND TP. Hải Phòng</title>
    <meta name="description" content="Hệ thống thông tin giải quyết thủ tục hành chính và Một cửa điện tử liên thông TP. Hải Phòng (HP-eGov)">
    <style>
        :root {
            --primary: #0B2E59;      /* Navy chủ đạo */
            --primary-dark: #071F3D;
            --accent-red: #C8102E;   /* Đỏ cờ nhấn */
            --accent-gold: #D4A017;  /* Vàng kim */
            --bg-light: #F4F6FA;     /* Nền sáng thanh lịch */
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --success: #16A34A;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Quốc gia & Thành phố */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            padding: 24px 20px;
            box-shadow: 0 4px 20px rgba(11, 46, 89, 0.15);
            border-bottom: 3px solid var(--accent-gold);
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logo-emblem {
            width: 54px;
            height: 54px;
            background: var(--accent-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-gold);
            font-weight: 800;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(200, 16, 46, 0.3);
            border: 2px solid #ffffff;
        }

        .brand-text h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .brand-text p {
            font-size: 13px;
            color: #cbd5e1;
            font-weight: 400;
        }

        .status-pill {
            background: rgba(22, 163, 74, 0.2);
            border: 1px solid var(--success);
            color: #4ade80;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ade80;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Container chính */
        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        .hero-banner {
            background: #ffffff;
            border-radius: 14px;
            padding: 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border-left: 6px solid var(--primary);
            margin-bottom: 30px;
        }

        .hero-banner h2 {
            font-size: 26px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .hero-banner p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 800px;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .card-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .card-desc {
            font-size: 13px;
            color: var(--text-muted);
        }

        .tech-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .tech-box h3 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tech-tag {
            background: #f1f5f9;
            color: #334155;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
        }

        /* Footer */
        .footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="brand-box">
                <div class="logo-emblem">HP</div>
                <div class="brand-text">
                    <h1>ỦY BAN NHÂN DÂN THÀNH PHỐ HẢI PHÒNG</h1>
                    <p>Hệ thống Một cửa điện tử liên thông & Cổng Dịch vụ công (HP-eGov)</p>
                </div>
            </div>
            <div class="status-pill">
                <span class="status-dot"></span>
                <span>Hệ thống đang hoạt động</span>
            </div>
        </div>
    </header>

    <main class="main-content">
        <section class="hero-banner">
            <h2>Hệ thống HP-eGov đang hoạt động</h2>
            <p>
                Nền tảng Lõi MVC PHP 8.2 thuần đã được khởi động thành công với kiến trúc chuẩn bảo mật Cấp độ 3, 
                tách biệt CSDL Master/Replica, kiểm định CSRF, phân quyền RBAC và cơ chế bất biến dữ liệu hành chính.
            </p>
        </section>

        <section class="grid-cards">
            <div class="card">
                <div class="card-title">Cơ sở dữ liệu</div>
                <div class="card-value" style="font-size: 20px; color: var(--success); margin-top: 8px;">
                    <?= e($dbStatus) ?>
                </div>
                <div class="card-desc">MariaDB 10.4+ InnoDB (50 bảng, 6 triggers WORM)</div>
            </div>

            <div class="card">
                <div class="card-title">Đơn vị hành chính</div>
                <div class="card-value"><?= e((string)$dvhcCount) ?></div>
                <div class="card-desc">15 quận/huyện/thành phố và xã/phường Hải Phòng</div>
            </div>

            <div class="card">
                <div class="card-title">Thủ tục hành chính</div>
                <div class="card-value"><?= e((string)$tthcCount) ?></div>
                <div class="card-desc">TTHC trọng điểm tích hợp E-Form JSON & BPMN</div>
            </div>
        </section>

        <section class="tech-box">
            <h3>Đặc tả Kiến trúc Lõi MVC (Phiên 3/16)</h3>
            <div class="badge-list">
                <span class="tech-tag">PHP 8.2 Strict Types</span>
                <span class="tech-tag">PSR-4 Pure Autoloader</span>
                <span class="tech-tag">Dynamic Regex Router</span>
                <span class="tech-tag">PDO Master/Replica</span>
                <span class="tech-tag">No-Physical-Delete Model</span>
                <span class="tech-tag">Security Headers Cấp độ 3</span>
                <span class="tech-tag">Rate Limiting 60 req/min</span>
                <span class="tech-tag">Hash Chain Audit WORM</span>
                <span class="tech-tag">Vietnamese Validator</span>
                <span class="tech-tag">Custom Test Runner</span>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2026 Ủy ban nhân dân Thành phố Hải Phòng. Phát triển theo chuẩn Kiến trúc Chính quyền điện tử Việt Nam.</p>
    </footer>
</body>
</html>
