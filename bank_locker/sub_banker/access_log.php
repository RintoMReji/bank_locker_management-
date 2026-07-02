<?php
$page_title = "Access Log";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_id  = intval($_POST['customer_id']);
    $lock_id  = intval($_POST['locker_id']);
    $acc_date = $_POST['access_date'];
    $acc_time = $_POST['access_time'];
    $purpose  = trim($_POST['purpose']);
    $approved = trim($_POST['approved_by']);
    $stmt = $conn->prepare("INSERT INTO access_log (customer_id,locker_id,access_date,access_time,purpose,approved_by) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iissss", $cust_id, $lock_id, $acc_date, $acc_time, $purpose, $approved);
    if ($stmt->execute()) $msg = "Access logged!";
    else $err = "Error: " . $conn->error;
}

$active_allocs = $conn->query("
    SELECT a.customer_id, a.locker_id, c.full_name, c.customer_id AS cid, l.locker_number
    FROM allocations a JOIN customers c ON a.customer_id=c.id JOIN lockers l ON a.locker_id=l.id
    WHERE a.status='active'
");
$log = $conn->query("
    SELECT al.*, c.full_name, c.customer_id AS cid, l.locker_number
    FROM access_log al JOIN customers c ON al.customer_id=c.id JOIN lockers l ON al.locker_id=l.id
    ORDER BY al.created_at DESC LIMIT 50
");
?>
<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>➕ Log Locker Access</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer & Locker *</label>
          <select name="customer_id" id="allocSelect" class="form-control" required>
            <option value="">-- Select Active Allocation --</option>
            <?php while($r=$active_allocs->fetch_assoc()): ?>
            <option value="<?= $r['customer_id'] ?>" data-lid="<?= $r['locker_id'] ?>">
              <?= htmlspecialchars($r['full_name']) ?> (<?= $r['cid'] ?>) - Locker <?= $r['locker_number'] ?>
            </option>
            <?php endwhile; ?>
          </select>
          <input type="hidden" name="locker_id" id="lockerHidden">
        </div>
        <div class="form-group">
          <label class="form-label">Access Date *</label>
          <input type="date" name="access_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Access Time *</label>
          <input type="time" name="access_time" class="form-control" required value="<?= date('H:i') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Approved By *</label>
          <input type="text" name="approved_by" class="form-control" required value="<?= htmlspecialchars($_SESSION['subbanker_name'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Purpose of Visit</label>
        <input type="text" name="purpose" class="form-control" placeholder="e.g. Deposit jewelry">
      </div>
      <button type="submit" class="btn btn-teal">📝 Log Access</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>📋 Recent Access Log (Last 50)</h3></div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Customer</th><th>Locker</th><th>Date</th><th>Time</th><th>Purpose</th><th>Approved By</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while($l=$log->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($l['full_name']) ?> <small>(<?= $l['cid'] ?>)</small></td>
          <td><?= htmlspecialchars($l['locker_number']) ?></td>
          <td><?= $l['access_date'] ?></td>
          <td><?= $l['access_time'] ?></td>
          <td><?= htmlspecialchars($l['purpose'] ?? '—') ?></td>
          <td><?= htmlspecialchars($l['approved_by'] ?? '—') ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.getElementById('allocSelect').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('lockerHidden').value = opt.getAttribute('data-lid') || '';
});
</script>
<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
