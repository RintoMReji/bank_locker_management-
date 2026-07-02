<?php
$page_title = "Customer Details";
require_once '../includes/header_admin.php';
$conn = getDBConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>⚠️ Customer ID not specified. <a href='customers.php'>Go back to Customers</a></div>";
    require_once '../includes/footer_admin.php';
    exit();
}

$cid = intval($_GET['id']);

// Fetch Customer details
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$customer_res = $stmt->get_result();

if ($customer_res->num_rows === 0) {
    echo "<div class='alert alert-danger'>⚠️ Customer not found. <a href='customers.php'>Go back to Customers</a></div>";
    require_once '../includes/footer_admin.php';
    exit();
}

$c = $customer_res->fetch_assoc();

// Fetch Locker Allocations
$allocations = $conn->query("SELECT a.*, l.locker_number, l.locker_size, l.location FROM allocations a JOIN lockers l ON a.locker_id=l.id WHERE a.customer_id=$cid ORDER BY a.created_at DESC");

// Fetch Joint Locker Holders / Nominees
$joint_members = $conn->query("SELECT * FROM joint_locker_holders WHERE customer_id=$cid ORDER BY created_at DESC");
?>

<div style="margin-bottom: 20px;">
    <a href="customers.php" class="btn btn-secondary">⬅️ Back to Customer List</a>
</div>

<div class="form-grid mb-20">
  <!-- Profile Card -->
  <div class="card">
    <div class="card-header">
      <h3>👤 Customer Profile</h3>
      <?= getStatusBadge($c['status']) ?>
    </div>
    <div class="card-body">
      <table style="width:100%; font-size: 14px;">
        <tbody>
          <tr>
            <td style="font-weight:600; width:35%; color:#64748b; border-bottom:1px solid #f1f5f9;">Customer ID:</td>
            <td style="font-weight:700; border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($c['customer_id']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:1px solid #f1f5f9;">Full Name:</td>
            <td style="border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($c['full_name']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:1px solid #f1f5f9;">Aadhar Number:</td>
            <td style="border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($c['aadhar_no']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:1px solid #f1f5f9;">Bank Account No:</td>
            <td style="border-bottom:1px solid #f1f5f9; font-family: monospace;"><?= htmlspecialchars($c['account_no']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:1px solid #f1f5f9;">Email Address:</td>
            <td style="border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($c['email']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:1px solid #f1f5f9;">Phone Number:</td>
            <td style="border-bottom:1px solid #f1f5f9;"><?= htmlspecialchars($c['phone']) ?></td>
          </tr>
          <tr>
            <td style="font-weight:600; color:#64748b; border-bottom:none;">Address:</td>
            <td style="border-bottom:none; line-height: 1.4;"><?= nl2br(htmlspecialchars($c['address'])) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Locker Allocation Summary -->
  <div class="card">
    <div class="card-header">
      <h3>🔒 Locker Allocations</h3>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="table-responsive">
        <table style="font-size:13px;">
          <thead>
            <tr>
              <th>Alloc No.</th>
              <th>Locker</th>
              <th>Size</th>
              <th>Expiry</th>
              <th>Payment</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $found_alloc = false;
            while ($a = $allocations->fetch_assoc()): 
                $found_alloc = true;
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($a['allocation_no']) ?></strong></td>
              <td><?= htmlspecialchars($a['locker_number']) ?></td>
              <td><?= getLockerSizeLabel($a['locker_size']) ?></td>
              <td><?= date('d M Y', strtotime($a['expiry_date'])) ?></td>
              <td><?= getStatusBadge($a['payment_status']) ?></td>
              <td><?= getStatusBadge($a['status']) ?></td>
            </tr>
            <?php 
            endwhile; 
            if (!$found_alloc): 
            ?>
            <tr>
              <td colspan="6" class="text-center" style="padding:30px; color:#888;">
                No locker allocations registered yet.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Joint Locker Facility: Family Members & Nominees Card -->
<div class="card">
  <div class="card-header">
    <h3>👥 Joint Locker Facility (Joint Holders & Nominees Details)</h3>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Type</th>
          <th>Relationship</th>
          <th>Aadhar No.</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Registered Date</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $i = 1;
        $found_joint = false;
        while ($jm = $joint_members->fetch_assoc()): 
            $found_joint = true;
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($jm['full_name']) ?></strong></td>
          <td>
            <?php if($jm['type'] === 'joint_holder'): ?>
                <span class="badge" style="background:#dbeafe; color:#1e40af;">Joint Holder</span>
            <?php else: ?>
                <span class="badge" style="background:#fef9c3; color:#854d0e;">Nominee</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($jm['relationship']) ?></td>
          <td><?= htmlspecialchars($jm['aadhar_no'] ?: '—') ?></td>
          <td><?= htmlspecialchars($jm['email'] ?: '—') ?></td>
          <td><?= htmlspecialchars($jm['phone'] ?: '—') ?></td>
          <td><?= getStatusBadge($jm['status']) ?></td>
          <td style="font-size:12px;"><?= date('d M Y', strtotime($jm['created_at'])) ?></td>
        </tr>
        <?php 
        endwhile; 
        if (!$found_joint): 
        ?>
        <tr>
          <td colspan="9" class="text-center" style="padding:40px; color:#64748b;">
            ❌ No family members or joint locker facility nominees have been registered by this customer.
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php 
$conn->close(); 
require_once '../includes/footer_admin.php'; 
?>
