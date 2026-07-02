<?php
require_once __DIR__ . '/includes/config.php';
$conn = getDBConnection();
$results = [];

// 1. Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
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
)";
if ($conn->query($sql)) {
    $results[] = "✅ notifications table ready";
} else {
    $results[] = "❌ notifications table creation failed: " . $conn->error;
}

// 2. Create notification_reads table
$sql = "CREATE TABLE IF NOT EXISTS notification_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    notification_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (customer_id, notification_id)
)";
if ($conn->query($sql)) {
    $results[] = "✅ notification_reads table ready";
} else {
    $results[] = "❌ notification_reads table creation failed: " . $conn->error;
}

$conn->close();

echo "Migration finished:\n";
foreach ($results as $res) {
    echo $res . "\n";
}
?>
