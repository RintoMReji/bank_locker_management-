<?php
$page_title = "Notifications";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = intval($_SESSION['customer_id']);

// Automatically mark all notifications as read for this customer when they view this page
$all_notifs_res = $conn->query("SELECT id FROM notifications WHERE customer_id=$cid OR customer_id IS NULL");
if ($all_notifs_res) {
    while ($n = $all_notifs_res->fetch_assoc()) {
        $nid = $n['id'];
        $conn->query("INSERT IGNORE INTO notification_reads (customer_id, notification_id) VALUES ($cid, $nid)");
    }
}

// Fetch all notifications (broadcast or specific to this customer)
$notifs = $conn->query("
    SELECT * FROM notifications 
    WHERE customer_id=$cid OR customer_id IS NULL 
    ORDER BY created_at DESC
");
?>

<style>
.notif-card {
  background: var(--white);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  padding: 20px;
  margin-bottom: 15px;
  box-shadow: var(--shadow);
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}
.notif-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: var(--accent);
}
.notif-card.general::before {
  background: var(--primary);
}
.notif-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}
.notif-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--primary);
}
.notif-meta {
  font-size: 11.5px;
  color: var(--text-muted);
}
.notif-body {
  font-size: 13.5px;
  color: var(--text);
  line-height: 1.5;
  white-space: pre-line;
}
.notif-footer {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px dashed var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  color: var(--text-muted);
}
.notif-channel-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
}
</style>

<div class="card">
  <div class="card-header">
    <h3>🔔 Bank Notifications & Alert History</h3>
  </div>
  <div class="card-body">
    <?php if ($notifs && $notifs->num_rows > 0): ?>
      <?php while ($n = $notifs->fetch_assoc()): 
        $is_broadcast = is_null($n['customer_id']);
      ?>
        <div class="notif-card <?= $is_broadcast ? 'general' : 'targeted' ?>">
          <div class="notif-header">
            <div>
              <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
              <div class="notif-meta">
                <span>By: <strong><?= htmlspecialchars($n['sender_name']) ?></strong> (<?= ucfirst(str_replace('_', ' ', $n['sender_type'])) ?>)</span>
              </div>
            </div>
            <span class="badge <?= $is_broadcast ? 'badge-secondary' : 'badge-warning' ?>">
              <?= $is_broadcast ? '📢 Broadcast' : '🔒 Direct Message' ?>
            </span>
          </div>
          <div class="notif-body">
            <?= htmlspecialchars($n['message']) ?>
          </div>
          <div class="notif-footer">
            <div>
              <span>Sent via: </span>
              <?php 
                $chans = explode(',', $n['channels']);
                foreach ($chans as $chan) {
                  $icon = '💻';
                  if ($chan === 'sms') $icon = '📱';
                  if ($chan === 'email') $icon = '📧';
                  echo '<span class="notif-channel-badge">' . $icon . ' ' . strtoupper($chan) . '</span> ';
                }
              ?>
            </div>
            <div>
              📅 <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?> (<?= timeAgo($n['created_at']) ?>)
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="text-center" style="padding: 50px 0; color: #888;">
        <div style="font-size: 48px; margin-bottom: 10px;">🔔</div>
        <h3>No Notifications Found</h3>
        <p>You will see bank broadcast alerts and direct messages here.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
$conn->close();
require_once '../includes/footer_customer.php';
?>
