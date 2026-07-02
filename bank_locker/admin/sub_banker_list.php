<?php
$page_title = "Sub-Banker Directory & Details";
require_once '../includes/header_admin.php';
$conn = getDBConnection();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM sub_banker";
if ($search) {
    $sql .= " WHERE full_name LIKE '%" . $conn->real_escape_string($search) . "%' 
             OR employee_id LIKE '%" . $conn->real_escape_string($search) . "%' 
             OR email LIKE '%" . $conn->real_escape_string($search) . "%'";
}
$sql .= " ORDER BY created_at DESC";
$bankers = $conn->query($sql);
?>

<style>
.sb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.sb-card {
    background: var(--surface-color, #fff);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid var(--border-color, #eee);
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.sb-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.sb-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid var(--border-color, #eee);
    padding-bottom: 15px;
    margin-bottom: 10px;
}
.sb-avatar {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, var(--primary-color, #2563eb), #1e40af);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: bold;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}
.sb-info {
    flex: 1;
    margin-left: 15px;
}
.sb-name {
    font-size: 19px;
    font-weight: 600;
    margin: 0;
    color: var(--text-color, #333);
}
.sb-id {
    font-size: 13px;
    color: var(--primary-color, #2563eb);
    background: rgba(37, 99, 235, 0.1);
    padding: 4px 10px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 6px;
    font-weight: 500;
}
.sb-detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 14.5px;
    padding: 8px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.05);
}
.sb-detail-row:last-child {
    border-bottom: none;
}
.sb-detail-label {
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sb-detail-val {
    font-weight: 500;
    color: var(--text-color, #333);
    text-align: right;
}
.sb-detail-val a {
    color: var(--primary-color, #2563eb);
    text-decoration: none;
}
.sb-detail-val a:hover {
    text-decoration: underline;
}
.sb-status {
    text-align: right;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color, #eee);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
    <h3>📋 Sub-Banker Directory & Details</h3>
    <form method="GET" style="display:flex;gap:8px;">
      <input type="text" name="q" class="form-control" placeholder="Search by name, ID, or email..." value="<?= htmlspecialchars($search) ?>" style="width:280px; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
      <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">🔍 Search</button>
    </form>
  </div>
  
  <div class="sb-grid">
    <?php if($bankers->num_rows > 0): ?>
        <?php while($sb = $bankers->fetch_assoc()): ?>
            <div class="sb-card">
                <div class="sb-card-header">
                    <div class="sb-avatar"><?= strtoupper(substr($sb['full_name'], 0, 1)) ?></div>
                    <div class="sb-info">
                        <h4 class="sb-name"><?= htmlspecialchars($sb['full_name']) ?></h4>
                        <div class="sb-id">Employee ID: <?= htmlspecialchars($sb['employee_id']) ?></div>
                    </div>
                </div>
                
                <div class="sb-detail-row">
                    <span class="sb-detail-label">👤 Username:</span>
                    <span class="sb-detail-val"><?= htmlspecialchars($sb['username']) ?></span>
                </div>
                <div class="sb-detail-row">
                    <span class="sb-detail-label">📧 Email:</span>
                    <span class="sb-detail-val"><a href="mailto:<?= htmlspecialchars($sb['email']) ?>"><?= htmlspecialchars($sb['email']) ?></a></span>
                </div>
                <div class="sb-detail-row">
                    <span class="sb-detail-label">📞 Phone:</span>
                    <span class="sb-detail-val"><?= htmlspecialchars($sb['phone']) ?></span>
                </div>
                <div class="sb-detail-row">
                    <span class="sb-detail-label">📅 Joined:</span>
                    <span class="sb-detail-val"><?= date('M d, Y h:i A', strtotime($sb['created_at'])) ?></span>
                </div>
                
                <div class="sb-status">
                    <span style="font-size:13px; color:#64748b;">Account Status</span>
                    <?= getStatusBadge($sb['status']) ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; padding: 30px; color:#64748b; grid-column: 1/-1; font-size: 16px;">No sub-bankers found matching your criteria.</p>
    <?php endif; ?>
  </div>
</div>

<?php 
$conn->close(); 
require_once '../includes/footer_admin.php'; 
?>
