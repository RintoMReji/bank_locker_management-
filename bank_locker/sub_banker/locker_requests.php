<?php
$type_title = isset($_GET['type']) ? ($_GET['type'] == 'new' ? 'New Customer' : 'Existing Customer') : '';
$page_title = "Locker Requests" . ($type_title ? " - $type_title" : "");
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $remarks = trim($_POST['remarks'] ?? '');
    $handler = 'Sub Banker: ' . $_SESSION['subbanker_name'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE locker_requests SET status='approved', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
        logActivity($conn, 'sub_banker', $_SESSION['subbanker_id'], $_SESSION['subbanker_name'], 'Approved Locker Request', "Approved locker request #$req_id with remarks: " . ($remarks ?: 'None'));
        $msg = "Locker request approved! You can now allocate a locker to this customer.";
    } elseif ($action === 'reject') {
        $conn->query("UPDATE locker_requests SET status='rejected', handled_by='$handler', handled_remarks='" . $conn->real_escape_string($remarks) . "', handled_at=NOW() WHERE id=$req_id");
        logActivity($conn, 'sub_banker', $_SESSION['subbanker_id'], $_SESSION['subbanker_name'], 'Rejected Locker Request', "Rejected locker request #$req_id with remarks: " . ($remarks ?: 'None'));
        $msg = "Locker request rejected.";
    }
}

$type = $_GET['type'] ?? '';
$type_filter = "";
if ($type === 'new') {
    $type_filter = " AND (SELECT COUNT(*) FROM allocations WHERE customer_id=c.id) = 0 ";
} elseif ($type === 'existing') {
    $type_filter = " AND (SELECT COUNT(*) FROM allocations WHERE customer_id=c.id) > 0 ";
}

$requests = $conn->query("
    SELECT lr.*, c.full_name, c.customer_id AS cid, c.email, c.phone
    FROM locker_requests lr JOIN customers c ON lr.customer_id=c.id
    WHERE 1=1 $type_filter
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
          <small style="color:#888;"><?= timeAgo($r['created_at']) ?></small>
        </div>
        <?= getStatusBadge($r['status']) ?>
      </div>
      <div class="approval-meta">
        <div><div class="meta-label">Size</div><div class="meta-value"><?= getLockerSizeLabel($r['locker_size']) ?></div></div>
        <div><div class="meta-label">Location</div><div class="meta-value"><?= htmlspecialchars($r['preferred_location'] ?: 'Any') ?></div></div>
        <div><div class="meta-label">Reason</div><div class="meta-value"><?= htmlspecialchars($r['reason'] ?: '—') ?></div></div>
        <?php if($r['handled_by']): ?>
        <div><div class="meta-label">Handled By</div><div class="meta-value"><?= htmlspecialchars($r['handled_by']) ?></div></div>
        <?php endif; ?>
      </div>
      <?php if($r['status'] === 'pending'): ?>
      <form method="POST" class="approval-actions">
        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
        <div class="form-group"><input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)"></div>
        <button type="submit" name="action" value="approve" class="btn btn-success">✅ Approve</button>
        <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Reject</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <?php if(!$found): ?><div class="text-center" style="padding:40px;color:#888;">No locker requests.</div><?php endif; ?>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
