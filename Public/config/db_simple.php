<?php
// Simplified database configuration for hosting
// Use this if environment variables are not working

// Set timezone to Manila time (UTC+8)
date_default_timezone_set('Asia/Manila');

// Database configuration - UPDATE THESE VALUES FOR YOUR HOSTING
$host = 'localhost';        // Your hosting database host
$dbname = 'rss';           // Your database name
$username = 'root';        // Your database username
$password = '';            // Your database password

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
