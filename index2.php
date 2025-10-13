<?php
// Simple test script for time logging functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Time Logging Functionality Test</h2>";

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
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<h3>Testing Time In Functionality</h3>";

// Test 1: Check if we can insert a time log
try {
    $employee_id = 1; // Test employee ID
    $current_date = date("Y-m-d");
    $current_time = date("H:i:s");
    
    echo "Testing with:<br>";
    echo "Employee ID: $employee_id<br>";
    echo "Date: $current_date<br>";
    echo "Time: $current_time<br><br>";
    
    // Check if there's already a time_in for today
    $checkStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_in IS NOT NULL");
    $checkStmt->execute([$employee_id, $current_date]);
    $existingLog = $checkStmt->fetch();
    
    if ($existingLog) {
        echo "⚠️ Time in already exists for today<br>";
    } else {
        echo "✅ No existing time in found, proceeding with test<br>";
        
        // Test insert
        $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
        $result = $stmt->execute([$employee_id, $current_date, $current_time]);
        
        if ($result) {
            echo "✅ Time in INSERT successful<br>";
            $insertId = $pdo->lastInsertId();
            echo "Insert ID: $insertId<br>";
            
            // Test update (time out)
            echo "<h3>Testing Time Out Functionality</h3>";
            $timeOutTime = date("H:i:s", strtotime($current_time . " +8 hours")); // 8 hours later
            echo "Testing time out at: $timeOutTime<br>";
            
            $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE id = ?");
            $updateResult = $updateStmt->execute([$timeOutTime, $insertId]);
            
            if ($updateResult) {
                echo "✅ Time out UPDATE successful<br>";
                
                // Verify the record
                $verifyStmt = $pdo->prepare("SELECT * FROM time_logs WHERE id = ?");
                $verifyStmt->execute([$insertId]);
                $record = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($record) {
                    echo "<h4>Final Record:</h4>";
                    echo "<table border='1'>";
                    echo "<tr><th>Field</th><th>Value</th></tr>";
                    foreach ($record as $field => $value) {
                        echo "<tr><td>$field</td><td>$value</td></tr>";
                    }
                    echo "</table>";
                }
                
                // Clean up test record
                $deleteStmt = $pdo->prepare("DELETE FROM time_logs WHERE id = ?");
                $deleteStmt->execute([$insertId]);
                echo "✅ Test record cleaned up<br>";
                
            } else {
                echo "❌ Time out UPDATE failed<br>";
            }
        } else {
            echo "❌ Time in INSERT failed<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
}

echo "<h3>Testing Overnight Shift Logic</h3>";

// Test 2: Test overnight shift logic (time out without time in for today)
try {
    $employee_id = 1;
    $yesterday = date("Y-m-d", strtotime("-1 day"));
    $yesterday_time = "17:00:00"; // 5 PM yesterday
    $today_time_out = "09:00:00"; // 9 AM today
    
    echo "Testing overnight shift:<br>";
    echo "Yesterday date: $yesterday<br>";
    echo "Yesterday time in: $yesterday_time<br>";
    echo "Today time out: $today_time_out<br><br>";
    
    // Insert yesterday's time in
    $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
    $stmt->execute([$employee_id, $yesterday, $yesterday_time]);
    $overnightId = $pdo->lastInsertId();
    
    echo "✅ Yesterday's time in inserted (ID: $overnightId)<br>";
    
    // Test finding open log
    $openStmt = $pdo->prepare("SELECT id FROM time_logs 
        WHERE employee_id = ? AND time_out IS NULL 
        ORDER BY log_date DESC, id DESC 
        LIMIT 1");
    $openStmt->execute([$employee_id]);
    $openLog = $openStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($openLog) {
        echo "✅ Found open log (ID: " . $openLog['id'] . ")<br>";
        
        // Test updating the open log
        $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE id = ?");
        $updateResult = $updateStmt->execute([$today_time_out, $openLog['id']]);
        
        if ($updateResult) {
            echo "✅ Overnight time out UPDATE successful<br>";
        } else {
            echo "❌ Overnight time out UPDATE failed<br>";
        }
    } else {
        echo "❌ No open log found<br>";
    }
    
    // Clean up test record
    $deleteStmt = $pdo->prepare("DELETE FROM time_logs WHERE id = ?");
    $deleteStmt->execute([$overnightId]);
    echo "✅ Overnight test record cleaned up<br>";
    
} catch (Exception $e) {
    echo "❌ Overnight test error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Test Summary</h3>";
echo "<p>If all tests show ✅, then the time logging functionality should work.</p>";
echo "<p>If any tests show ❌, those are the issues that need to be fixed.</p>";

echo "<h4>Common Issues to Check:</h4>";
echo "<ul>";
echo "<li><strong>Database Permissions:</strong> Ensure your database user has INSERT, UPDATE, SELECT permissions</li>";
echo "<li><strong>Table Structure:</strong> Verify time_logs table has correct columns (id, employee_id, log_date, time_in, time_out)</li>";
echo "<li><strong>PHP Version:</strong> Ensure hosting PHP version is compatible (7.4+ recommended)</li>";
echo "<li><strong>PDO Extensions:</strong> PDO and PDO_MySQL must be enabled</li>";
echo "</ul>";
?>
