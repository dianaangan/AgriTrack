# AgriTrack 🌾

> A comprehensive farm inventory management system built with PHP and MySQL. Features dual-portal architecture for seamless collaboration between farmers and administrators.

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Contributing](#contributing)

## 🎯 Overview

AgriTrack is a full-stack web application designed to streamline farm inventory management operations. The system provides separate, secure portals for farmers and administrators, enabling efficient product tracking, stock management, and comprehensive reporting.

**Key Highlights:**
- 🔐 Role-based access control with separate authentication systems
- 📊 Real-time analytics and reporting dashboards
- 📷 Product image upload and management
- 🔍 Advanced search and filtering capabilities
- 📱 Responsive, modern UI design

## ✨ Features

### 👨‍🌾 Farmer Portal

- **Dashboard**: Overview of inventory statistics, recent activities, and quick actions
- **Inventory Management**: Full CRUD operations for products with image uploads and thumbnails
- **Stock Control**: Quick stock management with visual indicators and alerts
- **Reports**: Comprehensive reports with category breakdowns, status filters, and export capabilities
- **Profile Settings**: Secure account management and profile customization

### 👨‍💼 Admin Portal

- **Admin Dashboard**: Platform-wide metrics, farmer statistics, and system overview
- **Farmer Management**: Complete user administration with search, sort, edit, and delete capabilities
- **Global Inventory View**: System-wide product inventory with advanced filtering options
- **Platform Reports**: Analytics across all farmers, top performers, and alert management
- **Access Control**: Secure admin authentication and session management

## 🛠️ Tech Stack

- **Backend**: PHP 8+ with PDO for database interactions
- **Database**: MySQL 8.0+ with InnoDB engine
- **Frontend**: HTML5, CSS3 (custom styling), Vanilla JavaScript
- **Server**: XAMPP / Apache with MySQL
- **File Storage**: Local filesystem for product images

## 🏗️ Architecture

The application follows a modular architecture with clear separation of concerns:

- **MVC-inspired structure**: Organized codebase with logical separation
- **Dual-portal system**: Independent authentication and routing for farmers and admins
- **Session-based security**: Secure login/logout with cache control headers
- **Database abstraction**: PDO-based data access layer
- **Reusable components**: Shared CSS and PHP includes for maintainability

## 📦 Installation

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or similar PHP/MySQL stack)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web browser (Chrome, Firefox, Safari, or Edge)

### Step-by-Step Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/dianaangan/AgriTrack.git
   cd AgriTrack
   ```

2. **Set up the database**
   - Open `config/database.php` and update your MySQL credentials:
     ```php
     $host = 'localhost';
     $dbname = 'your_database_name';
     $username = 'your_username';
     $password = 'your_password';
     ```

3. **Initialize the database**
   - Navigate to `http://localhost/AgriTrack/farmer/test_connection.php` in your browser
   - This will automatically create all required tables (farmers, admins, inventory, etc.)

4. **Create upload directory**
   ```bash
   mkdir -p uploads/products
   ```
   Ensure the directory has write permissions for the web server.

5. **Start the server**
   - Launch XAMPP and start Apache and MySQL services
   - Access the application at `http://localhost/AgriTrack/farmer/landing.php`

## ⚙️ Configuration

### Default Admin Account

The system automatically creates a default admin account on first initialization:

- **Email**: `admin@agritrack.com`
- **Password**: `admin123`

⚠️ **Important**: Change the default password immediately in a production environment.

### Custom Admin Account

To create a custom admin account, execute this SQL query:

```sql
INSERT INTO admins (firstName, lastName, email, password)
VALUES ('Your', 'Name', 'admin@example.com', PASSWORD('your_secure_password'));
```

Then log in at `http://localhost/AgriTrack/admin/admin_login.php`.

## 🚀 Usage

### For Farmers

1. Register a new account at the farmer landing page
2. Log in to access your dashboard
3. Add products to your inventory with images
4. Track stock levels and update as needed
5. Generate reports to analyze your inventory

### For Administrators

1. Log in using the admin portal
2. View platform-wide statistics and metrics
3. Manage farmer accounts (edit, delete, search)
4. Monitor global inventory across all farmers
5. Access comprehensive platform reports

## 📁 Project Structure

```
AgriTrack/
├── admin/                 # Admin portal pages
│   ├── admin_dashboard.php
│   ├── admin_farmers.php
│   ├── admin_inventory.php
│   ├── admin_login.php
│   └── ...
├── farmer/                # Farmer portal pages
│   ├── landing.php
│   ├── login.php
│   ├── home.php
│   ├── inventory.php
│   └── ...
├── css/                   # Shared stylesheets
│   ├── home.css
│   ├── inventory.css
│   ├── admin.css
│   └── ...
├── includes/              # Reusable PHP functions
│   ├── database.php
│   ├── farmer_functions.php
│   ├── admin_functions.php
│   └── inventory_functions.php
├── config/                # Configuration files
│   └── database.php       # Database connection & schema
├── uploads/               # Product images storage
│   └── products/
├── index.php              # Root redirect
└── README.md
```

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/AmazingFeature`)
3. **Make your changes** and test thoroughly
4. **Commit your changes** (`git commit -m 'Add some AmazingFeature'`)
5. **Push to the branch** (`git push origin feature/AmazingFeature`)
6. **Open a Pull Request**

### Development Guidelines

- Test all changes in both farmer and admin portals
- Maintain code consistency with existing style
- Include screenshots for UI-related changes
- Update documentation for new features
- Ensure backward compatibility

## 📝 Notes

- **Image Storage**: Product images are stored in `uploads/products/` directory. Only file paths are stored in the database. Make sure to include this directory in your backups.

- **Cache Control**: The application implements cache-busting mechanisms (`?v=2`) for assets to ensure proper updates across portals.

- **Security**: Always change default passwords and review security settings before deploying to production.

- **Browser Compatibility**: Tested on modern browsers (Chrome, Firefox, Safari, Edge).

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 👤 Author

**Diana Angan**

- GitHub: [@dianaangan](https://github.com/dianaangan)

## 🙏 Acknowledgments

- Built with modern PHP best practices
- Inspired by clean, minimalist UI design principles
- Created for efficient farm inventory management

---

⭐ **Star this repository if you find it helpful!**
