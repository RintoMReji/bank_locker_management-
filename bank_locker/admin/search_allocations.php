<?php
$page_title = "Search Allocations";
require_once '../includes/header_admin.php';
$conn = getDBConnection();

$search = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "SELECT a.*, c.full_name, c.customer_id AS cid, l.locker_number, l.locker_size
        FROM allocations a
        JOIN customers c ON a.customer_id=c.id
        JOIN lockers l ON a.locker_id=l.id WHERE 1=1";
$params = []; $types = '';

if ($search) {
    $sql .= " AND (c.full_name LIKE ? OR c.customer_id LIKE ? OR l.locker_number LIKE ? OR a.allocation_no LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s]);
    $types .= 'ssss';
}
if ($status_filter) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter; $types .= 's';
}
if ($date_from) {
    $sql .= " AND a.allocation_date >= ?";
    $params[] = $date_from; $types .= 's';
}
if ($date_to) {
    $sql .= " AND a.allocation_date <= ?";
    $params[] = $date_to; $types .= 's';
}
$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allocations = $stmt->get_result();
?>

<div class="card mb-20">
  <div class="card-header"><h3>🔍 Search Locker Allocations</h3></div>
  <div class="card-body">
    <form method="GET">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Search (Name / Customer ID / Locker / Alloc No.)</label>
          <input type="text" name="q" class="form-control" placeholder="Type to search..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Status Filter</label>
          <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="active" <?= $status_filter==='active'?'selected':'' ?>>Active</option>
            <option value="surrendered" <?= $status_filter==='surrendered'?'selected':'' ?>>Surrendered</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date From</label>
          <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Date To</label>
          <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">🔍 Search</button>
      <a href="search_allocations.php" class="btn btn-secondary" style="margin-left:10px;">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>📋 Results (<?= $allocations->num_rows ?> found)</h3></div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Alloc No.</th><th>Customer</th><th>Locker</th><th>Size</th><th>Date</th><th>Expiry</th><th>Rent</th><th>Payment</th><th>Status</th></tr>
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
        </tr>
        <?php endwhile; ?>
        <?php if($allocations->num_rows === 0): ?>
        <tr><td colspan="10" class="text-center" style="padding:30px;color:#888;">No allocations found matching your criteria.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
