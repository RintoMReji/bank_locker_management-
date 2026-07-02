<?php
$page_title = "Add Customer";
require_once '../includes/header_subbanker.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $aadhar  = trim($_POST['aadhar_no']);
    $account = trim($_POST['account_no']);
    $pass    = $_POST['password'];
    $cid     = generateCustomerID($conn);
    $hashed  = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO customers (customer_id,full_name,email,phone,address,aadhar_no,account_no,password) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $cid, $name, $email, $phone, $address, $aadhar, $account, $hashed);
    if ($stmt->execute()) {
        $new_cust_id = $conn->insert_id;
        logActivity($conn, 'sub_banker', $_SESSION['subbanker_id'], $_SESSION['subbanker_name'], 'Registered Customer', "Registered new customer: $name (CID: $cid, System ID: $new_cust_id)");
        $msg = "Customer registered! Customer ID: $cid";
    } else {
        $err = "Error: " . $conn->error;
    }
}
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card">
  <div class="card-header"><h3>👤 Register New Customer</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="Customer full name">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" required placeholder="customer@example.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number *</label>
          <input type="text" name="phone" class="form-control" required placeholder="10-digit mobile" maxlength="15">
        </div>
        <div class="form-group">
          <label class="form-label">Aadhar Number *</label>
          <input type="text" name="aadhar_no" class="form-control" required placeholder="12-digit Aadhar" maxlength="12">
        </div>
        <div class="form-group">
          <label class="form-label">Bank Account Number *</label>
          <input type="text" name="account_no" class="form-control" required placeholder="Savings account number">
        </div>
        <div class="form-group">
          <label class="form-label">Login Password *</label>
          <input type="password" name="password" class="form-control" required placeholder="Portal password">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Address *</label>
        <textarea name="address" class="form-control" required placeholder="Full postal address"></textarea>
      </div>
      <button type="submit" class="btn btn-teal">👤 Register Customer</button>
      <a href="customers.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
    </form>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_subbanker.php'; ?>
