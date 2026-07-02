<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();
$base = BASE_URL;

// Already logged in as nominee — redirect
if (isset($_SESSION['nominee_id'])) {
    header("Location: nominee_dashboard.php");
    exit();
}

$error = $_GET['error'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn      = getDBConnection();
    $login_email = trim($_POST['login_email'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($login_email && $password) {
        $stmt = $conn->prepare(
            "SELECT j.*, c.full_name AS owner_name, c.customer_id AS owner_cid
             FROM joint_locker_holders j
             JOIN customers c ON j.customer_id = c.id
             WHERE j.login_email = ? AND j.status = 'active' AND c.status = 'active' AND j.password IS NOT NULL"
        );
        $stmt->bind_param("s", $login_email);
        $stmt->execute();
        $nominee = $stmt->get_result()->fetch_assoc();

        if ($nominee && password_verify($password, $nominee['password'])) {
            $_SESSION['nominee_id']          = $nominee['id'];
            $_SESSION['nominee_name']        = $nominee['full_name'];
            $_SESSION['nominee_type']        = $nominee['type'];
            $_SESSION['nominee_relationship']= $nominee['relationship'];
            $_SESSION['nominee_customer_id'] = $nominee['customer_id'];
            $_SESSION['nominee_owner_name']  = $nominee['owner_name'];
            $_SESSION['nominee_owner_cid']   = $nominee['owner_cid'];
            header("Location: nominee_dashboard.php");
            exit();
        } else {
            $error = "Invalid login email or password, or account is inactive.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nominee / Joint Holder Login | Bank Locker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
<style>
  .nominee-login-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #004c8f 0%, #003566 60%, #001e3c 100%);
    padding: 24px;
    font-family: 'Open Sans', sans-serif;
  }
  .nominee-login-box {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 24px 60px rgba(0,0,0,.25);
    overflow: hidden;
  }
  .nlb-top {
    background: linear-gradient(135deg, #004c8f 0%, #003566 100%);
    padding: 36px 36px 28px;
    text-align: center;
  }
  .nlb-top .badge-nominee {
    display: inline-block;
    background: rgba(228,35,43,.15);
    border: 1px solid rgba(228,35,43,.4);
    color: #ffaaae;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    padding: 4px 14px;
    border-radius: 50px;
    margin-bottom: 14px;
  }
  .nlb-top .icon-wrap {
    width: 70px; height: 70px;
    background: rgba(255,255,255,.1);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 34px;
    margin: 0 auto 14px;
    border: 2px solid rgba(255,255,255,.2);
  }
  .nlb-top h1 {
    color: #fff;
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 4px;
  }
  .nlb-top p {
    color: rgba(255,255,255,.65);
    font-size: 12.5px;
  }
  .nlb-body { padding: 32px 36px; }
  .nlb-body .form-group { margin-bottom: 18px; }
  .nlb-body .form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 7px;
    letter-spacing: .2px;
  }
  .nlb-body .form-control {
    width: 100%;
    padding: 11px 15px;
    border: 1.5px solid #dce3ee;
    border-radius: 7px;
    font-size: 13.5px;
    font-family: inherit;
    background: #fff;
    color: #1a1a2e;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  .nlb-body .form-control:focus {
    border-color: #004c8f;
    box-shadow: 0 0 0 3px rgba(0,76,143,.1);
  }
  .btn-nominee-login {
    width: 100%;
    padding: 13px 20px;
    background: #e4232b;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background .2s, transform .15s, box-shadow .2s;
  }
  .btn-nominee-login:hover {
    background: #c81a21;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(228,35,43,.35);
  }
  .alert-err {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 7px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .nlb-footer {
    padding: 18px 36px 28px;
    text-align: center;
    font-size: 12.5px;
    color: #64748b;
    border-top: 1px solid #f0f4f9;
  }
  .nlb-footer a { color: #004c8f; font-weight: 700; text-decoration: none; }
  .nlb-footer a:hover { color: #e4232b; text-decoration: underline; }
  .info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 7px;
    padding: 12px 14px;
    font-size: 12px;
    color: #1e40af;
    margin-bottom: 18px;
    line-height: 1.5;
  }
</style>
</head>
<body class="customer-theme">
<div class="nominee-login-wrap">
  <div class="nominee-login-box">

    <div class="nlb-top">
      <div class="badge-nominee">👥 Nominee / Joint Holder Portal</div>
      <div class="icon-wrap">🔑</div>
      <h1>Nominee Login</h1>
      <p>Access your linked safe deposit locker details</p>
    </div>

    <div class="nlb-body">
      <?php if($error): ?>
      <div class="alert-err">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="info-box">
        ℹ️ Use the <strong>login email</strong> and <strong>password</strong> set by the primary locker holder for your nominee account.
      </div>

      <form method="POST" id="nominee-login-form">
        <div class="form-group">
          <label class="form-label" for="login_email">Nominee Login Email</label>
          <input type="email" id="login_email" name="login_email" class="form-control"
                 placeholder="nominee@example.com" required
                 value="<?= htmlspecialchars($_POST['login_email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="nominee_password">Password</label>
          <div style="position:relative;">
            <input type="password" id="nominee_password" name="password" class="form-control"
                   placeholder="Enter your password" required>
            <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#64748b;font-size:16px;" id="eye-btn">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn-nominee-login">🔐 Login as Nominee</button>
      </form>
    </div>

    <div class="nlb-footer">
      <a href="<?= $base ?>/customer/login.php">← Back to Customer Login</a>
      &nbsp;|&nbsp;
      <a href="<?= $base ?>/index.php">🏠 Home</a>
    </div>

  </div>
</div>

<script>
function togglePass() {
  var inp = document.getElementById('nominee_password');
  var btn = document.getElementById('eye-btn');
  if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
  else { inp.type = 'password'; btn.textContent = '👁️'; }
}
</script>
</body>
</html>
