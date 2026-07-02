<?php
$page_title = "Surrender Requests";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $handler = 'Admin: ' . $_SESSION['admin_name'];
    
    if ($action === 'approve') {
        $req = $conn->query("SELECT * FROM delete_requests WHERE id=$req_id AND status='pending'")->fetch_assoc();
        if ($req) {
            $conn->begin_transaction();
            $alloc = $conn->query("SELECT locker_id FROM allocations WHERE id={$req['allocation_id']}")->fetch_assoc();
            $conn->query("UPDATE allocations SET status='surrendered' WHERE id={$req['allocation_id']}");
            $conn->query("UPDATE lockers SET status='available' WHERE id={$alloc['locker_id']}");
            $conn->query("UPDATE delete_requests SET status='approved', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
            $conn->commit();
            $msg = "Surrender request approved. Locker has been surrendered.";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Approved surrender request #$req_id");
        }
    } elseif ($action === 'reject') {
        $conn->query("UPDATE delete_requests SET status='rejected', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
        $msg = "Surrender request rejected.";
        logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Rejected surrender request #$req_id");
    }
}

$requests = $conn->query("
    SELECT dr.*, c.full_name, c.customer_id AS cid, a.allocation_no, l.locker_number, l.locker_size
    FROM delete_requests dr
    JOIN customers c ON dr.customer_id=c.id
    JOIN allocations a ON dr.allocation_id=a.id
    JOIN lockers l ON a.locker_id=l.id
    ORDER BY FIELD(dr.status,'pending','approved','rejected'), dr.created_at DESC
");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>📥 Locker Surrender Requests</h3></div>
  <div class="card-body">
    <?php $found=false; while($r=$requests->fetch_assoc()): $found=true; ?>
    <div class="approval-card">
      <div class="approval-header">
        <div>
          <div class="approval-title"><?= htmlspecialchars($r['full_name']) ?> (<?= $r['cid'] ?>)</div>
          <small style="color:#888;">Requested: <?= timeAgo($r['created_at']) ?></small>
        </div>
        <?= getStatusBadge($r['status']) ?>
      </div>
      <div class="approval-meta">
        <div><div class="meta-label">Allocation</div><div class="meta-value"><?= htmlspecialchars($r['allocation_no']) ?></div></div>
        <div><div class="meta-label">Locker</div><div class="meta-value"><?= htmlspecialchars($r['locker_number']) ?> (<?= getLockerSizeLabel($r['locker_size']) ?>)</div></div>
        <div><div class="meta-label">Reason</div><div class="meta-value"><?= htmlspecialchars($r['reason']) ?></div></div>
        <?php if($r['handled_by']): ?>
        <div><div class="meta-label">Handled By</div><div class="meta-value"><?= htmlspecialchars($r['handled_by']) ?></div></div>
        <?php endif; ?>
        <?php if($r['handled_remarks']): ?>
        <div><div class="meta-label">Remarks</div><div class="meta-value"><?= htmlspecialchars($r['handled_remarks']) ?></div></div>
        <?php endif; ?>
      </div>
      <?php if($r['status'] === 'pending'): ?>
      <form method="POST" class="approval-actions">
        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
        <div class="form-group">
          <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
        </div>
        <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('Approve this surrender request?')">✅ Approve</button>
        <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Reject</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php if(!$found): ?>
    <div class="text-center" style="padding:40px;color:#888;">No surrender requests found.</div>
    <?php endif; ?>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
