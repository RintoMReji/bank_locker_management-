<?php
require_once __DIR__ . '/includes/config.php';

$message = "We are currently performing scheduled maintenance to improve our services. Please check back later.";

try {
    $conn = getDBConnection();
    $check = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($check && $check->num_rows > 0) {
        $result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_message' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['setting_value'])) {
                $message = $row['setting_value'];
            }
        }
        
        // Double check if maintenance mode is actually on. If it's off, don't show this page and redirect home.
        $mode_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
        if ($mode_res && $mode_res->num_rows > 0) {
            $mode_row = $mode_res->fetch_assoc();
            if ($mode_row['setting_value'] !== 'on') {
                header("Location: " . BASE_URL . "/");
                exit();
            }
        }
    }
} catch (Exception $e) {
    // Fail silently, use default message
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance | SecureVault Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }
        
        /* Subtle glowing background blobs */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(232, 160, 32, 0.1) 0%, rgba(0,0,0,0) 70%);
            top: -100px;
            left: -100px;
            z-index: 1;
        }
        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(37, 99, 168, 0.08) 0%, rgba(0,0,0,0) 70%);
            bottom: -150px;
            right: -100px;
            z-index: 1;
        }

        .maintenance-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 50px 40px;
            max-width: 580px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            z-index: 10;
            position: relative;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            text-decoration: none;
        }
        .logo-icon {
            font-size: 32px;
            background: rgba(232, 160, 32, 0.15);
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(232, 160, 32, 0.3);
        }
        .logo-text {
            text-align: left;
            line-height: 1.2;
        }
        .logo-text strong {
            display: block;
            font-size: 18px;
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .logo-text span {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .icon-wrapper {
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }
        .main-icon {
            font-size: 64px;
            animation: float 4s ease-in-out infinite;
            display: inline-block;
        }
        .gear-overlay {
            font-size: 28px;
            position: absolute;
            bottom: -5px;
            right: -10px;
            animation: spin 8s linear infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        h1 {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        .divider {
            height: 3px;
            width: 80px;
            background: linear-gradient(90deg, #e8a020 0%, #2563a8 100%);
            margin: 0 auto 24px;
            border-radius: 2px;
        }
        p.subtitle {
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .message-box {
            background: rgba(15, 23, 42, 0.4);
            border-left: 4px solid #e8a020;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            text-align: left;
            margin-bottom: 32px;
            font-size: 14px;
            line-height: 1.5;
            color: #e2e8f0;
        }
        .message-label {
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #e8a020;
            margin-bottom: 6px;
        }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2563a8;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 168, 0.25);
        }
        .btn-refresh:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 168, 0.4);
        }

        .footer-links {
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 20px;
            font-size: 12px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .footer-links a:hover {
            color: #e8a020;
        }
    </style>
</head>
<body>

    <div class="maintenance-card">
        <div class="logo-section">
            <div class="logo-icon">🏦</div>
            <div class="logo-text">
                <strong>SecureVault Bank</strong>
                <span>Locker Management System</span>
            </div>
        </div>

        <div class="icon-wrapper">
            <span class="main-icon">🛠️</span>
            <span class="gear-overlay">⚙️</span>
        </div>

        <h1>System Maintenance</h1>
        <div class="divider"></div>
        <p class="subtitle">We are currently executing scheduled upgrades to our locker portal. Normal service will be restored shortly.</p>

        <div class="message-box">
            <div class="message-label">Update from Operations Team</div>
            <div><?= htmlspecialchars($message) ?></div>
        </div>

        <button onclick="window.location.reload();" class="btn-refresh">🔄 Refresh Portal</button>

        <div class="footer-links">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(BANK_NAME) ?></span>
            <a href="<?= BASE_URL ?>/admin/login.php">🔑 Staff Login</a>
        </div>
    </div>

</body>
</html>
