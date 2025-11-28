<?php
session_start();

// Prevent caching so back button can't show stale dashboard after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - AgriTrack</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        /* Critical inline styles to ensure layout works immediately */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
        .content-header { background-color: white; border-bottom: 1px solid #e2e8f0; padding: 1.5rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .content-body { padding: 2rem; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .dashboard-card { background-color: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.5rem; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : 'css/home.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">AgriTrack</a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="home.php" class="nav-item active">
                    <span class="nav-icon">🏠</span>
                    <span>Home</span>
                </a>
                <a href="inventory.php" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Inventory</span>
                </a>
                <a href="add_product.php" class="nav-item">
                    <span class="nav-icon">➕</span>
                    <span>Add Products</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1 class="content-title">Home</h1>
            </header>

            <div class="content-body">
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Farmer'); ?>! 👋</h2>
                    <p>Manage your farm inventory efficiently and track your agricultural assets with ease. Get started by adding products or viewing your current inventory.</p>
                </div>

                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">➕</div>
                        <h3>Add New Products</h3>
                        <p>Add crops, livestock, and goods to your inventory with detailed information and categorization.</p>
                        <a href="add_product.php" class="btn-dashboard">Add Products</a>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">📦</div>
                        <h3>View Inventory</h3>
                        <p>Access a comprehensive list of all your inventory items with real-time quantity and status updates.</p>
                        <a href="inventory.php" class="btn-dashboard">View Inventory</a>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">✏️</div>
                        <h3>Update Details</h3>
                        <p>Modify product information including names, prices, and quantities to keep your inventory current.</p>
                        <a href="inventory.php" class="btn-dashboard">Update Details</a>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">📊</div>
                        <h3>Manage Stock</h3>
                        <p>Remove items or mark them as out of stock to maintain accurate inventory records.</p>
                        <a href="inventory.php" class="btn-dashboard">Manage Stock</a>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">📈</div>
                        <h3>Reports</h3>
                        <p>Generate reports on your inventory, sales, and farm operations for better decision making.</p>
                        <a href="reports.php" class="btn-dashboard">View Reports</a>
                    </div>

                    <div class="dashboard-card">
                        <div class="dashboard-card-icon">⚙️</div>
                        <h3>Settings</h3>
                        <p>Configure your account settings, farm details, and notification preferences.</p>
                        <a href="settings.php" class="btn-dashboard">Settings</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>

