<?php
require_once __DIR__ . '/config.php';

// Session check for admin
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: " . BASE_URL . "/admin/login.php");
        exit();
    }
}

// Session check for customer
function requireCustomerLogin() {
    if (!isset($_SESSION['customer_id'])) {
        header("Location: " . BASE_URL . "/customer/login.php");
        exit();
    }
}

// Session check for sub banker
function requireSubBankerLogin() {
    if (!isset($_SESSION['subbanker_id'])) {
        header("Location: " . BASE_URL . "/sub_banker/login.php");
        exit();
    }
}

// Generate unique customer ID
function generateCustomerID($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as cnt FROM customers");
    $row = $result->fetch_assoc();
    $num = str_pad($row['cnt'] + 1, 4, '0', STR_PAD_LEFT);
    return "CUST{$year}{$num}";
}

// Generate unique allocation number
function generateAllocationNo($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as cnt FROM allocations");
    $row = $result->fetch_assoc();
    $num = str_pad($row['cnt'] + 1, 4, '0', STR_PAD_LEFT);
    return "ALLOC{$year}{$num}";
}

// Generate unique employee ID
function generateEmployeeID($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as cnt FROM sub_banker");
    $row = $result->fetch_assoc();
    $num = str_pad($row['cnt'] + 1, 3, '0', STR_PAD_LEFT);
    return "EMP{$year}{$num}";
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Get locker size label
function getLockerSizeLabel($size) {
    $labels = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
    return $labels[$size] ?? ucfirst($size);
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'available'  => '<span class="badge badge-success">Available</span>',
        'allocated'  => '<span class="badge badge-danger">Allocated</span>',
        'maintenance'=> '<span class="badge badge-warning">Maintenance</span>',
        'active'     => '<span class="badge badge-success">Active</span>',
        'inactive'   => '<span class="badge badge-secondary">Inactive</span>',
        'paid'       => '<span class="badge badge-success">Paid</span>',
        'pending'    => '<span class="badge badge-warning">Pending</span>',
        'overdue'    => '<span class="badge badge-danger">Overdue</span>',
        'surrendered'=> '<span class="badge badge-secondary">Surrendered</span>',
        'approved'   => '<span class="badge badge-success">Approved</span>',
        'rejected'   => '<span class="badge badge-danger">Rejected</span>',
        'unread'     => '<span class="badge badge-warning">Unread</span>',
        'read'       => '<span class="badge badge-secondary">Read</span>',
        'replied'    => '<span class="badge badge-success">Replied</span>',
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}


// Dashboard stats
function getDashboardStats($conn) {
    $stats = [];
    $stats['total_lockers']     = $conn->query("SELECT COUNT(*) as c FROM lockers")->fetch_assoc()['c'];
    $stats['available_lockers'] = $conn->query("SELECT COUNT(*) as c FROM lockers WHERE status='available'")->fetch_assoc()['c'];
    $stats['allocated_lockers'] = $conn->query("SELECT COUNT(*) as c FROM lockers WHERE status='allocated'")->fetch_assoc()['c'];
    $stats['total_customers']   = $conn->query("SELECT COUNT(*) as c FROM customers")->fetch_assoc()['c'];
    $stats['active_allocations']= $conn->query("SELECT COUNT(*) as c FROM allocations WHERE status='active'")->fetch_assoc()['c'];
    $stats['total_revenue']     = $conn->query("SELECT COALESCE(SUM(rent_paid),0) as c FROM allocations WHERE payment_status='paid'")->fetch_assoc()['c'];
    $stats['total_sub_bankers'] = $conn->query("SELECT COUNT(*) as c FROM sub_banker")->fetch_assoc()['c'];
    
    // Safe queries for tables that may not exist yet
    $check = $conn->query("SHOW TABLES LIKE 'delete_requests'");
    $stats['pending_deletes']   = ($check->num_rows > 0) ? $conn->query("SELECT COUNT(*) as c FROM delete_requests WHERE status='pending'")->fetch_assoc()['c'] : 0;
    
    $check = $conn->query("SHOW TABLES LIKE 'locker_requests'");
    $stats['pending_locker_requests'] = ($check->num_rows > 0) ? $conn->query("SELECT COUNT(*) as c FROM locker_requests WHERE status='pending'")->fetch_assoc()['c'] : 0;
    
    $check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    $stats['unread_messages']   = ($check->num_rows > 0) ? $conn->query("SELECT COUNT(*) as c FROM contact_messages WHERE status='unread'")->fetch_assoc()['c'] : 0;
    
    $check = $conn->query("SHOW COLUMNS FROM access_log LIKE 'status'");
    $stats['pending_slots'] = ($check->num_rows > 0) ? $conn->query("SELECT COUNT(*) as c FROM access_log WHERE status='pending'")->fetch_assoc()['c'] : 0;
    
    return $stats;
}

// Log user activity
function logActivity($conn, $user_type, $user_id, $user_name, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_type, user_id, user_name, action, details, ip_address) VALUES (?,?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("sissss", $user_type, $user_id, $user_name, $action, $details, $ip);
        $stmt->execute();
    }
}

// Time ago helper
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y>1?'s':'') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m>1?'s':'') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d>1?'s':'') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h>1?'s':'') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i>1?'s':'') . ' ago';
    return 'Just now';
}

// Get customer unread notifications count
function getUnreadNotificationsCount($conn, $customer_id) {
    $cid = intval($customer_id);
    $q = "SELECT COUNT(*) as cnt 
          FROM notifications n 
          WHERE (n.customer_id = $cid OR n.customer_id IS NULL) 
            AND n.id NOT IN (SELECT notification_id FROM notification_reads WHERE customer_id = $cid)";
    $res = $conn->query($q);
    if ($res) {
        $row = $res->fetch_assoc();
        return intval($row['cnt']);
    }
    return 0;
}
?>
