<?php
$page_title = "Update Profile";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = ''; $err = '';
$sid = $_SESSION['subbanker_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name  = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $stmt = $conn->prepare("UPDATE sub_banker SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $phone, $sid);
        $stmt->execute();
        $_SESSION['subbanker_name'] = $name;
        $msg = "Profile updated!";
    }
    if ($action === 'change_password') {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $sb = $conn->query("SELECT password FROM sub_banker WHERE id=$sid")->fetch_assoc();
        if (!password_verify($old, $sb['password'])) { $err = "Current password is incorrect."; }
        elseif ($new !== $confirm) { $err = "Passwords do not match."; }
        elseif (strlen($new) < 6) { $err = "Password must be at least 6 characters."; }
        else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $conn->query("UPDATE sub_banker SET password='$hashed' WHERE id=$sid");
            $msg = "Password changed!";
        }
    }
}

$sb = $conn->query("SELECT * FROM sub_banker WHERE id=$sid")->fetch_assoc();
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-header"><h3>👤 Profile Information</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group"><label class="form-label">Employee ID</label><input type="text" class="form-control" value="<?= htmlspecialchars($sb['employee_id']) ?>" disabled></div>
        <div class="form-group"><label class="form-label">Username</label><input type="text" class="form-control" value="<?= htmlspecialchars($sb['username']) ?>" disabled></div>
        <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($sb['full_name']) ?>"></div>
        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($sb['email']) ?>"></div>
        <div class="form-group"><label class="form-label">Phone *</label><input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($sb['phone']) ?>"></div>
        <button type="submit" class="btn btn-teal">💾 Save</button>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>🔑 Change Password</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group"><label class="form-label">Current Password *</label><input type="password" name="old_password" class="form-control" required></div>
        <div class="form-group"><label class="form-label">New Password *</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
        <div class="form-group"><label class="form-label">Confirm *</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button type="submit" class="btn btn-warning">🔑 Change Password</button>
      </form>
    </div>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
