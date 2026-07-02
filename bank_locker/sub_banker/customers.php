<?php
$page_title = "Customers";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();

$msg = ''; 
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $cur = $conn->query("SELECT status FROM customers WHERE id=$id")->fetch_assoc()['status'];
    $new = ($cur === 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE customers SET status='$new' WHERE id=$id");
    $msg = "Customer status updated.";
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT c.*, (SELECT COUNT(*) FROM allocations a WHERE a.customer_id=c.id AND a.status='active') as active_lockers FROM customers c";
if ($search) $sql .= " WHERE c.full_name LIKE '%$search%' OR c.customer_id LIKE '%$search%' OR c.email LIKE '%$search%'";
$sql .= " ORDER BY c.created_at DESC";
$customers = $conn->query($sql);
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>👥 All Customers</h3>
    <div class="d-flex gap-10">
      <form method="GET" style="display:flex;gap:8px;">
        <input type="text" name="q" class="form-control" placeholder="Search name/ID/email..." value="<?= htmlspecialchars($search) ?>" style="width:250px;">
        <button type="submit" class="btn btn-teal btn-sm">🔍</button>
      </form>
      <a href="add_customer.php" class="btn btn-success btn-sm">➕ Add Customer</a>
    </div>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Customer ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Account No.</th><th>Active Lockers</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while($c=$customers->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($c['customer_id']) ?></strong></td>
          <td><?= htmlspecialchars($c['full_name']) ?></td>
          <td><?= htmlspecialchars($c['phone']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= htmlspecialchars($c['account_no']) ?></td>
          <td class="text-center"><?= $c['active_lockers'] ?></td>
          <td><?= getStatusBadge($c['status']) ?></td>
          <td>
            <div style="display:flex;gap:5px;">
              <a href="view_customer.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-teal" style="padding: 5px 10px;">
                🔍 View
              </a>
              <a href="?toggle=<?= $c['id'] ?>&q=<?= urlencode($search) ?>" class="btn btn-sm <?= $c['status']==='active'?'btn-warning':'btn-success' ?>" style="padding: 5px 10px;">
                <?= $c['status']==='active'?'🔒 Deactivate':'✅ Activate' ?>
              </a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
