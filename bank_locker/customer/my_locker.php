<?php
$page_title = "My Locker";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = $_SESSION['customer_id'];

$alloc = $conn->query("
    SELECT a.*, l.locker_number, l.locker_size, l.location, l.annual_rent, l.status as locker_status
    FROM allocations a JOIN lockers l ON a.locker_id=l.id
    WHERE a.customer_id=$cid AND a.status='active' LIMIT 1
")->fetch_assoc();
?>

<?php if(!$alloc): ?>
<div class="card">
  <div class="card-body text-center" style="padding:50px;">
    <div style="font-size:60px;margin-bottom:20px;">🔒</div>
    <h2 style="color:var(--primary);margin-bottom:10px;">No Active Locker</h2>
    <p style="color:#888;">You currently do not have an allocated locker. Please contact the bank to request one.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-header"><h3>🔒 Locker Details</h3></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div>
        <div style="background:#f8fafc;border-radius:12px;padding:25px;text-align:center;border:2px solid #e2e8f0;">
          <div style="font-size:60px;margin-bottom:10px;">🔐</div>
          <div style="font-size:28px;font-weight:700;color:var(--primary);"><?= htmlspecialchars($alloc['locker_number']) ?></div>
          <div style="color:#888;margin-top:5px;"><?= getLockerSizeLabel($alloc['locker_size']) ?> Locker</div>
          <div style="margin-top:10px;"><?= getStatusBadge($alloc['status']) ?></div>
        </div>
      </div>
      <div>
        <table style="width:100%;font-size:14px;">
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Allocation No.</td><td><?= htmlspecialchars($alloc['allocation_no']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Location</td><td><?= htmlspecialchars($alloc['location']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Allocated On</td><td><?= $alloc['allocation_date'] ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Expires On</td><td><?= $alloc['expiry_date'] ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Annual Rent</td><td><?= formatCurrency($alloc['annual_rent']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Rent Paid</td><td><?= formatCurrency($alloc['rent_paid']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Payment Status</td><td><?= getStatusBadge($alloc['payment_status']) ?></td></tr>
          <?php if($alloc['remarks']): ?>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Remarks</td><td><?= htmlspecialchars($alloc['remarks']) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
    <div class="alert alert-info mt-20">
      ℹ️ To access your locker, please visit the bank branch with a valid ID proof. Access is logged and monitored for security.
    </div>
  </div>
</div>
<?php endif; ?>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
