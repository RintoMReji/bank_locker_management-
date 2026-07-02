<?php
$page_title = "Allocate Locker";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_id  = intval($_POST['customer_id']);
    $lock_id  = intval($_POST['locker_id']);
    $alloc_dt = $_POST['allocation_date'];
    $expiry   = $_POST['expiry_date'];
    $rent     = floatval($_POST['rent_paid']);
    $pay_st   = $_POST['payment_status'];
    $remarks  = trim($_POST['remarks'] ?? '');
    $alloc_no = generateAllocationNo($conn);
    $allocated_by = 'Admin: ' . ($_SESSION['admin_name'] ?? 'Admin');

    $chk = $conn->query("SELECT status FROM lockers WHERE id=$lock_id")->fetch_assoc();
    if ($chk['status'] !== 'available') {
        $err = "Selected locker is no longer available.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO allocations (allocation_no,customer_id,locker_id,allocation_date,expiry_date,rent_paid,payment_status,allocated_by,remarks) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("siissdsss", $alloc_no, $cust_id, $lock_id, $alloc_dt, $expiry, $rent, $pay_st, $allocated_by, $remarks);
            $stmt->execute();
            $conn->query("UPDATE lockers SET status='allocated' WHERE id=$lock_id");
            $conn->commit();
            $msg = "Locker allocated successfully! Allocation No: $alloc_no";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Allocated locker - $alloc_no");
        } catch (Exception $e) {
            $conn->rollback();
            $err = "Failed to allocate: " . $e->getMessage();
        }
    }
}

$customers = $conn->query("SELECT id, customer_id, full_name FROM customers WHERE status='active' ORDER BY full_name");
$lockers   = $conn->query("SELECT id, locker_number, locker_size, annual_rent FROM lockers WHERE status='available' ORDER BY locker_number");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>➕ Allocate Locker to Customer</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Select Customer *</label>
          <select name="customer_id" class="form-control" required>
            <option value="">-- Choose Customer --</option>
            <?php while($c=$customers->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= $c['customer_id'] ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Select Available Locker *</label>
          <select name="locker_id" class="form-control" required id="lockerSelect">
            <option value="">-- Choose Locker --</option>
            <?php while($l=$lockers->fetch_assoc()): ?>
            <option value="<?= $l['id'] ?>" data-rent="<?= $l['annual_rent'] ?>">
              <?= htmlspecialchars($l['locker_number']) ?> - <?= getLockerSizeLabel($l['locker_size']) ?> (<?= formatCurrency($l['annual_rent']) ?>/yr)
            </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Allocation Date *</label>
          <input type="date" name="allocation_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Expiry Date *</label>
          <input type="date" name="expiry_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Rent Paid (₹) *</label>
          <input type="number" name="rent_paid" class="form-control" required id="rentField" placeholder="Auto-filled from locker" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Payment Status *</label>
          <select name="payment_status" class="form-control">
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" placeholder="Optional notes..."></textarea>
      </div>
      <button type="submit" class="btn btn-primary">🔑 Allocate Locker</button>
      <a href="allocations.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
    </form>
  </div>
</div>

<script>
document.getElementById('lockerSelect').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var rent = opt.getAttribute('data-rent');
    if (rent) document.getElementById('rentField').value = rent;
});
</script>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
