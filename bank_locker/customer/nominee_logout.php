<?php
session_start();
// Clear only nominee session keys
unset($_SESSION['nominee_id']);
unset($_SESSION['nominee_name']);
unset($_SESSION['nominee_type']);
unset($_SESSION['nominee_relationship']);
unset($_SESSION['nominee_customer_id']);
unset($_SESSION['nominee_owner_name']);
unset($_SESSION['nominee_owner_cid']);

require_once '../includes/config.php';
header("Location: " . BASE_URL . "/customer/nominee_login.php");
exit();
?>
