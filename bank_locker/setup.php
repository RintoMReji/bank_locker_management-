<?php
/**
 * Bank Locker Management System - Unified Setup & Installer
 * Automates database creation, table migrations, and default data seeding.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Configuration
$config_path = __DIR__ . '/includes/config.php';
if (!file_exists($config_path)) {
    die("Error: Configuration file 'includes/config.php' is missing.");
}
require_once $config_path;

$status_log = [];
$db_created = false;
$conn = null;

// 1. Attempt Connection to MySQL server to verify credentials and create DB
try {
    $mysql_conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($mysql_conn->connect_error) {
        throw new Exception("Connection to MySQL Host failed: " . $mysql_conn->connect_error);
    }
    
    // Create database if not exists
    $db_name_safe = $mysql_conn->real_escape_string(DB_NAME);
    if ($mysql_conn->query("CREATE DATABASE IF NOT EXISTS `$db_name_safe`")) {
        $status_log[] = ["step" => "Database Creation", "status" => "success", "message" => "Database '" . DB_NAME . "' verified/created successfully."];
        $db_created = true;
    } else {
        throw new Exception("Failed to create database: " . $mysql_conn->error);
    }
    $mysql_conn->close();
} catch (Exception $e) {
    $status_log[] = ["step" => "Database Verification", "status" => "error", "message" => $e->getMessage()];
}

// 2. If DB is created/verified, establish connection to the database
if ($db_created) {
    try {
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
    } catch (Exception $e) {
        $status_log[] = ["step" => "Database Connection", "status" => "error", "message" => $e->getMessage()];
    }
}

// 3. Run table migrations and seed default records
if ($conn) {
    // List of table structures
    $tables = [
        "admin" => "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT '',
            phone VARCHAR(15) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "customers" => "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id VARCHAR(20) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(15) NOT NULL,
            address TEXT NOT NULL,
            aadhar_no VARCHAR(12) NOT NULL,
            account_no VARCHAR(20) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "lockers" => "CREATE TABLE IF NOT EXISTS lockers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            locker_number VARCHAR(20) NOT NULL UNIQUE,
            locker_size ENUM('small', 'medium', 'large') NOT NULL,
            annual_rent DECIMAL(10,2) NOT NULL,
            status ENUM('available', 'allocated', 'maintenance') DEFAULT 'available',
            location VARCHAR(100) NOT NULL DEFAULT 'Main Branch Vault',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "allocations" => "CREATE TABLE IF NOT EXISTS allocations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            allocation_no VARCHAR(20) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            locker_id INT NOT NULL,
            allocation_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            rent_paid DECIMAL(10,2) NOT NULL,
            payment_status ENUM('paid', 'pending', 'overdue') DEFAULT 'paid',
            status ENUM('active', 'surrendered') DEFAULT 'active',
            allocated_by VARCHAR(100) DEFAULT 'Admin',
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (locker_id) REFERENCES lockers(id)
        )",
        "access_log" => "CREATE TABLE IF NOT EXISTS access_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            locker_id INT NOT NULL,
            access_date DATE NOT NULL,
            access_time TIME NOT NULL,
            purpose VARCHAR(255),
            approved_by VARCHAR(100),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (locker_id) REFERENCES lockers(id)
        )",
        "sub_banker" => "CREATE TABLE IF NOT EXISTS sub_banker (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            employee_id VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(15) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "locker_requests" => "CREATE TABLE IF NOT EXISTS locker_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            locker_size ENUM('small','medium','large') NOT NULL,
            preferred_location VARCHAR(100) DEFAULT '',
            reason TEXT,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            handled_by VARCHAR(100) DEFAULT NULL,
            handled_remarks TEXT,
            handled_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )",
        "delete_requests" => "CREATE TABLE IF NOT EXISTS delete_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            allocation_id INT NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            handled_by VARCHAR(100) DEFAULT NULL,
            handled_remarks TEXT,
            handled_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (allocation_id) REFERENCES allocations(id)
        )",
        "contact_messages" => "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            reply TEXT DEFAULT NULL,
            replied_by VARCHAR(100) DEFAULT NULL,
            status ENUM('unread','read','replied') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )",
        "password_resets" => "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type ENUM('admin','sub_banker','customer') NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "activity_log" => "CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type VARCHAR(20) NOT NULL,
            user_id INT NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "joint_locker_holders" => "CREATE TABLE IF NOT EXISTS joint_locker_holders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT '',
            phone VARCHAR(15) DEFAULT '',
            relationship VARCHAR(50) NOT NULL,
            type ENUM('joint_holder','nominee') NOT NULL,
            aadhar_no VARCHAR(12) DEFAULT '',
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )",
        "settings" => "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT NOT NULL
        )",
        "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_type ENUM('admin', 'sub_banker') NOT NULL,
            sender_id INT NOT NULL,
            sender_name VARCHAR(100) NOT NULL,
            customer_id INT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            channels VARCHAR(100) NOT NULL DEFAULT 'system',
            sms_status VARCHAR(50) DEFAULT NULL,
            email_status VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "notification_reads" => "CREATE TABLE IF NOT EXISTS notification_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            notification_id INT NOT NULL,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
            UNIQUE KEY unique_read (customer_id, notification_id)
        )"
    ];

    // Create tables
    foreach ($tables as $name => $query) {
        if ($conn->query($query)) {
            $status_log[] = ["step" => "Table: $name", "status" => "success", "message" => "Table '$name' is ready."];
        } else {
            $status_log[] = ["step" => "Table: $name", "status" => "error", "message" => "Failed creating table '$name': " . $conn->error];
        }
    }

    // Apply incremental updates / column checks
    // 1. Admin phone & email
    $check = $conn->query("SHOW COLUMNS FROM admin LIKE 'email'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE admin ADD COLUMN email VARCHAR(100) DEFAULT '', ADD COLUMN phone VARCHAR(15) DEFAULT ''")) {
            $status_log[] = ["step" => "Migration: Admin Columns", "status" => "success", "message" => "Added email/phone columns to admin table."];
        } else {
            $status_log[] = ["step" => "Migration: Admin Columns", "status" => "error", "message" => "Failed adding email/phone to admin table: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Migration: Admin Columns", "status" => "skipped", "message" => "Admin email/phone columns already present."];
    }

    // 2. Allocations allocated_by
    $check = $conn->query("SHOW COLUMNS FROM allocations LIKE 'allocated_by'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE allocations ADD COLUMN allocated_by VARCHAR(100) DEFAULT 'Admin'")) {
            $status_log[] = ["step" => "Migration: Allocations Column", "status" => "success", "message" => "Added allocated_by column to allocations table."];
        } else {
            $status_log[] = ["step" => "Migration: Allocations Column", "status" => "error", "message" => "Failed adding allocated_by to allocations table: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Migration: Allocations Column", "status" => "skipped", "message" => "Allocations allocated_by column already present."];
    }

    // 3. Access Log status
    $check = $conn->query("SHOW COLUMNS FROM access_log LIKE 'status'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE access_log ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'")) {
            $status_log[] = ["step" => "Migration: Access Log Column", "status" => "success", "message" => "Added status column to access_log table."];
        } else {
            $status_log[] = ["step" => "Migration: Access Log Column", "status" => "error", "message" => "Failed adding status to access_log: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Migration: Access Log Column", "status" => "skipped", "message" => "Access log status column already present."];
    }

    // 4. Joint Holder Password & Login Email
    $check = $conn->query("SHOW COLUMNS FROM joint_locker_holders LIKE 'password'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE joint_locker_holders ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER aadhar_no")) {
            $status_log[] = ["step" => "Migration: Joint Holders Password", "status" => "success", "message" => "Added password column to joint_locker_holders."];
        } else {
            $status_log[] = ["step" => "Migration: Joint Holders Password", "status" => "error", "message" => "Failed adding password column: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Migration: Joint Holders Password", "status" => "skipped", "message" => "joint_locker_holders password column already present."];
    }

    $check = $conn->query("SHOW COLUMNS FROM joint_locker_holders LIKE 'login_email'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE joint_locker_holders ADD COLUMN login_email VARCHAR(100) DEFAULT NULL AFTER password")) {
            $status_log[] = ["step" => "Migration: Joint Holders Email", "status" => "success", "message" => "Added login_email column to joint_locker_holders."];
        } else {
            $status_log[] = ["step" => "Migration: Joint Holders Email", "status" => "error", "message" => "Failed adding login_email column: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Migration: Joint Holders Email", "status" => "skipped", "message" => "joint_locker_holders login_email column already present."];
    }

    // Seeding Default Records
    // 1. Seed default Admin
    $check = $conn->query("SELECT id FROM admin WHERE username='admin'");
    if ($check && $check->num_rows === 0) {
        $admin_pass_hash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (username, password, full_name, email, phone) VALUES ('admin', ?, 'Bank Administrator', 'admin@securebank.com', '1234567890')");
        $stmt->bind_param("s", $admin_pass_hash);
        if ($stmt->execute()) {
            $status_log[] = ["step" => "Seed: Default Admin", "status" => "success", "message" => "Default admin created successfully (username: admin, password: password)."];
        } else {
            $status_log[] = ["step" => "Seed: Default Admin", "status" => "error", "message" => "Failed to seed default admin: " . $stmt->error];
        }
        $stmt->close();
    } else {
        $status_log[] = ["step" => "Seed: Default Admin", "status" => "skipped", "message" => "Default admin account already exists."];
    }

    // 2. Seed default Sub Banker
    $check = $conn->query("SELECT id FROM sub_banker WHERE username='subbanker'");
    if ($check && $check->num_rows === 0) {
        $subbanker_pass_hash = password_hash('subbanker123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO sub_banker (username, password, full_name, employee_id, email, phone) VALUES ('subbanker', ?, 'Sub Banker Officer', 'EMP2024001', 'subbanker@securebank.com', '9876543210')");
        $stmt->bind_param("s", $subbanker_pass_hash);
        if ($stmt->execute()) {
            $status_log[] = ["step" => "Seed: Default Sub-Banker", "status" => "success", "message" => "Default sub-banker created successfully (username: subbanker, password: subbanker123)."];
        } else {
            $status_log[] = ["step" => "Seed: Default Sub-Banker", "status" => "error", "message" => "Failed to seed default sub-banker: " . $stmt->error];
        }
        $stmt->close();
    } else {
        $status_log[] = ["step" => "Seed: Default Sub-Banker", "status" => "skipped", "message" => "Default sub-banker account already exists."];
    }

    // 3. Seed Maintenance Settings
    $check = $conn->query("SELECT setting_key FROM settings WHERE setting_key='maintenance_mode'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', 'off')")) {
            $status_log[] = ["step" => "Seed: Settings (Maintenance)", "status" => "success", "message" => "Default maintenance mode initialized to 'off'."];
        } else {
            $status_log[] = ["step" => "Seed: Settings (Maintenance)", "status" => "error", "message" => "Failed to seed maintenance setting: " . $conn->error];
        }
    } else {
        $status_log[] = ["step" => "Seed: Settings (Maintenance)", "status" => "skipped", "message" => "Maintenance mode setting already configured."];
    }

    $check = $conn->query("SELECT setting_key FROM settings WHERE setting_key='maintenance_message'");
    if ($check && $check->num_rows === 0) {
        $msg_default = "We are currently performing scheduled system upgrades. Please check back soon.";
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_message', ?)");
        $stmt->bind_param("s", $msg_default);
        if ($stmt->execute()) {
            $status_log[] = ["step" => "Seed: Settings (Message)", "status" => "success", "message" => "Default maintenance message initialized."];
        } else {
            $status_log[] = ["step" => "Seed: Settings (Message)", "status" => "error", "message" => "Failed to seed maintenance message: " . $stmt->error];
        }
        $stmt->close();
    } else {
        $status_log[] = ["step" => "Seed: Settings (Message)", "status" => "skipped", "message" => "Maintenance message already configured."];
    }

    // 4. Seed Lockers if empty
    $check = $conn->query("SELECT COUNT(*) AS count FROM lockers");
    $lockers_count = ($check) ? intval($check->fetch_assoc()['count']) : 0;
    if ($lockers_count === 0) {
        $lockers_data = [
            ['L001', 'small', 1500.00, 'Main Branch Vault - Row A'],
            ['L002', 'small', 1500.00, 'Main Branch Vault - Row A'],
            ['L003', 'small', 1500.00, 'Main Branch Vault - Row A'],
            ['L004', 'medium', 2500.00, 'Main Branch Vault - Row B'],
            ['L005', 'medium', 2500.00, 'Main Branch Vault - Row B'],
            ['L006', 'medium', 2500.00, 'Main Branch Vault - Row B'],
            ['L007', 'large', 4000.00, 'Main Branch Vault - Row C'],
            ['L008', 'large', 4000.00, 'Main Branch Vault - Row C'],
            ['L009', 'large', 4000.00, 'Main Branch Vault - Row C'],
            ['L010', 'small', 1500.00, 'Main Branch Vault - Row A'],
            ['L011', 'medium', 2500.00, 'Main Branch Vault - Row B'],
            ['L012', 'large', 4000.00, 'Main Branch Vault - Row C']
        ];
        
        $success_count = 0;
        $stmt = $conn->prepare("INSERT INTO lockers (locker_number, locker_size, annual_rent, location) VALUES (?, ?, ?, ?)");
        foreach ($lockers_data as $locker) {
            $stmt->bind_param("ssds", $locker[0], $locker[1], $locker[2], $locker[3]);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
        $stmt->close();
        $status_log[] = ["step" => "Seed: Sample Lockers", "status" => "success", "message" => "Successfully seeded $success_count/12 default lockers."];
    } else {
        $status_log[] = ["step" => "Seed: Sample Lockers", "status" => "skipped", "message" => "Locker database already has $lockers_count lockers seeded."];
    }

    $conn->close();
}

$has_errors = false;
foreach ($status_log as $log) {
    if ($log['status'] === 'error') {
        $has_errors = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup & Installer | SecureBank</title>
    <!-- Modern Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #004c8f;
            --primary-dark: #1a3a5c;
            --accent: #22c55e;
            --accent-hover: #16a34a;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg: #0b1329;
            --card-bg: #1c2541;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0b1329 0%, #1c2541 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(to right, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        /* Status items styling */
        .log-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 280px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .log-list::-webkit-scrollbar {
            width: 6px;
        }
        .log-list::-webkit-scrollbar-thumb {
            background-color: var(--border);
            border-radius: 3px;
        }

        .log-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background-color: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .log-item:hover {
            border-color: #475569;
            transform: translateX(2px);
        }

        .log-step {
            font-weight: 500;
            color: var(--text);
        }

        .log-message {
            color: var(--text-muted);
            margin-left: 10px;
            font-size: 13px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .badge-skipped {
            background-color: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Credentials Table */
        .cred-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 10px;
        }

        .cred-table th, .cred-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .cred-table th {
            color: var(--text-muted);
            font-weight: 600;
            background-color: rgba(15, 23, 42, 0.3);
        }

        .cred-table td code {
            font-family: monospace;
            background-color: #0f172a;
            padding: 2px 6px;
            border-radius: 4px;
            color: #60a5fa;
        }

        /* Access links cards */
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .link-card {
            background-color: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            transition: all 0.2s ease;
        }

        .link-card:hover {
            border-color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.08);
            transform: translateY(-2px);
        }

        .link-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .link-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Warnings Card */
        .warning-card {
            border-left: 4px solid var(--warning);
            background-color: rgba(245, 158, 11, 0.08);
        }

        .warning-card p {
            color: #fbd38d;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Action buttons */
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: #64748b;
        }

        .status-header {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 600;
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
        }

        .status-header-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .status-header-error {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔧 Bank Locker Management System</h1>
        <p>Unified System Setup & Verification Dashboard</p>
    </div>

    <!-- Installation Log -->
    <div class="card">
        <div class="card-title">
            <span>⚙️ Installation & Migration Steps</span>
        </div>
        
        <?php if ($has_errors): ?>
            <div class="status-header status-header-error">
                <span>⚠️ One or more steps encountered errors. Please check the log below and ensure your XAMPP MySQL server is running and configured correctly.</span>
            </div>
        <?php else: ?>
            <div class="status-header status-header-success">
                <span>✅ All setup tasks completed successfully! The database is fully initialized and ready.</span>
            </div>
        <?php endif; ?>

        <ul class="log-list">
            <?php foreach ($status_log as $log): ?>
                <li class="log-item">
                    <div>
                        <span class="log-step"><?= htmlspecialchars($log['step']) ?></span>
                        <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                    </div>
                    <div>
                        <span class="badge badge-<?= $log['status'] ?>"><?= $log['status'] ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if (!$has_errors): ?>
        <!-- Default Login Credentials -->
        <div class="card">
            <div class="card-title">
                <span>🔑 Default Authentication Credentials</span>
            </div>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Use the following pre-configured user credentials to sign in to the respective dashboards.</p>
            <table class="cred-table">
                <thead>
                    <tr>
                        <th>Role / Module</th>
                        <th>Default Username / Email</th>
                        <th>Default Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Administrator</strong></td>
                        <td><code>admin</code></td>
                        <td><code>password</code></td>
                    </tr>
                    <tr>
                        <td><strong>Sub-Banker</strong></td>
                        <td><code>subbanker</code></td>
                        <td><code>subbanker123</code></td>
                    </tr>
                    <tr>
                        <td><strong>Customer Portal</strong></td>
                        <td>Create via Admin Panel or Request Form</td>
                        <td>Configured during registration</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Quick Access Navigation -->
        <div class="card">
            <div class="card-title">
                <span>🚀 Module Access Center</span>
            </div>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Click any card below to launch the respective module in your browser.</p>
            <div class="links-grid">
                <a href="<?= BASE_URL ?>/admin/login.php" class="link-card" target="_blank">
                    <div class="link-title">
                        <span>👔 Admin Module</span>
                        <span>➔</span>
                    </div>
                    <span class="link-desc">Manage lockers, allocate slots, review surrender requests, and manage sub-bankers.</span>
                </a>
                <a href="<?= BASE_URL ?>/sub_banker/login.php" class="link-card" target="_blank">
                    <div class="link-title">
                        <span>🏦 Sub-Banker Portal</span>
                        <span>➔</span>
                    </div>
                    <span class="link-desc">Handle customer requests, check access logs, reply to contact queries, and send notifications.</span>
                </a>
                <a href="<?= BASE_URL ?>/customer/login.php" class="link-card" target="_blank">
                    <div class="link-title">
                        <span>👤 Customer Area</span>
                        <span>➔</span>
                    </div>
                    <span class="link-desc">View locker details, book access slots, joint facilities, and send messages to branch.</span>
                </a>
                <a href="<?= BASE_URL ?>/new_locker_request.php" class="link-card" target="_blank">
                    <div class="link-title">
                        <span>📩 Public Request</span>
                        <span>➔</span>
                    </div>
                    <span class="link-desc">Public registration portal for new customers requesting locker space.</span>
                </a>
            </div>
        </div>

        <!-- Security Warning -->
        <div class="card warning-card">
            <p><strong>⚠️ SECURITY WARNING:</strong> Please delete the <code>setup.php</code> file from your server root directory (<code><?= htmlspecialchars(__FILE__) ?></code>) immediately after you have completed and verified your setup to prevent unauthorized database resets.</p>
        </div>

        <div class="btn-container">
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">🏠 Go to System Homepage</a>
        </div>
    <?php else: ?>
        <div class="btn-container">
            <button onclick="window.location.reload();" class="btn btn-primary">🔄 Retry Installation</button>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
