<?php
/**
 * Database Configuration for AgriTrack
 * Handles connection to MySQL database
 */

// Database configuration
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'agritrack',
    'username' => 'root',
    'password' => '', // Default XAMPP MySQL password is empty
    'charset' => 'utf8mb4'
];

/**
 * Create database if it doesn't exist
 * @return bool
 */
function createDatabaseIfNotExists() {
    global $db_config;
    
    try {
        // Connect to MySQL without specifying database
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};charset={$db_config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        
        // Create database if it doesn't exist
        $dbname = $db_config['dbname'];
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Failed to create database: " . $e->getMessage());
        return false;
    }
}

/**
 * Create database connection
 * @return PDO|null
 */
function getDatabaseConnection() {
    global $db_config;
    
    try {
        // First, ensure database exists
        if (!createDatabaseIfNotExists()) {
            error_log("Failed to create database '{$db_config['dbname']}'. Check MySQL service.");
            return null;
        }
        
        // Now connect to the database
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        error_log("Database connection failed: " . $errorMsg);
        
        // Provide more helpful error messages
        if (strpos($errorMsg, 'Unknown database') !== false) {
            error_log("Database '{$db_config['dbname']}' does not exist. Attempting to create...");
        } elseif (strpos($errorMsg, 'Access denied') !== false) {
            error_log("MySQL access denied. Check username/password in config/database.php");
        } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No connection') !== false) {
            error_log("MySQL service may not be running. Start MySQL in XAMPP Control Panel.");
        }
        
        return null;
    }
}

/**
 * Test database connection
 * @return bool
 */
function testDatabaseConnection() {
    $pdo = getDatabaseConnection();
    return $pdo !== null;
}

/**
 * Initialize database tables if they don't exist
 * @return bool
 */
function initializeDatabase() {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // Create farmers table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS farmers (
            Id INT AUTO_INCREMENT PRIMARY KEY,
            firstName VARCHAR(100) NOT NULL,
            lastName VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Create admins table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            Id INT AUTO_INCREMENT PRIMARY KEY,
            firstName VARCHAR(100) NOT NULL,
            lastName VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Create default admin if no admins exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Default admin credentials: admin@agritrack.com / admin123
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Admin', 'User', 'admin@agritrack.com', $defaultPassword]);
        }
        
        // Create inventory table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS inventory (
            Id INT AUTO_INCREMENT PRIMARY KEY,
            farmer_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
            unit VARCHAR(50) NOT NULL,
            price DECIMAL(10, 2) DEFAULT NULL,
            description TEXT,
            image_path VARCHAR(255) DEFAULT NULL,
            status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (farmer_id) REFERENCES farmers(Id) ON DELETE CASCADE,
            INDEX idx_farmer_id (farmer_id),
            INDEX idx_category (category),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Ensure image_path column exists
        $pdo->exec("ALTER TABLE inventory ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL");

        return true;
        
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize database on include
if (testDatabaseConnection()) {
    initializeDatabase();
} else {
    error_log("Warning: Could not connect to database. Please check your database configuration.");
}
?>
