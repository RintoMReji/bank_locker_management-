<?php
$page_title = "Dashboard";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$stats = getDashboardStats($conn);

$recent = $conn->query("SELECT a.*, c.full_name, c.customer_id AS cid, l.locker_number FROM allocations a JOIN customers c ON a.customer_id=c.id JOIN lockers l ON a.locker_id=l.id ORDER BY a.created_at DESC LIMIT 5");
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">🔒</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['total_lockers'] ?></div><div class="stat-label">Total Lockers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">✅</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['available_lockers'] ?></div><div class="stat-label">Available</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">🔑</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['allocated_lockers'] ?></div><div class="stat-label">Allocated</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange">👥</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['total_customers'] ?></div><div class="stat-label">Customers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal">🏛️</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['total_sub_bankers'] ?></div><div class="stat-label">Sub-Bankers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple">💰</div>
    <div class="stat-info"><div class="stat-value"><?= formatCurrency($stats['total_revenue']) ?></div><div class="stat-label">Total Revenue</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange">📩</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['pending_locker_requests'] ?></div><div class="stat-label">Pending Locker Requests</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">🗑️</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['pending_deletes'] ?></div><div class="stat-label">Pending Delete Requests</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal">⏰</div>
    <div class="stat-info"><div class="stat-value"><?= $stats['pending_slots'] ?? 0 ?></div><div class="stat-label">Pending Slots</div></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>📋 Recent Allocations</h3>
    <a href="allocations.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>Alloc No.</th><th>Customer</th><th>Locker</th><th>Date</th><th>Expiry</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php while($row = $recent->fetch_assoc()): ?>
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

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
