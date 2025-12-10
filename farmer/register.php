<?php
session_start();

// Include database functions
require_once __DIR__ . '/../includes/farmer_functions.php';

// Handle registration form submission
$firstNameError = '';
$lastNameError = '';
$emailError = '';
$passwordError = '';
$confirmPasswordError = '';
$firstNameValue = '';
$lastNameValue = '';
$emailValue = '';
$passwordValue = '';
$confirmPasswordValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? '')); // Normalize email to lowercase
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Field-specific validation
    if (empty($firstName)) {
        $firstNameError = 'Please fill in first name';
    }
    if (empty($lastName)) {
        $lastNameError = 'Please fill in last name';
    }
    if (empty($email)) {
        $emailError = 'Please fill in email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = 'Please enter a valid email address';
        $emailValue = '';
    }
    if (empty($password)) {
        $passwordError = 'Please fill in password';
    } elseif (strlen($password) < 6) {
        $passwordError = 'Password must be at least 6 characters';
        $passwordValue = '';
    }
    if (empty($confirmPassword)) {
        $confirmPasswordError = 'Please confirm password';
    } elseif ($password !== $confirmPassword && !empty($password)) {
        $confirmPasswordError = 'Passwords do not match';
        $confirmPasswordValue = '';
    }
    
    // If no validation errors, proceed with registration
    if (empty($firstNameError) && empty($lastNameError) && empty($emailError) && empty($passwordError) && empty($confirmPasswordError)) {
        // Register farmer in database
        $registerResult = registerFarmer($firstName, $lastName, $email, $password);
        
        if ($registerResult['success']) {
            // Redirect to login page with success message
            $_SESSION['registration_success'] = 'Account created successfully! Please sign in to continue.';
            header('Location: login.php');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            exit;
        } else {
            // Show error in email field if email already exists
            if (strpos($registerResult['message'], 'email') !== false || strpos($registerResult['message'], 'already') !== false) {
                $emailError = $registerResult['message'];
                $emailValue = '';
            } else {
                $emailError = 'Registration failed';
                $emailValue = '';
            }
        }
    } else {
        // Clear values on error
        $firstNameValue = empty($firstNameError) ? $firstName : '';
        $lastNameValue = empty($lastNameError) ? $lastName : '';
        $emailValue = empty($emailError) ? $email : '';
        $passwordValue = '';
        $confirmPasswordValue = '';
    }
} else {
    // On GET request, preserve values if they were submitted
    $firstNameValue = htmlspecialchars($_POST['firstName'] ?? '');
    $lastNameValue = htmlspecialchars($_POST['lastName'] ?? '');
    $emailValue = htmlspecialchars($_POST['email'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Register - AgriTrack</title>
	<link rel="icon" type="image/png" href="../images/agritrack_logo.png?v=3">
	<link rel="stylesheet" href="../css/landing.styles.css">
	<link rel="stylesheet" href="../css/register.css">
</head>
<body>
	<header class="header">
		<div class="container">
			<div class="header-content">
				<div class="logo">
					<a href="landing.php" class="logo-text">Agr<span class="logo-i">i</span>Track</a>
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
							<div class="name-fields">
								<div class="form-row">
									<label for="firstName">First name</label>
									<input type="text" id="firstName" name="firstName" 
										class="<?php echo $firstNameError ? 'input-error' : ''; ?>"
										placeholder="<?php echo $firstNameError ? htmlspecialchars($firstNameError) : 'Enter your first name'; ?>" 
										required 
										value="<?php echo $firstNameValue; ?>" />
								</div>
								<div class="form-row">
									<label for="lastName">Last name</label>
									<input type="text" id="lastName" name="lastName" 
										class="<?php echo $lastNameError ? 'input-error' : ''; ?>"
										placeholder="<?php echo $lastNameError ? htmlspecialchars($lastNameError) : 'Enter your last name'; ?>" 
										required 
										value="<?php echo $lastNameValue; ?>" />
								</div>
							</div>

							<div class="form-row">
								<label for="email">Email</label>
								<input type="email" id="email" name="email" 
									class="<?php echo $emailError ? 'input-error' : ''; ?>"
									placeholder="<?php echo $emailError ? htmlspecialchars($emailError) : 'you@example.com'; ?>" 
									required 
									value="<?php echo $emailValue; ?>" />
							</div>

							<div class="form-row">
								<label for="password">Password</label>
								<input type="password" id="password" name="password" 
									class="<?php echo $passwordError ? 'input-error' : ''; ?>"
									placeholder="<?php echo $passwordError ? htmlspecialchars($passwordError) : 'At least 6 characters'; ?>" 
									minlength="6" 
									required />
							</div>

							<div class="form-row">
								<label for="confirmPassword">Confirm password</label>
								<input type="password" id="confirmPassword" name="confirmPassword" 
									class="<?php echo $confirmPasswordError ? 'input-error' : ''; ?>"
									placeholder="<?php echo $confirmPasswordError ? htmlspecialchars($confirmPasswordError) : 'Re-enter your password'; ?>" 
									minlength="6" 
									required />
							</div>

							<div class="form-actions">
								<button type="submit" class="btn btn-primary btn-large">Create account</button>
								<p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
							</div>
						</form>
					</div>
				</div>
			</div>
		</section>
	</main>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('register-form');
			const firstNameInput = document.getElementById('firstName');
			const lastNameInput = document.getElementById('lastName');
			const emailInput = document.getElementById('email');
			const passwordInput = document.getElementById('password');
			const confirmPasswordInput = document.getElementById('confirmPassword');

			// Clear error state when user starts typing
			firstNameInput.addEventListener('input', function() {
				if (this.classList.contains('input-error')) {
					this.classList.remove('input-error');
					this.placeholder = 'Enter your first name';
				}
			});

			lastNameInput.addEventListener('input', function() {
				if (this.classList.contains('input-error')) {
					this.classList.remove('input-error');
					this.placeholder = 'Enter your last name';
				}
			});

			emailInput.addEventListener('input', function() {
				if (this.classList.contains('input-error')) {
					this.classList.remove('input-error');
					this.placeholder = 'you@example.com';
				}
			});

			passwordInput.addEventListener('input', function() {
				if (this.classList.contains('input-error')) {
					this.classList.remove('input-error');
					this.placeholder = 'At least 6 characters';
				}
			});

			confirmPasswordInput.addEventListener('input', function() {
				if (this.classList.contains('input-error')) {
					this.classList.remove('input-error');
					this.placeholder = 'Re-enter your password';
				}
			});
		});
	</script>
</body>
</html>
