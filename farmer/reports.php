<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include inventory functions
require_once __DIR__ . '/../includes/inventory_functions.php';

// Get current farmer ID
$farmerId = $_SESSION['farmer_id'] ?? null;

// Get inventory statistics
$statsResult = $farmerId ? getInventoryStats($farmerId) : ['success' => false, 'stats' => []];
$stats = $statsResult['stats'] ?? [];

// Get detailed reports
$reportsResult = $farmerId ? getInventoryReports($farmerId) : ['success' => false, 'reports' => []];
$reports = $reportsResult['reports'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - AgriTrack</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2">
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
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/reports.css' : '../css/reports.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">AgriTrack</a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="home.php" class="nav-item">
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
                <a href="reports.php" class="nav-item active">
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
                <div>
                    <h1 class="content-title">Reports</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Comprehensive inventory analytics and insights</p>
                </div>
                <div class="header-actions">
                    <a href="inventory.php" class="btn-secondary">View Inventory</a>
                </div>
            </header>

            <div class="content-body">
                <!-- Summary Cards -->
                <div class="reports-summary">
                    <div class="summary-card">
                        <div class="summary-icon">📦</div>
                        <div class="summary-content">
                            <div class="summary-label">Total Products</div>
                            <div class="summary-value"><?php echo $stats['total_items'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-success">
                        <div class="summary-icon">✅</div>
                        <div class="summary-content">
                            <div class="summary-label">In Stock</div>
                            <div class="summary-value"><?php echo $stats['in_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-warning">
                        <div class="summary-icon">⚠️</div>
                        <div class="summary-content">
                            <div class="summary-label">Low Stock</div>
                            <div class="summary-value"><?php echo $stats['low_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="summary-card summary-error">
                        <div class="summary-icon">❌</div>
                        <div class="summary-content">
                            <div class="summary-label">Out of Stock</div>
                            <div class="summary-value"><?php echo $stats['out_of_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                    <?php if (isset($reports['total_value']) && $reports['total_value'] > 0): ?>
                    <div class="summary-card summary-value">
                        <div class="summary-icon">💰</div>
                        <div class="summary-content">
                            <div class="summary-label">Total Inventory Value</div>
                            <div class="summary-value">₱<?php echo number_format($reports['total_value'], 2); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Reports Grid -->
                <div class="reports-grid">
                    <!-- Category Breakdown -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Category Breakdown</h2>
                            <p>Products by category</p>
                        </div>
                        <div class="report-content">
                            <?php if (!empty($reports['category_breakdown'])): ?>
                                <div class="category-list">
                                    <?php foreach ($reports['category_breakdown'] as $category): ?>
                                        <div class="category-item">
                                            <div class="category-info">
                                                <span class="category-name"><?php echo htmlspecialchars($category['category']); ?></span>
                                                <span class="category-count"><?php echo $category['count']; ?> items</span>
                                            </div>
                                            <div class="category-bar">
                                                <?php 
                                                $maxCount = max(array_column($reports['category_breakdown'], 'count'));
                                                $percentage = $maxCount > 0 ? ($category['count'] / $maxCount) * 100 : 0;
                                                ?>
                                                <div class="category-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <div class="category-quantity">
                                                Total: <?php echo number_format($category['total_quantity'], 2); ?> <?php echo htmlspecialchars($category['category']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-report">
                                    <p>No categories found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stock Status Distribution -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Stock Status</h2>
                            <p>Distribution of inventory status</p>
                        </div>
                        <div class="report-content">
                            <?php if (!empty($reports['status_breakdown'])): ?>
                                <div class="status-list">
                                    <?php 
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
                                    $totalStatus = array_sum(array_column($reports['status_breakdown'], 'count'));
                                    foreach ($reports['status_breakdown'] as $status): 
                                        $percentage = $totalStatus > 0 ? ($status['count'] / $totalStatus) * 100 : 0;
                                    ?>
                                        <div class="status-item">
                                            <div class="status-info">
                                                <span class="status-dot" style="background-color: <?php echo $statusColors[$status['status']] ?? '#64748b'; ?>"></span>
                                                <span class="status-name"><?php echo $statusLabels[$status['status']] ?? $status['status']; ?></span>
                                                <span class="status-count"><?php echo $status['count']; ?></span>
                                            </div>
                                            <div class="status-bar">
                                                <div class="status-bar-fill" 
                                                     style="width: <?php echo $percentage; ?>%; background-color: <?php echo $statusColors[$status['status']] ?? '#64748b'; ?>">
                                                </div>
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

                    <!-- Low Stock Alerts -->
                    <?php if (!empty($reports['low_stock_items'])): ?>
                    <div class="report-card report-alert">
                        <div class="report-header">
                            <h2>Low Stock Alerts</h2>
                            <p>Items that need restocking</p>
                        </div>
                        <div class="report-content">
                            <div class="alert-list">
                                <?php foreach ($reports['low_stock_items'] as $item): ?>
                                    <div class="alert-item">
                                        <div class="alert-item-info">
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="alert-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                        </div>
                                        <div class="alert-item-quantity">
                                            <span class="quantity-label">Current:</span>
                                            <span class="quantity-value"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></span>
                                        </div>
                                        <a href="edit_product.php?id=<?php echo $item['Id']; ?>" class="btn-alert">Update</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Out of Stock Items -->
                    <?php if (!empty($reports['out_of_stock_items'])): ?>
                    <div class="report-card report-error">
                        <div class="report-header">
                            <h2>Out of Stock</h2>
                            <p>Items currently unavailable</p>
                        </div>
                        <div class="report-content">
                            <div class="alert-list">
                                <?php foreach ($reports['out_of_stock_items'] as $item): ?>
                                    <div class="alert-item">
                                        <div class="alert-item-info">
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <span class="alert-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                        </div>
                                        <div class="alert-item-date">
                                            Last updated: <?php echo date('M d, Y', strtotime($item['updated_at'])); ?>
                                        </div>
                                        <a href="edit_product.php?id=<?php echo $item['Id']; ?>" class="btn-alert">Restock</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Additions -->
                    <?php if (!empty($reports['recent_items'])): ?>
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Recent Additions</h2>
                            <p>Latest products added to inventory</p>
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

