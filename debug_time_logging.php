<?php
// Specific debug script for time logging issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Time Logging Debug - Hosting Environment</h2>";

// 1. Test environment loading
echo "<h3>1. Environment File Loading</h3>";
try {
    require_once 'Public/config/env.php';
    echo "✅ env.php loaded successfully<br>";
    
    // Test if EnvLoader class exists
    if (class_exists('EnvLoader')) {
        echo "✅ EnvLoader class found<br>";
        
        // Test loading .env file
        $loadResult = EnvLoader::load();
        if ($loadResult) {
            echo "✅ .env file loaded successfully<br>";
            
            // Test getting database values
            $dbHost = EnvLoader::get('DB_HOST', 'NOT_FOUND');
            $dbName = EnvLoader::get('DB_NAME', 'NOT_FOUND');
            $dbUser = EnvLoader::get('DB_USER', 'NOT_FOUND');
            $dbPass = EnvLoader::get('DB_PASS', 'NOT_FOUND');
            
            echo "DB_HOST: " . $dbHost . "<br>";
            echo "DB_NAME: " . $dbName . "<br>";
            echo "DB_USER: " . $dbUser . "<br>";
            echo "DB_PASS: " . (empty($dbPass) ? '[empty]' : '[set]') . "<br>";
        } else {
            echo "❌ Failed to load .env file<br>";
        }
    } else {
        echo "❌ EnvLoader class not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading environment: " . $e->getMessage() . "<br>";
}

// 2. Test database connection
echo "<h3>2. Database Connection Test</h3>";
try {
    require_once 'Public/config/db.php';
    echo "✅ db.php loaded successfully<br>";
    
    if (isset($pdo)) {
        echo "✅ PDO connection object created<br>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "✅ Database query test successful<br>";
        } else {
            echo "❌ Database query test failed<br>";
        }
    } else {
        echo "❌ PDO connection object not created<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

// 3. Test time_logs table
echo "<h3>3. Time Logs Table Test</h3>";
try {
    if (isset($pdo)) {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'time_logs'");
        if ($stmt->rowCount() > 0) {
            echo "✅ time_logs table exists<br>";
            
            // Check table structure
            $stmt = $pdo->query("DESCRIBE time_logs");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "Table columns: " . implode(', ', $columns) . "<br>";
            
            // Test inserting a record (we'll rollback)
            $pdo->beginTransaction();
            try {
                $testEmployeeId = 999999; // Non-existent employee
                $testDate = date('Y-m-d');
                $testTime = date('H:i:s');
                
                $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
                $result = $stmt->execute([$testEmployeeId, $testDate, $testTime]);
                
                if ($result) {
                    echo "✅ INSERT test successful<br>";
                } else {
                    echo "❌ INSERT test failed<br>";
                }
                
                $pdo->rollback(); // Rollback the test insert
                echo "✅ Transaction rollback successful<br>";
                
            } catch (Exception $e) {
                $pdo->rollback();
                echo "❌ INSERT test error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ time_logs table does NOT exist<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Table test error: " . $e->getMessage() . "<br>";
}

// 4. Test session functionality
echo "<h3>4. Session Test</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sessions are working<br>";
    
    // Simulate employee session
    $_SESSION['employee'] = ['id' => 1, 'role' => 'employee'];
    if (isset($_SESSION['employee']['id'])) {
        echo "✅ Employee session simulation successful<br>";
    }
} else {
    echo "❌ Sessions are NOT working<br>";
}

// 5. Test time_log_create.php file
echo "<h3>5. Time Log Create File Test</h3>";
$timeLogFile = 'Public/module/time_log_create.php';
if (file_exists($timeLogFile)) {
    echo "✅ time_log_create.php exists<br>";
    
    // Check file permissions
    $perms = fileperms($timeLogFile);
    echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
    
    // Check if file is readable
    if (is_readable($timeLogFile)) {
        echo "✅ File is readable<br>";
    } else {
        echo "❌ File is NOT readable<br>";
    }
} else {
    echo "❌ time_log_create.php NOT found<br>";
}

// 6. Test CSRF token functionality
echo "<h3>6. CSRF Token Test</h3>";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (isset($_SESSION['csrf_token']) && strlen($_SESSION['csrf_token']) === 64) {
    echo "✅ CSRF token generation working<br>";
} else {
    echo "❌ CSRF token generation failed<br>";
}

// 7. Test date/time functions
echo "<h3>7. Date/Time Functions Test</h3>";
$currentDate = date("Y-m-d");
$currentTime = date("H:i:s");
echo "Current date: " . $currentDate . "<br>";
echo "Current time: " . $currentTime . "<br>";

if ($currentDate && $currentTime) {
    echo "✅ Date/time functions working<br>";
} else {
    echo "❌ Date/time functions failed<br>";
}

// 8. Test file upload directories
echo "<h3>8. Upload Directories Test</h3>";
$uploadDirs = [
    'Public/uploads/',
    'Public/uploads/profile_images/',
    'Public/uploads/leave_attachments/',
    'Public/uploads/schedule_attachments/',
    'Public/uploads/time_adjustments/'
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ Directory exists: $dir<br>";
        if (is_writable($dir)) {
            echo "✅ Directory is writable: $dir<br>";
        } else {
            echo "❌ Directory is NOT writable: $dir<br>";
        }
    } else {
        echo "❌ Directory does NOT exist: $dir<br>";
    }
}

// 9. Test error logging
echo "<h3>9. Error Logging Test</h3>";
$testError = "Test error log entry - " . date('Y-m-d H:i:s');
error_log($testError);
echo "✅ Test error logged<br>";

// 10. Check recent error log entries
echo "<h3>10. Recent Error Log Entries</h3>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $lines = file($errorLog);
    $recentLines = array_slice($lines, -5); // Last 5 lines
    echo "<pre>" . htmlspecialchars(implode('', $recentLines)) . "</pre>";
} else {
    echo "Error log not accessible<br>";
}

echo "<hr>";
echo "<h3>Summary & Next Steps</h3>";
echo "<p>Based on the tests above, check for:</p>";
echo "<ul>";
echo "<li>❌ marks indicate issues that need to be fixed</li>";
echo "<li>✅ marks indicate working components</li>";
echo "<li>Focus on fixing the ❌ items first</li>";
echo "</ul>";

echo "<h4>Common Hosting Issues:</h4>";
echo "<ul>";
echo "<li><strong>File Permissions:</strong> Upload directories need to be writable (755 or 777)</li>";
echo "<li><strong>Database Credentials:</strong> Ensure .env file has correct hosting database details</li>";
echo "<li><strong>PHP Extensions:</strong> PDO and PDO_MySQL must be enabled</li>";
echo "<li><strong>Session Path:</strong> Sessions directory must be writable</li>";
echo "<li><strong>Error Logging:</strong> Check hosting error logs for specific PHP errors</li>";
echo "</ul>";
?>
