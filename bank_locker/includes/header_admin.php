<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireAdminLogin();
$page_title = isset($page_title) ? $page_title . ' | Admin' : 'Admin Panel';
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body>
<div class="wrapper">
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="bank-icon">🏦</div>
    <h2>SecureBank</h2>
    <p>Admin Panel</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="<?= $base ?>/admin/dashboard.php" <?= (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'class="active"':'' ?>>
      <span class="icon">📊</span> Dashboard
    </a>
    <div class="nav-section-label">Locker Management</div>
    <a href="<?= $base ?>/admin/lockers.php" <?= (basename($_SERVER['PHP_SELF'])=='lockers.php')?'class="active"':'' ?>>
      <span class="icon">🔒</span> Manage Lockers
    </a>
    <a href="<?= $base ?>/admin/allocations.php" <?= (basename($_SERVER['PHP_SELF'])=='allocations.php')?'class="active"':'' ?>>
      <span class="icon">📋</span> Allocations
    </a>
    <a href="<?= $base ?>/admin/allocate_locker.php" <?= (basename($_SERVER['PHP_SELF'])=='allocate_locker.php')?'class="active"':'' ?>>
      <span class="icon">➕</span> Assign Locker
    </a>
    <a href="<?= $base ?>/admin/search_allocations.php" <?= (basename($_SERVER['PHP_SELF'])=='search_allocations.php')?'class="active"':'' ?>>
      <span class="icon">🔍</span> Search Allocations
    </a>
    <div class="nav-section-label">People</div>
    <a href="<?= $base ?>/admin/customers.php" <?= (basename($_SERVER['PHP_SELF'])=='customers.php')?'class="active"':'' ?>>
      <span class="icon">👥</span> Customers
    </a>
    <a href="<?= $base ?>/admin/add_customer.php" <?= (basename($_SERVER['PHP_SELF'])=='add_customer.php')?'class="active"':'' ?>>
      <span class="icon">👤</span> Add Customer
    </a>
    <a href="<?= $base ?>/admin/sub_bankers.php" <?= (basename($_SERVER['PHP_SELF'])=='sub_bankers.php'||basename($_SERVER['PHP_SELF'])=='add_sub_banker.php')?'class="active"':'' ?>>
      <span class="icon">🏛️</span> Manage Sub-Bankers
    </a>
    <a href="<?= $base ?>/admin/sub_banker_list.php" <?= (basename($_SERVER['PHP_SELF'])=='sub_banker_list.php')?'class="active"':'' ?>>
      <span class="icon">📋</span> Sub-Banker Details
    </a>
    <div class="nav-section-label">Approvals</div>
    <a href="<?= $base ?>/admin/locker_requests.php" <?= (basename($_SERVER['PHP_SELF'])=='locker_requests.php')?'class="active"':'' ?>>
      <span class="icon">📩</span> Locker Requests
    </a>
    <a href="<?= $base ?>/admin/approve_slots.php" <?= (basename($_SERVER['PHP_SELF'])=='approve_slots.php')?'class="active"':'' ?>>
      <span class="icon">⏰</span> Approve Slots
    </a>
    <a href="<?= $base ?>/admin/surrender_requests.php" <?= (basename($_SERVER['PHP_SELF'])=='surrender_requests.php')?'class="active"':'' ?>>
      <span class="icon">📥</span> Surrender Requests
    </a>
    <a href="<?= $base ?>/admin/contact_messages.php" <?= (basename($_SERVER['PHP_SELF'])=='contact_messages.php')?'class="active"':'' ?>>
      <span class="icon">💬</span> Contact Messages
    </a>
    <a href="<?= $base ?>/admin/notify_customers.php" <?= (basename($_SERVER['PHP_SELF'])=='notify_customers.php')?'class="active"':'' ?>>
      <span class="icon">📢</span> Send Notification
    </a>
    <div class="nav-section-label">Reports & Logs</div>
    <a href="<?= $base ?>/admin/reports.php" <?= (basename($_SERVER['PHP_SELF'])=='reports.php')?'class="active"':'' ?>>
      <span class="icon">📈</span> Generate Reports
    </a>
    <a href="<?= $base ?>/admin/access_log.php" <?= (basename($_SERVER['PHP_SELF'])=='access_log.php')?'class="active"':'' ?>>
      <span class="icon">📝</span> Access Logs
    </a>
    <a href="<?= $base ?>/admin/activity_logs.php" <?= (basename($_SERVER['PHP_SELF'])=='activity_logs.php')?'class="active"':'' ?>>
      <span class="icon">📜</span> System Activity Logs
    </a>
    <div class="nav-section-label">System</div>
    <a href="<?= $base ?>/admin/settings.php" <?= (basename($_SERVER['PHP_SELF'])=='settings.php')?'class="active"':'' ?>>
      <span class="icon">🔧</span> System Settings
    </a>
    <div class="nav-section-label">Account</div>
    <a href="<?= $base ?>/admin/profile.php" <?= (basename($_SERVER['PHP_SELF'])=='profile.php')?'class="active"':'' ?>>
      <span class="icon">⚙️</span> Update Profile
    </a>
    <a href="<?= $base ?>/admin/manage_passwords.php" <?= (basename($_SERVER['PHP_SELF'])=='manage_passwords.php')?'class="active"':'' ?>>
      <span class="icon">🔑</span> Manage Passwords
    </a>
    <a href="<?= $base ?>/admin/logout.php"><span class="icon">🚪</span> Logout</a>
  </nav>
</aside>
<div class="main-content">
<div class="topbar">
  <h1><?= $page_title ?></h1>
  <div class="topbar-right">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_name']??'A',0,1)) ?></div>
      <div>
        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($_SESSION['admin_name']??'Admin') ?></div>
        <div style="font-size:11px;">Administrator</div>
      </div>
    </div>
  </div>
</div>
<div class="content">
