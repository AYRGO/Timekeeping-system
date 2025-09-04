<?php
// Test script specifically for public_html .env file location
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Public HTML Environment File Test</h2>";

// Show current paths
echo "<h3>Path Information:</h3>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current File: " . __FILE__ . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Working Directory: " . getcwd() . "<br><br>";

// Test .env file location
$envPath = $_SERVER['DOCUMENT_ROOT'] . '/.env';
echo "<h3>Environment File Test:</h3>";
echo "Looking for .env at: " . $envPath . "<br>";
echo "File exists: " . (file_exists($envPath) ? "YES ✅" : "NO ❌") . "<br>";

if (file_exists($envPath)) {
    echo "File is readable: " . (is_readable($envPath) ? "YES ✅" : "NO ❌") . "<br>";
    echo "File size: " . filesize($envPath) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($envPath)) . "<br>";
}

// Test the updated env.php
echo "<h3>Environment Loading Test:</h3>";
try {
    require_once 'Public/config/env.php';
    echo "✅ env.php loaded successfully<br>";
    
    if (class_exists('EnvLoader')) {
        echo "✅ EnvLoader class found<br>";
        
        // Test loading .env file
        $loadResult = EnvLoader::load();
        if ($loadResult) {
            echo "✅ .env file loaded successfully from public_html!<br><br>";
            
            // Test getting database values
            echo "<h3>Database Configuration:</h3>";
            $dbHost = EnvLoader::get('DB_HOST', 'NOT_FOUND');
            $dbName = EnvLoader::get('DB_NAME', 'NOT_FOUND');
            $dbUser = EnvLoader::get('DB_USER', 'NOT_FOUND');
            $dbPass = EnvLoader::get('DB_PASS', 'NOT_FOUND');
            
            echo "DB_HOST: " . htmlspecialchars($dbHost) . "<br>";
            echo "DB_NAME: " . htmlspecialchars($dbName) . "<br>";
            echo "DB_USER: " . htmlspecialchars($dbUser) . "<br>";
            echo "DB_PASS: " . (empty($dbPass) ? '[empty]' : '[set - ' . strlen($dbPass) . ' characters]') . "<br><br>";
            
            // Test database connection
            echo "<h3>Database Connection Test:</h3>";
            try {
                $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "✅ Database connection successful!<br>";
                
                // Test a simple query
                $stmt = $pdo->query("SELECT 1 as test");
                $result = $stmt->fetch();
                if ($result && $result['test'] == 1) {
                    echo "✅ Database query test successful<br>";
                } else {
                    echo "❌ Database query test failed<br>";
                }
                
            } catch (PDOException $e) {
                echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "❌ Failed to load .env file<br>";
        }
    } else {
        echo "❌ EnvLoader class not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading environment: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>If you see ✅ for .env file loading and database connection, your time logging should work now!</p>";
echo "<p>If you see ❌, check your .env file in the public_html directory and verify the database credentials.</p>";
?>
