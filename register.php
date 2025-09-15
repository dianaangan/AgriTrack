<?php
session_start();

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Simple validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Simple file-based storage for demo (replace with database)
        $users_file = 'data/users.txt';
        $users = [];
        
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true) ?: [];
        }
        
        // Check if email already exists
        if (isset($users[$email])) {
            $error = 'An account with this email already exists';
        } else {
            // Add new user
            $users[$email] = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Ensure data directory exists
            if (!is_dir('data')) {
                mkdir('data', 0755, true);
            }
            
            if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $firstName;
                header('Location: /landing.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Register - AgriTrack</title>
	<link rel="stylesheet" href="/css/landing.styles.css">
	<link rel="stylesheet" href="/css/register.css">
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
							<h1>Create your agritrack account</h1>
							<p style="color: green;">Sign up to start tracking inventory and manage your farm with ease.</p>
						</div>
					</div>

					<div>
						<form id="register-form" class="form" method="POST">
							<?php if (isset($error)): ?>
								<div style="color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
									<?php echo htmlspecialchars($error); ?>
								</div>
							<?php endif; ?>
							
							<div class="name-fields">
								<div class="form-row">
									<label for="firstName">First name</label>
									<input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>" />
								</div>
								<div class="form-row">
									<label for="lastName">Last name</label>
									<input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>" />
								</div>
							</div>

							<div class="form-row">
								<label for="email">Email</label>
								<input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
							</div>

							<div class="form-row">
								<label for="password">Password</label>
								<input type="password" id="password" name="password" placeholder="At least 6 characters" minlength="6" required />
							</div>

							<div class="form-row">
								<label for="confirmPassword">Confirm password</label>
								<input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" minlength="6" required />
							</div>

							<div class="form-actions">
								<button type="submit" class="btn btn-primary btn-large">Create account</button>
								<p class="auth-footer">Already have an account? <a href="/login.php">Sign in</a></p>
							</div>
						</form>
					</div>
				</div>
			</div>
		</section>
	</main>
</body>
</html>
