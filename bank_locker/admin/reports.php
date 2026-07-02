<?php
$page_title = "Generate Reports";
require_once '../includes/header_admin.php';
$conn = getDBConnection();

$report_type = $_GET['type'] ?? 'overview';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
?>

<div class="card mb-20 no-print">
  <div class="card-header"><h3>📈 Generate Reports</h3></div>
  <div class="card-body">
    <form method="GET" class="d-flex gap-10 align-center" style="flex-wrap:wrap;">
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Report Type</label>
        <select name="type" class="form-control">
          <option value="overview" <?= $report_type==='overview'?'selected':'' ?>>📊 Overview Summary</option>
          <option value="revenue" <?= $report_type==='revenue'?'selected':'' ?>>💰 Revenue Report</option>
          <option value="lockers" <?= $report_type==='lockers'?'selected':'' ?>>🔒 Locker Occupancy</option>
          <option value="customers" <?= $report_type==='customers'?'selected':'' ?>>👥 Customer Report</option>
          <option value="access" <?= $report_type==='access'?'selected':'' ?>>📝 Access Log Report</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
      </div>
      <div class="form-group" style="margin-bottom:0;padding-top:22px;">
        <button type="submit" class="btn btn-primary">📈 Generate</button>
        <button type="button" class="btn btn-secondary" onclick="window.print()" style="margin-left:8px;">🖨️ Print</button>
      </div>
    </form>
  </div>
</div>

<?php if($report_type === 'overview'): ?>
<?php $stats = getDashboardStats($conn); ?>
<div class="report-section">
  <h3>📊 System Overview — <?= date('d M Y', strtotime($date_from)) ?> to <?= date('d M Y', strtotime($date_to)) ?></h3>
  <div class="report-summary">
    <div class="report-summary-item"><div class="rsi-value"><?= $stats['total_lockers'] ?></div><div class="rsi-label">Total Lockers</div></div>
    <div class="report-summary-item"><div class="rsi-value"><?= $stats['available_lockers'] ?></div><div class="rsi-label">Available</div></div>
    <div class="report-summary-item"><div class="rsi-value"><?= $stats['allocated_lockers'] ?></div><div class="rsi-label">Allocated</div></div>
    <div class="report-summary-item"><div class="rsi-value"><?= $stats['total_customers'] ?></div><div class="rsi-label">Total Customers</div></div>
    <div class="report-summary-item"><div class="rsi-value"><?= $stats['total_sub_bankers'] ?></div><div class="rsi-label">Sub-Bankers</div></div>
    <div class="report-summary-item"><div class="rsi-value"><?= formatCurrency($stats['total_revenue']) ?></div><div class="rsi-label">Total Revenue</div></div>
  </div>
</div>

<?php elseif($report_type === 'revenue'): ?>
<?php
$rev = $conn->query("SELECT SUM(rent_paid) as total, COUNT(*) as cnt, payment_status FROM allocations WHERE allocation_date BETWEEN '$date_from' AND '$date_to' GROUP BY payment_status");
$total_rev = $conn->query("SELECT COALESCE(SUM(rent_paid),0) as t FROM allocations WHERE allocation_date BETWEEN '$date_from' AND '$date_to' AND payment_status='paid'")->fetch_assoc()['t'];
?>
<div class="report-section">
  <h3>💰 Revenue Report — <?= date('d M Y', strtotime($date_from)) ?> to <?= date('d M Y', strtotime($date_to)) ?></h3>
  <div class="report-summary">
    <div class="report-summary-item"><div class="rsi-value"><?= formatCurrency($total_rev) ?></div><div class="rsi-label">Total Revenue (Paid)</div></div>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table>
        <thead><tr><th>Payment Status</th><th>Count</th><th>Total Amount</th></tr></thead>
        <tbody>
          <?php while($r=$rev->fetch_assoc()): ?>
          <tr><td><?= getStatusBadge($r['payment_status']) ?></td><td><?= $r['cnt'] ?></td><td><?= formatCurrency($r['total']) ?></td></tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($report_type === 'lockers'): ?>
<?php
$by_size = $conn->query("SELECT locker_size, status, COUNT(*) as cnt FROM lockers GROUP BY locker_size, status ORDER BY locker_size, status");
$sizes = [];
while($r=$by_size->fetch_assoc()) { $sizes[$r['locker_size']][$r['status']] = $r['cnt']; }
?>
<div class="report-section">
  <h3>🔒 Locker Occupancy Report</h3>
  <div class="card">
    <div class="table-responsive">
      <table>
        <thead><tr><th>Size</th><th>Available</th><th>Allocated</th><th>Maintenance</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach($sizes as $size => $data): ?>
          <?php $avail = $data['available']??0; $alloc = $data['allocated']??0; $maint = $data['maintenance']??0; ?>
          <tr>
            <td><strong><?= getLockerSizeLabel($size) ?></strong></td>
            <td><span class="badge badge-success"><?= $avail ?></span></td>
            <td><span class="badge badge-danger"><?= $alloc ?></span></td>
            <td><span class="badge badge-warning"><?= $maint ?></span></td>
            <td><strong><?= $avail+$alloc+$maint ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($report_type === 'customers'): ?>
<?php $custs = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM allocations a WHERE a.customer_id=c.id AND a.status='active') as active_lockers FROM customers c ORDER BY c.created_at DESC"); ?>
<div class="report-section">
  <h3>👥 Customer Report</h3>
  <div class="card">
    <div class="table-responsive">
      <table>
        <thead><tr><th>#</th><th>Customer ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Active Lockers</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
          <?php $i=1; while($c=$custs->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($c['customer_id']) ?></strong></td>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['phone']) ?></td>
            <td class="text-center"><?= $c['active_lockers'] ?></td>
            <td><?= getStatusBadge($c['status']) ?></td>
            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($report_type === 'access'): ?>
<?php $logs = $conn->query("SELECT al.*, c.full_name, c.customer_id AS cid, l.locker_number FROM access_log al JOIN customers c ON al.customer_id=c.id JOIN lockers l ON al.locker_id=l.id WHERE al.access_date BETWEEN '$date_from' AND '$date_to' ORDER BY al.access_date DESC, al.access_time DESC"); ?>
<div class="report-section">
  <h3>📝 Access Log Report — <?= date('d M Y', strtotime($date_from)) ?> to <?= date('d M Y', strtotime($date_to)) ?></h3>
  <div class="card">
    <div class="table-responsive">
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Locker</th><th>Date</th><th>Time</th><th>Purpose</th><th>Approved By</th></tr></thead>
        <tbody>
          <?php $i=1; while($l=$logs->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($l['full_name']) ?> <small>(<?= $l['cid'] ?>)</small></td>
            <td><?= htmlspecialchars($l['locker_number']) ?></td>
            <td><?= $l['access_date'] ?></td>
            <td><?= $l['access_time'] ?></td>
            <td><?= htmlspecialchars($l['purpose']??'—') ?></td>
            <td><?= htmlspecialchars($l['approved_by']??'—') ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;color:#888;font-size:12px;">
  Report generated on <?= date('d M Y, h:i A') ?> | SecureBank Ltd. — Bank Locker Management System
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
