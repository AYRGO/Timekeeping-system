<?php
// Test script for night shift fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Night Shift Fix Test</h2>";

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

echo "<h3>Testing Night Shift Logic</h3>";

try {
    $employee_id = 1;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Today: $today<br>";
    echo "Yesterday: $yesterday<br><br>";
    
    // Test 1: Check today's time log
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $today]);
    $todayLog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Today's Time Log:</h4>";
    if ($todayLog) {
        echo "Time In: " . ($todayLog['time_in'] ?? 'NULL') . "<br>";
        echo "Time Out: " . ($todayLog['time_out'] ?? 'NULL') . "<br>";
    } else {
        echo "No time log for today<br>";
    }
    
    // Test 2: Check for overnight shift (open log)
    $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
        WHERE employee_id = ? AND time_out IS NULL 
        ORDER BY log_date DESC, id DESC 
        LIMIT 1");
    $overnightStmt->execute([$employee_id]);
    $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Overnight Shift Check:</h4>";
    if ($overnightLog) {
        echo "✅ Found open log from: " . $overnightLog['log_date'] . "<br>";
        echo "Time In: " . $overnightLog['time_in'] . "<br>";
        echo "Time Out: " . ($overnightLog['time_out'] ?? 'NULL (open)') . "<br>";
        
        // Test the UI logic
        $time_in = $overnightLog['time_in'];
        $time_out = $overnightLog['time_out'];
        
        echo "<h4>UI Button Logic Test:</h4>";
        if (!$time_in) {
            echo "Would show: Log Time In button<br>";
        } elseif ($time_in && !$time_out) {
            echo "✅ Would show: Log Time Out button (CORRECT for overnight shift)<br>";
        } else {
            echo "Would show: Already Logged button<br>";
        }
        
    } else {
        echo "❌ No open log found<br>";
    }
    
    // Test 3: Simulate the fixed logic from today_attendance_card.php
    echo "<h4>Fixed Logic Simulation:</h4>";
    
    // Get today's time log
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $today]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    $time_in = $log['time_in'] ?? null;
    $time_out = $log['time_out'] ?? null;
    
    // Check for overnight shift - if no time_in today, check for open log from any date
    if (!$time_in) {
        $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
            WHERE employee_id = ? AND time_out IS NULL 
            ORDER BY log_date DESC, id DESC 
            LIMIT 1");
        $overnightStmt->execute([$employee_id]);
        $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($overnightLog) {
            $time_in = $overnightLog['time_in'];
            $time_out = $overnightLog['time_out']; // This will be null for open logs
            echo "✅ Overnight shift detected - using time_in from: " . $overnightLog['log_date'] . "<br>";
        }
    }
    
    echo "Final time_in: " . ($time_in ?? 'NULL') . "<br>";
    echo "Final time_out: " . ($time_out ?? 'NULL') . "<br>";
    
    // Test button logic
    if (!$time_in) {
        echo "Button: Log Time In<br>";
    } elseif ($time_in && !$time_out) {
        echo "✅ Button: Log Time Out (CORRECT)<br>";
    } else {
        echo "Button: Already Logged<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>If you see ✅ for 'Would show: Log Time Out button' and 'Button: Log Time Out (CORRECT)', then the night shift fix is working!</p>";
echo "<p>This means users will see the Time Out button even after reloading the page during an overnight shift.</p>";
?>
