<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$currentPage = 'inventory';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();
$stats = [
    'total_items' => 0,
    'unique_farmers' => 0,
    'low_stock' => 0,
    'inventory_value' => 0
];
$inventoryItems = [];
$farmersList = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory");
        $stats['total_items'] = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(DISTINCT farmer_id) as total FROM inventory");
        $stats['unique_farmers'] = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventory WHERE status IN ('low_stock','out_of_stock')");
        $stats['low_stock'] = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("SELECT SUM(quantity * price) as total FROM inventory WHERE price IS NOT NULL");
        $stats['inventory_value'] = $stmt->fetch()['total'] ?? 0;

        $stmt = $pdo->query("
            SELECT i.*, f.firstName, f.lastName, f.email
            FROM inventory i
            JOIN farmers f ON i.farmer_id = f.Id
            ORDER BY i.created_at DESC
        ");
        $inventoryItems = $stmt->fetchAll() ?: [];

        foreach ($inventoryItems as $item) {
            $farmersList[$item['farmer_id']] = $item['firstName'] . ' ' . $item['lastName'];
        }
        asort($farmersList);
    } catch (PDOException $e) {
        error_log("Admin inventory error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Inventory - AgriTrack Admin</title>
    <link rel="icon" type="image/png" href="../images/agritrack_logo.png?v=3">
    <style>
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

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="content-title">All Inventory</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">View and monitor every product added by farmers</p>
                </div>
            </header>

            <div class="content-body admin-dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Products</div>
                            <div class="stat-value"><?php echo $stats['total_items']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stat-label">Farmers Contributing</div>
                            <div class="stat-value"><?php echo $stats['unique_farmers']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <div class="stat-label">Low / Out of Stock</div>
                            <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-error">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-label">Inventory Value</div>
                            <div class="stat-value">₱<?php echo number_format($stats['inventory_value'], 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="inventory-section">
                    <div class="section-header">
                        <h2>Product Directory</h2>
                        <div class="table-controls">
                            <input type="search" id="inventory-search" class="search-input compact" placeholder="Search products or farmers...">
                            <select id="inventory-status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($inventoryItems)): ?>
                        <div class="empty-state">
                            <h3>No inventory found</h3>
                            <p>Once farmers add products, they will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="inventory-table admin-products-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Farmer</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventoryItems as $item): ?>
                                    <tr data-name="<?php echo strtolower(htmlspecialchars($item['product_name'] . ' ' . $item['firstName'] . ' ' . $item['lastName'])); ?>"
                                        data-farmer="<?php echo htmlspecialchars($item['farmer_id']); ?>"
                                        data-status="<?php echo htmlspecialchars($item['status']); ?>">
                                        <td>
                                            <div class="product-details">
                                                <?php if (!empty($item['image_path'])): ?>
                                                    <div class="product-thumb">
                                                        <img src="<?php echo htmlspecialchars('../' . ltrim($item['image_path'], '/')); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="product-thumb placeholder">📦</div>
                                                <?php endif; ?>
                                                <div class="product-name">
                                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <span class="product-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?><?php echo strlen($item['description']) > 50 ? '...' : ''; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="farmer-name-cell">
                                                <div class="farmer-avatar">
                                                    <?php echo strtoupper(substr($item['firstName'], 0, 1) . substr($item['lastName'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="farmer-name"><?php echo htmlspecialchars($item['firstName'] . ' ' . $item['lastName']); ?></div>
                                                    <div class="farmer-name-email"><?php echo htmlspecialchars($item['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                        <td><span class="quantity-value"><?php echo number_format($item['quantity'], 2); ?></span> <span class="text-muted"><?php echo htmlspecialchars($item['unit']); ?></span></td>
                                        <td>
                                            <?php if (!is_null($item['price'])): ?>
                                                ₱<?php echo number_format($item['price'], 2); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch ($item['status']) {
                                                case 'in_stock':
                                                    $statusClass = 'status-success';
                                                    $statusText = 'In Stock';
                                                    break;
                                                case 'low_stock':
                                                    $statusClass = 'status-warning';
                                                    $statusText = 'Low Stock';
                                                    break;
                                                case 'out_of_stock':
                                                default:
                                                    $statusClass = 'status-error';
                                                    $statusText = 'Out of Stock';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('inventory-search');
            const statusFilter = document.getElementById('inventory-status');
            const tableBody = document.querySelector('.admin-products-table tbody');
            const allRows = Array.from(tableBody.querySelectorAll('tr'));

            function filterRows() {
                const query = searchInput.value.toLowerCase();
                const status = statusFilter.value;

                allRows.forEach(row => {
                    const matchesSearch = !query || row.dataset.name.includes(query);
                    const matchesStatus = !status || row.dataset.status === status;

                    row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
                });
            }

            filterRows();
            searchInput.addEventListener('input', filterRows);
            statusFilter.addEventListener('change', filterRows);
        });
    </script>
</body>
</html>
