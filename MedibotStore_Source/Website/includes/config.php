<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kms_website');

// Site Configuration
define('SITE_NAME', 'MediSync Medical & Health Care');
define('SITE_URL', 'http://localhost'); 

// Feature Flags    
define('ENABLE_AUTO_DISCOUNT', true);  // Set to false to disable automatic discounts
define('AUTO_DISCOUNT_PERCENT', 10);    // Default discount percentage
define('AUTO_DISCOUNT_MIN_RATING', 3); // Minimum rating required for discount

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Session Configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>