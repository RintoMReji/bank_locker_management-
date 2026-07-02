<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireSubBankerLogin();
$page_title = isset($page_title) ? $page_title . ' | Sub Banker' : 'Sub Banker Panel';
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
<aside class="sidebar sidebar-subbanker">
  <div class="sidebar-header">
    <div class="bank-icon">🏛️</div>
    <h2>SecureBank</h2>
    <p>Sub Banker Panel</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="<?= $base ?>/sub_banker/dashboard.php" <?= (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'class="active"':'' ?>>
      <span class="icon">📊</span> Dashboard
    </a>
    <div class="nav-section-label">Locker Management</div>
    <a href="<?= $base ?>/sub_banker/lockers.php" <?= (basename($_SERVER['PHP_SELF'])=='lockers.php')?'class="active"':'' ?>>
      <span class="icon">🔒</span> Manage Lockers
    </a>
    <a href="<?= $base ?>/sub_banker/allocations.php" <?= (basename($_SERVER['PHP_SELF'])=='allocations.php')?'class="active"':'' ?>>
      <span class="icon">📋</span> Allocations
    </a>
    <a href="<?= $base ?>/sub_banker/allocate_locker.php" <?= (basename($_SERVER['PHP_SELF'])=='allocate_locker.php')?'class="active"':'' ?>>
      <span class="icon">➕</span> Assign Locker
    </a>
    <a href="<?= $base ?>/sub_banker/search_allocations.php" <?= (basename($_SERVER['PHP_SELF'])=='search_allocations.php')?'class="active"':'' ?>>
      <span class="icon">🔍</span> Search Allocations
    </a>
    <div class="nav-section-label">Customers</div>
    <a href="<?= $base ?>/sub_banker/customers.php" <?= (basename($_SERVER['PHP_SELF'])=='customers.php')?'class="active"':'' ?>>
      <span class="icon">👥</span> Customers
    </a>
    <a href="<?= $base ?>/sub_banker/add_customer.php" <?= (basename($_SERVER['PHP_SELF'])=='add_customer.php')?'class="active"':'' ?>>
      <span class="icon">👤</span> Add Customer
    </a>
    <div class="nav-section-label">Approvals</div>
    <a href="<?= $base ?>/sub_banker/locker_requests.php?type=new" <?= (basename($_SERVER['PHP_SELF'])=='locker_requests.php' && ($_GET['type']??'')=='new')?'class="active"':'' ?>>
      <span class="icon">📩</span> Approve Bank Lockers New Customer
    </a>
    <a href="<?= $base ?>/sub_banker/approve_slots.php" <?= (basename($_SERVER['PHP_SELF'])=='approve_slots.php')?'class="active"':'' ?>>
      <span class="icon">⏰</span> Approve Slots
    </a>
    <a href="<?= $base ?>/sub_banker/contact_messages.php" <?= (basename($_SERVER['PHP_SELF'])=='contact_messages.php')?'class="active"':'' ?>>
      <span class="icon">💬</span> Contact Messages
    </a>
    <a href="<?= $base ?>/sub_banker/notify_customers.php" <?= (basename($_SERVER['PHP_SELF'])=='notify_customers.php')?'class="active"':'' ?>>
      <span class="icon">📢</span> Send Notification
    </a>
    <div class="nav-section-label">Reports</div>
    <a href="<?= $base ?>/sub_banker/reports.php" <?= (basename($_SERVER['PHP_SELF'])=='reports.php')?'class="active"':'' ?>>
      <span class="icon">📈</span> Generate Reports
    </a>
    <a href="<?= $base ?>/sub_banker/access_log.php" <?= (basename($_SERVER['PHP_SELF'])=='access_log.php')?'class="active"':'' ?>>
      <span class="icon">📝</span> Access Logs
    </a>
    <div class="nav-section-label">Account</div>
    <a href="<?= $base ?>/sub_banker/profile.php" <?= (basename($_SERVER['PHP_SELF'])=='profile.php')?'class="active"':'' ?>>
      <span class="icon">⚙️</span> Update Profile
    </a>
    <a href="<?= $base ?>/sub_banker/logout.php"><span class="icon">🚪</span> Logout</a>
  </nav>
</aside>
<div class="main-content">
<div class="topbar topbar-subbanker">
  <h1><?= $page_title ?></h1>
  <div class="topbar-right">
    <span class="role-tag role-tag-subbanker">Sub Banker</span>
    <div class="user-info">
      <div class="user-avatar user-avatar-teal"><?= strtoupper(substr($_SESSION['subbanker_name']??'S',0,1)) ?></div>
      <div>
        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($_SESSION['subbanker_name']??'Sub Banker') ?></div>
        <div style="font-size:11px;"><?= htmlspecialchars($_SESSION['subbanker_eid']??'') ?></div>
      </div>
    </div>
  </div>
</div>
<div class="content">
