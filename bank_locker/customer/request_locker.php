<?php
$page_title = "Request New Locker";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = $_SESSION['customer_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $size = $_POST['locker_size'];
    $location = trim($_POST['preferred_location']);
    $reason = trim($_POST['reason']);
    
    // Check no pending request
    $exists = $conn->query("SELECT id FROM locker_requests WHERE customer_id=$cid AND status='pending'")->num_rows;
    if ($exists) {
        $err = "You already have a pending locker request. Please wait for it to be processed.";
    } else {
        $stmt = $conn->prepare("INSERT INTO locker_requests (customer_id, locker_size, preferred_location, reason) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $cid, $size, $location, $reason);
        $stmt->execute();
        $msg = "Locker request submitted! The bank will review and assign a locker to you.";
    }
}

$requests = $conn->query("SELECT * FROM locker_requests WHERE customer_id=$cid ORDER BY created_at DESC");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>📩 Request a New Locker</h3></div>
  <div class="card-body">
    <div class="alert alert-info">ℹ️ Fill out this form to request a bank locker. Our team will review and allocate a suitable locker.</div>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Preferred Locker Size *</label>
          <select name="locker_size" class="form-control" required>
            <option value="small">Small (₹1,500/year)</option>
            <option value="medium">Medium (₹2,500/year)</option>
            <option value="large">Large (₹4,000/year)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Location</label>
          <input type="text" name="preferred_location" class="form-control" placeholder="e.g. Main Branch, Near entrance">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Purpose</label>
        <textarea name="reason" class="form-control" placeholder="Why do you need a locker? (optional)" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">📩 Submit Request</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>📋 My Locker Requests</h3></div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Size</th><th>Location</th><th>Status</th><th>Date</th><th>Handled By</th><th>Remarks</th></tr></thead>
      <tbody>
        <?php $i=1; $found=false; while($r=$requests->fetch_assoc()): $found=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= getLockerSizeLabel($r['locker_size']) ?></td>
          <td><?= htmlspecialchars($r['preferred_location'] ?: '—') ?></td>
          <td><?= getStatusBadge($r['status']) ?></td>
          <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td><?= htmlspecialchars($r['handled_by'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['handled_remarks'] ?? '—') ?></td>
        </tr>
        <?php endwhile; if(!$found): ?>
        <tr><td colspan="7" class="text-center" style="padding:30px;color:#888;">No requests yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
