<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include inventory functions
require_once __DIR__ . '/includes/inventory_functions.php';

// Get current farmer ID
$farmerId = $_SESSION['farmer_id'] ?? null;

// Handle quick quantity update
if (isset($_POST['update_quantity']) && $farmerId) {
    $itemId = intval($_POST['item_id'] ?? 0);
    $newQuantity = floatval($_POST['quantity'] ?? 0);
    
    if ($itemId > 0 && $newQuantity >= 0) {
        $result = updateInventoryItem($itemId, $farmerId, ['quantity' => $newQuantity]);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Stock quantity updated successfully';
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        header('Location: manage_stock.php');
        exit;
    }
}

// Handle mark as out of stock
if (isset($_GET['mark_out']) && $farmerId) {
    $itemId = intval($_GET['mark_out']);
    $result = updateInventoryItem($itemId, $farmerId, ['quantity' => 0]);
    if ($result['success']) {
        $_SESSION['success_message'] = 'Item marked as out of stock';
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: manage_stock.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && $farmerId) {
    $itemId = intval($_GET['delete']);
    $result = deleteInventoryItem($itemId, $farmerId);
    if ($result['success']) {
        $_SESSION['success_message'] = $result['message'];
    } else {
        $_SESSION['error_message'] = $result['message'];
    }
    header('Location: manage_stock.php');
    exit;
}

// Get all inventory items
$inventoryResult = $farmerId ? getInventoryItems($farmerId) : ['success' => false, 'items' => []];
$inventoryItems = $inventoryResult['items'] ?? [];

// Get inventory statistics
$statsResult = $farmerId ? getInventoryStats($farmerId) : ['success' => false, 'stats' => []];
$stats = $statsResult['stats'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stock - AgriTrack</title>
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
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/manage_stock.css' : 'css/manage_stock.css'; ?>?v=<?php echo time(); ?>">
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
                <a href="#" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Reports</span>
                </a>
                <a href="#" class="nav-item">
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
                    <h1 class="content-title">Manage Stock</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Quickly update quantities, mark items out of stock, or remove items</p>
                </div>
                <div class="header-actions">
                    <a href="inventory.php" class="btn-secondary">View Inventory</a>
                </div>
            </header>

            <div class="content-body">
                <!-- Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Items</div>
                            <div class="stat-value"><?php echo $stats['total_items'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-label">In Stock</div>
                            <div class="stat-value"><?php echo $stats['in_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <div class="stat-label">Low Stock</div>
                            <div class="stat-value"><?php echo $stats['low_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-error">
                        <div class="stat-icon">❌</div>
                        <div class="stat-content">
                            <div class="stat-label">Out of Stock</div>
                            <div class="stat-value"><?php echo $stats['out_of_stock'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Stock Management Table -->
                <div class="inventory-section">
                    <div class="section-header">
                        <h2>Stock Management</h2>
                        <div class="table-controls">
                            <input type="text" id="search-input" placeholder="Search products..." class="search-input">
                            <select id="filter-status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="in_stock">In Stock</option>
                                <option value="low_stock">Low Stock</option>
                                <option value="out_of_stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($inventoryItems)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h3>No inventory items yet</h3>
                            <p>Start by adding your first product to manage stock.</p>
                            <a href="add_product.php" class="btn-primary">Add Your First Product</a>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="inventory-table stock-table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Current Quantity</th>
                                        <th>Unit</th>
                                        <th>Status</th>
                                        <th>Quick Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventoryItems as $item): ?>
                                        <tr data-status="<?php echo htmlspecialchars($item['status']); ?>">
                                            <td>
                                                <div class="product-name">
                                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="category-badge"><?php echo htmlspecialchars($item['category']); ?></span>
                                            </td>
                                            <td>
                                                <form method="POST" class="quantity-update-form" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['Id']; ?>">
                                                    <input 
                                                        type="number" 
                                                        name="quantity" 
                                                        value="<?php echo number_format($item['quantity'], 2); ?>" 
                                                        step="0.01" 
                                                        min="0" 
                                                        class="quantity-input"
                                                        required
                                                    />
                                                    <button type="submit" name="update_quantity" class="btn-update" title="Update Quantity">
                                                        <span>✓</span>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
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
                                                        $statusClass = 'status-error';
                                                        $statusText = 'Out of Stock';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($item['status'] !== 'out_of_stock'): ?>
                                                        <a href="?mark_out=<?php echo $item['Id']; ?>" 
                                                           class="btn-icon btn-warning" 
                                                           title="Mark as Out of Stock"
                                                           onclick="return confirm('Mark this item as out of stock?')">
                                                            <span>⚠️</span>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="edit_product.php?id=<?php echo $item['Id']; ?>" class="btn-icon" title="Edit Details">
                                                        <span>✏️</span>
                                                    </a>
                                                    <a href="?delete=<?php echo $item['Id']; ?>" 
                                                       class="btn-icon btn-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
                                                        <span>🗑️</span>
                                                    </a>
                                                </div>
                                            </td>
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
        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const statusFilter = document.getElementById('filter-status');
            const tableRows = document.querySelectorAll('.stock-table tbody tr');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedStatus = statusFilter.value;

                tableRows.forEach(row => {
                    const productName = row.querySelector('.product-name strong').textContent.toLowerCase();
                    const status = row.dataset.status;

                    const matchesSearch = productName.includes(searchTerm);
                    const matchesStatus = !selectedStatus || status === selectedStatus;

                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (searchInput) searchInput.addEventListener('input', filterTable);
            if (statusFilter) statusFilter.addEventListener('change', filterTable);
        });
    </script>
</body>
</html>

