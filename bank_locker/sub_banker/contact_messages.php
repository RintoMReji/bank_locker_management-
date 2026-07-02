<?php
$page_title = "Contact Messages";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_id = intval($_POST['msg_id']);
    $reply = trim($_POST['reply'] ?? '');
    $handler = 'Sub Banker: ' . $_SESSION['subbanker_name'];
    
    if (!empty($reply)) {
        $stmt = $conn->prepare("UPDATE contact_messages SET reply=?, replied_by=?, status='replied' WHERE id=?");
        $stmt->bind_param("ssi", $reply, $handler, $msg_id);
        $stmt->execute();
        $msg = "Reply sent successfully.";
    }
}

$messages = $conn->query("
    SELECT cm.*, c.full_name, c.customer_id AS cid, c.email, c.phone
    FROM contact_messages cm
    JOIN customers c ON cm.customer_id=c.id
    ORDER BY FIELD(cm.status,'unread','read','replied'), cm.created_at DESC
");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>💬 Customer Messages</h3></div>
  <div class="card-body">
    <?php $found=false; while($m=$messages->fetch_assoc()): $found=true; ?>
    <div class="approval-card">
      <div class="approval-header">
        <div>
          <div class="approval-title"><?= htmlspecialchars($m['subject']) ?></div>
          <small style="color:#888;">From: <?= htmlspecialchars($m['full_name']) ?> (<?= $m['cid'] ?>) | <?= timeAgo($m['created_at']) ?></small>
        </div>
        <?= getStatusBadge($m['status']) ?>
      </div>
      <div class="approval-meta" style="grid-template-columns: 1fr; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
        <div style="font-size: 14px; color: #334155; line-height: 1.6;"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
      </div>
      
      <?php if($m['status'] !== 'replied'): ?>
      <form method="POST" class="approval-actions" style="flex-direction: column; gap: 10px; align-items: stretch;">
        <input type="hidden" name="msg_id" value="<?= $m['id'] ?>">
        <div class="form-group" style="margin-bottom:0;">
          <textarea name="reply" class="form-control" placeholder="Write your reply here..." rows="3" required></textarea>
        </div>
        <div style="text-align:right;">
            <button type="submit" class="btn btn-primary">📨 Send Reply</button>
        </div>
      </form>
      <?php else: ?>
      <div class="msg-reply" style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; border-radius: 4px;">
        <div class="reply-label" style="font-weight: 600; font-size: 12px; color: #10b981; text-transform: uppercase; margin-bottom: 5px;">Bank Reply (<?= htmlspecialchars($m['replied_by']) ?>)</div>
        <div style="font-size: 14px; color: #065f46; line-height: 1.6;"><?= nl2br(htmlspecialchars($m['reply'])) ?></div>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php if(!$found): ?>
    <div class="text-center" style="padding:40px;color:#888;">No contact messages found.</div>
    <?php endif; ?>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
