<?php
$page_title = "System Activity Logs";
require_once '../includes/header_admin.php';
$conn = getDBConnection();

// Filters and Search inputs
$filter_type = $_GET['user_type'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = "";

// Filter by user type
if ($filter_type === 'sub_banker') {
    $where[] = "user_type = 'sub_banker'";
} elseif ($filter_type === 'customer') {
    $where[] = "user_type = 'customer'";
} elseif ($filter_type === 'admin') {
    $where[] = "user_type = 'admin'";
}

// Search keyword
if (!empty($search)) {
    $where[] = "(user_name LIKE ? OR action LIKE ? OR details LIKE ? OR ip_address LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$where_clause = "";
if (count($where) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where);
}

// Fetch 100 most recent logs matching criteria
$sql = "SELECT * FROM activity_log $where_clause ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$log_results = $stmt->get_result();

// Count stats
$sub_banker_count = $conn->query("SELECT COUNT(*) as c FROM activity_log WHERE user_type='sub_banker'")->fetch_assoc()['c'];
$customer_count = $conn->query("SELECT COUNT(*) as c FROM activity_log WHERE user_type='customer'")->fetch_assoc()['c'];
$admin_count = $conn->query("SELECT COUNT(*) as c FROM activity_log WHERE user_type='admin'")->fetch_assoc()['c'];
?>

<style>
    .filter-bar {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: var(--shadow);
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .log-details-box {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11.5px;
        max-width: 320px;
        white-space: pre-wrap;
        word-break: break-all;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 6px 10px;
        border-radius: 6px;
        color: #334155;
    }
    .stats-summary {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
    }
    .summary-item {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 12px 20px;
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--shadow);
    }
    .summary-item .label {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .summary-item .val {
        font-size: 18px;
        font-weight: 800;
        color: var(--primary);
    }
</style>

<!-- Log Count Summary Stats -->
<div class="stats-summary">
    <div class="summary-item">
        <span class="label">Sub-Banker (Sub-Admin) Logs</span>
        <span class="val" style="color: #d97706;"><?= $sub_banker_count ?></span>
    </div>
    <div class="summary-item">
        <span class="label">Customer Logs</span>
        <span class="val" style="color: #16a34a;"><?= $customer_count ?></span>
    </div>
    <div class="summary-item">
        <span class="label">Admin Logs</span>
        <span class="val" style="color: #dc2626;"><?= $admin_count ?></span>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" class="filter-group" style="width: 100%; justify-content: space-between;">
        <div class="filter-group">
            <div>
                <label class="form-label" style="margin-bottom: 4px; font-size: 11px;">Filter User Type</label>
                <select name="user_type" class="form-control" onchange="this.form.submit()" style="padding: 8px 12px; width: 200px;">
                    <option value="">All Users</option>
                    <option value="sub_banker" <?= ($filter_type === 'sub_banker') ? 'selected' : '' ?>>Sub-Bankers (Sub-Admins)</option>
                    <option value="customer" <?= ($filter_type === 'customer') ? 'selected' : '' ?>>Customers</option>
                    <option value="admin" <?= ($filter_type === 'admin') ? 'selected' : '' ?>>Admins</option>
                </select>
            </div>
            
            <div>
                <label class="form-label" style="margin-bottom: 4px; font-size: 11px;">Search Keywords</label>
                <input type="text" name="search" class="form-control" placeholder="Search user, action, details..." value="<?= htmlspecialchars($search) ?>" style="padding: 8px 12px; width: 280px;">
            </div>
        </div>
        
        <div class="filter-group" style="align-self: flex-end;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding: 9px 18px;">🔍 Filter</button>
            <a href="activity_logs.php" class="btn btn-secondary btn-sm" style="padding: 9px 18px;">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Logs Data Table Card -->
<div class="card">
    <div class="card-header">
        <h3>📋 System Activity Logs (Last 100 Records)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Time</th>
                    <th style="width: 120px;">User Type</th>
                    <th style="width: 160px;">User Name (ID)</th>
                    <th style="width: 200px;">Action Performed</th>
                    <th>Action Details</th>
                    <th style="width: 130px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($log_results->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 30px; color: var(--text-muted);">
                            No activity log entries found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($log = $log_results->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= date('Y-m-d', strtotime($log['created_at'])) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <?php if ($log['user_type'] === 'admin'): ?>
                                    <span class="badge badge-danger" style="background: #fee2e2; color: #dc2626;">Admin</span>
                                <?php elseif ($log['user_type'] === 'sub_banker'): ?>
                                    <span class="badge badge-warning" style="background: #fef9c3; color: #d97706;">Sub-Banker</span>
                                <?php elseif ($log['user_type'] === 'customer'): ?>
                                    <span class="badge badge-success" style="background: #dcfce7; color: #16a34a;">Customer</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?= htmlspecialchars($log['user_type']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($log['user_name']) ?></div>
                                <small style="color: var(--text-muted);">ID: <?= $log['user_id'] ?></small>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($log['details'])): ?>
                                    <div class="log-details-box"><?= htmlspecialchars($log['details']) ?></div>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #475569;">
                                    <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                                </code>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
require_once '../includes/footer_admin.php';
?>
