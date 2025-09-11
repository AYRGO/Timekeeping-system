<?php
/**
 * Comprehensive Time Logging Test
 * This script tests all aspects of the time logging functionality
 */

session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

echo "<h1>🕐 Comprehensive Time Logging Test</h1>";
echo "<p><strong>Test Time:</strong> " . date("Y-m-d H:i:s") . "</p>";

// Test 1: Session Management
echo "<h2>1. Session Management Test</h2>";
$employee_id = $_SESSION['employee']['id'] ?? null;
echo "<p><strong>Employee ID:</strong> " . ($employee_id ? $employee_id : 'NOT SET') . "</p>";
echo "<p><strong>CSRF Token:</strong> " . (isset($_SESSION['csrf_token']) ? 'SET' : 'NOT SET') . "</p>";
echo "<p><strong>Session Timeout:</strong> " . (isset($_SESSION['last_activity']) ? (time() - $_SESSION['last_activity']) . ' seconds ago' : 'NOT SET') . "</p>";

if (!$employee_id) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> No employee ID in session. Please log in first.</p>";
    exit;
}

// Test 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
try {
    $testStmt = $pdo->prepare("SELECT 1");
    $testStmt->execute();
    echo "<p style='color: green;'><strong>✅ PASS:</strong> Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Employee Validation
echo "<h2>3. Employee Validation Test</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, status FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo "<p style='color: red;'><strong>❌ FAIL:</strong> Employee not found in database</p>";
        exit;
    }
    
    if ($employee['status'] !== 'active') {
        echo "<p style='color: red;'><strong>❌ FAIL:</strong> Employee account is inactive</p>";
        exit;
    }
    
    echo "<p style='color: green;'><strong>✅ PASS:</strong> Employee is valid and active</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Employee validation failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Time Logs Table Structure
echo "<h2>4. Database Table Structure Test</h2>";
try {
    $descStmt = $pdo->prepare("DESCRIBE time_logs");
    $descStmt->execute();
    $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'employee_id', 'log_date', 'time_in', 'time_out'];
    $foundColumns = array_column($columns, 'Field');
    
    $missingColumns = array_diff($requiredColumns, $foundColumns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'><strong>✅ PASS:</strong> All required columns exist</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ FAIL:</strong> Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Table structure check failed: " . $e->getMessage() . "</p>";
}

// Test 5: Today's Time Log
echo "<h2>5. Today's Time Log Test</h2>";
$current_date = date("Y-m-d");
try {
    $logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $logStmt->execute([$employee_id, $current_date]);
    $todayLog = $logStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($todayLog) {
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> Today's log found:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $todayLog['id'] . "</li>";
        echo "<li><strong>Time In:</strong> " . ($todayLog['time_in'] ?? 'Not set') . "</li>";
        echo "<li><strong>Time Out:</strong> " . ($todayLog['time_out'] ?? 'Not set') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> No time log found for today</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Today's log check failed: " . $e->getMessage() . "</p>";
}

// Test 6: Open Time Logs
echo "<h2>6. Open Time Logs Test</h2>";
try {
    $openStmt = $pdo->prepare("SELECT id, log_date, time_in, time_out FROM time_logs WHERE employee_id = ? AND time_out IS NULL ORDER BY log_date DESC, id DESC");
    $openStmt->execute([$employee_id]);
    $openLogs = $openStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($openLogs) {
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> Found " . count($openLogs) . " open time log(s):</p>";
        foreach ($openLogs as $log) {
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $log['id'] . " | <strong>Date:</strong> " . $log['log_date'] . " | <strong>Time In:</strong> " . $log['time_in'] . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> No open time logs found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Open logs check failed: " . $e->getMessage() . "</p>";
}

// Test 7: CSRF Token Test
echo "<h2>7. CSRF Token Test</h2>";
$csrf_token = $_SESSION['csrf_token'] ?? '';
$csrf_time = $_SESSION['csrf_token_time'] ?? 0;
$token_age = time() - $csrf_time;

echo "<p><strong>Token:</strong> " . substr($csrf_token, 0, 8) . "...</p>";
echo "<p><strong>Token Age:</strong> " . $token_age . " seconds</p>";
echo "<p><strong>Token Expired:</strong> " . ($token_age > 1800 ? 'YES' : 'NO') . "</p>";

if (empty($csrf_token)) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> No CSRF token found</p>";
} else {
    echo "<p style='color: green;'><strong>✅ PASS:</strong> CSRF token exists</p>";
}

// Test 8: Rate Limiting Test
echo "<h2>8. Rate Limiting Test</h2>";
$rate_limit_key = "rate_limit_" . $employee_id;
$rate_data = $_SESSION[$rate_limit_key] ?? ['count' => 0, 'last_reset' => time()];

if ((time() - $rate_data['last_reset']) > 60) {
    $rate_data = ['count' => 0, 'last_reset' => time()];
}

echo "<p><strong>Current Rate:</strong> " . $rate_data['count'] . "/10 requests per minute</p>";
echo "<p><strong>Rate Limit Status:</strong> " . ($rate_data['count'] > 10 ? 'EXCEEDED' : 'OK') . "</p>";

if ($rate_data['count'] > 10) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Rate limit exceeded</p>";
} else {
    echo "<p style='color: green;'><strong>✅ PASS:</strong> Rate limit OK</p>";
}

// Test 9: Simulate Time In (Dry Run)
echo "<h2>9. Time In Simulation Test</h2>";
try {
    // Check if already logged in today
    $checkStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_in IS NOT NULL");
    $checkStmt->execute([$employee_id, $current_date]);
    $existingLog = $checkStmt->fetch();
    
    if ($existingLog) {
        echo "<p style='color: orange;'><strong>⚠️ WARNING:</strong> Already logged time in for today (Log ID: " . $existingLog['id'] . ")</p>";
    } else {
        echo "<p style='color: green;'><strong>✅ READY:</strong> Can log time in</p>";
        
        // Test the insert query (without executing)
        $testStmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> Time in query prepared successfully</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Time in simulation failed: " . $e->getMessage() . "</p>";
}

// Test 10: Simulate Time Out (Dry Run)
echo "<h2>10. Time Out Simulation Test</h2>";
try {
    // Check for open logs
    $openStmt = $pdo->prepare("SELECT id, log_date FROM time_logs WHERE employee_id = ? AND time_out IS NULL ORDER BY log_date DESC, id DESC LIMIT 1");
    $openStmt->execute([$employee_id]);
    $openLog = $openStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($openLog) {
        echo "<p style='color: green;'><strong>✅ READY:</strong> Can log time out (Open Log ID: " . $openLog['id'] . ")</p>";
        
        // Test the update query (without executing)
        $testStmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE id = ?");
        echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> Time out query prepared successfully</p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ WARNING:</strong> No open time log found for time out</p>";
        
        // Check if there's a log for today
        $todayStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ?");
        $todayStmt->execute([$employee_id, $current_date]);
        $todayLog = $todayStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($todayLog) {
            echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> Found today's log (ID: " . $todayLog['id'] . ") - can update with time out</p>";
        } else {
            echo "<p style='color: blue;'><strong>ℹ️ INFO:</strong> No today's log - can create new record with time out</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ FAIL:</strong> Time out simulation failed: " . $e->getMessage() . "</p>";
}

// Test 11: Error Log Check
echo "<h2>11. Recent Error Log Check</h2>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $recentLogs = array_slice(explode("\n", $logContent), -20);
    
    echo "<p><strong>Recent Error Log Entries (last 20):</strong></p>";
    echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;'>";
    foreach ($recentLogs as $log) {
        if (trim($log)) {
            echo htmlspecialchars($log) . "<br>";
        }
    }
    echo "</div>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ WARNING:</strong> Error log file not found or not configured</p>";
}

echo "<h2>🎯 Test Summary</h2>";
echo "<p><strong>All tests completed!</strong> Check the results above to identify any issues.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If all tests pass, the time logging should work properly</li>";
echo "<li>If any tests fail, fix the issues before testing time logging</li>";
echo "<li>Use the diagnostic tool to monitor real-time logging</li>";
echo "</ul>";

echo "<p><a href='time_logging_diagnostic.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Open Diagnostic Tool</a></p>";
echo "<p><a href='time_log_create.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⏰ Go to Time Logging</a></p>";
?>

