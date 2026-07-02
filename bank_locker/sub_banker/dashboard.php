<?php
$page_title = "Dashboard";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$stats = getDashboardStats($conn);

$recent = $conn->query("SELECT a.*, c.full_name, c.customer_id AS cid, l.locker_number FROM allocations a JOIN customers c ON a.customer_id=c.id JOIN lockers l ON a.locker_id=l.id ORDER BY a.created_at DESC LIMIT 5");
?>

<div class="quick-actions">
  <a href="lockers.php" class="quick-action-btn"><span class="qa-icon">🔒</span><span class="qa-label">Manage Lockers</span></a>
  <a href="allocate_locker.php" class="quick-action-btn"><span class="qa-icon">➕</span><span class="qa-label">Assign Locker</span></a>
  <a href="customers.php" class="quick-action-btn"><span class="qa-icon">👥</span><span class="qa-label">Customers</span></a>
  <a href="locker_requests.php" class="quick-action-btn"><span class="qa-icon">📩</span><span class="qa-label">Locker Requests</span></a>
  <a href="approve_slots.php" class="quick-action-btn"><span class="qa-icon">⏰</span><span class="qa-label">Approve Slots</span></a>
  <a href="reports.php" class="quick-action-btn"><span class="qa-icon">📈</span><span class="qa-label">Reports</span></a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon blue">🔒</div><div class="stat-info"><div class="stat-value"><?= $stats['total_lockers'] ?></div><div class="stat-label">Total Lockers</div></div></div>
  <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><div class="stat-value"><?= $stats['available_lockers'] ?></div><div class="stat-label">Available</div></div></div>
  <div class="stat-card"><div class="stat-icon red">🔑</div><div class="stat-info"><div class="stat-value"><?= $stats['allocated_lockers'] ?></div><div class="stat-label">Allocated</div></div></div>
  <div class="stat-card"><div class="stat-icon orange">👥</div><div class="stat-info"><div class="stat-value"><?= $stats['total_customers'] ?></div><div class="stat-label">Customers</div></div></div>
  <div class="stat-card"><div class="stat-icon purple">📩</div><div class="stat-info"><div class="stat-value"><?= $stats['pending_locker_requests'] ?></div><div class="stat-label">Pending Requests</div></div></div>
  <div class="stat-card"><div class="stat-icon teal">⏰</div><div class="stat-info"><div class="stat-value"><?= $stats['pending_slots'] ?? 0 ?></div><div class="stat-label">Pending Slots</div></div></div>
</div>

<div class="card">
  <div class="card-header"><h3>📋 Recent Allocations</h3><a href="allocations.php" class="btn btn-outline btn-sm">View All</a></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Alloc No.</th><th>Customer</th><th>Locker</th><th>Date</th><th>Expiry</th><th>Status</th></tr></thead>
      <tbody>
        <?php while($row=$recent->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['allocation_no']) ?></td>
          <td><?= htmlspecialchars($row['full_name']) ?> <small style="color:#888;">(<?= $row['cid'] ?>)</small></td>
          <td><?= htmlspecialchars($row['locker_number']) ?></td>
          <td><?= $row['allocation_date'] ?></td>
          <td><?= $row['expiry_date'] ?></td>
          <td><?= getStatusBadge($row['status']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
