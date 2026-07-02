<?php
$page_title = "System Settings";
require_once '../includes/header_admin.php';

$conn = getDBConnection();
$msg = '';
$err = '';

// Check if settings table exists, if not create it (fail-safe)
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
)");

// Initialize settings if they don't exist
$check_mode = $conn->query("SELECT setting_value FROM settings WHERE setting_key='maintenance_mode'");
if ($check_mode->num_rows === 0) {
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', 'off')");
}
$check_msg = $conn->query("SELECT setting_value FROM settings WHERE setting_key='maintenance_message'");
if ($check_msg->num_rows === 0) {
    $default_msg = "We are currently performing scheduled system upgrades. Please check back soon.";
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_message', '" . $conn->real_escape_string($default_msg) . "')");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['maintenance_mode'] ?? 'off';
    $message = trim($_POST['maintenance_message'] ?? '');
    
    if ($mode !== 'on' && $mode !== 'off') {
        $mode = 'off';
    }
    
    // Update settings
    $stmt1 = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='maintenance_mode'");
    $stmt1->bind_param("s", $mode);
    $stmt1->execute();
    
    $stmt2 = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='maintenance_message'");
    $stmt2->bind_param("s", $message);
    $stmt2->execute();
    
    $msg = "System settings updated successfully!";
    
    // Log Activity
    $aid = $_SESSION['admin_id'] ?? 0;
    $aname = $_SESSION['admin_name'] ?? 'Admin';
    logActivity($conn, 'admin', $aid, $aname, "Updated system settings", "Maintenance Mode: " . strtoupper($mode));
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$current_mode = $settings['maintenance_mode'] ?? 'off';
$current_message = $settings['maintenance_message'] ?? '';
?>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        align-items: start;
    }
    
    /* Interactive Card Selectors */
    .mode-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .mode-card {
        border: 2px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
        cursor: pointer;
        position: relative;
        transition: all 0.25s ease;
        background: var(--white);
    }
    
    .mode-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }
    
    .mode-card input[type="radio"] {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 20px;
        height: 20px;
        accent-color: var(--primary);
        cursor: pointer;
    }
    
    .mode-card.selected-off {
        border-color: var(--success);
        background: #f0fdf4;
    }
    .mode-card.selected-off .mode-icon {
        color: var(--success);
    }
    
    .mode-card.selected-on {
        border-color: var(--danger);
        background: #fef2f2;
    }
    .mode-card.selected-on .mode-icon {
        color: var(--danger);
    }
    
    .mode-icon {
        font-size: 32px;
        margin-bottom: 12px;
        transition: transform 0.25s ease;
    }
    
    .mode-card:hover .mode-icon {
        transform: scale(1.1);
    }
    
    .mode-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--text);
    }
    
    .mode-desc {
        font-size: 12.5px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    /* Live status widget */
    .status-widget {
        text-align: center;
        padding: 30px 20px;
    }
    .status-indicator {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        margin: 0 auto 16px;
    }
    .status-indicator.online {
        background: #dcfce7;
        color: var(--success);
        box-shadow: 0 0 0 8px rgba(22, 163, 74, 0.1);
    }
    .status-indicator.offline {
        background: #fee2e2;
        color: var(--danger);
        box-shadow: 0 0 0 8px rgba(220, 38, 38, 0.1);
        animation: pulseGlow 2s infinite;
    }
    
    @keyframes pulseGlow {
        0%, 100% { box-shadow: 0 0 0 8px rgba(220, 38, 38, 0.1); }
        50% { box-shadow: 0 0 0 16px rgba(220, 38, 38, 0.2); }
    }
    
    .status-text {
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 4px;
    }
    .status-sub {
        font-size: 12px;
        color: var(--text-muted);
    }
</style>

<?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="settings-grid">
    <div class="card">
        <div class="card-header">
            <h3>⚙️ Server Maintenance Configuration</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="settingsForm">
                <div class="form-group">
                    <label class="form-label">Select Operations Mode</label>
                    <div class="mode-selector">
                        <!-- Standard Mode Card -->
                        <div class="mode-card <?= ($current_mode === 'off') ? 'selected-off' : '' ?>" onclick="selectMode('off')">
                            <input type="radio" name="maintenance_mode" id="mode_off" value="off" <?= ($current_mode === 'off') ? 'checked' : '' ?>>
                            <div class="mode-icon">🟢</div>
                            <div class="mode-title">Standard Mode</div>
                            <div class="mode-desc">The portal is fully online. Customers, sub-bankers, and public visitors can log in and manage lockers as usual.</div>
                        </div>
                        
                        <!-- Maintenance Mode Card -->
                        <div class="mode-card <?= ($current_mode === 'on') ? 'selected-on' : '' ?>" onclick="selectMode('on')">
                            <input type="radio" name="maintenance_mode" id="mode_on" value="on" <?= ($current_mode === 'on') ? 'checked' : '' ?>>
                            <div class="mode-icon">⚠️</div>
                            <div class="mode-title">Maintenance Mode</div>
                            <div class="mode-desc">The portal is closed to the public. Non-admin users are blocked and shown the maintenance screen. Admins retain full panel access.</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="maintenance_message">Custom Maintenance Message</label>
                    <textarea name="maintenance_message" id="maintenance_message" class="form-control" placeholder="Write a message explaining the downtime..." style="min-height: 120px;"><?= htmlspecialchars($current_message) ?></textarea>
                    <small style="color: var(--text-muted); display: block; margin-top: 6px;">This message will be visible on the maintenance screen to visitors and customers.</small>
                </div>

                <button type="submit" class="btn btn-primary mt-15">💾 Save Settings</button>
            </form>
        </div>
    </div>
    
    <!-- Status Widget Panel -->
    <div class="card">
        <div class="card-header">
            <h3>📊 Live Status</h3>
        </div>
        <div class="card-body status-widget">
            <?php if ($current_mode === 'on'): ?>
                <div class="status-indicator offline">⚠️</div>
                <div class="status-text" style="color: var(--danger);">Offline</div>
                <div class="status-sub">Undergoing Maintenance</div>
            <?php else: ?>
                <div class="status-indicator online">✅</div>
                <div class="status-text" style="color: var(--success);">Online</div>
                <div class="status-sub">Standard System Operations</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function selectMode(mode) {
    // Check the corresponding radio button
    const radio = document.getElementById('mode_' + mode);
    if (radio) {
        radio.checked = true;
    }
    
    // Toggle visual card styles
    const cards = document.querySelectorAll('.mode-card');
    cards.forEach(card => {
        card.classList.remove('selected-off');
        card.classList.remove('selected-on');
    });
    
    const selectedCard = radio.closest('.mode-card');
    if (mode === 'off') {
        selectedCard.classList.add('selected-off');
    } else {
        selectedCard.classList.add('selected-on');
    }
}
</script>

<?php
$conn->close();
require_once '../includes/footer_admin.php';
?>
