<?php
require_once '../includes/config.php';
session_start();
$base = BASE_URL;
$msg = ''; $err = ''; $step = 'request';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'find_account') {
        $username = trim($_POST['username']);
        $admin = $conn->query("SELECT id, full_name FROM admin WHERE username='" . $conn->real_escape_string($username) . "'")->fetch_assoc();
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $conn->prepare("INSERT INTO password_resets (user_type, user_id, token, expires_at) VALUES ('admin',?,?,?)");
            $stmt->bind_param("iss", $admin['id'], $token, $expires);
            $stmt->execute();
            $step = 'reset';
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_name'] = $admin['full_name'];
        } else {
            $err = "Username not found.";
        }
    }
    
    if ($action === 'reset_password') {
        $token = $_SESSION['reset_token'] ?? '';
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if ($new !== $confirm) {
            $err = "Passwords do not match.";
            $step = 'reset';
        } elseif (strlen($new) < 6) {
            $err = "Password must be at least 6 characters.";
            $step = 'reset';
        } else {
            $reset = $conn->query("SELECT * FROM password_resets WHERE token='" . $conn->real_escape_string($token) . "' AND used=0 AND expires_at > NOW()")->fetch_assoc();
            if ($reset) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $conn->query("UPDATE admin SET password='$hashed' WHERE id={$reset['user_id']}");
                $conn->query("UPDATE password_resets SET used=1 WHERE id={$reset['id']}");
                unset($_SESSION['reset_token'], $_SESSION['reset_name']);
                $msg = "Password reset successful! <a href='login.php' style='color:#15803d;font-weight:700;'>Login now</a>";
                $step = 'done';
            } else {
                $err = "Invalid or expired reset token. Please try again.";
                $step = 'request';
            }
        }
    }
    if (isset($conn)) $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Recovery | Admin</title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="icon">🔑</div>
      <h1>Password Recovery</h1>
      <p>Admin Account Recovery</p>
    </div>
    <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
    
    <?php if($step === 'request'): ?>
    <form method="POST">
      <input type="hidden" name="action" value="find_account">
      <div class="form-group">
        <label class="form-label">Admin Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter your username" required>
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">🔍 Find Account</button>
    </form>
    <?php elseif($step === 'reset'): ?>
    <div class="alert alert-info">ℹ️ Account found: <strong><?= htmlspecialchars($_SESSION['reset_name']??'') ?></strong></div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <div class="form-group">
        <label class="form-label">New Password *</label>
        <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters" minlength="6">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn btn-success btn-lg" style="width:100%;justify-content:center;">🔑 Reset Password</button>
    </form>
    <?php endif; ?>
    
    <div class="text-center mt-15" style="font-size:12px;color:#888;">
      <a href="login.php" style="color:#1a3a5c;">← Back to Login</a>
    </div>
  </div>
</div>
</body>
</html>
