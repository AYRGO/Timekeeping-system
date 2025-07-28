<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Database configuration from environment variables
$host = EnvLoader::get('DB_HOST', 'localhost');
$dbname = EnvLoader::get('DB_NAME', 'rss2');  // Temporary fallback until env is working
$username = EnvLoader::get('DB_USER', 'root');
$password = EnvLoader::get('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set timezone for database connections
    $pdo->exec("SET time_zone = '+08:00'");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?>