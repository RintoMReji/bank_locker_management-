<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireCustomerLogin();

$conn = getDBConnection();
$cid  = intval($_SESSION['customer_id']);

// Get alerts from session if they exist
$msg = $_SESSION['success_msg'] ?? '';
$err = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Check if customer has any active locker allocation.
$alloc_check = $conn->query("SELECT id FROM allocations WHERE customer_id=$cid AND status='active'")->num_rows;

// ── Toggle Status ────────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id    = intval($_GET['toggle']);
    $check = $conn->query("SELECT status, full_name, type FROM joint_locker_holders WHERE id=$id AND customer_id=$cid");
    if ($check->num_rows > 0) {
        $row        = $check->fetch_assoc();
        $cur        = $row['status'];
        $name       = $row['full_name'];
        $type       = $row['type'];
        $new_status = ($cur === 'active') ? 'inactive' : 'active';
        
        $stmt = $conn->prepare("UPDATE joint_locker_holders SET status=? WHERE id=? AND customer_id=?");
        $stmt->bind_param("sii", $new_status, $id, $cid);
        $stmt->execute();
        
        logActivity($conn, 'customer', $cid, $_SESSION['customer_name'], 'Toggled Joint/Nominee Status', "Toggled status of $name ($type) to $new_status");
        $_SESSION['success_msg'] = "Status updated successfully.";
    } else {
        $_SESSION['error_msg'] = "Unauthorized action.";
    }
    header("Location: joint_locker.php");
    exit();
}

// ── Delete Member ────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id    = intval($_GET['delete']);
    $check = $conn->query("SELECT full_name, type FROM joint_locker_holders WHERE id=$id AND customer_id=$cid");
    if ($check->num_rows > 0) {
        $row  = $check->fetch_assoc();
        $name = $row['full_name'];
        $type = $row['type'];
        
        $stmt = $conn->prepare("DELETE FROM joint_locker_holders WHERE id=? AND customer_id=?");
        $stmt->bind_param("ii", $id, $cid);
        $stmt->execute();
        
        logActivity($conn, 'customer', $cid, $_SESSION['customer_name'], 'Deleted Joint/Nominee Member', "Removed $name ($type)");
        $_SESSION['success_msg'] = "Member / Nominee removed successfully.";
    } else {
        $_SESSION['error_msg'] = "Unauthorized action.";
    }
    header("Location: joint_locker.php");
    exit();
}

// ── Add New Member ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $full_name    = sanitize($_POST['full_name']);
    $relationship = sanitize($_POST['relationship']);
    $type         = sanitize($_POST['type']);
    $email        = sanitize($_POST['email']);
    $phone        = sanitize($_POST['phone']);
    $aadhar_no    = sanitize($_POST['aadhar_no']);
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) {
        $err = "Full name is required.";
    } elseif (!empty($aadhar_no) && (!ctype_digit($aadhar_no) || strlen($aadhar_no) !== 12)) {
        $err = "Aadhar number must be exactly 12 numeric digits.";
    } elseif (empty($email) && !empty($password)) {
        $err = "An email address is required to enable login credentials.";
    } elseif (!empty($password) && $password !== $confirm_pass) {
        $err = "Passwords do not match.";
    } elseif (!empty($password) && strlen($password) < 6) {
        $err = "Password must be at least 6 characters.";
    } elseif (!empty($email)) {
        // Check duplicate email (either as login_email or email) in joint_locker_holders
        $dup_stmt = $conn->prepare("SELECT id FROM joint_locker_holders WHERE login_email=? OR email=?");
        $dup_stmt->bind_param("ss", $email, $email);
        $dup_stmt->execute();
        if ($dup_stmt->get_result()->num_rows > 0) {
            $err = "This email is already registered to another nominee / joint holder.";
        } else {
            // Check duplicate email in customers table
            $dup_cust = $conn->prepare("SELECT id FROM customers WHERE email=?");
            $dup_cust->bind_param("s", $email);
            $dup_cust->execute();
            if ($dup_cust->get_result()->num_rows > 0) {
                $err = "This email is already registered to a customer account.";
            }
        }
    }

    if (empty($err)) {
        $hashed_pass = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        $le          = (!empty($email) && !empty($password)) ? $email : null;

        $stmt = $conn->prepare(
            "INSERT INTO joint_locker_holders
             (customer_id, full_name, relationship, type, email, phone, aadhar_no, password, login_email)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issssssss", $cid, $full_name, $relationship, $type, $email, $phone, $aadhar_no, $hashed_pass, $le);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Joint Holder / Nominee successfully registered." .
                   (!empty($le) ? " They can now log in at <strong><a href='nominee_login.php'>Nominee Login</a></strong> using their personal email." : "");
            logActivity($conn, 'customer', $cid, $_SESSION['customer_name'], 'Added Joint/Nominee Member', "Added $full_name ($type)");
            header("Location: joint_locker.php");
            exit();
        } else {
            $err = "Error adding record: " . $conn->error;
        }
    }
}

// ── Update Password for existing nominee ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_password') {
    $mid          = intval($_POST['member_id']);
    $email        = sanitize($_POST['email']);
    $password     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Verify ownership and get current password state
    $own = $conn->query("SELECT id, password, email, full_name FROM joint_locker_holders WHERE id=$mid AND customer_id=$cid");
    if ($own->num_rows === 0) {
        $err = "Unauthorized action.";
    } elseif (empty($email)) {
        $err = "Email address is required.";
    } else {
        $member_data = $own->fetch_assoc();
        $has_password = !empty($member_data['password']);
        $name = $member_data['full_name'];

        if (empty($password) && !$has_password) {
            $err = "A password is required when enabling login for the first time.";
        } elseif (!empty($password) && $password !== $confirm_pass) {
            $err = "Passwords do not match.";
        } elseif (!empty($password) && strlen($password) < 6) {
            $err = "Password must be at least 6 characters.";
        } else {
            // Check duplicate email (excluding this member)
            $dup_stmt = $conn->prepare("SELECT id FROM joint_locker_holders WHERE (login_email=? OR email=?) AND id != ?");
            $dup_stmt->bind_param("ssi", $email, $email, $mid);
            $dup_stmt->execute();
            if ($dup_stmt->get_result()->num_rows > 0) {
                $err = "This email is already used by another nominee.";
            } else {
                // Check duplicate email in customers table
                $dup_cust = $conn->prepare("SELECT id FROM customers WHERE email=?");
                $dup_cust->bind_param("s", $email);
                $dup_cust->execute();
                if ($dup_cust->get_result()->num_rows > 0) {
                    $err = "This email is already registered to a customer account.";
                } else {
                    if (!empty($password)) {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE joint_locker_holders SET email=?, login_email=?, password=? WHERE id=? AND customer_id=?");
                        $stmt->bind_param("sssii", $email, $email, $hashed, $mid, $cid);
                    } else {
                        $login_email_val = $has_password ? $email : null;
                        $stmt = $conn->prepare("UPDATE joint_locker_holders SET email=?, login_email=? WHERE id=? AND customer_id=?");
                        $stmt->bind_param("ssii", $email, $login_email_val, $mid, $cid);
                    }
                    $stmt->execute();
                    logActivity($conn, 'customer', $cid, $_SESSION['customer_name'], 'Updated Joint/Nominee Credentials', "Updated credentials for $name (ID $mid)");
                    $_SESSION['success_msg'] = "Login credentials updated successfully.";
                    header("Location: joint_locker.php");
                    exit();
                }
            }
        }
    }
}

// Fetch all registered members
$members = $conn->query("SELECT * FROM joint_locker_holders WHERE customer_id=$cid ORDER BY created_at DESC");

$page_title = "Joint Locker Facility";
require_once '../includes/header_customer.php';
?>

<?php if($msg): ?>
<div class="alert alert-success">✅ <?= $msg ?></div>
<?php endif; ?>
<?php if($err): ?>
<div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<?php if($alloc_check === 0): ?>
<div class="alert alert-info mb-20">
    🔒 <strong>Note:</strong> You currently do not have an active locker allocation. You can still pre-register your joint holders and nominees below, which will be automatically linked once a locker is allocated to you.
</div>
<?php endif; ?>

<!-- ── ADD NEW MEMBER FORM ───────────────────────────────────────────────── -->
<div class="card mb-20">
  <div class="card-header">
    <h3>👥 Add Joint Holder / Nominee</h3>
  </div>
  <div class="card-body">
    <div style="font-size:14px;color:#475569;margin-bottom:20px;line-height:1.6;background:#f0f4f9;padding:14px 18px;border-radius:8px;border-left:4px solid #004c8f;">
      Share locker access securely with your family members. Registered members can be given a <strong>login email &amp; password</strong> so they can independently view locker details through the <strong>Nominee Login Portal</strong>.
    </div>

    <form method="POST">
      <input type="hidden" name="action" value="add">

      <!-- ── Personal Details ──────────────────────────────────────────── -->
      <div style="font-size:12px;font-weight:700;color:#004c8f;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;padding-bottom:6px;border-bottom:2px solid #dce3ee;">
        👤 Personal Information
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" required placeholder="Member's full name">
        </div>
        <div class="form-group">
          <label class="form-label">Relationship *</label>
          <select name="relationship" class="form-control" required>
            <option value="Spouse">Spouse</option>
            <option value="Son">Son</option>
            <option value="Daughter">Daughter</option>
            <option value="Father">Father</option>
            <option value="Mother">Mother</option>
            <option value="Brother">Brother</option>
            <option value="Sister">Sister</option>
            <option value="Nominee">Designated Nominee</option>
            <option value="Other">Other Family Member</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Access Sharing Type *</label>
          <select name="type" class="form-control" required>
            <option value="joint_holder">Joint Account Holder (Full Access)</option>
            <option value="nominee">Nominee (Access in event of contingency)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Aadhar Number (12 Digits)</label>
          <input type="text" name="aadhar_no" class="form-control" placeholder="Optional — for verification" maxlength="12">
        </div>
        <div class="form-group">
          <label class="form-label">Personal Email</label>
          <input type="email" name="email" class="form-control" placeholder="member@example.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" name="phone" class="form-control" placeholder="10-digit mobile number" maxlength="15">
        </div>
      </div>

      <!-- ── Login Credentials ─────────────────────────────────────────── -->
      <div style="font-size:12px;font-weight:700;color:#004c8f;text-transform:uppercase;letter-spacing:1px;margin:20px 0 14px;padding-bottom:6px;border-bottom:2px solid #dce3ee;">
        🔐 Nominee Login Credentials <span style="font-weight:400;color:#64748b;font-size:11px;text-transform:none;letter-spacing:0;">(optional — allows nominee to log in independently)</span>
      </div>
      <div style="font-size:13px;color:#64748b;margin-bottom:15px;">
        ℹ️ The nominee's <strong>Personal Email</strong> entered above will automatically be used as their login email.
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary">👥 Add Member / Nominee</button>
    </form>
  </div>
</div>

<!-- ── REGISTERED MEMBERS TABLE ─────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <h3>📋 Registered Family Members &amp; Nominees</h3>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Type</th>
          <th>Relationship</th>
          <th>Aadhar</th>
          <th>Contact</th>
          <th>Login Email</th>
          <th>Password</th>
          <th>Status</th>
          <th>Added</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 1; $found = false;
        while ($m = $members->fetch_assoc()):
            $found = true;
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
          <td>
            <?php if ($m['type'] === 'joint_holder'): ?>
              <span class="badge" style="background:#004c8f;color:#fff;">Joint Holder</span>
            <?php else: ?>
              <span class="badge" style="background:#7c3aed;color:#fff;">Nominee</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($m['relationship']) ?></td>
          <td><?= htmlspecialchars($m['aadhar_no'] ?: '—') ?></td>
          <td>
            <div style="font-size:12px;color:#475569;">
              📞 <?= htmlspecialchars($m['phone'] ?: '—') ?><br>
              ✉️ <?= htmlspecialchars($m['email'] ?: '—') ?>
            </div>
          </td>
          <td>
            <?php if ($m['login_email']): ?>
              <span style="font-size:12px;color:#004c8f;font-weight:600;">
                <?= htmlspecialchars($m['login_email']) ?>
              </span>
            <?php else: ?>
              <span style="color:#94a3b8;font-size:12px;">Not set</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($m['password']): ?>
              <span class="badge badge-success">✅ Set</span>
            <?php else: ?>
              <span class="badge badge-secondary">Not Set</span>
            <?php endif; ?>
          </td>
          <td><?= getStatusBadge($m['status']) ?></td>
          <td style="font-size:12px;"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
              <!-- Toggle Active/Inactive -->
              <a href="?toggle=<?= $m['id'] ?>"
                 class="btn btn-sm <?= $m['status']==='active' ? 'btn-warning' : 'btn-success' ?>"
                 style="padding:4px 8px;font-size:11px;">
                <?= $m['status']==='active' ? 'Deactivate' : 'Activate' ?>
              </a>
              <!-- Set / Update Credentials -->
              <button type="button"
                      class="btn btn-sm btn-primary"
                      style="padding:4px 8px;font-size:11px;"
                      onclick="openCredModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['email'] ?? '', ENT_QUOTES) ?>')">
                🔐 Set Login
              </button>
              <!-- Remove -->
              <a href="?delete=<?= $m['id'] ?>"
                 class="btn btn-sm btn-danger"
                 onclick="return confirm('Remove this family member / nominee?')"
                 style="padding:4px 8px;font-size:11px;">
                Remove
              </a>
            </div>
          </td>
        </tr>
        <?php
        endwhile;
        if (!$found):
        ?>
        <tr>
          <td colspan="11" class="text-center" style="padding:30px;color:#64748b;">
            No joint holders or nominees registered yet. Use the form above to add a member.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── SET CREDENTIALS MODAL ────────────────────────────────────────────── -->
<div id="cred-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:460px;margin:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
    <div style="background:linear-gradient(135deg,#004c8f,#003566);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
      <span style="color:#fff;font-weight:700;font-size:15px;">🔐 Set Nominee Login Credentials</span>
      <button onclick="closeCredModal()" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:20px;cursor:pointer;line-height:1;">×</button>
    </div>
    <form method="POST" style="padding:24px;">
      <input type="hidden" name="action" value="set_password">
      <input type="hidden" name="member_id" id="modal-member-id">

      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" id="modal-email" class="form-control" required placeholder="member@example.com">
        <small style="font-size:11px;color:#64748b;display:block;margin-top:4px;">This personal email address will serve as their login username.</small>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span style="color:#64748b;font-weight:400;">(leave blank to keep current)</span></label>
        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
      </div>
      <div style="display:flex;gap:10px;">
        <button type="submit" class="btn btn-primary">💾 Save Credentials</button>
        <button type="button" onclick="closeCredModal()" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCredModal(id, email) {
  document.getElementById('modal-member-id').value = id;
  document.getElementById('modal-email').value     = email;
  var modal = document.getElementById('cred-modal');
  modal.style.display = 'flex';
}
function closeCredModal() {
  document.getElementById('cred-modal').style.display = 'none';
}
// Close on backdrop click
document.getElementById('cred-modal').addEventListener('click', function(e) {
  if (e.target === this) closeCredModal();
});
</script>

<?php
$conn->close();
require_once '../includes/footer_customer.php';
?>
