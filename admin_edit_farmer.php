<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$currentPage = 'farmers';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/includes/farmer_functions.php';

$farmerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$farmer = $farmerId ? getFarmerById($farmerId) : null;

if (!$farmer) {
    $_SESSION['admin_error_message'] = 'Farmer not found.';
    header('Location: admin_farmers.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        $result = updateFarmerProfile($farmerId, [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email
        ]);

        if ($result['success']) {
            $_SESSION['admin_success_message'] = 'Farmer account updated successfully.';
            header('Location: admin_farmers.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }

    $farmer['firstName'] = $firstName;
    $farmer['lastName'] = $lastName;
    $farmer['email'] = $email;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Farmer - AgriTrack Admin</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : 'css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/settings.css' : 'css/settings.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">AgriTrack</a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="<?php echo ($currentPage === 'dashboard') ? 'nav-item active' : 'nav-item'; ?>">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </a>
                <a href="admin_farmers.php" class="<?php echo ($currentPage === 'farmers') ? 'nav-item active' : 'nav-item'; ?>">
                    <span class="nav-icon">👥</span>
                    <span>Farmers</span>
                </a>
                <a href="admin_inventory.php" class="<?php echo ($currentPage === 'inventory') ? 'nav-item active' : 'nav-item'; ?>">
                    <span class="nav-icon">📦</span>
                    <span>All Inventory</span>
                </a>
                <a href="admin_reports.php" class="<?php echo ($currentPage === 'reports') ? 'nav-item active' : 'nav-item'; ?>">
                    <span class="nav-icon">📈</span>
                    <span>Reports</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="admin_logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="content-title">Edit Farmer</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Update farmer account details</p>
                </div>
                <div class="header-actions">
                    <a href="admin_farmers.php" class="btn-secondary">Back to Farmers</a>
                </div>
            </header>

            <div class="content-body">
                <div class="settings-container" style="max-width: 640px;">
                    <div class="settings-card">
                        <div class="settings-header">
                            <h2>Farmer Information</h2>
                            <p>Manage the farmer's basic information</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <span>⚠️</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="settings-form">
                            <div class="form-row-group">
                                <div class="form-row">
                                    <label for="firstName">First Name <span class="required">*</span></label>
                                    <input type="text" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($farmer['firstName']); ?>">
                                </div>
                                <div class="form-row">
                                    <label for="lastName">Last Name <span class="required">*</span></label>
                                    <input type="text" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($farmer['lastName']); ?>">
                                </div>
                            </div>

                            <div class="form-row form-row-spaced">
                                <label for="email">Email Address <span class="required">*</span></label>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($farmer['email']); ?>">
                            </div>

                            <div class="form-actions">
                                <a href="admin_farmers.php" class="btn-secondary">Cancel</a>
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

