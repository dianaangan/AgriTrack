<?php
/**
 * Admin Management Functions
 * Handles admin authentication
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Authenticate admin login
 * @param string $email
 * @param string $password
 * @return array
 */
function authenticateAdmin($email, $password) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed. Please check if MySQL is running in XAMPP.', 'error_type' => 'general'];
    }
    
    try {
        // Get admin by email
        $stmt = $pdo->prepare("SELECT Id, firstName, lastName, email, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid email', 'error_type' => 'email'];
        }
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            return [
                'success' => true,
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['Id'],
                    'firstName' => $admin['firstName'],
                    'lastName' => $admin['lastName'],
                    'email' => $admin['email']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid password', 'error_type' => 'password'];
        }
        
    } catch (PDOException $e) {
        error_log("Admin authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication failed. Please try again.', 'error_type' => 'general'];
    }
}

/**
 * Get admin by ID
 * @param int $adminId
 * @return array|null
 */
function getAdminById($adminId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT Id, firstName, lastName, email, created_at FROM admins WHERE Id = ?");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Get admin error: " . $e->getMessage());
        return null;
    }
}

