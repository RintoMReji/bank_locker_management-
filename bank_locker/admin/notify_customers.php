<?php
$page_title = "Send Notification";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'send') {
        $recipient_type = $_POST['recipient_type']; // 'all' or specific customer_id
        $title = sanitize($_POST['title']);
        $message = sanitize($_POST['message']);
        
        $selected_channels = $_POST['channels'] ?? [];
        if (empty($selected_channels)) {
            $selected_channels[] = 'system';
        }
        
        $channels_str = implode(',', $selected_channels);
        $customer_id = ($recipient_type === 'all') ? null : intval($recipient_type);
        
        // Mocking Text/SMS and Email status
        $sms_status = in_array('sms', $selected_channels) ? 'Delivered' : null;
        $email_status = in_array('email', $selected_channels) ? 'Sent' : null;
        
        $sender_type = 'admin';
        $sender_id = $_SESSION['admin_id'];
        $sender_name = $_SESSION['admin_name'];
        
        $stmt = $conn->prepare("INSERT INTO notifications (sender_type, sender_id, sender_name, customer_id, title, message, channels, sms_status, email_status) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("siissssss", $sender_type, $sender_id, $sender_name, $customer_id, $title, $message, $channels_str, $sms_status, $email_status);
        
        if ($stmt->execute()) {
            $notif_id = $conn->insert_id;
            
            // Build the channels detail message
            $sent_channels = [];
            if (in_array('system', $selected_channels)) $sent_channels[] = "System Portal";
            if (in_array('sms', $selected_channels)) $sent_channels[] = "SMS (Text Message)";
            if (in_array('email', $selected_channels)) $sent_channels[] = "Email Address";
            
            $msg = "Notification sent successfully via: " . implode(', ', $sent_channels);
            
            // Log user activity
            $target = ($recipient_type === 'all') ? 'All Customers' : "Customer ID: $recipient_type";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Sent notification (#$notif_id) to $target", "Channels: $channels_str");
        } else {
            $err = "Failed to send notification: " . $conn->error;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $notif_id = intval($_POST['notif_id']);
        
        // Verify notification was sent by an admin
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id=? AND sender_type='admin'");
        $stmt->bind_param("i", $notif_id);
        if ($stmt->execute()) {
            $msg = "Notification deleted successfully.";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Deleted notification #$notif_id");
        } else {
            $err = "Failed to delete notification: " . $conn->error;
        }
    }
}

// Fetch all customers for targeted sending
$customers = $conn->query("SELECT id, customer_id, full_name FROM customers WHERE status='active' ORDER BY full_name");

// Fetch sent notifications history
$history = $conn->query("
    SELECT n.*, c.full_name AS customer_name, c.customer_id AS cid 
    FROM notifications n 
    LEFT JOIN customers c ON n.customer_id=c.id 
    ORDER BY n.created_at DESC
");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header">
    <h3>📢 Compose and Send Customer Notification</h3>
  </div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="send">
      
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Recipient Group *</label>
          <select name="recipient_type" class="form-control" required>
            <option value="all">📢 Broadcast (All Active Customers)</option>
            <?php while($c = $customers->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>">🔒 Specific: <?= htmlspecialchars($c['full_name']) ?> (<?= $c['customer_id'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Notification Title / Subject *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Locker Rent Renewal Notice">
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Notification Message *</label>
        <textarea name="message" class="form-control" required placeholder="Type your message here..." style="min-height: 120px;"></textarea>
      </div>
      
      <div class="form-group">
        <label class="form-label">Dispatch Channels (Select all that apply)</label>
        <div style="display: flex; gap: 24px; flex-wrap: wrap; padding: 10px 0;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="channels[]" value="system" checked style="transform: scale(1.1);">
            💻 System Web Portal Alert (Mandatory)
          </label>
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="channels[]" value="sms" style="transform: scale(1.1);">
            📱 Text Message / SMS Alert (Mocked)
          </label>
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="channels[]" value="email" style="transform: scale(1.1);">
            📧 Email Dispatch (Mocked)
          </label>
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary">📢 Dispatch Notification</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h3>📋 Sent Notification History</h3>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Sender</th>
          <th>Recipient</th>
          <th>Title & Message</th>
          <th>Channels Used</th>
          <th>Mock Dispatch Reports</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($history && $history->num_rows > 0): ?>
          <?php while ($h = $history->fetch_assoc()): ?>
            <tr>
              <td style="white-space: nowrap;">
                <strong><?= date('Y-m-d', strtotime($h['created_at'])) ?></strong><br/>
                <small style="color:#888;"><?= date('h:i A', strtotime($h['created_at'])) ?></small>
              </td>
              <td>
                <strong><?= htmlspecialchars($h['sender_name']) ?></strong><br/>
                <small style="color:#888;"><?= ucfirst($h['sender_type']) ?></small>
              </td>
              <td>
                <?php if (is_null($h['customer_id'])): ?>
                  <span class="badge badge-secondary">📢 All Customers</span>
                <?php else: ?>
                  <span class="badge badge-warning">🔒 targeted</span><br/>
                  <small><strong><?= htmlspecialchars($h['customer_name']) ?></strong> (<?= htmlspecialchars($h['cid']) ?>)</small>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;"><?= htmlspecialchars($h['title']) ?></div>
                <div style="font-size: 12px; color: #555; max-width: 300px; max-height: 80px; overflow-y: auto; white-space: pre-line;"><?= htmlspecialchars($h['message']) ?></div>
              </td>
              <td>
                <?php 
                  $ch = explode(',', $h['channels']);
                  foreach ($ch as $c) {
                    $c_label = strtoupper($c);
                    $c_badge = 'badge-secondary';
                    if ($c === 'sms') $c_badge = 'badge-success';
                    if ($c === 'email') $c_badge = 'badge-warning';
                    echo "<span class='badge $c_badge' style='margin: 2px 0;'>$c_label</span><br/>";
                  }
                ?>
              </td>
              <td style="font-size: 11px; line-height: 1.4;">
                💻 Web Portal: <span class="badge badge-success">Published</span><br/>
                <?php if ($h['sms_status']): ?>
                  📱 SMS/Text: <span class="badge badge-success"><?= htmlspecialchars($h['sms_status']) ?></span><br/>
                <?php endif; ?>
                <?php if ($h['email_status']): ?>
                  📧 Email: <span class="badge badge-success"><?= htmlspecialchars($h['email_status']) ?></span><br/>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this notification? It will be removed from customer feeds.');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="notif_id" value="<?= $h['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center" style="padding: 30px; color: #888;">No notifications have been sent yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$conn->close();
require_once '../includes/footer_admin.php';
?>
