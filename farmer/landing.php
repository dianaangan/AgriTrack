<?php 
session_start();

// If user is logged in, redirect to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriTrack - Farm Inventory Management</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2">
    <link rel="stylesheet" href="../css/landing.styles.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-text">AgriTrack</span>
                </div>

                <nav class="nav">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#about" class="nav-link">About</a>
                </nav>

                <div class="header-buttons">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                        <span style="color: #64748b; font-size: 0.875rem;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <a href="logout.php" class="btn btn-ghost">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-ghost">Login</a>
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
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
                        <div class="hero-badge">Smart Farm Operations</div>
                        
                        <div class="hero-heading">
                            <h1>Track Inventory. <span class="gradient-text">Plan Harvests.</span></h1>
                            <p>Keep inputs and outputs organized, schedule harvests with confidence, and use clear insights to reduce waste and grow profitability.</p>
                        </div>

                        <div class="hero-buttons">
                            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                <a href="home.php" class="btn btn-primary btn-large">Go to Home</a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-primary btn-large">Sign Up</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hero-image animate-on-scroll" data-animate="right">
                        <div class="image-container">
                            <img src="images/landing.jpg" alt="Modern farmer using AgriTrack on tablet in greenhouse">
                            <div class="image-overlay"></div>
                        </div>

                        <div class="floating-stat floating-stat-left">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Stock Accuracy</div>
                        </div>

                        <div class="floating-stat floating-stat-right">
                            <div class="stat-number">2×</div>
                            <div class="stat-label">Faster Planning</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="section-header animate-on-scroll" data-animate="up">
                    <h2>Simple inventory management for farmers</h2>
                    <p>Keep track of your crops, livestock, and goods with an easy-to-use inventory system designed specifically for agricultural operations.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/add-products.jpg" alt="Adding new farm products" />
                            </div>
                            <div class="feature-text">
                                <h3>Add New Products</h3>
                                <p>Easily add crops, livestock, and goods to your inventory with detailed information and categorization.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/update-details.jpg" alt="Updating product details" />
                            </div>
                            <div class="feature-text">
                                <h3>Update Product Details</h3>
                                <p>Modify product information including names, prices, and quantities to keep your inventory current.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/view-inventory.jpg" alt="Viewing current inventory" />
                            </div>
                            <div class="feature-text">
                                <h3>View Current Inventory</h3>
                                <p>Access a comprehensive list of all your inventory items with real-time quantity and status updates.</p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card animate-on-scroll" data-animate="up">
                        <div class="feature-content">
                            <div class="feature-icon">
                                <img src="images/manage-stock.jpg" alt="Managing stock status" />
                            </div>
                            <div class="feature-text">
                                <h3>Manage Stock Status</h3>
                                <p>Remove items or mark them as out of stock to maintain accurate inventory records.</p>
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
                    <h2>Trusted by farmers worldwide</h2>
                    <p>Replace spreadsheets with a single, reliable source of truth that your whole team can use in the field or in the office.</p>
                </div>

                <div class="stats-grid animate-on-scroll" data-animate="up">
                    <div class="stat-card">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-title">Stock Accuracy</div>
                        <div class="stat-description">Precise inventory tracking with real-time updates</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">2×</div>
                        <div class="stat-title">Faster Harvest Planning</div>
                        <div class="stat-description">Streamlined scheduling saves hours of manual work</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">−25%</div>
                        <div class="stat-title">Input Waste Reduction</div>
                        <div class="stat-description">Smart analytics help optimize resource usage</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number">500+</div>
                        <div class="stat-title">Farms Trust AgriTrack</div>
                        <div class="stat-description">Growing community of successful farmers</div>
                    </div>
                </div>

                <div class="stats-footer animate-on-scroll" data-animate="up">
                    <span>Join thousands of farmers who have transformed their operations</span>
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
