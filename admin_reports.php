<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$currentPage = 'reports';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$pdo = getDatabaseConnection();

$summary = [
    'total_items' => 0,
    'unique_farmers' => 0,
    'in_stock' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
    'inventory_value' => 0
];

$reports = [
    'category_breakdown' => [],
    'status_breakdown' => [],
    'top_farmers' => [],
    'low_stock_items' => [],
    'out_of_stock_items' => [],
    'recent_items' => []
];

if ($pdo) {
    try {
        // Summary stats
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
        $summary['total_items'] = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("SELECT COUNT(DISTINCT farmer_id) as total FROM inventory");
        $summary['unique_farmers'] = (int)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("SELECT SUM(quantity * price) as total FROM inventory WHERE price IS NOT NULL");
        $summary['inventory_value'] = (float)($stmt->fetch()['total'] ?? 0);

        $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM inventory GROUP BY status");
        $statusData = $stmt->fetchAll() ?: [];
        foreach ($statusData as $row) {
            $status = $row['status'] ?? 'unknown';
            $count = (int)$row['total'];
            switch ($status) {
                case 'in_stock':
                    $summary['in_stock'] = $count;
                    break;
                case 'low_stock':
                    $summary['low_stock'] = $count;
                    break;
                case 'out_of_stock':
                    $summary['out_of_stock'] = $count;
                    break;
            }
        }
        $reports['status_breakdown'] = $statusData;

        // Category breakdown
        $stmt = $pdo->query("
            SELECT category, COUNT(*) as count, SUM(quantity) as total_quantity
            FROM inventory
            GROUP BY category
            ORDER BY count DESC
        ");
        $reports['category_breakdown'] = $stmt->fetchAll() ?: [];

        // Top farmers by number of products
        $stmt = $pdo->query("
            SELECT f.Id, f.firstName, f.lastName, f.email,
                   COUNT(i.Id) as item_count,
                   COALESCE(SUM(i.quantity), 0) as total_quantity,
                   COALESCE(SUM(i.quantity * i.price), 0) as total_value
            FROM farmers f
            LEFT JOIN inventory i ON i.farmer_id = f.Id
            GROUP BY f.Id
            ORDER BY item_count DESC, total_quantity DESC
            LIMIT 5
        ");
        $reports['top_farmers'] = $stmt->fetchAll() ?: [];

        // Low stock items
        $stmt = $pdo->query("
            SELECT Id, product_name, category, quantity, unit, updated_at
            FROM inventory
            WHERE status = 'low_stock'
            ORDER BY updated_at DESC
            LIMIT 8
        ");
        $reports['low_stock_items'] = $stmt->fetchAll() ?: [];

        // Out of stock items
        $stmt = $pdo->query("
            SELECT Id, product_name, category, updated_at
            FROM inventory
            WHERE status = 'out_of_stock'
            ORDER BY updated_at DESC
            LIMIT 8
        ");
        $reports['out_of_stock_items'] = $stmt->fetchAll() ?: [];

        // Recent additions
        $stmt = $pdo->query("
            SELECT i.Id, i.product_name, i.category, i.quantity, i.unit, i.created_at,
                   f.firstName, f.lastName
            FROM inventory i
            LEFT JOIN farmers f ON f.Id = i.farmer_id
            ORDER BY i.created_at DESC
            LIMIT 8
        ");
        $reports['recent_items'] = $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        error_log("Admin reports error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - AgriTrack Admin</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : 'css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/inventory.css' : 'css/inventory.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/reports.css' : 'css/reports.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/admin.css' : 'css/admin.css'; ?>?v=<?php echo time(); ?>">
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
                    <h1 class="content-title">Reports</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Platform-wide inventory analytics</p>
                </div>
            </header>

            <div class="content-body">
                <div class="reports-summary">
                    <div class="summary-card">
                        <div class="summary-icon">📦</div>
                        <div class="summary-content">
                            <div class="summary-label">Total Products</div>
                            <div class="summary-value"><?php echo $summary['total_items']; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-success">
                        <div class="summary-icon">👥</div>
                        <div class="summary-content">
                            <div class="summary-label">Farmers Contributing</div>
                            <div class="summary-value"><?php echo $summary['unique_farmers']; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-success">
                        <div class="summary-icon">✅</div>
                        <div class="summary-content">
                            <div class="summary-label">In Stock</div>
                            <div class="summary-value"><?php echo $summary['in_stock']; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-warning">
                        <div class="summary-icon">⚠️</div>
                        <div class="summary-content">
                            <div class="summary-label">Low Stock</div>
                            <div class="summary-value"><?php echo $summary['low_stock']; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-error">
                        <div a class="summary-icon">❌</div>
                        <div class="summary-content">
                            <div class="summary-label">Out of Stock</div>
                            <div class="summary-value"><?php echo $summary['out_of_stock']; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-value">
                        <div class="summary-icon">💰</div>
                        <div class="summary-content">
                            <div class="summary-label">Inventory Value</div>
                            <div class="summary-value">₱<?php echo number_format($summary['inventory_value'], 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="reports-grid">
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Category Breakdown</h2>
                            <p>Products grouped by category</p>
                        </div>
                        <div class="report-content">
                            <?php if (!empty($reports['category_breakdown'])): ?>
                                <div class="category-list">
                                    <?php
                                    $maxCategoryCount = max(array_column($reports['category_breakdown'], 'count'));
                                    foreach ($reports['category_breakdown'] as $category):
                                        $percentage = $maxCategoryCount > 0 ? ($category['count'] / $maxCategoryCount) * 100 : 0;
                                    ?>
                                    <div class="category-item">
                                        <div class="category-info">
                                            <span class="category-name"><?php echo htmlspecialchars($category['category']); ?></span>
                                            <span class="category-count"><?php echo $category['count']; ?> items</span>
                                        </div>
                                        <div class="category-bar">
                                            <div class="category-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <div class="category-quantity">
                                            Total: <?php echo number_format($category['total_quantity'], 2); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-report">
                                    <p>No category data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-header">
                            <h2>Top Farmers</h2>
                            <p>Most active contributors</p>
                        </div>
                        <div class="report-content">
                            <?php if (!empty($reports['top_farmers'])): ?>
                                <div class="status-list">
                                    <?php foreach ($reports['top_farmers'] as $farmer): ?>
                                        <div class="status-item">
                                            <div class="status-info">
                                                <span class="status-name"><?php echo htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?></span>
                                                <span class="status-count"><?php echo $farmer['item_count']; ?> items</span>
                                            </div>
                                            <div class="status-bar">
                                                <div class="status-bar-fill" style="width: 100%; background-color: #2563eb;"></div>
                                            </div>
                                            <div class="status-percentage">
                                                <?php echo number_format($farmer['total_quantity'], 2); ?> units
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-report">
                                    <p>No farmer data to display</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-header">
                            <h2>Stock Distribution</h2>
                            <p>Inventory status overview</p>
                        </div>
                        <div class="report-content">
                            <?php if (!empty($reports['status_breakdown'])):
                                $statusLabels = [
                                    'in_stock' => 'In Stock',
                                    'low_stock' => 'Low Stock',
                                    'out_of_stock' => 'Out of Stock'
                                ];
                                $statusColors = [
                                    'in_stock' => '#059669',
                                    'low_stock' => '#d97706',
                                    'out_of_stock' => '#dc2626'
                                ];
                                $totalStatus = array_sum(array_column($reports['status_breakdown'], 'total'));
                                ?>
                                <div class="status-list">
                                    <?php foreach ($reports['status_breakdown'] as $status):
                                        $percentage = $totalStatus > 0 ? ($status['total'] / $totalStatus) * 100 : 0;
                                        ?>
                                        <div class="status-item">
                                            <div class="status-info">
                                                <span class="status-dot" style="background-color: <?php echo $statusColors[$status['status']] ?? '#64748b'; ?>"></span>
                                                <span class="status-name"><?php echo $statusLabels[$status['status']] ?? 'Unknown'; ?></span>
                                                <span class="status-count"><?php echo $status['total']; ?></span>
                                            </div>
                                            <div class="status-bar">
                                                <div class="status-bar-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $statusColors[$status['status']] ?? '#64748b'; ?>"></div>
                                            </div>
                                            <div class="status-percentage"><?php echo number_format($percentage, 1); ?>%</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-report">
                                    <p>No status data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($reports['low_stock_items'])): ?>
                    <div class="report-card report-warning">
                        <div class="report-header">
                            <h2>Low Stock Alerts</h2>
                            <p>Items that need attention</p>
                        </div>
                        <div class="report-content">
                            <div class="alert-list">
                                <?php foreach ($reports['low_stock_items'] as $item): ?>
                                    <div class="alert-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="alert-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                        </div>
                                        <div class="alert-item-quantity">
                                            <?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                        </div>
                                        <span class="alert-date"><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($reports['out_of_stock_items'])): ?>
                    <div class="report-card report-error">
                        <div class="report-header">
                            <h2>Out of Stock</h2>
                            <p>Products to restock</p>
                        </div>
                        <div class="report-content">
                            <div class="alert-list">
                                <?php foreach ($reports['out_of_stock_items'] as $item): ?>
                                    <div class="alert-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="alert-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                        </div>
                                        <span class="alert-date"><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($reports['recent_items'])): ?>
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Recent Additions</h2>
                            <p>Latest products from farmers</p>
                        </div>
                        <div class="report-content">
                            <div class="recent-list">
                                <?php foreach ($reports['recent_items'] as $item): ?>
                                    <div class="recent-item">
                                        <div class="recent-item-info">
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="recent-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                        </div>
                                        <div class="recent-item-details">
                                            <span><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></span>
                                            <span class="recent-date"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
                                        </div>
                                        <?php if (!empty($item['firstName'])): ?>
                                        <div class="recent-item-farmer">
                                            by <?php echo htmlspecialchars($item['firstName'] . ' ' . $item['lastName']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

