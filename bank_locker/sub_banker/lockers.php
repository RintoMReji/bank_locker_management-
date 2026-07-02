<?php
$page_title = "Manage Lockers";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();

$msg = ''; $err = '';
// Add locker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $num = trim($_POST['locker_number']);
    $size = $_POST['locker_size'];
    $rent = floatval($_POST['annual_rent']);
    $loc  = trim($_POST['location']);
    $stmt = $conn->prepare("INSERT INTO lockers (locker_number, locker_size, annual_rent, location) VALUES (?,?,?,?)");
    $stmt->bind_param("ssds", $num, $size, $rent, $loc);
    if ($stmt->execute()) $msg = "Locker added successfully!";
    else $err = "Error: " . $conn->error;
}
// Update status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']); $st = $_GET['status'];
    if (in_array($st, ['available','maintenance'])) {
        $chk = $conn->query("SELECT status FROM lockers WHERE id=$id")->fetch_assoc();
        if ($chk['status'] !== 'allocated') {
            $conn->query("UPDATE lockers SET status='$st' WHERE id=$id");
            $msg = "Locker status updated.";
        } else { $err = "Cannot change status of an allocated locker."; }
    }
}

$lockers = $conn->query("SELECT * FROM lockers ORDER BY locker_number");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>➕ Add New Locker</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">Locker Number</label>
          <input type="text" name="locker_number" class="form-control" placeholder="e.g. L013" required>
        </div>
        <div class="form-group">
          <label class="form-label">Locker Size</label>
          <select name="locker_size" class="form-control" required>
            <option value="small">Small</option>
            <option value="medium">Medium</option>
            <option value="large">Large</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Annual Rent (₹)</label>
          <input type="number" name="annual_rent" class="form-control" placeholder="e.g. 1500" required min="1" step="0.01">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Location / Description</label>
        <input type="text" name="location" class="form-control" placeholder="e.g. Main Branch Vault - Row A" required>
      </div>
      <button type="submit" class="btn btn-teal">➕ Add Locker</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>🔒 All Lockers</h3></div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Locker No.</th><th>Size</th><th>Annual Rent</th><th>Location</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while($l=$lockers->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($l['locker_number']) ?></strong></td>
          <td><?= getLockerSizeLabel($l['locker_size']) ?></td>
          <td><?= formatCurrency($l['annual_rent']) ?></td>
          <td><?= htmlspecialchars($l['location']) ?></td>
          <td><?= getStatusBadge($l['status']) ?></td>
          <td>
            <?php if($l['status'] !== 'allocated'): ?>
            <?php if($l['status'] !== 'available'): ?>
            <a href="?status=available&id=<?= $l['id'] ?>" class="btn btn-success btn-sm">✅ Set Available</a>
            <?php endif; ?>
            <?php if($l['status'] !== 'maintenance'): ?>
            <a href="?status=maintenance&id=<?= $l['id'] ?>" class="btn btn-warning btn-sm">🔧 Maintenance</a>
            <?php endif; ?>
            <?php else: ?>
            <span style="font-size:12px;color:#888;">Allocated</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
