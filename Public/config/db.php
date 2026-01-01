<?php
// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Load environment variables
require_once __DIR__ . '/env.php';
if (class_exists('EnvLoader')) {
    EnvLoader::load();
}

// Database configuration from environment variables
// Auto-detect environment: use different database for local vs production
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || 
            $_SERVER['SERVER_ADDR'] === '127.0.0.1' || 
            strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);

if ($isLocal) {
    // Local XAMPP settings
    $host = EnvLoader::get('DB_HOST', 'localhost');
    $dbname = EnvLoader::get('DB_NAME', 'rss');
    $username = EnvLoader::get('DB_USER', 'root');
    $password = EnvLoader::get('DB_PASS', '');
} else {
    // Hostinger production settings
    $host = EnvLoader::get('DB_HOST', 'localhost');
    $dbname = EnvLoader::get('DB_NAME', 'u8162220874_u8162220874');
    $username = EnvLoader::get('DB_USER', 'u8162220874_harley2026');
    $password = EnvLoader::get('DB_PASS', 'Gr33n$$wRf');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set timezone for database connections to Manila time (UTC+8)
    $pdo->exec("SET time_zone = '+08:00'");
    
} catch (PDOException $e) { 
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?>