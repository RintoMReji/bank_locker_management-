<?php
$page_title = "Surrender Locker";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = $_SESSION['customer_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alloc_id = intval($_POST['allocation_id']);
    $reason = trim($_POST['reason']);
    
    // Verify
    $alloc = $conn->query("SELECT * FROM allocations WHERE id=$alloc_id AND customer_id=$cid AND status='active'")->fetch_assoc();
    if (!$alloc) { $err = "Invalid allocation."; }
    else {
        // Check no duplicate pending request
        $exists = $conn->query("SELECT id FROM delete_requests WHERE allocation_id=$alloc_id AND status='pending'")->num_rows;
        if ($exists) { $err = "A delete request is already pending for this locker."; }
        else {
            $stmt = $conn->prepare("INSERT INTO delete_requests (customer_id, allocation_id, reason) VALUES (?,?,?)");
            $stmt->bind_param("iis", $cid, $alloc_id, $reason);
            $stmt->execute();
            $msg = "Surrender request submitted successfully! The bank will review your request.";
        }
    }
}

// Active allocations
$allocs = $conn->query("
    SELECT a.id, a.allocation_no, l.locker_number, l.locker_size
    FROM allocations a JOIN lockers l ON a.locker_id=l.id
    WHERE a.customer_id=$cid AND a.status='active'
");
$active = []; while($r=$allocs->fetch_assoc()) $active[] = $r;

// Existing requests
$requests = $conn->query("
    SELECT dr.*, a.allocation_no, l.locker_number
    FROM delete_requests dr JOIN allocations a ON dr.allocation_id=a.id JOIN lockers l ON a.locker_id=l.id
    WHERE dr.customer_id=$cid ORDER BY dr.created_at DESC
");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if(!empty($active)): ?>
<div class="card mb-20">
  <div class="card-header"><h3>📥 Surrender Locker Request</h3></div>
  <div class="card-body">
    <div class="alert alert-info">ℹ️ Submit a request to surrender your locker. The bank will review and process it.</div>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Select Locker *</label>
          <select name="allocation_id" class="form-control" required>
            <option value="">-- Choose --</option>
            <?php foreach($active as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['locker_number']) ?> — <?= $a['allocation_no'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Reason *</label>
          <input type="text" name="reason" class="form-control" required placeholder="Why do you want to surrender this locker?">
        </div>
      </div>
      <button type="submit" class="btn btn-danger" onclick="return confirm('Submit surrender request?')">📥 Submit Request</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3>📋 My Surrender Requests</h3></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Locker</th><th>Allocation</th><th>Reason</th><th>Status</th><th>Date</th><th>Remarks</th></tr></thead>
      <tbody>
        <?php $i=1; $found=false; while($r=$requests->fetch_assoc()): $found=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($r['locker_number']) ?></td>
          <td><?= htmlspecialchars($r['allocation_no']) ?></td>
          <td><?= htmlspecialchars($r['reason']) ?></td>
          <td><?= getStatusBadge($r['status']) ?></td>
          <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td><?= htmlspecialchars($r['handled_remarks'] ?? '—') ?></td>
        </tr>
        <?php endwhile; if(!$found): ?>
        <tr><td colspan="7" class="text-center" style="padding:30px;color:#888;">No surrender requests.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
