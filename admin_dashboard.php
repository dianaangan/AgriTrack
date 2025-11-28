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
require_once __DIR__ . '/config/database.php';

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
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        /* Critical inline styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : 'css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/inventory.css' : 'css/inventory.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/admin.css' : 'css/admin.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Items</div>
                            <div class="stat-value"><?php echo $stats['total_items'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Value</div>
                            <div class="stat-value">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-panels">
                    <div class="panel-card">
                        <div class="section-header">
                            <h2>System Overview</h2>
                            <p>Quick snapshot of platform activity</p>
                        </div>
                        <div class="overview-grid">
                            <div class="overview-item">
                                <span class="overview-label">Active Farmers</span>
                                <strong class="overview-value"><?php echo $stats['total_farmers'] ?? 0; ?></strong>
                            </div>
                            <div class="overview-item">
                                <span class="overview-label">Inventory Items</span>
                                <strong class="overview-value"><?php echo $stats['total_items'] ?? 0; ?></strong>
                            </div>
                            <div class="overview-item">
                                <span class="overview-label">Avg Items / Farmer</span>
                                <strong class="overview-value"><?php echo $averageItems; ?></strong>
                            </div>
                            <div class="overview-item">
                                <span class="overview-label">Inventory Value</span>
                                <strong class="overview-value">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="panel-card latest-card">
                        <h2>Latest Farmer</h2>
                        <?php if ($latestFarmer): ?>
                            <div class="latest-info">
                                <span class="latest-name"><?php echo htmlspecialchars($latestFarmer['firstName'] . ' ' . $latestFarmer['lastName']); ?></span>
                                <span class="latest-email"><?php echo htmlspecialchars($latestFarmer['email']); ?></span>
                                <span class="latest-date">Joined <?php echo date('M d, Y', strtotime($latestFarmer['created_at'])); ?></span>
                            </div>
                        <?php else: ?>
                            <p style="color:#64748b;">No farmers yet.</p>
                        <?php endif; ?>
                        <div class="latest-footer">
                            <a href="#" class="btn-secondary">View all farmers</a>
                        </div>
                    </div>
                </div>

                <!-- Recent Farmers -->
                <?php if (!empty($recentFarmers)): ?>
                <div class="inventory-section">
                    <div class="section-header">
                        <h2>Recent Farmers</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFarmers as $farmer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($farmer['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

