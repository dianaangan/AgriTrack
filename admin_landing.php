<?php 
session_start();

// If admin is logged in, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - AgriTrack</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/landing.styles.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-text">AgriTrack Admin</span>
                </div>

                <nav class="nav">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#about" class="nav-link">About</a>
                </nav>

                <div class="header-buttons">
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <span style="color: #64748b; font-size: 0.875rem;">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <a href="admin_logout.php" class="btn btn-ghost">Logout</a>
                    <?php else: ?>
                        <a href="admin_login.php" class="btn btn-primary">Admin Login</a>
                        <a href="landing.php" class="btn btn-ghost">Farmer Portal</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text animate-on-scroll" data-animate="left">
                        <div class="hero-badge">Admin Portal</div>
                        
                        <div class="hero-heading">
                            <h1>Admin Dashboard. <span class="gradient-text">Manage Everything.</span></h1>
                            <p>Monitor all farmers, track inventory across the platform, generate comprehensive reports, and manage the AgriTrack system with powerful admin tools.</p>
                        </div>

                        <div class="hero-buttons">
                            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                                <a href="admin_dashboard.php" class="btn btn-primary btn-large">Go to Dashboard</a>
                            <?php else: ?>
                                <a href="admin_login.php" class="btn btn-primary btn-large">Admin Login</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hero-image animate-on-scroll" data-animate="right">
                        <div class="image-container">
                            <img src="images/landing.jpg" alt="Admin dashboard for AgriTrack">
                            <div class="image-overlay"></div>
                        </div>

                        <div class="floating-stat floating-stat-left">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">System Control</div>
                        </div>

                        <div class="floating-stat floating-stat-right">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Monitoring</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="section-header animate-on-scroll" data-animate="up">
                    <h2>Complete admin control for AgriTrack</h2>
                    <p>Manage all farmers, monitor inventory, generate reports, and maintain system-wide oversight with comprehensive admin tools.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/add-products.jpg" alt="Manage farmers" />
                            </div>
                            <div class="feature-text">
                                <h3>Manage Farmers</h3>
                                <p>View all registered farmers, their accounts, and manage farmer profiles across the platform.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/update-details.jpg" alt="View all inventory" />
                            </div>
                            <div class="feature-text">
                                <h3>View All Inventory</h3>
                                <p>Monitor inventory items from all farmers, track quantities, and view system-wide inventory statistics.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/view-inventory.jpg" alt="Generate reports" />
                            </div>
                            <div class="feature-text">
                                <h3>Generate Reports</h3>
                                <p>Create comprehensive reports on farmers, inventory, and platform usage for better decision making.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/manage-stock.jpg" alt="System management" />
                            </div>
                            <div class="feature-text">
                                <h3>System Management</h3>
                                <p>Maintain system health, manage admin accounts, and oversee platform operations.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section id="about" class="stats">
            <div class="container">
                <div class="section-header animate-on-scroll" data-animate="up">
                    <h2>Powerful admin tools</h2>
                    <p>Complete control and visibility over the entire AgriTrack platform with advanced administrative features.</p>
                </div>

                <div class="stats-grid animate-on-scroll" data-animate="up">
                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-title">Platform Control</div>
                        <div class="stat-description">Full administrative access to all features</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">24/7</div>
                        <div class="stat-title">System Monitoring</div>
                        <div class="stat-description">Real-time tracking of all platform activities</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">∞</div>
                        <div class="stat-title">Unlimited Access</div>
                        <div class="stat-description">View and manage all farmers and inventory</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">100%</div>
                        <div class="stat-title">Data Visibility</div>
                        <div class="stat-description">Complete insights into platform usage</div>
                    </div>
                </div>

                <div class="stats-footer animate-on-scroll" data-animate="up">
                    <span>Manage the entire AgriTrack platform with confidence</span>
                </div>
            </div>
        </section>
    </main>
    <script>
    (function() {
        // IntersectionObserver to trigger animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

        document.querySelectorAll('.animate-on-scroll').forEach((el) => observer.observe(el));

        // Smooth navigation handling for header links
        const headerHeight = document.querySelector('.header')?.offsetHeight || 64;
        document.querySelectorAll('a.nav-link[href^="#"]').forEach((link) => {
            link.addEventListener('click', (e) => {
                const targetId = link.getAttribute('href');
                if (!targetId || targetId === '#') return;
                const target = document.querySelector(targetId);
                if (!target) return;
                e.preventDefault();
                const top = target.getBoundingClientRect().top + window.pageYOffset - headerHeight + 1;
                window.scrollTo({ top, behavior: 'smooth' });
            });
        });
    })();
    </script>
</body>
</html>

