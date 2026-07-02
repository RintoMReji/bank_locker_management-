<?php
require_once '../includes/config.php';
session_start();
$base = BASE_URL;
if (isset($_SESSION['admin_id'])) { header("Location: dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            // Log activity
            $check = $conn->query("SHOW TABLES LIKE 'activity_log'");
            if ($check->num_rows > 0) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $stmt2 = $conn->prepare("INSERT INTO activity_log (user_type,user_id,user_name,action,ip_address) VALUES ('admin',?,?,'Login',?)");
                $stmt2->bind_param("iss", $admin['id'], $admin['full_name'], $ip);
                $stmt2->execute();
            }
            header("Location: dashboard.php"); exit();
        } else {
            $error = "Invalid username or password.";
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
<title>Admin Login | Bank Locker Management</title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="icon">🏦</div>
      <h1>Banker Login</h1>
      <p>Bank Locker Management System</p>
    </div>
    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter admin username" required value="<?= htmlspecialchars($_POST['username']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">🔐 Login</button>
    </form>
    <div class="text-center mt-15" style="font-size:12px;color:#888;">
      <a href="forgot_password.php" style="color:#1a3a5c;font-weight:600;">Forgot Password?</a>
      &nbsp;|&nbsp; <a href="<?= $base ?>/index.php" style="color:#1a3a5c;">← Back to Home</a>
    </div>

  </div>
</div>
</body>
</html>
