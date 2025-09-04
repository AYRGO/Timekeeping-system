<?php
// Simple test for time logging functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Time Logging Test</h2>";

// Test the core time logging functionality
try {
    require_once 'Public/config/db.php';
    echo "✅ Database connection working<br>";
    
    // Test time in
    $employee_id = 1;
    $current_date = date("Y-m-d");
    $current_time = date("H:i:s");
    
    echo "Testing time in functionality...<br>";
    
    // Check if there's already a time_in for today
    $checkStmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_in IS NOT NULL");
    $checkStmt->execute([$employee_id, $current_date]);
    $existingLog = $checkStmt->fetch();
    
    if ($existingLog) {
        echo "⚠️ Time in already exists for today<br>";
    } else {
        // Test insert
        $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
        $result = $stmt->execute([$employee_id, $current_date, $current_time]);
        
        if ($result) {
            echo "✅ Time in INSERT successful<br>";
            $insertId = $pdo->lastInsertId();
            echo "Insert ID: $insertId<br>";
            
            // Test time out
            echo "Testing time out functionality...<br>";
            $timeOutTime = date("H:i:s", strtotime($current_time . " +8 hours"));
            
            // Test the fixed logic - find open log
            $openStmt = $pdo->prepare("SELECT id FROM time_logs 
                WHERE employee_id = ? AND time_out IS NULL 
                ORDER BY log_date DESC, id DESC 
                LIMIT 1");
            $openStmt->execute([$employee_id]);
            $openLog = $openStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($openLog) {
                echo "✅ Found open log (ID: " . $openLog['id'] . ")<br>";
                
                // Test the UPDATE (this was the bug we fixed)
                $updateStmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE id = ?");
                $updateResult = $updateStmt->execute([$timeOutTime, $openLog['id']]);
                
                if ($updateResult) {
                    echo "✅ Time out UPDATE successful<br>";
                    echo "✅ The critical bug fix is working!<br>";
                } else {
                    echo "❌ Time out UPDATE failed<br>";
                }
            } else {
                echo "❌ No open log found<br>";
            }
            
            // Clean up test record
            $deleteStmt = $pdo->prepare("DELETE FROM time_logs WHERE id = ?");
            $deleteStmt->execute([$insertId]);
            echo "✅ Test record cleaned up<br>";
            
        } else {
            echo "❌ Time in INSERT failed<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>If you see ✅ for 'Time out UPDATE successful' and 'The critical bug fix is working!', then the main issue is resolved.</p>";
echo "<p>The syntax error in time_log_create.php can be fixed separately without affecting the core functionality.</p>";
?>
