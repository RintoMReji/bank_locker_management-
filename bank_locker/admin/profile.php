<?php
$page_title = "Update Profile";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';
$aid = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $stmt = $conn->prepare("UPDATE admin SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $aid);
        $stmt->execute();
        $_SESSION['admin_name'] = $name;
        $msg = "Profile updated successfully!";
        logActivity($conn, 'admin', $aid, $name, "Updated profile");
    }
    
    if ($action === 'change_password') {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $admin = $conn->query("SELECT password FROM admin WHERE id=$aid")->fetch_assoc();
        if (!password_verify($old, $admin['password'])) {
            $err = "Current password is incorrect.";
        } elseif ($new !== $confirm) {
            $err = "New passwords do not match.";
        } elseif (strlen($new) < 6) {
            $err = "Password must be at least 6 characters.";
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $conn->query("UPDATE admin SET password='$hashed' WHERE id=$aid");
            $msg = "Password changed successfully!";
            logActivity($conn, 'admin', $aid, $_SESSION['admin_name'], "Changed password");
        }
    }
}

$admin = $conn->query("SELECT * FROM admin WHERE id=$aid")->fetch_assoc();
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-header"><h3>👤 Profile Information</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($admin['full_name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']??'') ?>" placeholder="admin@bank.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($admin['phone']??'') ?>" placeholder="Phone number">
        </div>
        <button type="submit" class="btn btn-primary">💾 Save Profile</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>🔑 Change Password</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label">Current Password *</label>
          <input type="password" name="old_password" class="form-control" required placeholder="Enter current password">
        </div>
        <div class="form-group">
          <label class="form-label">New Password *</label>
          <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters" minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
        </div>
        <button type="submit" class="btn btn-warning">🔑 Change Password</button>
      </form>
    </div>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
