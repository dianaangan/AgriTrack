<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AgriTrack</title>
    <link rel="stylesheet" href="/css/landing.styles.css">
    <style>
        .dashboard {
            padding: 2rem 0;
            min-height: calc(100vh - 4rem);
        }
        .dashboard-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .dashboard-header {
            margin-bottom: 2rem;
        }
        .dashboard-header h1 {
            font-size: 2rem;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .dashboard-header p {
            color: #64748b;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .dashboard-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.5);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .dashboard-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }
        .dashboard-card p {
            color: #64748b;
            margin-bottom: 1rem;
        }
        .btn-dashboard {
            background-color: #059669;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-dashboard:hover {
            background-color: rgba(5, 150, 105, 0.9);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="/landing.php" class="logo-text">AgriTrack</a>
                </div>

                <nav class="nav">
                    <a href="/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="#inventory" class="nav-link">Inventory</a>
                    <a href="#reports" class="nav-link">Reports</a>
                </nav>

                <div class="header-buttons">
                    <span style="color: #64748b; font-size: 0.875rem;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="/logout.php" class="btn btn-ghost">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Welcome to your Dashboard</h1>
                <p>Manage your farm inventory and track your agricultural operations.</p>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Add New Products</h3>
                    <p>Add crops, livestock, and goods to your inventory with detailed information and categorization.</p>
                    <a href="#" class="btn-dashboard">Add Products</a>
                </div>

                <div class="dashboard-card">
                    <h3>View Inventory</h3>
                    <p>Access a comprehensive list of all your inventory items with real-time quantity and status updates.</p>
                    <a href="#" class="btn-dashboard">View Inventory</a>
                </div>

                <div class="dashboard-card">
                    <h3>Update Details</h3>
                    <p>Modify product information including names, prices, and quantities to keep your inventory current.</p>
                    <a href="#" class="btn-dashboard">Update Details</a>
                </div>

                <div class="dashboard-card">
                    <h3>Manage Stock</h3>
                    <p>Remove items or mark them as out of stock to maintain accurate inventory records.</p>
                    <a href="#" class="btn-dashboard">Manage Stock</a>
                </div>

                <div class="dashboard-card">
                    <h3>Reports</h3>
                    <p>Generate reports on your inventory, sales, and farm operations for better decision making.</p>
                    <a href="#" class="btn-dashboard">View Reports</a>
                </div>

                <div class="dashboard-card">
                    <h3>Settings</h3>
                    <p>Configure your account settings, farm details, and notification preferences.</p>
                    <a href="#" class="btn-dashboard">Settings</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
