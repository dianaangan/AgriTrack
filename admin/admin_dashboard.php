<?php
session_start();

// Prevent dashboard from being cached so Back after logout doesn't expose data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$currentPage = 'dashboard';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Get statistics
$pdo = getDatabaseConnection();
$stats = [];
$recentFarmers = [];

if ($pdo) {
    try {
        // Total farmers
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM farmers");
        $stats['total_farmers'] = $stmt->fetch()['count'];
        
        // Total inventory items
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory");
        $stats['total_items'] = $stmt->fetch()['count'];
        
        // Total inventory value
        $stmt = $pdo->query("SELECT SUM(quantity * price) as total FROM inventory WHERE price IS NOT NULL");
        $result = $stmt->fetch();
        $stats['total_value'] = $result['total'] ?? 0;
        
        // Recent farmers (last 5)
        $stmt = $pdo->query("SELECT Id, firstName, lastName, email, created_at FROM farmers ORDER BY created_at DESC LIMIT 5");
        $recentFarmers = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Admin dashboard error: " . $e->getMessage());
    }
}

$averageItems = ($stats['total_farmers'] ?? 0) > 0
    ? round(($stats['total_items'] ?? 0) / max($stats['total_farmers'], 1), 1)
    : 0;
$latestFarmer = $recentFarmers[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriTrack</title>
    <link rel="icon" type="image/png" href="../images/agritrack_logo.png?v=3">
    <style>
        /* Critical inline styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : '../css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/inventory.css' : '../css/inventory.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/admin.css' : '../css/admin.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="admin_landing.php" class="sidebar-logo-text">Agr<span class="logo-i">i</span>Track</a>
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="content-title">Admin Dashboard</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>!</p>
                </div>
            </header>

            <div class="content-body admin-dashboard">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Farmers</div>
                            <div class="stat-value"><?php echo $stats['total_farmers'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Items</div>
                            <div class="stat-value"><?php echo $stats['total_items'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Value</div>
                            <div class="stat-value">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <div class="stat-label">Avg Items / Farmer</div>
                            <div class="stat-value"><?php echo $averageItems; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="dashboard-panels">
                    <!-- Latest Farmer Card -->
                    <div class="panel-card">
                        <h2 class="panel-title">Latest Farmer</h2>
                        <?php if ($latestFarmer): ?>
                            <div class="latest-farmer">
                                <div class="latest-avatar">
                                    <?php echo strtoupper(substr($latestFarmer['firstName'], 0, 1) . substr($latestFarmer['lastName'], 0, 1)); ?>
                                </div>
                                <div class="latest-details">
                                    <div class="latest-name"><?php echo htmlspecialchars($latestFarmer['firstName'] . ' ' . $latestFarmer['lastName']); ?></div>
                                    <div class="latest-email"><?php echo htmlspecialchars($latestFarmer['email']); ?></div>
                                    <div class="latest-date">Joined <?php echo date('M d, Y', strtotime($latestFarmer['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="panel-footer">
                                <a href="admin_farmers.php" class="btn-link">View all farmers →</a>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No farmers registered yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Farmers -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <h2 class="panel-title">Recent Farmers</h2>
                            <a href="admin_farmers.php" class="btn-link">View all →</a>
                        </div>
                        <?php if (!empty($recentFarmers)): ?>
                        <div class="recent-farmers-list">
                            <?php foreach ($recentFarmers as $farmer): ?>
                            <div class="recent-farmer-item">
                                <div class="farmer-avatar-small">
                                    <?php echo strtoupper(substr($farmer['firstName'], 0, 1) . substr($farmer['lastName'], 0, 1)); ?>
                                </div>
                                <div class="farmer-info">
                                    <div class="farmer-name"><?php echo htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?></div>
                                    <div class="farmer-email"><?php echo htmlspecialchars($farmer['email']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="empty-state">No farmers yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Prevent back navigation to login page after successful login
        (function() {
            // Replace the login page in browser history with dashboard page
            // This prevents users from going back to login after authentication
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Handle browser back button
            window.addEventListener('popstate', function(event) {
                // When user clicks back button, redirect to landing page
                // This prevents going back to login page
                window.location.href = 'admin_landing.php';
            });

            // Push a new state to history so back button works properly
            // This ensures the back button goes to landing instead of login
            window.history.pushState({ page: 'dashboard' }, '', window.location.href);
        })();
    </script>
</body>
</html>

