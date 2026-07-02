<?php
$page_title = "Slot Booking";
require_once '../includes/header_customer.php';
$conn = getDBConnection();
$cid = intval($_SESSION['customer_id']);
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alloc_id = intval($_POST['allocation_id']);
    $purpose = trim($_POST['purpose']);
    $access_date = trim($_POST['access_date']);
    $access_time = trim($_POST['access_time']);
    
    // Verify allocation belongs to this customer
    $alloc = $conn->query("SELECT a.*, l.locker_number FROM allocations a JOIN lockers l ON a.locker_id=l.id WHERE a.id=$alloc_id AND a.customer_id=$cid AND a.status='active'")->fetch_assoc();
    if ($alloc) {
        $status = 'pending';
        $approved = null;
        $stmt = $conn->prepare("INSERT INTO access_log (customer_id, locker_id, access_date, access_time, purpose, approved_by, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssss", $cid, $alloc['locker_id'], $access_date, $access_time, $purpose, $approved, $status);
        if ($stmt->execute()) {
            $msg = "Locker access slot booked successfully! Waiting for admin approval. Locker: " . $alloc['locker_number'] . " on " . $access_date . " at " . date('h:i A', strtotime($access_time));
        } else {
            $err = "Failed to book slot: " . $conn->error;
        }
    } else {
        $err = "Invalid allocation or locker not active.";
    }
}

// Get active allocations
$allocs = $conn->query("
    SELECT a.id, a.allocation_no, l.locker_number, l.locker_size, l.location
    FROM allocations a JOIN lockers l ON a.locker_id=l.id
    WHERE a.customer_id=$cid AND a.status='active'
");
$rows = [];
while($r = $allocs->fetch_assoc()) $rows[] = $r;

// Recent access requests
$recent = $conn->query("
    SELECT al.*, l.locker_number FROM access_log al JOIN lockers l ON al.locker_id=l.id
    WHERE al.customer_id=$cid ORDER BY al.created_at DESC LIMIT 10
");

// Available slots checker
$check_date = (isset($_GET['check_date']) && $_GET['check_date'] >= date('Y-m-d')) ? $_GET['check_date'] : date('Y-m-d');
$safe_date = $conn->real_escape_string($check_date);
$booked_res = $conn->query("SELECT access_time FROM access_log WHERE access_date='$safe_date' AND status IN ('pending','approved')");
$booked_times = [];
while($bt = $booked_res->fetch_assoc()) $booked_times[] = date('H:i', strtotime($bt['access_time']));

// Banking time slots: 9:00 AM – 5:00 PM every 30 minutes
$time_slots = [];
for($t = strtotime('09:00'); $t <= strtotime('17:00'); $t += 1800) $time_slots[] = date('H:i', $t);
$available_count = count(array_filter($time_slots, fn($s) => !in_array($s, $booked_times)));
?>

<?php if($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if(empty($rows)): ?>
<div class="card mb-20">
  <div class="card-body text-center" style="padding:50px;">
    <div style="font-size:60px;margin-bottom:20px;">🔒</div>
    <h2 style="color:var(--primary);margin-bottom:10px;">No Active Locker</h2>
    <p style="color:#888;">You don't have an active locker to access. <a href="request_locker.php">Request one here</a>.</p>
  </div>
</div>
<?php else: ?>
<div class="card mb-20">
  <div class="card-header"><h3>🔐 Book Locker Access Slot</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Select Locker *</label>
          <select name="allocation_id" class="form-control" required>
            <?php foreach($rows as $a): ?>
            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['locker_number']) ?> — <?= getLockerSizeLabel($a['locker_size']) ?> (<?= htmlspecialchars($a['location']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Purpose of Visit *</label>
          <input type="text" name="purpose" class="form-control" required placeholder="e.g. Deposit documents, Retrieve jewelry">
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Date *</label>
          <input type="date" name="access_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Time *</label>
          <input type="time" name="access_time" class="form-control" required value="<?= date('H:i') ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">🔐 Request Slot Booking</button>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
.slots-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-top:5px;}
.time-slot{border-radius:10px;padding:14px 10px;text-align:center;border:2px solid;transition:transform 0.15s,box-shadow 0.15s;}
.time-slot:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.1);}
.slot-available{background:linear-gradient(135deg,#e8f8f0,#d4f5e2);border-color:#27ae60;}
.slot-booked{background:linear-gradient(135deg,#fde8e8,#fbd5d5);border-color:#e74c3c;opacity:0.8;}
.slot-time{font-weight:700;font-size:14px;color:var(--primary);}
.slot-available .slot-time{color:#1a6b3a;}
.slot-booked .slot-time{color:#c0392b;}
.slot-status{font-size:11px;margin-top:5px;font-weight:600;letter-spacing:0.3px;}
.slots-legend{display:flex;gap:20px;margin-top:14px;font-size:13px;flex-wrap:wrap;}
.legend-dot{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:5px;vertical-align:middle;}
.check-date-form{display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.avail-summary{display:flex;gap:18px;margin-bottom:15px;flex-wrap:wrap;}
.avail-badge{padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;}
.avail-badge.green{background:#e8f8f0;color:#27ae60;border:1px solid #b2e4c8;}
.avail-badge.red{background:#fde8e8;color:#e74c3c;border:1px solid #f5c6c6;}
.avail-badge.blue{background:#e8f0fb;color:#2c5fba;border:1px solid #b2c8f5;}
</style>

<div class="card mb-20">
  <div class="card-header"><h3>📅 Available Slots</h3></div>
  <div class="card-body">
    <form method="GET" class="check-date-form">
      <label style="font-weight:600;color:var(--primary);white-space:nowrap;">Select Date:</label>
      <input type="date" name="check_date" class="form-control" value="<?= htmlspecialchars($check_date) ?>" min="<?= date('Y-m-d') ?>" style="max-width:180px;" onchange="this.form.submit()">
      <button type="submit" class="btn btn-primary" style="white-space:nowrap;">🔍 Check Slots</button>
    </form>
    <div class="avail-summary">
      <span class="avail-badge blue">📅 <?= date('l, d M Y', strtotime($check_date)) ?></span>
      <span class="avail-badge green">🟢 <?= $available_count ?> Available</span>
      <span class="avail-badge red">🔴 <?= count($time_slots) - $available_count ?> Booked</span>
    </div>
    <div class="slots-grid">
      <?php foreach($time_slots as $slot): $booked = in_array($slot, $booked_times); ?>
      <div class="time-slot <?= $booked ? 'slot-booked' : 'slot-available' ?>">
        <div class="slot-time"><?= date('h:i A', strtotime($slot)) ?></div>
        <div class="slot-status"><?= $booked ? '🔴 Booked' : '🟢 Available' ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="slots-legend">
      <span><span class="legend-dot" style="background:#27ae60;"></span>Available &mdash; you can book this slot</span>
      <span><span class="legend-dot" style="background:#e74c3c;"></span>Booked &mdash; slot already taken</span>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h3>📋 My Recent Booked Slots</h3></div>
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
          <th>Approved/Handled By</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; $found=false; while($l=$recent->fetch_assoc()): $found=true; ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($l['locker_number']) ?></td>
          <td><?= $l['access_date'] ?></td>
          <td><?= date('h:i A', strtotime($l['access_time'])) ?></td>
          <td><?= htmlspecialchars($l['purpose']??'—') ?></td>
          <td><?= getStatusBadge($l['status']) ?></td>
          <td><?= htmlspecialchars($l['approved_by']??'—') ?></td>
        </tr>
        <?php endwhile; if(!$found): ?>
        <tr><td colspan="7" class="text-center" style="padding:30px;color:#888;">No slots booked yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $conn->close(); require_once '../includes/footer_customer.php'; ?>
