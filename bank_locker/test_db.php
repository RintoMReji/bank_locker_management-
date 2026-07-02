<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';
require_once 'includes/functions.php';

$conn = getDBConnection();
echo "=== DB CONNECTION ===\n";
echo $conn->connect_error ? "FAIL: ".$conn->connect_error."\n" : "OK\n";

echo "\n=== TABLE CHECK ===\n";
$tables = ['customers','lockers','allocations','access_log','notifications','notification_reads',
           'locker_requests','delete_requests','contact_messages','joint_locker_holders',
           'sub_banker','admin','settings','activity_log'];
foreach ($tables as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    echo "$t: " . ($r && $r->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
}

echo "\n=== NOTIFICATIONS TABLE COLUMNS ===\n";
$r = $conn->query("DESCRIBE notifications");
if ($r) { while ($row = $r->fetch_assoc()) echo $row['Field']." (".$row['Type'].")\n"; }
else echo "ERROR: ".$conn->error."\n";

echo "\n=== NOTIFICATION_READS TABLE COLUMNS ===\n";
$r = $conn->query("DESCRIBE notification_reads");
if ($r) { while ($row = $r->fetch_assoc()) echo $row['Field']." (".$row['Type'].")\n"; }
else echo "ERROR: ".$conn->error."\n";

echo "\n=== SAMPLE NOTIFICATIONS QUERY TEST ===\n";
$cid = 1;
$res = $conn->query("SELECT n.*, (nr.id IS NOT NULL) AS is_read FROM notifications n LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.customer_id = $cid WHERE n.customer_id = $cid OR n.customer_id IS NULL ORDER BY n.created_at DESC LIMIT 3");
if ($res) echo "Query OK - Rows: ".$res->num_rows."\n";
else echo "Query FAILED: ".$conn->error."\n";

echo "\n=== UNREAD COUNT HELPER TEST ===\n";
$cnt = getUnreadNotificationsCount($conn, 1);
echo "Unread count for cid=1: $cnt\n";

$conn->close();
echo "\nDone.\n";
?>
