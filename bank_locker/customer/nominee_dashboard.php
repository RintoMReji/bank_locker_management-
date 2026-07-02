<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
session_start();
$base = BASE_URL;

// Require nominee login
if (!isset($_SESSION['nominee_id'])) {
    header("Location: nominee_login.php");
    exit();
}

$conn          = getDBConnection();
$nominee_id    = $_SESSION['nominee_id'];
$customer_id   = $_SESSION['nominee_customer_id'];

// Fetch nominee own details
$nominee = $conn->query("SELECT * FROM joint_locker_holders WHERE id=$nominee_id")->fetch_assoc();

// Fetch primary customer info
$customer = $conn->query("SELECT * FROM customers WHERE id=$customer_id")->fetch_assoc();

// Check if nominee or primary customer is deactivated or deleted
if (!$nominee || $nominee['status'] !== 'active' || !$customer || $customer['status'] !== 'active') {
    unset($_SESSION['nominee_id']);
    unset($_SESSION['nominee_name']);
    unset($_SESSION['nominee_type']);
    unset($_SESSION['nominee_relationship']);
    unset($_SESSION['nominee_customer_id']);
    unset($_SESSION['nominee_owner_name']);
    unset($_SESSION['nominee_owner_cid']);
    
    header("Location: nominee_login.php?error=" . urlencode("Account is deactivated or suspended."));
    exit();
}

// Fetch locker allocation(s) of the primary holder
$allocs = $conn->query("
    SELECT a.*, l.locker_number, l.locker_size, l.location, l.annual_rent
    FROM allocations a
    JOIN lockers l ON a.locker_id = l.id
    WHERE a.customer_id = $customer_id AND a.status = 'active'
    ORDER BY a.created_at DESC
");
$alloc_rows = [];
while ($r = $allocs->fetch_assoc()) $alloc_rows[] = $r;

// Fetch recent access history (last 5 entries for this customer's lockers)
$access_log = $conn->query("
    SELECT al.access_date, al.access_time, al.purpose, al.status, l.locker_number
    FROM access_log al
    JOIN lockers l ON al.locker_id = l.id
    WHERE al.customer_id = $customer_id
    ORDER BY al.created_at DESC
    LIMIT 5
");

// Count all joint holders/nominees under this account
$joint_count = $conn->query("SELECT COUNT(*) as c FROM joint_locker_holders WHERE customer_id=$customer_id AND status='active'")->fetch_assoc()['c'];

$page_title = "Nominee Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nominee Dashboard | Bank Locker</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
<style>
  /* ── Nominee dashboard sidebar ── */
  .sidebar-nominee {
    background: linear-gradient(180deg, #004c8f 0%, #003566 100%) !important;
  }
  .sidebar-nominee .sidebar-header h2 { color: #c8a84b; }
  .sidebar-nominee .sidebar-nav a:hover,
  .sidebar-nominee .sidebar-nav a.active {
    background: rgba(255,255,255,.08);
    border-left-color: #e4232b;
  }
  .topbar-nominee h1 { color: #004c8f; }
  .user-avatar-nominee { background: #e4232b !important; }

  /* ── Read-only info blocks ── */
  .info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 28px;
  }
  .info-block {
    background: #fff;
    border-radius: 10px;
    border: 1px solid #dce3ee;
    box-shadow: 0 4px 12px rgba(0,76,143,.05);
    overflow: hidden;
  }
  .info-block-header {
    background: #004c8f;
    color: #fff;
    padding: 12px 20px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .3px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .info-block-body { padding: 18px 20px; }
  .info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f4f9;
    font-size: 13px;
  }
  .info-row:last-child { border-bottom: none; }
  .info-label { color: #64748b; font-weight: 700; min-width: 130px; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }
  .info-value { color: #1a1a2e; font-weight: 600; }

  /* ── Type badge ── */
  .type-badge-joint { background: #004c8f; color: #fff; }
  .type-badge-nominee { background: #7c3aed; color: #fff; }

  /* ── Readonly notice ── */
  .readonly-notice {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #93c5fd;
    border-radius: 10px;
    padding: 14px 20px;
    font-size: 13px;
    color: #1e40af;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
  }

  /* ── Locker card ── */
  .locker-detail-card {
    background: linear-gradient(135deg, #004c8f 0%, #003566 100%);
    border-radius: 12px;
    padding: 22px 24px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(0,76,143,.25);
  }
  .locker-detail-card .locker-icon-big {
    font-size: 42px;
    background: rgba(255,255,255,.12);
    width: 72px; height: 72px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .locker-detail-card .locker-info h3 {
    font-size: 22px; font-weight: 800; margin-bottom: 4px;
  }
  .locker-detail-card .locker-info p { font-size: 13px; opacity: .75; }
  .locker-detail-card .locker-meta {
    display: flex; gap: 16px; margin-top: 10px; flex-wrap: wrap;
  }
  .locker-detail-card .locker-meta span {
    background: rgba(255,255,255,.15);
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
  }
  .no-locker-box {
    background: #f8fafc;
    border: 2px dashed #dce3ee;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    color: #64748b;
  }
  .no-locker-box .no-icon { font-size: 50px; margin-bottom: 12px; }
  @media (max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body class="customer-theme">
<div class="wrapper">

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar sidebar-nominee">
    <div class="sidebar-header">
      <div class="bank-icon">🏦</div>
      <h2>SecureBank</h2>
      <p>Nominee Portal</p>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">My Access</div>
      <a href="nominee_dashboard.php" class="active">
        <span class="icon">📊</span> Dashboard
      </a>
      <div class="nav-section-label">Account</div>
      <a href="nominee_logout.php" onclick="return confirm('Logout from nominee portal?')">
        <span class="icon">🚪</span> Logout
      </a>
    </nav>
  </aside>

  <!-- ── MAIN CONTENT ── -->
  <div class="main-content">
    <div class="topbar topbar-nominee">
      <h1>Nominee Dashboard</h1>
      <div class="topbar-right">
        <div class="user-info">
          <div class="user-avatar user-avatar-nominee">
            <?= strtoupper(substr($_SESSION['nominee_name'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:700;color:#1a1a2e;"><?= htmlspecialchars($_SESSION['nominee_name']) ?></div>
            <div style="font-size:11px;color:#64748b;">
              <?= $_SESSION['nominee_type'] === 'joint_holder' ? '🔵 Joint Holder' : '🟣 Nominee' ?>
              &nbsp;·&nbsp; <?= htmlspecialchars($_SESSION['nominee_relationship']) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="content">

      <!-- Read-only notice -->
      <div class="readonly-notice">
        🔒 You are viewing this portal as a <strong><?= $_SESSION['nominee_type'] === 'joint_holder' ? 'Joint Account Holder' : 'Designated Nominee' ?></strong>.
        This is a <strong>read-only view</strong>. For changes, please contact the primary locker holder.
      </div>

      <!-- Stats row -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">🔒</div>
          <div class="stat-info">
            <div class="stat-value"><?= count($alloc_rows) ?></div>
            <div class="stat-label">Active Locker(s)</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">👥</div>
          <div class="stat-info">
            <div class="stat-value"><?= $joint_count ?></div>
            <div class="stat-label">Joint Members/Nominees</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon <?= $_SESSION['nominee_type'] === 'joint_holder' ? 'blue' : 'purple' ?>">
            <?= $_SESSION['nominee_type'] === 'joint_holder' ? '🤝' : '📋' ?>
          </div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:16px;"><?= $_SESSION['nominee_type'] === 'joint_holder' ? 'Joint' : 'Nominee' ?></div>
            <div class="stat-label">My Access Type</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">👤</div>
          <div class="stat-info">
            <div class="stat-value" style="font-size:15px;word-break:break-word;"><?= htmlspecialchars($_SESSION['nominee_owner_cid']) ?></div>
            <div class="stat-label">Primary Holder ID</div>
          </div>
        </div>
      </div>

      <!-- My Info + Primary Holder Info -->
      <div class="info-grid">
        <!-- My Nominee Info -->
        <div class="info-block">
          <div class="info-block-header">
            🪪 My Nominee Details
          </div>
          <div class="info-block-body">
            <div class="info-row">
              <span class="info-label">Full Name</span>
              <span class="info-value"><?= htmlspecialchars($nominee['full_name']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Relationship</span>
              <span class="info-value"><?= htmlspecialchars($nominee['relationship']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Access Type</span>
              <span class="info-value">
                <?php if ($nominee['type'] === 'joint_holder'): ?>
                  <span class="badge type-badge-joint">Joint Holder</span>
                <?php else: ?>
                  <span class="badge type-badge-nominee">Nominee</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Phone</span>
              <span class="info-value"><?= htmlspecialchars($nominee['phone'] ?: '—') ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Aadhar No.</span>
              <span class="info-value">
                <?php
                  $aadhar = $nominee['aadhar_no'] ?? '';
                  echo $aadhar ? '••••••••' . substr($aadhar, -4) : '—';
                ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Login Email</span>
              <span class="info-value"><?= htmlspecialchars($nominee['login_email'] ?: '—') ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Registered On</span>
              <span class="info-value"><?= date('d M Y', strtotime($nominee['created_at'])) ?></span>
            </div>
          </div>
        </div>

        <!-- Primary Holder Info -->
        <div class="info-block">
          <div class="info-block-header" style="background:#003566;">
            👤 Primary Locker Holder
          </div>
          <div class="info-block-body">
            <div class="info-row">
              <span class="info-label">Full Name</span>
              <span class="info-value"><?= htmlspecialchars($customer['full_name']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Customer ID</span>
              <span class="info-value"><?= htmlspecialchars($customer['customer_id']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Email</span>
              <span class="info-value"><?= htmlspecialchars($customer['email']) ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Phone</span>
              <span class="info-value"><?= htmlspecialchars($customer['phone'] ?: '—') ?></span>
            </div>
            <div class="info-row">
              <span class="info-label">Account No.</span>
              <span class="info-value">
                <?php
                  $acc = $customer['account_no'] ?? '';
                  echo $acc ? '••••' . substr($acc, -4) : '—';
                ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Member Since</span>
              <span class="info-value"><?= date('d M Y', strtotime($customer['created_at'])) ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Linked Locker Allocations -->
      <div class="card mb-20" style="margin-bottom:24px;">
        <div class="card-header">
          <h3>🔒 Linked Locker Allocation(s)</h3>
        </div>
        <div class="card-body">
          <?php if (empty($alloc_rows)): ?>
          <div class="no-locker-box">
            <div class="no-icon">🔓</div>
            <strong>No active locker allocations found</strong>
            <p style="margin-top:8px;font-size:13px;">The primary holder has no active locker yet.</p>
          </div>
          <?php else: foreach ($alloc_rows as $alloc): ?>
          <div class="locker-detail-card">
            <div class="locker-icon-big">🔐</div>
            <div class="locker-info">
              <h3><?= htmlspecialchars($alloc['locker_number']) ?></h3>
              <p><?= getLockerSizeLabel($alloc['locker_size']) ?> Locker &mdash; <?= htmlspecialchars($alloc['location']) ?></p>
              <div class="locker-meta">
                <span>📋 <?= htmlspecialchars($alloc['allocation_no']) ?></span>
                <span>📅 From <?= $alloc['allocation_date'] ?></span>
                <span>⏳ Expires <?= $alloc['expiry_date'] ?></span>
                <span>💰 Rent <?= formatCurrency($alloc['annual_rent']) ?>/yr</span>
              </div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- Recent Access History -->
      <div class="card">
        <div class="card-header">
          <h3>📋 Recent Access History</h3>
          <span style="font-size:12px;color:#64748b;">Last 5 entries for linked locker(s)</span>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Locker</th>
                <th>Date</th>
                <th>Time</th>
                <th>Purpose</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 1; $found = false; while ($log = $access_log->fetch_assoc()): $found = true; ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($log['locker_number']) ?></strong></td>
                <td><?= $log['access_date'] ?></td>
                <td><?= date('h:i A', strtotime($log['access_time'])) ?></td>
                <td><?= htmlspecialchars($log['purpose'] ?? '—') ?></td>
                <td><?= getStatusBadge($log['status']) ?></td>
              </tr>
              <?php endwhile; if (!$found): ?>
              <tr>
                <td colspan="6" class="text-center" style="padding:30px;color:#64748b;">
                  No access history found for linked locker(s).
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- .content -->
  </div><!-- .main-content -->
</div><!-- .wrapper -->

<?php $conn->close(); ?>
</body>
</html>
