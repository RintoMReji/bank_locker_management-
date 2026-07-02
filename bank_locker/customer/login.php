<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();
$base = BASE_URL;
if (isset($_SESSION['customer_id'])) { header("Location: dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn  = getDBConnection();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email=? AND status='active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $cust = $stmt->get_result()->fetch_assoc();
        if ($cust && password_verify($pass, $cust['password'])) {
            $_SESSION['customer_id']   = $cust['id'];
            $_SESSION['customer_name'] = $cust['full_name'];
            $_SESSION['customer_cid']  = $cust['customer_id'];
            logActivity($conn, 'customer', $cust['id'], $cust['full_name'], 'Logged In', 'Customer logged in successfully');
            header("Location: dashboard.php"); exit();
        } else { $error = "Invalid email/password or account inactive."; }
    } else { $error = "Please fill all fields."; }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Login | Bank Locker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
<style>
  .login-page-customer {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #004c8f 0%, #003566 60%, #001e3c 100%);
    padding: 24px;
    font-family: 'Open Sans', sans-serif;
  }
  .login-container {
    width: 100%;
    max-width: 440px;
  }
  .login-box {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.25);
    margin-bottom: 16px;
  }
  .login-top {
    background: linear-gradient(135deg, #004c8f 0%, #003566 100%);
    padding: 32px 36px 24px;
    text-align: center;
  }
  .login-top .icon-wrap {
    width: 66px; height: 66px;
    background: rgba(255,255,255,.12);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px;
    margin: 0 auto 14px;
    border: 2px solid rgba(255,255,255,.2);
  }
  .login-top h1 { color: #fff; font-size: 20px; font-weight: 800; margin-bottom: 4px; }
  .login-top p  { color: rgba(255,255,255,.65); font-size: 12.5px; }
  .login-body { padding: 30px 36px; }

  /* Nominee login card */
  .nominee-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 28px rgba(0,0,0,.18);
    overflow: hidden;
  }
  .nominee-card-inner {
    background: linear-gradient(135deg, #3b0764 0%, #6d28d9 100%);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .nominee-card-inner .nc-icon {
    width: 50px; height: 50px;
    background: rgba(255,255,255,.15);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
  }
  .nominee-card-inner .nc-text h3 {
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 2px;
  }
  .nominee-card-inner .nc-text p {
    color: rgba(255,255,255,.7);
    font-size: 11.5px;
    line-height: 1.4;
  }
  .nominee-card-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 24px;
    background: #faf5ff;
    text-decoration: none;
    transition: background .2s;
  }
  .nominee-card-btn:hover { background: #f3e8ff; }
  .nominee-card-btn span {
    font-size: 13px;
    font-weight: 700;
    color: #6d28d9;
  }
  .nominee-card-btn .arrow {
    font-size: 18px;
    color: #6d28d9;
    transition: transform .2s;
  }
  .nominee-card-btn:hover .arrow { transform: translateX(4px); }

  /* Divider */
  .login-divider {
    text-align: center;
    color: rgba(255,255,255,.5);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    margin: 14px 0;
    position: relative;
  }
  .login-divider::before, .login-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 38%;
    height: 1px;
    background: rgba(255,255,255,.25);
  }
  .login-divider::before { left: 0; }
  .login-divider::after  { right: 0; }

  /* Form */
  .form-label { display:block;font-size:12.5px;font-weight:700;color:#1a1a2e;margin-bottom:7px; }
  .form-control { width:100%;padding:11px 15px;border:1.5px solid #dce3ee;border-radius:7px;font-size:13.5px;font-family:inherit;background:#fff;color:#1a1a2e;transition:border-color .2s,box-shadow .2s;outline:none; }
  .form-control:focus { border-color:#004c8f;box-shadow:0 0 0 3px rgba(0,76,143,.1); }
  .form-group { margin-bottom:18px; }
  .btn-login { width:100%;padding:13px 20px;background:#e4232b;color:#fff;border:none;border-radius:7px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s,transform .15s,box-shadow .2s; }
  .btn-login:hover { background:#c81a21;transform:translateY(-1px);box-shadow:0 6px 18px rgba(228,35,43,.35); }
  .alert-err { background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:7px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px; }
  .login-footer { text-align:center;font-size:12.5px;color:rgba(255,255,255,.6);margin-top:12px; }
  .login-footer a { color:rgba(255,255,255,.85);font-weight:700;text-decoration:none; }
  .login-footer a:hover { color:#fff;text-decoration:underline; }
</style>
</head>
<body class="customer-theme">
<div class="login-page-customer">
  <div class="login-container">

    <!-- ── CUSTOMER LOGIN BOX ── -->
    <div class="login-box">
      <div class="login-top">
        <div class="icon-wrap">👤</div>
        <h1>Customer Login</h1>
        <p>Bank Locker Management System</p>
      </div>
      <div class="login-body">
        <?php if($error): ?>
        <div class="alert-err">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="form-group">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="your@email.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Your password" required>
          </div>
          <button type="submit" class="btn-login">🔐 Login to Customer Portal</button>
        </form>
        <div class="text-center mt-15" style="font-size:12.5px;color:#64748b;">
          New customer?
          <a href="<?= $base ?>/new_locker_request.php" style="color:#004c8f;font-weight:700;">Request a locker</a>
          &nbsp;|&nbsp;
          <a href="<?= $base ?>/index.php" style="color:#888;">← Home</a>
        </div>
      </div>
    </div>

    <!-- ── DIVIDER ── -->
    <div class="login-divider">OR</div>

    <!-- ── NOMINEE / JOINT HOLDER LOGIN CARD ── -->
    <div class="nominee-card">
      <div class="nominee-card-inner">
        <div class="nc-icon">👥</div>
        <div class="nc-text">
          <h3>Nominee / Joint Holder Login</h3>
          <p>Are you a registered nominee or joint account holder? Access your linked locker details here.</p>
        </div>
      </div>
      <a href="<?= $base ?>/customer/nominee_login.php" class="nominee-card-btn">
        <span>🔑 Login as Nominee / Joint Holder</span>
        <span class="arrow">→</span>
      </a>
    </div>

    <!-- ── FOOTER LINKS ── -->
    <div class="login-footer" style="margin-top:16px;">
      <a href="<?= $base ?>/index.php">🏠 Back to Homepage</a>
    </div>

  </div>
</div>
</body>
</html>
