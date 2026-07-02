<?php
$page_title = "Contact Bank";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = $_SESSION['customer_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO contact_messages (customer_id, subject, message) VALUES (?,?,?)");
    $stmt->bind_param("iss", $cid, $subject, $message);
    $stmt->execute();
    $msg = "Message sent successfully! The bank will reply soon.";
}

$messages = $conn->query("SELECT * FROM contact_messages WHERE customer_id=$cid ORDER BY created_at DESC");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>📞 Send Message to Bank</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Subject *</label>
        <input type="text" name="subject" class="form-control" required placeholder="e.g. Query about locker renewal">
      </div>
      <div class="form-group">
        <label class="form-label">Message *</label>
        <textarea name="message" class="form-control" required placeholder="Describe your query or concern in detail..." rows="4"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">📨 Send Message</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>💬 My Messages</h3></div>
  <div class="card-body">
    <?php $found=false; while($m=$messages->fetch_assoc()): $found=true; ?>
    <div class="message-card">
      <div class="msg-header">
        <div class="msg-subject"><?= htmlspecialchars($m['subject']) ?></div>
        <div>
          <?= getStatusBadge($m['status']) ?>
          <span class="msg-date"><?= timeAgo($m['created_at']) ?></span>
        </div>
      </div>
      <div class="msg-body"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
      <?php if($m['reply']): ?>
      <div class="msg-reply">
        <div class="reply-label">Bank Reply (<?= htmlspecialchars($m['replied_by']??'Bank') ?>)</div>
        <?= nl2br(htmlspecialchars($m['reply'])) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php if(!$found): ?>
    <div class="text-center" style="padding:30px;color:#888;">No messages yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
