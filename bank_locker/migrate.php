<?php
/**
 * Migration V3 - Complete 4-Module System
 * Adds all tables needed for Banker, Sub-Banker, Customer, and Registration modules
 */
require_once __DIR__ . '/includes/config.php';
$conn = getDBConnection();
$results = [];

// 1. Locker Requests table
$sql = "CREATE TABLE IF NOT EXISTS locker_requests (
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
)";
if ($conn->query($sql)) $results[] = "✅ locker_requests table ready";
else $results[] = "❌ locker_requests: " . $conn->error;

// 2. Delete Requests table
$sql = "CREATE TABLE IF NOT EXISTS delete_requests (
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
)";
if ($conn->query($sql)) $results[] = "✅ delete_requests table ready";
else $results[] = "❌ delete_requests: " . $conn->error;

// 3. Contact Messages table
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    reply TEXT DEFAULT NULL,
    replied_by VARCHAR(100) DEFAULT NULL,
    status ENUM('unread','read','replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
)";
if ($conn->query($sql)) $results[] = "✅ contact_messages table ready";
else $results[] = "❌ contact_messages: " . $conn->error;

// 4. Password Resets table
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','sub_banker','customer') NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) $results[] = "✅ password_resets table ready";
else $results[] = "❌ password_resets: " . $conn->error;

// 5. Activity Log table
$sql = "CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) $results[] = "✅ activity_log table ready";
else $results[] = "❌ activity_log: " . $conn->error;

// 6. Add email/phone to admin table if missing
$check = $conn->query("SHOW COLUMNS FROM admin LIKE 'email'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN email VARCHAR(100) DEFAULT '', ADD COLUMN phone VARCHAR(15) DEFAULT ''");
    $results[] = "✅ Added email/phone to admin table";
} else $results[] = "⏭️ admin email/phone already exists";

// 8. Ensure allocated_by column exists
$check = $conn->query("SHOW COLUMNS FROM allocations LIKE 'allocated_by'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE allocations ADD COLUMN allocated_by VARCHAR(100) DEFAULT 'Admin'");
    $results[] = "✅ Added allocated_by to allocations";
} else $results[] = "⏭️ allocated_by already exists";

// 8b. Ensure access_log status column exists
$check = $conn->query("SHOW COLUMNS FROM access_log LIKE 'status'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE access_log ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved'");
    $results[] = "✅ Added status to access_log table";
} else $results[] = "⏭️ access_log status already exists";

// 9. Ensure sub_banker table exists
$sql = "CREATE TABLE IF NOT EXISTS sub_banker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) $results[] = "✅ sub_banker table ready";
else $results[] = "❌ sub_banker: " . $conn->error;

// 10. Default sub banker
$check = $conn->query("SELECT id FROM sub_banker WHERE username='subbanker'");
if ($check->num_rows === 0) {
    $hash = password_hash('subbanker123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO sub_banker (username,password,full_name,employee_id,email,phone) VALUES (?,?,?,?,?,?)");
    $u='subbanker'; $n='Sub Banker Officer'; $e='EMP2024001'; $em='subbanker@securebank.com'; $p='9876543210';
    $stmt->bind_param("ssssss",$u,$hash,$n,$e,$em,$p);
    $stmt->execute();
    $results[] = "✅ Default sub banker created";
} else $results[] = "⏭️ Default sub banker exists";

// 11. Joint Locker Holders / Nominees table
$sql = "CREATE TABLE IF NOT EXISTS joint_locker_holders (
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
)";
if ($conn->query($sql)) $results[] = "✅ joint_locker_holders table ready";
else $results[] = "❌ joint_locker_holders: " . $conn->error;

// 12. Add password column to joint_locker_holders if missing
$check = $conn->query("SHOW COLUMNS FROM joint_locker_holders LIKE 'password'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE joint_locker_holders ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER aadhar_no");
    $results[] = "✅ Added password column to joint_locker_holders";
} else $results[] = "⏭️ joint_locker_holders.password already exists";

// 13. Add login_email column to joint_locker_holders if missing
$check = $conn->query("SHOW COLUMNS FROM joint_locker_holders LIKE 'login_email'");
if ($check && $check->num_rows === 0) {
    $conn->query("ALTER TABLE joint_locker_holders ADD COLUMN login_email VARCHAR(100) DEFAULT NULL AFTER password");
    $results[] = "✅ Added login_email column to joint_locker_holders";
} else $results[] = "⏭️ joint_locker_holders.login_email already exists";

// 14. Create Settings table for server maintenance mode
$sql = "CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
)";
if ($conn->query($sql)) {
    $results[] = "✅ settings table ready";
    
    // Default maintenance mode
    $check = $conn->query("SELECT setting_key FROM settings WHERE setting_key='maintenance_mode'");
    if ($check && $check->num_rows === 0) {
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', 'off')");
        $results[] = "✅ Default maintenance mode setting initialized ('off')";
    } else {
        $results[] = "⏭️ maintenance_mode setting already exists";
    }
    
    // Default maintenance message
    $check = $conn->query("SELECT setting_key FROM settings WHERE setting_key='maintenance_message'");
    if ($check && $check->num_rows === 0) {
        $msg_default = "We are currently performing scheduled system upgrades. Please check back soon.";
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_message', '" . $conn->real_escape_string($msg_default) . "')");
        $results[] = "✅ Default maintenance message initialized";
    } else {
        $results[] = "⏭️ maintenance_message setting already exists";
    }
} else {
    $results[] = "❌ settings table creation failed: " . $conn->error;
}

$conn->close();
echo "<html><head><title>Migration V3</title><link rel='stylesheet' href='/bank_locker/css/style.css'></head>";
echo "<body style='padding:40px;background:#f1f5f9;'><div style='max-width:600px;margin:0 auto;'>";
echo "<div class='card'><div class='card-header'><h3>🔧 Migration V3 - Complete 4-Module System</h3></div><div class='card-body'>";
foreach ($results as $r) echo "<div style='padding:8px 0;border-bottom:1px solid #eee;font-size:14px;'>$r</div>";
echo "<div style='margin-top:20px;'><a href='/bank_locker/index.php' class='btn btn-primary'>🏠 Go to Homepage</a></div>";
echo "</div></div></div></body></html>";
