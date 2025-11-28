<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_farmers.php');
    exit;
}

$farmerId = isset($_POST['farmer_id']) ? intval($_POST['farmer_id']) : 0;

if (!$farmerId) {
    $_SESSION['admin_error_message'] = 'Invalid farmer selected.';
    header('Location: admin_farmers.php');
    exit;
}

$pdo = getDatabaseConnection();

if (!$pdo) {
    $_SESSION['admin_error_message'] = 'Database connection failed.';
    header('Location: admin_farmers.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM farmers WHERE Id = ?");
    $stmt->execute([$farmerId]);

    if ($stmt->rowCount()) {
        $_SESSION['admin_success_message'] = 'Farmer account deleted.';
    } else {
        $_SESSION['admin_error_message'] = 'Farmer account not found.';
    }
} catch (PDOException $e) {
    error_log("Admin delete farmer error: " . $e->getMessage());
    $_SESSION['admin_error_message'] = 'Failed to delete farmer account.';
}

header('Location: admin_farmers.php');
exit;

