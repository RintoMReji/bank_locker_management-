<?php
$page_title = "My Dashboard";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = intval($_SESSION['customer_id']);

$allocs = $conn->query("SELECT a.*, l.locker_number, l.locker_size, l.location FROM allocations a JOIN lockers l ON a.locker_id=l.id WHERE a.customer_id=$cid ORDER BY a.created_at DESC");
$rows = []; while ($r = $allocs->fetch_assoc()) $rows[] = $r;
$active_count = count(array_filter($rows, fn($r) => $r['status']==='active'));
$last_access = $conn->query("SELECT al.access_date, al.access_time, l.locker_number FROM access_log al JOIN lockers l ON al.locker_id=l.id WHERE al.customer_id=$cid ORDER BY al.created_at DESC LIMIT 1")->fetch_assoc();

// Pending requests counts
$pending_locker_req = $conn->query("SELECT COUNT(*) as c FROM locker_requests WHERE customer_id=$cid AND status='pending'")->fetch_assoc()['c'];
$pending_delete_req = $conn->query("SELECT COUNT(*) as c FROM delete_requests WHERE customer_id=$cid AND status='pending'")->fetch_assoc()['c'];

// Active joint locker members/nominees count
$joint_count = $conn->query("SELECT COUNT(*) as c FROM joint_locker_holders WHERE customer_id=$cid AND status='active'")->fetch_assoc()['c'];

// Fetch 3 most recent notifications for this customer (broadcast or targeted) with read status
$recent_notifs = $conn->query("
    SELECT n.*, (nr.id IS NOT NULL) AS is_read
    FROM notifications n
    LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.customer_id = $cid
    WHERE n.customer_id = $cid OR n.customer_id IS NULL
    ORDER BY n.created_at DESC
    LIMIT 3
");
?>

<div class="quick-actions">
  <a href="my_locker.php" class="quick-action-btn"><span class="qa-icon">🔒</span><span class="qa-label">My Locker</span></a>
  <a href="access_locker.php" class="quick-action-btn"><span class="qa-icon">🔐</span><span class="qa-label">Slot Booking</span></a>
  <a href="joint_locker.php" class="quick-action-btn"><span class="qa-icon">👥</span><span class="qa-label">Joint Facility</span></a>
  <a href="request_locker.php" class="quick-action-btn"><span class="qa-icon">📩</span><span class="qa-label">Request Locker</span></a>
  <a href="contact.php" class="quick-action-btn"><span class="qa-icon">📞</span><span class="qa-label">Contact Bank</span></a>
  <a href="profile.php" class="quick-action-btn"><span class="qa-icon">👤</span><span class="qa-label">My Profile</span></a>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon blue">🔒</div><div class="stat-info"><div class="stat-value"><?= $active_count ?></div><div class="stat-label">Active Locker(s)</div></div></div>
  <div class="stat-card"><div class="stat-icon purple">📋</div><div class="stat-info"><div class="stat-value"><?= count($rows) ?></div><div class="stat-label">Total Allocations</div></div></div>
  <div class="stat-card"><div class="stat-icon teal">👥</div><div class="stat-info"><div class="stat-value"><?= $joint_count ?></div><div class="stat-label">Joint/Nominees</div></div></div>
  <div class="stat-card"><div class="stat-icon green">📅</div><div class="stat-info"><div class="stat-value"><?= $last_access ? $last_access['access_date'] : '—' ?></div><div class="stat-label">Last Access</div></div></div>
  <div class="stat-card"><div class="stat-icon orange">📩</div><div class="stat-info"><div class="stat-value"><?= $pending_locker_req ?></div><div class="stat-label">Pending Requests</div></div></div>
</div>

<div class="card mb-20" style="border-left: 4px solid var(--accent);">
  <div class="card-header">
    <h3>📢 Recent Bank Alerts</h3>
    <a href="notifications.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="card-body" style="padding: 15px 24px;">
    <?php if ($recent_notifs && $recent_notifs->num_rows > 0): ?>
      <?php while ($n = $recent_notifs->fetch_assoc()): 
        $is_read = (bool)$n['is_read'];
      ?>
        <div style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; position: relative;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
            <div style="font-weight: 700; font-size: 14px; color: var(--primary);">
              <?= htmlspecialchars($n['title']) ?>
              <?php if (!$is_read): ?>
                <span class="badge badge-danger" style="padding: 2px 6px; font-size: 9px; vertical-align: middle; margin-left: 5px;">NEW</span>
              <?php endif; ?>
            </div>
            <small style="color: #888; white-space: nowrap;"><?= timeAgo($n['created_at']) ?></small>
          </div>
          <div style="color: #555; margin-top: 6px; font-size: 13px; line-height: 1.4;">
            <?= nl2br(htmlspecialchars(substr($n['message'], 0, 150))) ?><?= strlen($n['message']) > 150 ? '...' : '' ?>
          </div>
          <small style="color: #999; display: block; margin-top: 5px;">Sent by: <?= htmlspecialchars($n['sender_name']) ?> (<?= ucfirst(str_replace('_', ' ', $n['sender_type'])) ?>)</small>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="text-center" style="padding: 20px 0; color: #888;">No bank notifications at this time.</div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>🔑 My Locker Allocations</h3></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>Alloc No.</th><th>Locker</th><th>Size</th><th>Location</th><th>From</th><th>Expiry</th><th>Rent</th><th>Payment</th><th>Status</th></tr></thead>
      <tbody>
        <?php if(empty($rows)): ?>
        <tr><td colspan="9" class="text-center" style="padding:30px;color:#888;">No allocations. <a href="request_locker.php">Request a locker</a>.</td></tr>
        <?php else: foreach($rows as $a): ?>
        <tr>
          <td><strong><?= htmlspecialchars($a['allocation_no']) ?></strong></td>
          <td><?= htmlspecialchars($a['locker_number']) ?></td>
          <td><?= getLockerSizeLabel($a['locker_size']) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($a['location']) ?></td>
          <td><?= $a['allocation_date'] ?></td>
          <td><?= $a['expiry_date'] ?></td>
          <td><?= formatCurrency($a['rent_paid']) ?></td>
          <td><?= getStatusBadge($a['payment_status']) ?></td>
          <td><?= getStatusBadge($a['status']) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
