<?php
$page_title = "Locker Requests";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $handler = 'Admin: ' . $_SESSION['admin_name'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE locker_requests SET status='approved', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
        $msg = "Locker request approved. You can now allocate a locker to this customer.";
        logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Approved locker request #$req_id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE locker_requests SET status='rejected', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
        $msg = "Locker request rejected.";
        logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Rejected locker request #$req_id");
    }
}

$requests = $conn->query("
    SELECT lr.*, c.full_name, c.customer_id AS cid, c.email, c.phone
    FROM locker_requests lr
    JOIN customers c ON lr.customer_id=c.id
    ORDER BY FIELD(lr.status,'pending','approved','rejected'), lr.created_at DESC
");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>📩 Customer Locker Requests</h3></div>
  <div class="card-body">
    <?php $found=false; while($r=$requests->fetch_assoc()): $found=true; ?>
    <div class="approval-card">
      <div class="approval-header">
        <div>
          <div class="approval-title"><?= htmlspecialchars($r['full_name']) ?> (<?= $r['cid'] ?>)</div>
          <small style="color:#888;"><?= htmlspecialchars($r['email']) ?> | <?= htmlspecialchars($r['phone']) ?> | <?= timeAgo($r['created_at']) ?></small>
        </div>
        <?= getStatusBadge($r['status']) ?>
      </div>
      <div class="approval-meta">
        <div><div class="meta-label">Preferred Size</div><div class="meta-value"><?= getLockerSizeLabel($r['locker_size']) ?></div></div>
        <div><div class="meta-label">Preferred Location</div><div class="meta-value"><?= htmlspecialchars($r['preferred_location'] ?: 'Any') ?></div></div>
        <div><div class="meta-label">Reason</div><div class="meta-value"><?= htmlspecialchars($r['reason'] ?: '—') ?></div></div>
        <?php if($r['handled_by']): ?>
        <div><div class="meta-label">Handled By</div><div class="meta-value"><?= htmlspecialchars($r['handled_by']) ?></div></div>
        <?php endif; ?>
      </div>
      <?php if($r['status'] === 'pending'): ?>
      <form method="POST" class="approval-actions">
        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
        <div class="form-group">
          <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
        </div>
        <button type="submit" name="action" value="approve" class="btn btn-success">✅ Approve</button>
        <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Reject</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php if(!$found): ?>
    <div class="text-center" style="padding:40px;color:#888;">No locker requests found.</div>
    <?php endif; ?>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
