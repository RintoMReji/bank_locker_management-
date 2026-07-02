<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();
$base = BASE_URL;
if (isset($_SESSION['customer_id'])) { header("Location: dashboard.php"); exit(); }

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn    = getDBConnection();
    $name    = trim($_POST['full_name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $aadhar  = trim($_POST['aadhar_no']);
    $account = trim($_POST['account_no']);
    $pass    = $_POST['password'];
    $cpass   = $_POST['confirm_password'];

    if ($pass !== $cpass) {
        $err = "Passwords do not match.";
    } else {
        $cid    = generateCustomerID($conn);
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("INSERT INTO customers (customer_id,full_name,email,phone,address,aadhar_no,account_no,password) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $cid, $name, $email, $phone, $address, $aadhar, $account, $hashed);
        if ($stmt->execute()) {
            $msg = "Registration successful! Your Customer ID: <strong>$cid</strong>. <a href='login.php'>Login now</a>";
        } else {
            $err = "Registration failed: " . $conn->error;
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Registration | Bank Locker</title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body class="customer-theme">
<div class="login-page login-page-customer" style="align-items:flex-start;padding:40px 20px;">
  <div style="background:white;border-radius:16px;padding:40px;width:100%;max-width:600px;margin:0 auto;box-shadow:0 10px 25px rgba(0,0,0,.15);">
    <div class="login-logo">
      <div class="icon">📝</div>
      <h1>Customer Registration</h1>
      <p>Create your bank locker account</p>
    </div>
    <?php if($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="As per bank records">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="your@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" required placeholder="10-digit number" maxlength="15">
        </div>
        <div class="form-group">
          <label class="form-label">Aadhar Number *</label>
          <input type="text" name="aadhar_no" class="form-control" required placeholder="12-digit Aadhar" maxlength="12">
        </div>
        <div class="form-group">
          <label class="form-label">Bank Account Number *</label>
          <input type="text" name="account_no" class="form-control" required placeholder="Savings account no.">
        </div>
        <div class="form-group">
          <label class="form-label">Address *</label>
          <input type="text" name="address" class="form-control" required placeholder="Full address">
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-control" required placeholder="Create password">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">📝 Register</button>
    </form>
    <div class="text-center mt-15" style="font-size:13px;">
      Already have an account? <a href="login.php" style="color:#1a3a5c;font-weight:600;">Login here</a>
      &nbsp;|&nbsp; <a href="<?= $base ?>/index.php" style="color:#888;">← Home</a>
    </div>
  </div>
</div>
</body>
</html>
