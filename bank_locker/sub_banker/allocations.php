<?php
$page_title = "Allocations";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = '';

// Surrender locker
if (isset($_GET['surrender'])) {
    $id = intval($_GET['surrender']);
    $row = $conn->query("SELECT locker_id FROM allocations WHERE id=$id AND status='active'")->fetch_assoc();
    if ($row) {
        $conn->begin_transaction();
        $conn->query("UPDATE allocations SET status='surrendered' WHERE id=$id");
        $conn->query("UPDATE lockers SET status='available' WHERE id={$row['locker_id']}");
        $conn->commit();
        $msg = "Locker surrendered and is now available.";
    }
}

$allocations = $conn->query("
    SELECT a.*, c.full_name, c.customer_id AS cid, l.locker_number, l.locker_size
    FROM allocations a
    JOIN customers c ON a.customer_id=c.id
    JOIN lockers l ON a.locker_id=l.id
    ORDER BY a.created_at DESC
");
?>
<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>📋 All Allocations</h3>
    <a href="allocate_locker.php" class="btn btn-success btn-sm">➕ New Allocation</a>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Alloc No.</th><th>Customer</th><th>Locker</th><th>Size</th><th>Date</th><th>Expiry</th><th>Rent</th><th>Payment</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php $i=1; while($a=$allocations->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($a['allocation_no']) ?></strong></td>
          <td><?= htmlspecialchars($a['full_name']) ?><br><small style="color:#888;"><?= $a['cid'] ?></small></td>
          <td><?= htmlspecialchars($a['locker_number']) ?></td>
          <td><?= getLockerSizeLabel($a['locker_size']) ?></td>
          <td><?= $a['allocation_date'] ?></td>
          <td><?= $a['expiry_date'] ?></td>
          <td><?= formatCurrency($a['rent_paid']) ?></td>
          <td><?= getStatusBadge($a['payment_status']) ?></td>
          <td><?= getStatusBadge($a['status']) ?></td>
          <td>
            <?php if($a['status']==='active'): ?>
            <a href="?surrender=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Surrender this locker?')">🔓 Surrender</a>
            <?php else: ?>
            <span style="font-size:12px;color:#888;">Closed</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
