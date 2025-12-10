<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

$currentPage = 'farmers';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();
$stats = [
    'total_farmers' => 0,
    'new_week' => 0,
    'new_month' => 0
];
$farmers = [];

if ($pdo) {
    try {
        // Stats
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM farmers");
        $stats['total_farmers'] = $stmt->fetch()['count'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM farmers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['new_week'] = $stmt->fetch()['count'] ?? 0;

        $stmt = $pdo->query("SELECT COUNT(*) as count FROM farmers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['new_month'] = $stmt->fetch()['count'] ?? 0;

        // Farmer list
        $stmt = $pdo->query("SELECT Id, firstName, lastName, email, created_at FROM farmers ORDER BY created_at DESC");
        $farmers = $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        error_log("Admin farmers error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers - AgriTrack Admin</title>
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

        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="content-title">Farmers</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Manage all registered farmers in the platform</p>
                </div>
            </header>

            <div class="content-body admin-dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stat-label">Total Farmers</div>
                            <div class="stat-value"><?php echo $stats['total_farmers']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📆</div>
                        <div class="stat-content">
                            <div class="stat-label">New (Last 7 Days)</div>
                            <div class="stat-value"><?php echo $stats['new_week']; ?></div>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">🗓️</div>
                        <div class="stat-content">
                            <div class="stat-label">New (Last 30 Days)</div>
                            <div class="stat-value"><?php echo $stats['new_month']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="inventory-section">
                    <?php if (isset($_SESSION['admin_success_message'])): ?>
                        <div class="alert alert-success" style="margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($_SESSION['admin_success_message']); unset($_SESSION['admin_success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['admin_error_message'])): ?>
                        <div class="alert alert-error" style="margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($_SESSION['admin_error_message']); unset($_SESSION['admin_error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="section-header" style="flex-wrap: wrap;">
                        <div>
                            <h2>Farmer Directory</h2>
                            <p>Search and manage farmer accounts</p>
                        </div>
                        <div class="table-controls" id="farmer-filter-form">
                            <input type="search" id="farmer-search" class="search-input compact" placeholder="Search farmers...">
                            <select id="farmer-sort" class="filter-select">
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                                <option value="name_az">Name A–Z</option>
                                <option value="name_za">Name Z–A</option>
                            </select>
                            <select id="farmer-status" class="filter-select">
                                <option value="">All Status</option>
                                <option value="active" selected>Active</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($farmers)): ?>
                        <div class="empty-state">
                            <p>No farmers found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="inventory-table farmers-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($farmers as $farmer): ?>
                                    <tr data-name="<?php echo strtolower(htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName'])); ?>"
                                        data-email="<?php echo strtolower(htmlspecialchars($farmer['email'])); ?>"
                                        data-joined="<?php echo strtotime($farmer['created_at']); ?>"
                                        data-status="active">
                                        <td>
                                            <div class="farmer-name-cell">
                                                <div class="farmer-avatar">
                                                    <?php echo strtoupper(substr($farmer['firstName'], 0, 1) . substr($farmer['lastName'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="farmer-name"><?php echo htmlspecialchars($farmer['firstName'] . ' ' . $farmer['lastName']); ?></div>
                                                    <div class="farmer-name-email"><?php echo htmlspecialchars($farmer['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="farmer-email-desktop"><?php echo htmlspecialchars($farmer['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($farmer['created_at'])); ?></td>
                                        <td><span class="status-badge status-active">Active</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_edit_farmer.php?id=<?php echo $farmer['Id']; ?>" class="btn-icon" title="Edit">
                                                    ✏️
                                                </a>
                                                <form action="admin_delete_farmer.php" method="POST" onsubmit="return confirm('Delete this farmer account? This cannot be undone.');">
                                                    <input type="hidden" name="farmer_id" value="<?php echo $farmer['Id']; ?>">
                                                    <button type="submit" class="btn-icon btn-danger" title="Delete">🗑️</button>
                                                </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('farmer-search');
            const sortSelect = document.getElementById('farmer-sort');
            const statusSelect = document.getElementById('farmer-status');
            const tableBody = document.querySelector('.farmers-table tbody');
            const allRows = Array.from(tableBody.querySelectorAll('tr'));

            function sortRows(rows, sortValue) {
                return rows.sort((a, b) => {
                    switch (sortValue) {
                        case 'oldest':
                            return a.dataset.joined - b.dataset.joined;
                        case 'name_az':
                            return a.dataset.name.localeCompare(b.dataset.name);
                        case 'name_za':
                            return b.dataset.name.localeCompare(a.dataset.name);
                        case 'newest':
                        default:
                            return b.dataset.joined - a.dataset.joined;
                    }
                });
            }

            function filterRows() {
                const query = searchInput.value.toLowerCase();
                const status = statusSelect.value;

                let filtered = allRows.filter(row => {
                    const name = row.dataset.name;
                    const email = row.dataset.email;
                    const rowStatus = row.dataset.status;

                    const matchesSearch = !query || name.includes(query) || email.includes(query);
                    const matchesStatus = !status || status === rowStatus;

                    return matchesSearch && matchesStatus;
                });

                const sorted = sortRows(filtered, sortSelect.value);

                tableBody.innerHTML = '';
                sorted.forEach(row => tableBody.appendChild(row));
            }

            searchInput.addEventListener('input', filterRows);
            sortSelect.addEventListener('change', filterRows);
            statusSelect.addEventListener('change', filterRows);

            filterRows();
        });
    </script>
</body>
</html>

