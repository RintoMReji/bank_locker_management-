<?php
$page_title = "Approve Slots";
require_once '../includes/header_admin.php';
$conn = getDBConnection();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $access_id = intval($_POST['access_id']);
    $action = $_POST['action'];
    $admin_name = 'Admin: ' . $_SESSION['admin_name'];
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE access_log SET status='approved', approved_by=? WHERE id=?");
        $stmt->bind_param("si", $admin_name, $access_id);
        if ($stmt->execute()) {
            $msg = "Slot booking approved successfully!";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Approved access slot booking #$access_id");
        } else {
            $err = "Error: " . $conn->error;
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE access_log SET status='rejected', approved_by=? WHERE id=?");
        $stmt->bind_param("si", $admin_name, $access_id);
        if ($stmt->execute()) {
            $msg = "Slot booking rejected.";
            logActivity($conn, 'admin', $_SESSION['admin_id'], $_SESSION['admin_name'], "Rejected access slot booking #$access_id");
        } else {
            $err = "Error: " . $conn->error;
        }
    }
}

// Fetch pending slots
$pending = $conn->query("
    SELECT al.*, c.full_name, c.customer_id AS cid, c.phone, c.email, l.locker_number, l.locker_size, l.location
    FROM access_log al
    JOIN customers c ON al.customer_id=c.id
    JOIN lockers l ON al.locker_id=l.id
    WHERE al.status='pending'
    ORDER BY al.access_date ASC, al.access_time ASC
");

// Status filter for all booked slots
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
if (!in_array($status_filter, ['all','pending','approved','rejected'])) $status_filter = 'all';
$where_sql = $status_filter !== 'all' ? "WHERE al.status='" . $conn->real_escape_string($status_filter) . "'" : "";

$all_slots = $conn->query("
    SELECT al.*, c.full_name, c.customer_id AS cid, c.phone, c.email, l.locker_number, l.locker_size, l.location
    FROM access_log al
    JOIN customers c ON al.customer_id=c.id
    JOIN lockers l ON al.locker_id=l.id
    $where_sql
    ORDER BY al.access_date DESC, al.access_time DESC
");

// Counts per status
$cnt_res = $conn->query("SELECT status, COUNT(*) as cnt FROM access_log GROUP BY status");
$count_map = ['all'=>0,'pending'=>0,'approved'=>0,'rejected'=>0];
while($cr = $cnt_res->fetch_assoc()) {
    $count_map[$cr['status']] = (int)$cr['cnt'];
    $count_map['all'] += (int)$cr['cnt'];
}

// Available slots checker logic
$check_date = isset($_GET['check_date']) ? $_GET['check_date'] : date('Y-m-d');
$safe_date = $conn->real_escape_string($check_date);

// Fetch all bookings for the check_date (both pending and approved status)
$booked_res = $conn->query("
    SELECT al.access_time, al.purpose, al.status, c.full_name, c.customer_id AS cid, l.locker_number
    FROM access_log al
    JOIN customers c ON al.customer_id=c.id
    JOIN lockers l ON al.locker_id=l.id
    WHERE al.access_date='$safe_date' AND al.status IN ('pending','approved')
    ORDER BY al.access_time ASC
");

$booked_slots_info = [];
while($bt = $booked_res->fetch_assoc()) {
    $time_formatted = date('H:i', strtotime($bt['access_time']));
    $booked_slots_info[$time_formatted][] = $bt;
}

// Banking time slots: 9:00 AM – 5:00 PM every 30 minutes
$time_slots = [];
for($t = strtotime('09:00'); $t <= strtotime('17:00'); $t += 1800) {
    $time_slots[] = date('H:i', $t);
}

// Calculate available count
$available_count = 0;
foreach ($time_slots as $slot) {
    if (!isset($booked_slots_info[$slot])) {
        $available_count++;
    }
}
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="card mb-20">
  <div class="card-header"><h3>⏰ Pending Slot Bookings</h3></div>
  <div class="card-body">
    <?php $found=false; while($r=$pending->fetch_assoc()): $found=true; ?>
    <div class="approval-card">
      <div class="approval-header">
        <div>
          <div class="approval-title"><?= htmlspecialchars($r['full_name']) ?> (<?= htmlspecialchars($r['cid']) ?>)</div>
          <small style="color:#888;"><?= htmlspecialchars($r['email']) ?> | <?= htmlspecialchars($r['phone']) ?> | Requested <?= timeAgo($r['created_at']) ?></small>
        </div>
        <?= getStatusBadge($r['status']) ?>
      </div>
      <div class="approval-meta">
        <div><div class="meta-label">Locker Number</div><div class="meta-value"><?= htmlspecialchars($r['locker_number']) ?> (<?= getLockerSizeLabel($r['locker_size']) ?>)</div></div>
        <div><div class="meta-label">Locker Location</div><div class="meta-value"><?= htmlspecialchars($r['location']) ?></div></div>
        <div><div class="meta-label">Requested Date</div><div class="meta-value"><strong><?= $r['access_date'] ?></strong></div></div>
        <div><div class="meta-label">Requested Time</div><div class="meta-value"><strong><?= date('h:i A', strtotime($r['access_time'])) ?></strong></div></div>
        <div style="grid-column: span 2;"><div class="meta-label">Purpose of Visit</div><div class="meta-value"><?= htmlspecialchars($r['purpose'] ?: '—') ?></div></div>
      </div>
      <form method="POST" class="approval-actions">
        <input type="hidden" name="access_id" value="<?= $r['id'] ?>">
        <button type="submit" name="action" value="approve" class="btn btn-success">✅ Approve Slot</button>
        <button type="submit" name="action" value="reject" class="btn btn-danger">❌ Reject Slot</button>
      </form>
    </div>
    <?php endwhile; if(!$found): ?>
    <div class="text-center" style="padding:40px;color:#888;">No pending slot bookings found.</div>
    <?php endif; ?>
  </div>
</div>

<style>
.slots-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 15px;
}
.time-slot-card {
  border-radius: var(--radius);
  padding: 15px;
  border: 2px solid;
  transition: transform 0.2s, box-shadow 0.2s;
  background: var(--white);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.time-slot-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}
.slot-available {
  border-color: #27ae60;
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
}
.slot-booked {
  border-color: #e74c3c;
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
}
.slot-time {
  font-weight: 700;
  font-size: 15px;
  color: var(--primary-dark);
  border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
  padding-bottom: 5px;
  margin-bottom: 8px;
}
.slot-available .slot-time {
  color: #14532d;
}
.slot-booked .slot-time {
  color: #7f1d1d;
}
.slot-status {
  font-size: 12px;
  font-weight: 700;
  display: inline-block;
  margin-bottom: 5px;
}
.slot-available .slot-status {
  color: #15803d;
}
.slot-booked .slot-status {
  color: #b91c1c;
}
.slot-bookings-list {
  font-size: 11.5px;
  color: #451a03;
  margin-top: 5px;
  line-height: 1.4;
}
.slot-booking-item {
  background: rgba(255, 255, 255, 0.6);
  padding: 5px 8px;
  border-radius: 4px;
  margin-bottom: 4px;
  border-left: 3px solid #ef4444;
}
.slots-legend {
  display: flex;
  gap: 20px;
  margin-top: 20px;
  font-size: 13px;
  flex-wrap: wrap;
  padding-top: 10px;
  border-top: 1px solid var(--border);
}
.legend-dot {
  display: inline-block;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-right: 5px;
  vertical-align: middle;
}
.check-date-form {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.avail-summary {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.avail-badge {
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid;
}
.avail-badge.green {
  background: #f0fdf4;
  color: #16a34a;
  border-color: #bbf7d0;
}
.avail-badge.red {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fecaca;
}
.avail-badge.blue {
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
}
</style>

<div class="card mb-20">
  <div class="card-header">
    <h3>📅 Slot Availability Checker</h3>
  </div>
  <div class="card-body">
    <form method="GET" class="check-date-form">
      <?php if(isset($_GET['status'])): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status']) ?>">
      <?php endif; ?>
      <label style="font-weight:600;color:var(--primary);white-space:nowrap;">Select Date:</label>
      <input type="date" name="check_date" class="form-control" value="<?= htmlspecialchars($check_date) ?>" style="max-width:180px;" onchange="this.form.submit()">
      <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Check Slots</button>
    </form>
    
    <div class="avail-summary">
      <span class="avail-badge blue">📅 <?= date('l, d M Y', strtotime($check_date)) ?></span>
      <span class="avail-badge green">🟢 <?= $available_count ?> Available</span>
      <span class="avail-badge red">🔴 <?= count($time_slots) - $available_count ?> Booked</span>
    </div>
    
    <div class="slots-grid">
      <?php foreach($time_slots as $slot): 
        $is_booked = isset($booked_slots_info[$slot]); 
        $bookings = $is_booked ? $booked_slots_info[$slot] : [];
      ?>
      <div class="time-slot-card <?= $is_booked ? 'slot-booked' : 'slot-available' ?>">
        <div>
          <div class="slot-time"><?= date('h:i A', strtotime($slot)) ?></div>
          <span class="slot-status"><?= $is_booked ? '🔴 Booked' : '🟢 Available' ?></span>
          
          <?php if($is_booked): ?>
            <div class="slot-bookings-list">
              <?php foreach($bookings as $b): ?>
                <div class="slot-booking-item">
                  <strong><?= htmlspecialchars($b['locker_number']) ?></strong> - <?= htmlspecialchars($b['full_name']) ?> <small>(<?= htmlspecialchars($b['cid']) ?>)</small>
                  <?php if($b['purpose']): ?>
                    <br/><span style="font-style:italic;color:#666;">"<?= htmlspecialchars($b['purpose']) ?>"</span>
                  <?php endif; ?>
                  <br/><?= getStatusBadge($b['status']) ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="slots-legend">
      <span><span class="legend-dot" style="background:#27ae60;"></span>Available &mdash; slot is free for booking</span>
      <span><span class="legend-dot" style="background:#e74c3c;"></span>Booked &mdash; slot has active requests/approvals</span>
    </div>
  </div>
</div>

<style>
.slot-filter-tabs{display:flex;gap:8px;flex-wrap:wrap;}
.filter-tab{padding:6px 16px;border-radius:20px;text-decoration:none;font-size:13px;font-weight:600;background:#f0f4f8;color:#555;transition:all 0.2s;border:1px solid #dde6f0;}
.filter-tab.active{background:#1a3a5c;color:#fff;border-color:#1a3a5c;}
.filter-tab:hover:not(.active){background:#dbe8f4;color:#1a3a5c;}
.tab-count{background:rgba(0,0,0,0.12);padding:1px 8px;border-radius:10px;font-size:11px;margin-left:5px;}
.filter-tab.active .tab-count{background:rgba(255,255,255,0.25);}
</style>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <h3>📋 All Customer Booked Slots</h3>
    <div class="slot-filter-tabs">
      <a href="?status=all" class="filter-tab <?= $status_filter==='all'?'active':'' ?>">All <span class="tab-count"><?= $count_map['all'] ?></span></a>
      <a href="?status=pending" class="filter-tab <?= $status_filter==='pending'?'active':'' ?>">⏳ Pending <span class="tab-count"><?= $count_map['pending'] ?></span></a>
      <a href="?status=approved" class="filter-tab <?= $status_filter==='approved'?'active':'' ?>">✅ Approved <span class="tab-count"><?= $count_map['approved'] ?></span></a>
      <a href="?status=rejected" class="filter-tab <?= $status_filter==='rejected'?'active':'' ?>">❌ Rejected <span class="tab-count"><?= $count_map['rejected'] ?></span></a>
    </div>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Locker</th>
          <th>Location</th>
          <th>Access Date</th>
          <th>Access Time</th>
          <th>Purpose</th>
          <th>Status</th>
          <th>Handled By</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; $found_all=false; while($h=$all_slots->fetch_assoc()): $found_all=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($h['full_name']) ?> <small>(<?= htmlspecialchars($h['cid']) ?>)</small></td>
          <td><?= htmlspecialchars($h['phone']) ?></td>
          <td><?= htmlspecialchars($h['locker_number']) ?> <small style="color:#888;">(<?= getLockerSizeLabel($h['locker_size']) ?>)</small></td>
          <td style="font-size:12px;"><?= htmlspecialchars($h['location']) ?></td>
          <td><strong><?= $h['access_date'] ?></strong></td>
          <td><?= date('h:i A', strtotime($h['access_time'])) ?></td>
          <td><?= htmlspecialchars($h['purpose'] ?: '—') ?></td>
          <td><?= getStatusBadge($h['status']) ?></td>
          <td><?= htmlspecialchars($h['approved_by'] ?: '—') ?></td>
        </tr>
        <?php endwhile; if(!$found_all): ?>
        <tr><td colspan="10" class="text-center" style="padding:30px;color:#888;">No booked slots found<?= $status_filter!=='all'?' for this status':'' ?>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_admin.php'; ?>
