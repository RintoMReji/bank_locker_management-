<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'bank_locker_db');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Site Configuration
define('SITE_NAME', 'Bank Locker Management System');
define('BANK_NAME', 'SecureBank Ltd.');

// Base URL - determines the subfolder path for the application
// Change this if you move the app to a different folder or to root
define('BASE_URL', '/bank_locker');

// Server Maintenance Mode Checker
function checkMaintenanceMode() {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Allow admin pages, migration page, and the maintenance page itself
    $is_admin = (strpos($script_name, '/admin/') !== false);
    $is_maintenance = (strpos($script_name, 'maintenance.php') !== false);
    $is_migrate = (strpos($script_name, 'migrate.php') !== false);
    
    if ($is_admin || $is_maintenance || $is_migrate) {
        return;
    }
    
    try {
        $temp_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$temp_conn->connect_error) {
            $temp_conn->set_charset("utf8");
            $check = $temp_conn->query("SHOW TABLES LIKE 'settings'");
            if ($check && $check->num_rows > 0) {
                $result = $temp_conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode' LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if ($row['setting_value'] === 'on') {
                        $temp_conn->close();
                        header("Location: " . BASE_URL . "/maintenance.php");
                        exit();
                    }
                }
            }
            $temp_conn->close();
        }
    } catch (Exception $e) {
        // Fail silently and allow access if database is down or table doesn't exist
    }
}
checkMaintenanceMode();

