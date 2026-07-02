<?php
require_once __DIR__ . '/includes/config.php';
session_start();
$base = BASE_URL;
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $aadhar     = trim($_POST['aadhar_no']);
    $account_no = trim($_POST['account_no']);
    $locker_size = $_POST['locker_size'];
    $location   = trim($_POST['preferred_location']);
    $reason     = trim($_POST['reason']);

    // Validate
    if (!$full_name || !$email || !$phone || !$aadhar || !$account_no) {
        $err = "Please fill all required fields.";
    } else {
        // Check if customer already exists with this email
        $existing = $conn->query("SELECT id FROM customers WHERE email='" . $conn->real_escape_string($email) . "'")->fetch_assoc();
        
        if ($existing) {
            // Customer exists - just create locker request
            $cid = $existing['id'];
            $check = $conn->query("SELECT id FROM locker_requests WHERE customer_id=$cid AND status='pending'")->num_rows;
            if ($check > 0) {
                $err = "You already have a pending locker request. Please wait for it to be processed.";
            } else {
                $stmt = $conn->prepare("INSERT INTO locker_requests (customer_id, locker_size, preferred_location, reason) VALUES (?,?,?,?)");
                $stmt->bind_param("isss", $cid, $locker_size, $location, $reason);
                $stmt->execute();
                $msg = "Locker request submitted successfully! Since you already have an account, you can <a href='$base/customer/login.php' style='color:#15803d;font-weight:700;'>login here</a> to track your request.";
            }
        } else {
            // New customer - register first, then create request
            $customer_id = '';
            $year = date('Y');
            $cnt = $conn->query("SELECT COUNT(*) as c FROM customers")->fetch_assoc()['c'];
            $customer_id = "CUST" . $year . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);
            
            // Generate a default password (phone number)
            $default_pass = password_hash($phone, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO customers (customer_id, full_name, email, phone, address, aadhar_no, account_no, password) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssss", $customer_id, $full_name, $email, $phone, $address, $aadhar, $account_no, $default_pass);
            
            if ($stmt->execute()) {
                $new_cid = $conn->insert_id;
                
                // Create locker request
                $stmt2 = $conn->prepare("INSERT INTO locker_requests (customer_id, locker_size, preferred_location, reason) VALUES (?,?,?,?)");
                $stmt2->bind_param("isss", $new_cid, $locker_size, $location, $reason);
                $stmt2->execute();
                
                $msg = "✅ Locker request submitted successfully!<br><br>
                    <strong>Your Account Details:</strong><br>
                    Customer ID: <strong>$customer_id</strong><br>
                    Login Email: <strong>$email</strong><br>
                    Default Password: <strong>Your phone number ($phone)</strong><br><br>
                    <a href='$base/customer/login.php' style='color:#15803d;font-weight:700;'>Login to your account</a> to track your request status.";
            } else {
                $err = "Error: " . $conn->error . " (Email or Account No. may already exist)";
            }
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Locker Request | Bank Locker Management</title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
<style>
.request-page{min-height:100vh;background:linear-gradient(135deg,#004c8f 0%,#003566 100%);display:flex;align-items:center;justify-content:center;padding:30px 20px}
.request-container{background:#fff;border-radius:18px;max-width:700px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}
.request-top{background:linear-gradient(135deg,#004c8f,#003566);color:white;padding:30px;text-align:center}
.request-top .r-icon{font-size:56px;margin-bottom:10px}
.request-top h1{font-size:22px;font-weight:700;margin-bottom:5px}
.request-top p{font-size:13px;opacity:.7}
.request-form{padding:30px}
.section-label{font-size:12px;font-weight:700;color:#004c8f;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;margin-top:5px;padding-bottom:6px;border-bottom:2px solid #e2e8f0}
</style>
</head>
<body class="customer-theme">
<div class="request-page">
  <div class="request-container">
    <div class="request-top">
      <div class="r-icon">📩</div>
      <h1>Request a New Bank Locker</h1>
      <p>Fill in your details below to request a locker allocation</p>
    </div>
    <div class="request-form">
      <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
      
      <?php if(!$msg): ?>
      <form method="POST">
        <div class="section-label">👤 Personal Information</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['full_name']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="phone" class="form-control" placeholder="10-digit number" required maxlength="15" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Aadhar Number *</label>
            <input type="text" name="aadhar_no" class="form-control" placeholder="12-digit Aadhar" required maxlength="12" value="<?= htmlspecialchars($_POST['aadhar_no']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Bank Account Number *</label>
            <input type="text" name="account_no" class="form-control" placeholder="Your bank account no." required value="<?= htmlspecialchars($_POST['account_no']??'') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" placeholder="Your residential address" rows="2"><?= htmlspecialchars($_POST['address']??'') ?></textarea>
        </div>
 
        <div class="section-label" style="margin-top:20px;">🔒 Locker Preference</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Preferred Locker Size *</label>
            <select name="locker_size" class="form-control" required>
              <option value="small">Small — ₹1,500/year</option>
              <option value="medium">Medium — ₹2,500/year</option>
              <option value="large">Large — ₹4,000/year</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Preferred Location</label>
            <input type="text" name="preferred_location" class="form-control" placeholder="e.g. Main Branch" value="<?= htmlspecialchars($_POST['preferred_location']??'') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason / Purpose</label>
          <textarea name="reason" class="form-control" placeholder="Why do you need a locker? (optional)" rows="2"><?= htmlspecialchars($_POST['reason']??'') ?></textarea>
        </div>
 
        <div class="alert alert-info" style="margin-top:10px;">
          ℹ️ If you're a new customer, an account will be auto-created. Your <strong>phone number</strong> will be your default login password.
        </div>
 
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:10px;">📩 Submit Locker Request</button>
      </form>
      <?php endif; ?>
      
      <div class="text-center mt-15" style="font-size:13px;">
        Already have an account? <a href="<?= $base ?>/customer/login.php" style="color:#004c8f;font-weight:600;">Login here</a>
        &nbsp;|&nbsp; <a href="<?= $base ?>/index.php" style="color:#888;">← Back to Home</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
