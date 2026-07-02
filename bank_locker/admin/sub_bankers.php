<?php
$page_title = "Manage Sub-Bankers";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $cur = $conn->query("SELECT status FROM sub_banker WHERE id=$id")->fetch_assoc()['status'];
    $new = ($cur === 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE sub_banker SET status='$new' WHERE id=$id");
    $msg = "Sub-banker status updated to $new.";
    logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Toggled sub-banker #$id to $new");
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM sub_banker WHERE id=$id");
    $msg = "Sub-banker deleted.";
    logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Deleted sub-banker #$id");
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM sub_banker";
if ($search) $sql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($search) . "%' OR employee_id LIKE '%" . $conn->real_escape_string($search) . "%' OR email LIKE '%" . $conn->real_escape_string($search) . "%'";
$sql .= " ORDER BY created_at DESC";
$bankers = $conn->query($sql);
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>🏛️ All Sub-Bankers</h3>
    <div class="d-flex gap-10">
      <form method="GET" style="display:flex;gap:8px;">
        <input type="text" name="q" class="form-control" placeholder="Search name/ID/email..." value="<?= htmlspecialchars($search) ?>" style="width:250px;">
        <button type="submit" class="btn btn-primary btn-sm">🔍</button>
      </form>
      <a href="add_sub_banker.php" class="btn btn-success btn-sm">➕ Add Sub-Banker</a>
    </div>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Employee ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Status</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while($sb=$bankers->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($sb['employee_id']) ?></strong></td>
          <td><?= htmlspecialchars($sb['full_name']) ?></td>
          <td><?= htmlspecialchars($sb['username']) ?></td>
          <td><?= htmlspecialchars($sb['email']) ?></td>
          <td><?= htmlspecialchars($sb['phone']) ?></td>
          <td><?= getStatusBadge($sb['status']) ?></td>
          <td><?= date('d M Y', strtotime($sb['created_at'])) ?></td>
          <td>
            <a href="?toggle=<?= $sb['id'] ?>&q=<?= urlencode($search) ?>" class="btn btn-sm <?= $sb['status']==='active'?'btn-warning':'btn-success' ?>">
              <?= $sb['status']==='active'?'🔒 Deactivate':'✅ Activate' ?>
            </a>
            <a href="?delete=<?= $sb['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this sub-banker?')">🗑️</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
