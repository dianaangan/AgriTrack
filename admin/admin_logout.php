<?php
session_start();

// Destroy admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_lastName']);

// Redirect to admin login
header('Location: admin_login.php');
exit;

