<?php
session_start();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Simple hardcoded credentials for demo (replace with database)
        $valid_credentials = [
            'admin@agritrack.com' => 'password123',
            'farmer@example.com' => 'farm123'
        ];
        
        if (isset($valid_credentials[$email]) && $valid_credentials[$email] === $password) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = explode('@', $email)[0];
            header('Location: /landing.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login - AgriTrack</title>
	<link rel="stylesheet" href="/css/landing.styles.css">
	<link rel="stylesheet" href="/css/login.css">
</head>
<body>
	<header class="header">
		<div class="container">
			<div class="header-content">
				<div class="logo">
					<a href="/landing.php" class="logo-text">AgriTrack</a>
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
							<h1>Sign in to your AgriTrack account</h1>
							<p>Enter your credentials to access your inventory dashboard.</p>
						</div>
					</div>

					<div>
						<form id="login-form" class="form" method="POST">
							<?php if (isset($error)): ?>
								<div style="color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
									<?php echo htmlspecialchars($error); ?>
								</div>
							<?php endif; ?>
							
							<div class="form-row">
								<label for="email">Email</label>
								<input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
							</div>
							<div class="form-row">
								<label for="password">Password</label>
								<input type="password" id="password" name="password" placeholder="Your password" required />
							</div>
							<div class="helper-row">
								<a href="#" class="nav-link" style="font-size:.875rem;">Forgot Password?</a>
							</div>
							<div class="form-actions">
								<button type="submit" class="btn btn-primary btn-large">Sign In</button>
							</div>
							<p class="auth-footer" style="color:#64748b;">Don't have an account? <a href="/register.php">Sign Up</a></p>
						</form>
					</div>
				</div>
			</div>
		</section>
	</main>
</body>
</html>
