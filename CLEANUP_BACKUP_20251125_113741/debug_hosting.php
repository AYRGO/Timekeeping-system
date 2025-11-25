<?php
// Debug script to identify hosting issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Hosting Environment Debug</h2>";

// 1. Check PHP version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Check if PDO is available
echo "<h3>2. PDO Extension</h3>";
if (extension_loaded('pdo')) {
    echo "✅ PDO is loaded<br>";
    if (extension_loaded('pdo_mysql')) {
        echo "✅ PDO MySQL is loaded<br>";
    } else {
        echo "❌ PDO MySQL is NOT loaded<br>";
    }
} else {
    echo "❌ PDO is NOT loaded<br>";
}

// 3. Check environment file
echo "<h3>3. Environment File</h3>";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✅ .env file exists at: " . $envPath . "<br>";
} else {
    echo "❌ .env file NOT found at: " . $envPath . "<br>";
}

// 4. Test database connection with fallback values
echo "<h3>4. Database Connection Test</h3>";
try {
    // Try with common hosting database settings
    $host = 'localhost';
    $dbname = 'rss'; // or your actual database name
    $username = 'root'; // or your hosting username
    $password = ''; // or your hosting password
    
    echo "Attempting connection with:<br>";
    echo "Host: $host<br>";
    echo "Database: $dbname<br>";
    echo "Username: $username<br>";
    echo "Password: " . (empty($password) ? '[empty]' : '[set]') . "<br><br>";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful!<br>";
    
    // Test time_logs table
    $stmt = $pdo->query("SHOW TABLES LIKE 'time_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✅ time_logs table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE time_logs");
        echo "<h4>time_logs table structure:</h4>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ time_logs table does NOT exist<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// 5. Check file permissions
echo "<h3>5. File Permissions</h3>";
$testFile = __DIR__ . '/Public/module/time_log_create.php';
if (file_exists($testFile)) {
    echo "✅ time_log_create.php exists<br>";
    echo "File permissions: " . substr(sprintf('%o', fileperms($testFile)), -4) . "<br>";
} else {
    echo "❌ time_log_create.php NOT found<br>";
}

// 6. Check session functionality
echo "<h3>6. Session Test</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sessions are working<br>";
    $_SESSION['test'] = 'test_value';
    if (isset($_SESSION['test'])) {
        echo "✅ Session write/read working<br>";
    }
} else {
    echo "❌ Sessions are NOT working<br>";
}

// 7. Check error reporting
echo "<h3>7. Error Reporting</h3>";
echo "Error reporting level: " . error_reporting() . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>";
if (ini_get('log_errors')) {
    echo "Error log file: " . ini_get('error_log') . "<br>";
}

echo "<h3>8. Recent Error Log (if available)</h3>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -10); // Last 10 lines
    echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
} else {
    echo "Error log not accessible or not set<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If .env file is missing, create one with your hosting database credentials</li>";
echo "<li>If database connection fails, check your hosting database settings</li>";
echo "<li>If time_logs table doesn't exist, import your database structure</li>";
echo "<li>Check your hosting error logs for specific PHP errors</li>";
echo "</ol>";
?>
