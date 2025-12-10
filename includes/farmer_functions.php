<?php
/**
 * Farmer Management Functions
 * Handles farmer registration, authentication, and profile management
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Register a new farmer
 * @param string $firstName
 * @param string $lastName
 * @param string $email
 * @param string $password
 * @return array
 */
function registerFarmer($firstName, $lastName, $email, $password) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed. Please check if MySQL is running in XAMPP.'];
    }
    
    try {
        // Normalize email to lowercase for case-insensitive comparison
        $email = trim(strtolower($email));
        
        // Check if email already exists (case-insensitive - email is already normalized)
        $stmt = $pdo->prepare("SELECT Id FROM farmers WHERE LOWER(email) = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'This email is already registered. Please use a different email or sign in if you already have an account.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new farmer (email is already normalized to lowercase)
        $stmt = $pdo->prepare("INSERT INTO farmers (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$firstName, $lastName, $email, $hashedPassword]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Registration successful', 'farmer_id' => $pdo->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
        
    } catch (PDOException $e) {
        error_log("Farmer registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Authenticate farmer login
 * @param string $email
 * @param string $password
 * @return array
 */
function authenticateFarmer($email, $password) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed. Please check if MySQL is running in XAMPP.', 'error_type' => 'general'];
    }
    
    try {
        // Get farmer by email
        $stmt = $pdo->prepare("SELECT Id, firstName, lastName, email, password FROM farmers WHERE email = ?");
        $stmt->execute([$email]);
        $farmer = $stmt->fetch();
        
        if (!$farmer) {
            return ['success' => false, 'message' => 'Invalid email', 'error_type' => 'email'];
        }
        
        // Verify password
        if (password_verify($password, $farmer['password'])) {
            return [
                'success' => true,
                'message' => 'Login successful',
                'farmer' => [
                    'id' => $farmer['Id'],
                    'firstName' => $farmer['firstName'],
                    'lastName' => $farmer['lastName'],
                    'email' => $farmer['email']
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid password', 'error_type' => 'password'];
        }
        
    } catch (PDOException $e) {
        error_log("Farmer authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication failed. Please try again.', 'error_type' => 'general'];
    }
}

/**
 * Get farmer by ID
 * @param int $farmerId
 * @return array|null
 */
function getFarmerById($farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT Id, firstName, lastName, email, created_at FROM farmers WHERE Id = ?");
        $stmt->execute([$farmerId]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Get farmer error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if email already exists for a farmer
 * @param string $email
 * @param int|null $excludeId Optional: exclude this farmer ID (for updates)
 * @return bool
 */
function farmerEmailExists($email, $excludeId = null) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $email = trim(strtolower($email));
        if (empty($email)) {
            return false;
        }
        
        if ($excludeId) {
            $stmt = $pdo->prepare("SELECT Id FROM farmers WHERE LOWER(TRIM(email)) = ? AND Id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT Id FROM farmers WHERE LOWER(TRIM(email)) = ?");
            $stmt->execute([$email]);
        }
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        error_log("Check email exists error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update farmer profile
 * @param int $farmerId
 * @param array $data
 * @return array
 */
function updateFarmerProfile($farmerId, $data) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed. Please check if MySQL is running in XAMPP.'];
    }
    
    try {
        // Ensure email is unique when updating (case-insensitive)
        if (isset($data['email'])) {
            $email = trim(strtolower($data['email']));
            if (farmerEmailExists($email, $farmerId)) {
                return ['success' => false, 'message' => 'An account with this email already exists. Please use a different email.'];
            }
            // Normalize email to lowercase for storage
            $data['email'] = $email;
        }

        $allowedFields = ['firstName', 'lastName', 'email'];
        $updateFields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $values[] = $farmerId;
        $sql = "UPDATE farmers SET " . implode(', ', $updateFields) . " WHERE Id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
        
    } catch (PDOException $e) {
        error_log("Update farmer error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
}

/**
 * Change farmer password
 * @param int $farmerId
 * @param string $currentPassword
 * @param string $newPassword
 * @return array
 */
function changeFarmerPassword($farmerId, $currentPassword, $newPassword) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed. Please check if MySQL is running in XAMPP.'];
    }
    
    try {
        // Get current password
        $stmt = $pdo->prepare("SELECT password FROM farmers WHERE Id = ?");
        $stmt->execute([$farmerId]);
        $farmer = $stmt->fetch();
        
        if (!$farmer) {
            return ['success' => false, 'message' => 'Farmer not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $farmer['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update password
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE farmers SET password = ? WHERE Id = ?");
        $result = $stmt->execute([$hashedNewPassword, $farmerId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
        
    } catch (PDOException $e) {
        error_log("Change password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}
?>
