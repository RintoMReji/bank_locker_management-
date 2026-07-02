<?php
$page_title = "Manage Passwords";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['user_type'];
    $user_id = intval($_POST['user_id']);
    $new_pass = $_POST['new_password'];
    
    if (strlen($new_pass) < 6) {
        $err = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $table = ($user_type === 'admin') ? 'admin' : (($user_type === 'sub_banker') ? 'sub_banker' : 'customers');
        $conn->query("UPDATE $table SET password='$hashed' WHERE id=$user_id");
        $msg = "Password reset successfully for " . ucfirst(str_replace('_',' ',$user_type)) . " ID #$user_id";
        logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Reset password for $user_type #$user_id");
    }
}

$admins = $conn->query("SELECT id, username, full_name FROM admin ORDER BY id");
$sub_bankers = $conn->query("SELECT id, username, full_name, employee_id FROM sub_banker ORDER BY id");
$customers = $conn->query("SELECT id, customer_id, full_name, email FROM customers ORDER BY id");
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>🔑 Reset User Password</h3></div>
  <div class="card-body">
    <form method="POST" id="resetForm">
      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label">User Type *</label>
          <select name="user_type" id="userType" class="form-control" required onchange="updateUserList()">
            <option value="">-- Select Type --</option>
            <option value="admin">Admin</option>
            <option value="sub_banker">Sub-Banker</option>
            <option value="customer">Customer</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Select User *</label>
          <select name="user_id" id="userId" class="form-control" required>
            <option value="">-- Select User --</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">New Password *</label>
          <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters" minlength="6">
        </div>
      </div>
      <button type="submit" class="btn btn-warning" onclick="return confirm('Reset this user\'s password?')">🔑 Reset Password</button>
    </form>
  </div>
</div>

<!-- User Lists -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-header"><h3>👔 Admins</h3></div>
    <div class="table-responsive">
      <table><thead><tr><th>ID</th><th>Username</th><th>Name</th></tr></thead><tbody>
        <?php while($a=$admins->fetch_assoc()): ?>
        <tr><td><?= $a['id'] ?></td><td><?= htmlspecialchars($a['username']) ?></td><td><?= htmlspecialchars($a['full_name']) ?></td></tr>
        <?php endwhile; ?>
      </tbody></table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>🏛️ Sub-Bankers</h3></div>
    <div class="table-responsive">
      <table><thead><tr><th>EID</th><th>Username</th><th>Name</th></tr></thead><tbody>
        <?php while($s=$sub_bankers->fetch_assoc()): ?>
        <tr><td><?= htmlspecialchars($s['employee_id']) ?></td><td><?= htmlspecialchars($s['username']) ?></td><td><?= htmlspecialchars($s['full_name']) ?></td></tr>
        <?php endwhile; ?>
      </tbody></table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>👥 Customers</h3></div>
    <div class="table-responsive">
      <table><thead><tr><th>CID</th><th>Name</th><th>Email</th></tr></thead><tbody>
        <?php while($c=$customers->fetch_assoc()): ?>
        <tr><td><?= htmlspecialchars($c['customer_id']) ?></td><td><?= htmlspecialchars($c['full_name']) ?></td><td><?= htmlspecialchars($c['email']) ?></td></tr>
        <?php endwhile; ?>
      </tbody></table>
    </div>
  </div>
</div>

<script>
var admins = <?= json_encode($conn->query("SELECT id, username, full_name FROM admin ORDER BY id")->fetch_all(MYSQLI_ASSOC)) ?>;
var subbankers = <?= json_encode($conn->query("SELECT id, username, full_name, employee_id FROM sub_banker ORDER BY id")->fetch_all(MYSQLI_ASSOC)) ?>;
var customers = <?= json_encode($conn->query("SELECT id, customer_id, full_name, email FROM customers ORDER BY id")->fetch_all(MYSQLI_ASSOC)) ?>;

function updateUserList() {
    var type = document.getElementById('userType').value;
    var sel = document.getElementById('userId');
    sel.innerHTML = '<option value="">-- Select User --</option>';
    var list = (type==='admin') ? admins : (type==='sub_banker') ? subbankers : customers;
    list.forEach(function(u) {
        var label = (type==='admin') ? u.full_name + ' (' + u.username + ')' :
                    (type==='sub_banker') ? u.full_name + ' (' + u.employee_id + ')' :
                    u.full_name + ' (' + u.customer_id + ')';
        sel.innerHTML += '<option value="' + u.id + '">' + label + '</option>';
    });
}
</script>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
