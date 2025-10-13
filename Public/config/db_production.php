<?php
// Hostinger-optimized database configuration
// This file replaces the existing db.php for production

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Try to load .env file
$envLoaded = loadEnv(__DIR__ . '/../../.env');

// Database configuration with fallbacks
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'your_database_name';
$db_user = getenv('DB_USER') ?: 'your_database_user';
$db_pass = getenv('DB_PASS') ?: 'your_database_password';
$db_port = getenv('DB_PORT') ?: '3306';

// Set timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');

// Error reporting for production
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', getenv('LOG_PATH') ?: '/tmp/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Create PDO connection with optimized settings for Hostinger
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT => 30, // Hostinger timeout optimization
        PDO::ATTR_PERSISTENT => false // Disable persistent connections for shared hosting
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Optimize for Hostinger shared hosting
    $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    $pdo->exec("SET SESSION time_zone='+08:00'"); // Manila timezone
    
} catch (PDOException $e) {
    // Log error in production, display in development
    if (getenv('APP_ENV') === 'production') {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact support.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Start session with secure settings for production
if (session_status() === PHP_SESSION_NONE) {
    $secure = (getenv('APP_ENV') === 'production');
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $secure ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

// Helper function to check if running on Hostinger
function isHostingerEnvironment() {
    return strpos($_SERVER['HTTP_HOST'] ?? '', 'hostinger') !== false || 
           file_exists('/usr/local/hestia') ||
           getenv('APP_ENV') === 'production';
}

// Set memory and execution limits for Hostinger
if (isHostingerEnvironment()) {
    ini_set('memory_limit', '256M');
    ini_set('max_execution_time', 300);
    ini_set('max_input_time', 60);
}
?>