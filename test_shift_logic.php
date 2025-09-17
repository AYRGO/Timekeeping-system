<?php
// Comprehensive test for both day shift and night shift logic
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Shift Logic Test - Day Shift vs Night Shift</h2>";

// Include the necessary files
try {
    require_once 'Public/config/db.php';
    echo "✅ Database connection loaded<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Start session
session_start();

// Simulate employee session
$_SESSION['employee'] = ['id' => 1, 'role' => 'employee'];

echo "<h3>Testing Both Day Shift and Night Shift Logic</h3>";

try {
    $employee_id = 1;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Today: $today<br>";
    echo "Yesterday: $yesterday<br><br>";
    
    // Test 1: Regular Day Shift Scenario (7 AM - 7 PM)
    echo "<h4>Test 1: Regular Day Shift (7 AM - 7 PM)</h4>";
    
    // Clean up any existing test data
    $cleanupStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date IN (?, ?)");
    $cleanupStmt->execute([$employee_id, $today, $yesterday]);
    echo "✅ Cleaned up test data<br>";
    
    // Scenario 1a: No time log for today (should show "Log Time In")
    echo "<h5>Scenario 1a: No time log for today</h5>";
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $today]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    $time_in = $log['time_in'] ?? null;
    $time_out = $log['time_out'] ?? null;
    
    // Check for overnight shift - only if no time_in today AND there's an open log from yesterday or earlier
    if (!$time_in) {
        $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
            WHERE employee_id = ? AND time_out IS NULL AND log_date < ?
            ORDER BY log_date DESC, id DESC 
            LIMIT 1");
        $overnightStmt->execute([$employee_id, $today]);
        $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($overnightLog) {
            $time_in = $overnightLog['time_in'];
            $time_out = $overnightLog['time_out'];
        }
    }
    
    echo "Time In: " . ($time_in ?? 'NULL') . "<br>";
    echo "Time Out: " . ($time_out ?? 'NULL') . "<br>";
    
    if (!$time_in) {
        echo "✅ Button: Log Time In (CORRECT for new day)<br>";
    } elseif ($time_in && !$time_out) {
        echo "❌ Button: Log Time Out (WRONG - should be Log Time In)<br>";
    } else {
        echo "❌ Button: Already Logged (WRONG)<br>";
    }
    
    // Scenario 1b: Time in logged for today (should show "Log Time Out")
    echo "<h5>Scenario 1b: Time in logged for today</h5>";
    $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
    $stmt->execute([$employee_id, $today, '07:00:00']);
    echo "✅ Inserted time in for today<br>";
    
    // Re-run the logic
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $today]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    $time_in = $log['time_in'] ?? null;
    $time_out = $log['time_out'] ?? null;
    
    // Check for overnight shift - only if no time_in today AND there's an open log from yesterday or earlier
    if (!$time_in) {
        $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
            WHERE employee_id = ? AND time_out IS NULL AND log_date < ?
            ORDER BY log_date DESC, id DESC 
            LIMIT 1");
        $overnightStmt->execute([$employee_id, $today]);
        $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($overnightLog) {
            $time_in = $overnightLog['time_in'];
            $time_out = $overnightLog['time_out'];
        }
    }
    
    echo "Time In: " . ($time_in ?? 'NULL') . "<br>";
    echo "Time Out: " . ($time_out ?? 'NULL') . "<br>";
    
    if (!$time_in) {
        echo "❌ Button: Log Time In (WRONG)<br>";
    } elseif ($time_in && !$time_out) {
        echo "✅ Button: Log Time Out (CORRECT for day shift)<br>";
    } else {
        echo "❌ Button: Already Logged (WRONG)<br>";
    }
    
    // Test 2: Night Shift Scenario
    echo "<h4>Test 2: Night Shift Scenario</h4>";
    
    // Clean up today's data and create yesterday's time in
    $cleanupStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $cleanupStmt->execute([$employee_id, $today]);
    
    // Create yesterday's time in (no time out - overnight shift)
    $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
    $stmt->execute([$employee_id, $yesterday, '19:00:00']); // 7 PM yesterday
    echo "✅ Created overnight shift: time in yesterday at 7 PM, no time out<br>";
    
    // Scenario 2a: Today with overnight shift from yesterday
    echo "<h5>Scenario 2a: Today with overnight shift from yesterday</h5>";
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $today]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    $time_in = $log['time_in'] ?? null;
    $time_out = $log['time_out'] ?? null;
    
    // Check for overnight shift - only if no time_in today AND there's an open log from yesterday or earlier
    if (!$time_in) {
        $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
            WHERE employee_id = ? AND time_out IS NULL AND log_date < ?
            ORDER BY log_date DESC, id DESC 
            LIMIT 1");
        $overnightStmt->execute([$employee_id, $today]);
        $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($overnightLog) {
            $time_in = $overnightLog['time_in'];
            $time_out = $overnightLog['time_out'];
            echo "✅ Found overnight shift from: " . $overnightLog['log_date'] . "<br>";
        }
    }
    
    echo "Time In: " . ($time_in ?? 'NULL') . "<br>";
    echo "Time Out: " . ($time_out ?? 'NULL') . "<br>";
    
    if (!$time_in) {
        echo "❌ Button: Log Time In (WRONG - should detect overnight shift)<br>";
    } elseif ($time_in && !$time_out) {
        echo "✅ Button: Log Time Out (CORRECT for overnight shift)<br>";
    } else {
        echo "❌ Button: Already Logged (WRONG)<br>";
    }
    
    // Clean up test data
    $cleanupStmt = $pdo->prepare("DELETE FROM time_logs WHERE employee_id = ? AND log_date IN (?, ?)");
    $cleanupStmt->execute([$employee_id, $today, $yesterday]);
    echo "✅ Cleaned up test data<br>";
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Expected Results:</strong></p>";
echo "<ul>";
echo "<li>✅ Regular day shift: Shows 'Log Time In' when no time logged today</li>";
echo "<li>✅ Regular day shift: Shows 'Log Time Out' when time in logged today</li>";
echo "<li>✅ Night shift: Shows 'Log Time Out' when there's an open log from yesterday</li>";
echo "</ul>";
echo "<p><strong>If all scenarios show ✅, then both day shift and night shift logic work correctly!</strong></p>";
?>
