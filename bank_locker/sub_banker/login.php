<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();
$base = BASE_URL;
if (isset($_SESSION['subbanker_id'])) { header("Location: dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM sub_banker WHERE username = ? AND status='active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $sb = $result->fetch_assoc();
        if ($sb && password_verify($password, $sb['password'])) {
            $_SESSION['subbanker_id'] = $sb['id'];
            $_SESSION['subbanker_name'] = $sb['full_name'];
            $_SESSION['subbanker_eid'] = $sb['employee_id'];
            logActivity($conn, 'sub_banker', $sb['id'], $sb['full_name'], 'Logged In', 'Sub-banker logged in successfully');
            header("Location: dashboard.php"); exit();
        } else {
            $error = "Invalid username or password, or account is inactive.";
        }
    } else {
        $error = "Please fill all fields.";
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sub Banker Login | Bank Locker Management</title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body>
<div class="login-page login-page-teal">
  <div class="login-box">
    <div class="login-logo">
      <div class="icon">🏛️</div>
      <h1>Sub Banker Login</h1>
      <p>Bank Locker Management System</p>
      <span class="role-tag role-tag-subbanker" style="margin-top:8px;">Sub Banker Portal</span>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter sub banker username" required value="<?= htmlspecialchars($_POST['username']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-teal btn-lg" style="width:100%;justify-content:center;">🏛️ Login as Sub Banker</button>
    </form>
    <div class="text-center mt-15" style="font-size:12px;color:#888;">
      <a href="<?= $base ?>/index.php" style="color:var(--teal);">← Back to Home</a>
    </div>
  </div>
</div>
</body>
</html>
