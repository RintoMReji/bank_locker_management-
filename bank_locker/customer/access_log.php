<?php
$page_title = "Access History";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = intval($_SESSION['customer_id']);

$log = $conn->query("
    SELECT al.*, l.locker_number
    FROM access_log al JOIN lockers l ON al.locker_id=l.id
    WHERE al.customer_id=$cid ORDER BY al.created_at DESC
");
?>

<div class="card">
  <div class="card-header"><h3>📝 My Locker Access History</h3></div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Locker</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Approved By</th></tr>
      </thead>
      <tbody>
        <?php $i=1; $found=false; while($l=$log->fetch_assoc()): $found=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($l['locker_number']) ?></td>
          <td><?= $l['access_date'] ?></td>
          <td><?= date('h:i A', strtotime($l['access_time'])) ?></td>
          <td><?= htmlspecialchars($l['purpose'] ?? '—') ?></td>
          <td><?= getStatusBadge($l['status']) ?></td>
          <td><?= htmlspecialchars($l['approved_by'] ?? '—') ?></td>
        </tr>
        <?php endwhile; if(!$found): ?>
        <tr><td colspan="6" class="text-center" style="padding:30px;color:#888;">No access history found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
