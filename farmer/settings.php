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
require_once __DIR__ . '/../includes/farmer_functions.php';

// Get current farmer ID
$farmerId = $_SESSION['farmer_id'] ?? null;

// Get farmer data
$farmer = $farmerId ? getFarmerById($farmerId) : null;

if (!$farmer) {
    $_SESSION['error_message'] = 'Unable to load profile information';
    header('Location: home.php');
    exit;
}

// Field-specific error variables
$firstNameError = '';
$lastNameError = '';
$emailError = '';
$profileSuccess = false;

// Field values - initialize with existing farmer data
$firstNameValue = htmlspecialchars($farmer['firstName'] ?? '');
$lastNameValue = htmlspecialchars($farmer['lastName'] ?? '');
$emailValue = htmlspecialchars($farmer['email'] ?? '');

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $farmerId) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Field-specific validation
    if (empty($firstName)) {
        $firstNameError = 'First name is required';
    }
    
    if (empty($lastName)) {
        $lastNameError = 'Last name is required';
    }
    
    if (empty($email)) {
        $emailError = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Please enter a valid email address';
        $emailValue = '';
    } else {
        // Check for duplicate email (excluding current farmer)
        if (farmerEmailExists($email, $farmerId)) {
            $emailError = 'An account with this email already exists. Please use a different email.';
            $emailValue = '';
        }
    }
    
    // If no validation errors, proceed with update
    if (empty($firstNameError) && empty($lastNameError) && empty($emailError)) {
        $data = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email
        ];
        
        $result = updateFarmerProfile($farmerId, $data);
        
        if ($result['success']) {
            $profileSuccess = true;
            // Update session data
            $_SESSION['user_name'] = $firstName;
            $_SESSION['farmer_lastName'] = $lastName;
            $_SESSION['user_email'] = $email;
            // Reload farmer data
            $farmer = getFarmerById($farmerId);
            // Update field values with new data
            $firstNameValue = htmlspecialchars($farmer['firstName'] ?? '');
            $lastNameValue = htmlspecialchars($farmer['lastName'] ?? '');
            $emailValue = htmlspecialchars($farmer['email'] ?? '');
        } else {
            // Show error in email field if database error (including duplicate check)
            $emailError = $result['message'];
            $emailValue = '';
        }
    } else {
        // Preserve valid values, clear invalid ones
        $firstNameValue = empty($firstNameError) ? $firstName : '';
        $lastNameValue = empty($lastNameError) ? $lastName : '';
        $emailValue = empty($emailError) ? $email : '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - AgriTrack</title>
    <link rel="icon" type="image/png" href="../images/agritrack_logo.png?v=3">
    <style>
        /* Critical inline styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background: radial-gradient(1200px 400px at -10% -10%, rgba(34, 197, 94, 0.06) 0%, transparent 60%), radial-gradient(800px 300px at 110% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 60%), linear-gradient(to bottom, #ffffff, #f0fdf4); width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; background: radial-gradient(1200px 400px at -10% -10%, rgba(34, 197, 94, 0.06) 0%, transparent 60%), radial-gradient(800px 300px at 110% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 60%), linear-gradient(to bottom, #ffffff, #f0fdf4); }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : '../css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/settings.css' : '../css/settings.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">Agr<span class="logo-i">i</span>Track</a>
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

                            <?php if ($profileSuccess): ?>
                                <div class="alert alert-success" id="success-alert">
                                    <span>✅</span>
                                    <span>Profile updated successfully!</span>
                                </div>
                            <?php endif; ?>

                            <div class="profile-display" id="profile-display" style="display: <?php echo ($firstNameError || $lastNameError || $emailError) ? 'none' : 'block'; ?>;">
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

                            <form method="POST" action="settings.php" class="settings-form" id="profile-form" style="display: <?php echo ($firstNameError || $lastNameError || $emailError) ? 'block' : 'none'; ?>;">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-row-group">
                                    <div class="form-row">
                                        <label for="firstName">First Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="firstName" 
                                            name="firstName" 
                                            class="<?php echo $firstNameError ? 'input-error' : ''; ?>"
                                            placeholder="<?php echo $firstNameError ? htmlspecialchars($firstNameError) : ''; ?>"
                                            required
                                            value="<?php echo $firstNameValue; ?>"
                                        >
                                    </div>
                                    <div class="form-row">
                                        <label for="lastName">Last Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="lastName" 
                                            name="lastName" 
                                            class="<?php echo $lastNameError ? 'input-error' : ''; ?>"
                                            placeholder="<?php echo $lastNameError ? htmlspecialchars($lastNameError) : ''; ?>"
                                            required
                                            value="<?php echo $lastNameValue; ?>"
                                        >
                                    </div>
                                </div>

                                <div class="form-row form-row-spaced">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        class="<?php echo $emailError ? 'input-error' : ''; ?>"
                                        placeholder="<?php echo $emailError ? htmlspecialchars($emailError) : ''; ?>"
                                        required
                                        value="<?php echo $emailValue; ?>"
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
            const firstNameInput = document.getElementById('firstName');
            const lastNameInput = document.getElementById('lastName');
            const emailInput = document.getElementById('email');
            
            firstNameInput.value = document.getElementById('display-firstName').textContent;
            lastNameInput.value = document.getElementById('display-lastName').textContent;
            emailInput.value = document.getElementById('display-email').textContent;
            
            // Clear error states
            firstNameInput.classList.remove('input-error');
            lastNameInput.classList.remove('input-error');
            emailInput.classList.remove('input-error');
            firstNameInput.placeholder = '';
            lastNameInput.placeholder = '';
            emailInput.placeholder = '';
        });
        
        // Clear error state when user starts typing
        const firstNameInput = document.getElementById('firstName');
        const lastNameInput = document.getElementById('lastName');
        const emailInput = document.getElementById('email');
        
        if (firstNameInput) {
            firstNameInput.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                    this.placeholder = '';
                }
            });
        }
        
        if (lastNameInput) {
            lastNameInput.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                    this.placeholder = '';
                }
            });
        }
        
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                    this.placeholder = '';
                }
            });
        }
        
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

