<?php
/**
 * Time Logging Diagnostic Tool
 * Use this to diagnose time logging issues
 */

session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

$employee_id = $_SESSION['employee']['id'] ?? null;
$current_date = date("Y-m-d");
$current_time = date("H:i:s");

echo "<h1>Time Logging Diagnostic</h1>";
echo "<p><strong>Current Time:</strong> $current_date $current_time</p>";

// Check session
echo "<h2>Session Status</h2>";
echo "<p><strong>Employee ID:</strong> " . ($employee_id ? $employee_id : 'NOT SET') . "</p>";
echo "<p><strong>CSRF Token:</strong> " . (isset($_SESSION['csrf_token']) ? 'SET' : 'NOT SET') . "</p>";

if (!$employee_id) {
    echo "<p style='color: red;'><strong>ERROR:</strong> Employee ID not found in session. User needs to log in.</p>";
    exit;
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    $testStmt = $pdo->prepare("SELECT 1");
    $testStmt->execute();
    echo "<p style='color: green;'><strong>✓ Database connection successful</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Database connection failed:</strong> " . $e->getMessage() . "</p>";
    exit;
}

// Check time_logs table structure
echo "<h2>Database Table Check</h2>";
try {
    $descStmt = $pdo->prepare("DESCRIBE time_logs");
    $descStmt->execute();
    $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>time_logs table structure:</strong></p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error checking table structure:</strong> " . $e->getMessage() . "</p>";
}

// Check today's time log
echo "<h2>Today's Time Log</h2>";
try {
    $logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $logStmt->execute([$employee_id, $current_date]);
    $todayLog = $logStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($todayLog) {
        echo "<p><strong>Today's log found:</strong></p>";
        echo "<ul>";
        foreach ($todayLog as $key => $value) {
            echo "<li><strong>$key:</strong> " . ($value ?? 'NULL') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><strong>No time log found for today</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error checking today's log:</strong> " . $e->getMessage() . "</p>";
}

// Check for open logs
echo "<h2>Open Time Logs</h2>";
try {
    $openStmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND time_out IS NULL ORDER BY log_date DESC, id DESC");
    $openStmt->execute([$employee_id]);
    $openLogs = $openStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($openLogs) {
        echo "<p><strong>Open logs found:</strong></p>";
        foreach ($openLogs as $log) {
            echo "<ul>";
            foreach ($log as $key => $value) {
                echo "<li><strong>$key:</strong> " . ($value ?? 'NULL') . "</li>";
            }
            echo "</ul><hr>";
        }
    } else {
        echo "<p><strong>No open logs found</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>✗ Error checking open logs:</strong> " . $e->getMessage() . "</p>";
}

// Test time in
echo "<h2>Test Time In</h2>";
if (isset($_POST['test_time_in'])) {
    try {
        $testStmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
        $result = $testStmt->execute([$employee_id, $current_date, $current_time]);
        
        if ($result) {
            echo "<p style='color: green;'><strong>✓ Test time in successful</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>✗ Test time in failed</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ Test time in error:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='csrf_token' value='" . ($_SESSION['csrf_token'] ?? '') . "'>";
    echo "<button type='submit' name='test_time_in'>Test Time In</button>";
    echo "</form>";
}

// Test time out
echo "<h2>Test Time Out</h2>";
if (isset($_POST['test_time_out'])) {
    try {
        $openStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND time_out IS NULL ORDER BY log_date DESC, id DESC LIMIT 1");
        $openStmt->execute([$employee_id]);
        $openLog = $openStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($openLog) {
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE id = ?");
            $result = $updateStmt->execute([$current_time, $openLog['id']]);
            
            if ($result) {
                echo "<p style='color: green;'><strong>✓ Test time out successful</strong></p>";
            } else {
                echo "<p style='color: red;'><strong>✗ Test time out failed</strong></p>";
            }
        } else {
            echo "<p style='color: red;'><strong>✗ No open log found for time out</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ Test time out error:</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='csrf_token' value='" . ($_SESSION['csrf_token'] ?? '') . "'>";
    echo "<button type='submit' name='test_time_out'>Test Time Out</button>";
    echo "</form>";
}

// Show recent error logs
echo "<h2>Recent Error Logs</h2>";
$errorLog = file_get_contents('/var/log/apache2/error.log');
$recentErrors = array_slice(explode("\n", $errorLog), -50);
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
foreach ($recentErrors as $error) {
    if (strpos($error, 'TIME') !== false || strpos($error, 'time_log') !== false) {
        echo htmlspecialchars($error) . "\n";
    }
}
echo "</pre>";

echo "<p><a href='time_log_create.php'>← Back to Time Logging</a></p>";
?>

