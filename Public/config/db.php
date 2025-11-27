<?php
// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Load environment variables
require_once __DIR__ . '/env.php';
if (class_exists('EnvLoader')) {
    EnvLoader::load();
}

// Database configuration from environment variables
$host = EnvLoader::get('DB_HOST', 'localhost');
// Auto-detect environment: use different database for local vs production
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], 'localhost') !== false);
$defaultDbName = $isLocal ? 'rss' : 'u816220874_calendartype';
$dbname = EnvLoader::get('DB_NAME', $defaultDbName);
$username = EnvLoader::get('DB_USER', 'root');
$password = EnvLoader::get('DB_PASS', '');

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