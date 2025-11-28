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

// Include farmer functions
require_once __DIR__ . '/includes/farmer_functions.php';

// Get current farmer ID
$farmerId = $_SESSION['farmer_id'] ?? null;

// Get farmer data
$farmer = $farmerId ? getFarmerById($farmerId) : null;

if (!$farmer) {
    $_SESSION['error_message'] = 'Unable to load profile information';
    header('Location: home.php');
    exit;
}

$profileError = '';
$profileSuccess = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $farmerId) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($firstName)) {
        $profileError = 'First name is required';
    } elseif (empty($lastName)) {
        $profileError = 'Last name is required';
    } elseif (empty($email)) {
        $profileError = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileError = 'Please enter a valid email address';
    } else {
        $data = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email
        ];
        
        $result = updateFarmerProfile($farmerId, $data);
        
        if ($result['success']) {
            $profileSuccess = true;
            // Update session data
            $_SESSION['farmer_firstName'] = $firstName;
            $_SESSION['farmer_lastName'] = $lastName;
            $_SESSION['farmer_email'] = $email;
            // Reload farmer data
            $farmer = getFarmerById($farmerId);
        } else {
            $profileError = $result['message'];
        }
    }
}


// Pre-fill form data
$firstName = isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : htmlspecialchars($farmer['firstName'] ?? '');
$lastName = isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : htmlspecialchars($farmer['lastName'] ?? '');
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($farmer['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AgriTrack</title>
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
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/settings.css' : 'css/settings.css'; ?>?v=<?php echo time(); ?>">
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
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item active">
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
                    <h1 class="content-title">Settings</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Manage your account and preferences</p>
                </div>
            </header>

            <div class="content-body">
                <div class="settings-container">
                    <!-- Profile Information Section -->
                    <div class="settings-section">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h2>Profile Information</h2>
                                <p>Update your personal information</p>
                            </div>

                            <?php if ($profileError): ?>
                                <div class="alert alert-error">
                                    <span>⚠️</span>
                                    <span><?php echo htmlspecialchars($profileError); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($profileSuccess): ?>
                                <div class="alert alert-success" id="success-alert">
                                    <span>✅</span>
                                    <span>Profile updated successfully!</span>
                                </div>
                            <?php endif; ?>

                            <div class="profile-display" id="profile-display">
                                <div class="profile-info">
                                    <div class="info-row">
                                        <span class="info-label">First Name</span>
                                        <span class="info-value" id="display-firstName"><?php echo htmlspecialchars($farmer['firstName'] ?? ''); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Last Name</span>
                                        <span class="info-value" id="display-lastName"><?php echo htmlspecialchars($farmer['lastName'] ?? ''); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Email Address</span>
                                        <span class="info-value" id="display-email"><?php echo htmlspecialchars($farmer['email'] ?? ''); ?></span>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="button" class="btn-primary" id="edit-account-btn">Edit Account</button>
                                </div>
                            </div>

                            <form method="POST" action="settings.php" class="settings-form" id="profile-form" style="display: none;">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-row-group">
                                    <div class="form-row">
                                        <label for="firstName">First Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="firstName" 
                                            name="firstName" 
                                            required
                                            value="<?php echo $firstName; ?>"
                                        >
                                    </div>
                                    <div class="form-row">
                                        <label for="lastName">Last Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="lastName" 
                                            name="lastName" 
                                            required
                                            value="<?php echo $lastName; ?>"
                                        >
                                    </div>
                                </div>

                                <div class="form-row form-row-spaced">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        required
                                        value="<?php echo $email; ?>"
                                    >
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn-secondary" id="cancel-edit-btn">Cancel</button>
                                    <button type="submit" class="btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="settings-section">
                        <div class="settings-card">
                            <div class="settings-header">
                                <h2>Account Information</h2>
                                <p>Your account details</p>
                            </div>

                            <div class="account-info">
                                <div class="info-item">
                                    <span class="info-label">Account ID</span>
                                    <span class="info-value">#<?php echo htmlspecialchars($farmer['Id']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Member Since</span>
                                    <span class="info-value"><?php echo date('F d, Y', strtotime($farmer['created_at'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?php echo htmlspecialchars($farmer['email']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle edit mode
        const editBtn = document.getElementById('edit-account-btn');
        const cancelBtn = document.getElementById('cancel-edit-btn');
        const profileDisplay = document.getElementById('profile-display');
        const profileForm = document.getElementById('profile-form');
        
        editBtn.addEventListener('click', function() {
            profileDisplay.style.display = 'none';
            profileForm.style.display = 'block';
        });
        
        cancelBtn.addEventListener('click', function() {
            profileForm.style.display = 'none';
            profileDisplay.style.display = 'block';
            // Reset form values to original
            document.getElementById('firstName').value = document.getElementById('display-firstName').textContent;
            document.getElementById('lastName').value = document.getElementById('display-lastName').textContent;
            document.getElementById('email').value = document.getElementById('display-email').textContent;
        });
        
        // Auto-dismiss success alert
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 300);
            }, 3000); // Hide after 3 seconds
        }
    </script>
</body>
</html>

