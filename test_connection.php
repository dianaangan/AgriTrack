<?php
/**
 * Database Connection Test Script
 * Use this to test if your database connection is working properly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/farmer_functions.php';

echo "<h2>AgriTrack Database Connection Test</h2>";

// Test 1: Basic database connection
echo "<h3>Test 1: Database Connection</h3>";
$pdo = getDatabaseConnection();
if ($pdo) {
    echo "✅ Database connection successful!<br>";
    echo "Connected to: agritrack database<br>";
} else {
    echo "❌ Database connection failed!<br>";
    echo "Please check your XAMPP MySQL service and database configuration.<br>";
    exit;
}

// Test 2: Check if farmers table exists
echo "<h3>Test 2: Farmers Table Check</h3>";
try {
    $stmt = $pdo->query("DESCRIBE farmers");
    $columns = $stmt->fetchAll();
    echo "✅ Farmers table exists with " . count($columns) . " columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
} catch (PDOException $e) {
    echo "❌ Farmers table does not exist or has issues: " . $e->getMessage() . "<br>";
    echo "Attempting to create table...<br>";
    
    if (initializeDatabase()) {
        echo "✅ Farmers table created successfully!<br>";
    } else {
        echo "❌ Failed to create farmers table.<br>";
    }
}

// Test 3: Test farmer registration (optional)
echo "<h3>Test 3: Farmer Registration Test</h3>";
$testResult = registerFarmer("Test", "Farmer", "test@example.com", "password123");
if ($testResult['success']) {
    echo "✅ Test farmer registration successful!<br>";
    echo "Test farmer ID: " . $testResult['farmer_id'] . "<br>";
    
    // Test authentication
    $authResult = authenticateFarmer("test@example.com", "password123");
    if ($authResult['success']) {
        echo "✅ Test farmer authentication successful!<br>";
        echo "Farmer name: " . $authResult['farmer']['firstName'] . " " . $authResult['farmer']['lastName'] . "<br>";
    } else {
        echo "❌ Test farmer authentication failed: " . $authResult['message'] . "<br>";
    }
} else {
    echo "❌ Test farmer registration failed: " . $testResult['message'] . "<br>";
}

// Test 4: Check existing farmers
echo "<h3>Test 4: Existing Farmers</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM farmers");
    $result = $stmt->fetch();
    echo "Total farmers in database: " . $result['count'] . "<br>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT Id, firstName, lastName, email, created_at FROM farmers ORDER BY created_at DESC LIMIT 5");
        $farmers = $stmt->fetchAll();
        echo "Recent farmers:<br>";
        foreach ($farmers as $farmer) {
            echo "- " . $farmer['firstName'] . " " . $farmer['lastName'] . " (" . $farmer['email'] . ") - " . $farmer['created_at'] . "<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ Error checking farmers: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Summary:</strong> Your PHP backend is now connected to the MySQL database!</p>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li>Register new farmers at <a href='/register.php'>/register.php</a></li>";
echo "<li>Login existing farmers at <a href='/login.php'>/login.php</a></li>";
echo "<li>Access the home page at <a href='home.php'>home.php</a></li>";
echo "</ul>";
echo "<p><em>Note: You can delete this test file (test_connection.php) after confirming everything works.</em></p>";
?>
