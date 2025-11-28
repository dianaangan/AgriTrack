<?php
session_start();

// Prevent caching so authenticated admins don't see login when using Back
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// Include admin functions
require_once __DIR__ . '/../includes/admin_functions.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Authenticate using database
        $authResult = authenticateAdmin($email, $password);
        
        if ($authResult['success']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $authResult['admin']['email'];
            $_SESSION['admin_name'] = $authResult['admin']['firstName'];
            $_SESSION['admin_id'] = $authResult['admin']['id'];
            $_SESSION['admin_lastName'] = $authResult['admin']['lastName'];
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = $authResult['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Login - AgriTrack</title>
	<link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2">
	<link rel="stylesheet" href="../css/landing.styles.css">
	<link rel="stylesheet" href="../css/login.css">
</head>
<body>
	<header class="header">
		<div class="container">
			<div class="header-content">
				<div class="logo">
					<a href="admin_landing.php" class="logo-text">AgriTrack Admin</a>
				</div>
			</div>
		</div>
	</header>

	<main>
		<section class="hero">
			<div class="container">
				<div class="hero-content">
					<div class="hero-text">
						<div class="hero-heading">
							<h1>Sign in to your AgriTrack admin account</h1>
							<p>Enter your credentials to access the admin control dashboard.</p>
						</div>
					</div>

					<div>
						<form id="login-form" class="form" method="POST">
							<!-- Loading Overlay -->
							<div id="loading-overlay" class="loading-overlay" style="display: none;">
								<div class="loading-spinner">
									<div class="spinner"></div>
									<p class="loading-text">Signing in...</p>
								</div>
							</div>
							
							<?php if (isset($error)): ?>
								<div style="color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
									<?php echo htmlspecialchars($error); ?>
								</div>
							<?php endif; ?>
							
							<div class="form-row">
								<label for="email">Email</label>
								<input type="email" id="email" name="email" placeholder="admin@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
							</div>
							<div class="form-row">
								<label for="password">Password</label>
								<input type="password" id="password" name="password" placeholder="Your password" required />
							</div>
							<div class="helper-row">
								<a href="#" class="nav-link" style="font-size:.875rem;">Forgot Password?</a>
							</div>
							<div class="form-actions">
								<button type="submit" id="submit-btn" class="btn btn-primary btn-large">
									<span class="btn-text">Sign In</span>
									<span class="btn-loader" style="display: none;">
										<svg class="spinner-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
												<animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
												<animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
											</circle>
										</svg>
									</span>
								</button>
							</div>
							<p class="auth-footer" style="color:#64748b;">Farmer portal? <a href="../farmer/landing.php">Go to farmer portal</a></p>
						</form>
					</div>
				</div>
			</div>
		</section>
	</main>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('login-form');
			const submitBtn = document.getElementById('submit-btn');
			const btnText = submitBtn.querySelector('.btn-text');
			const btnLoader = submitBtn.querySelector('.btn-loader');
			const loadingOverlay = document.getElementById('loading-overlay');
			const emailInput = document.getElementById('email');
			const passwordInput = document.getElementById('password');

			form.addEventListener('submit', function(e) {
				// Validate form before showing loading
				if (!emailInput.value || !passwordInput.value) {
					return; // Let browser validation handle it
				}

				// Show loading state
				submitBtn.disabled = true;
				btnText.style.display = 'none';
				btnLoader.style.display = 'inline-flex';
				loadingOverlay.style.display = 'flex';
				
				// Prevent double submission
				form.style.pointerEvents = 'none';
			});
		});

        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
                window.location.reload();
            }
        });
	</script>
</body>
</html>

