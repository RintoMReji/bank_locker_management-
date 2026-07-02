<?php
$page_title = "Add Sub-Banker";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $name     = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $eid      = generateEmployeeID($conn);
    $hashed   = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO sub_banker (username,password,full_name,employee_id,email,phone) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $username, $hashed, $name, $eid, $email, $phone);
    if ($stmt->execute()) {
        $msg = "Sub-Banker created! Employee ID: $eid | Username: $username";
        logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Created sub-banker: $name ($eid)");
    } else {
        $err = "Error: " . $conn->error;
    }
}
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>🏛️ Register New Sub-Banker</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="Sub-banker full name">
        </div>
        <div class="form-group">
          <label class="form-label">Username *</label>
          <input type="text" name="username" class="form-control" required placeholder="Login username">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="email@bank.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" required placeholder="10-digit number" maxlength="15">
        </div>
        <div class="form-group">
          <label class="form-label">Login Password *</label>
          <input type="password" name="password" class="form-control" required placeholder="Set password" minlength="6">
        </div>
      </div>
      <div class="alert alert-info">ℹ️ Employee ID will be auto-generated upon registration.</div>
      <button type="submit" class="btn btn-primary">🏛️ Register Sub-Banker</button>
      <a href="sub_bankers.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
    </form>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
