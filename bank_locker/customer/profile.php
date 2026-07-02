<?php
$page_title = "My Profile";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = $_SESSION['customer_id'];
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $newpass = $_POST['new_password'];
    
    $safe_phone = mysqli_real_escape_string($conn, $phone);
    $safe_address = mysqli_real_escape_string($conn, $address);
    
    if ($newpass) {
        $oldpass = $_POST['old_password'];
        $chk = $conn->query("SELECT password FROM customers WHERE id=$cid")->fetch_assoc();
        if (!password_verify($oldpass, $chk['password'])) {
            $err = "Current password is incorrect.";
        } else {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $conn->query("UPDATE customers SET phone='$safe_phone', address='$safe_address', password='$hashed' WHERE id=$cid");
            $msg = "Profile and password updated successfully.";
        }
    } else {
        $conn->query("UPDATE customers SET phone='$safe_phone', address='$safe_address' WHERE id=$cid");
        $msg = "Profile updated successfully.";
    }
}

$cust = $conn->query("SELECT * FROM customers WHERE id=$cid")->fetch_assoc();
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>👤 My Profile</h3></div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;">
      <div>
        <h4 style="margin-bottom:15px;color:var(--primary);">Account Information</h4>
        <table style="width:100%;font-size:14px;">
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Customer ID</td><td><?= htmlspecialchars($cust['customer_id']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Full Name</td><td><?= htmlspecialchars($cust['full_name']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Email</td><td><?= htmlspecialchars($cust['email']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Aadhar No.</td><td><?= htmlspecialchars($cust['aadhar_no']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Account No.</td><td><?= htmlspecialchars($cust['account_no']) ?></td></tr>
          <tr><td style="padding:8px 0;color:#888;font-weight:600;">Member Since</td><td><?= date('d M Y', strtotime($cust['created_at'])) ?></td></tr>
        </table>
      </div>
      <div>
        <h4 style="margin-bottom:15px;color:var(--primary);">Update Profile</h4>
        <form method="POST">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($cust['phone']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($cust['address']) ?></textarea>
          </div>
          <hr style="margin:15px 0;border-color:#e2e8f0;">
          <p style="font-size:12px;color:#888;margin-bottom:10px;">Leave password fields blank to keep current password</p>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="old_password" class="form-control" placeholder="Required to change password">
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="New password (optional)">
          </div>
          <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
