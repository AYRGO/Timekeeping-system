<?php
/**
 * Test Script for Approved Schedule Requests Feature
 * 
 * This script tests if the calendar properly displays approved schedule change requests
 * from the post_schedule_change_requests table.
 */

// Include your database connection
require_once __DIR__ . '/db/db.php'; // Adjust path as needed

echo "<h2>Testing Approved Schedule Requests Feature</h2>\n";
echo "<hr>\n\n";

// Test employee ID - change this to test different employees
$test_employee_id = 75; // Using example from database
$test_date = '2025-08-15'; // Using example date from database

echo "<h3>Test Configuration:</h3>\n";
echo "Employee ID: $test_employee_id<br>\n";
echo "Test Date: $test_date<br>\n";
echo "<hr>\n\n";

try {
    // Check if there are any approved requests for this employee
    echo "<h3>1. Checking for Approved Requests:</h3>\n";
    $stmt = $pdo->prepare("
        SELECT * FROM post_schedule_change_requests 
        WHERE employee_id = ? 
        AND status = 'Approved'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$test_employee_id]);
    $approvedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($approvedRequests) . " approved request(s) for employee $test_employee_id<br>\n";
    
    if (count($approvedRequests) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>\n";
        echo "<tr>
                <th>ID</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Schedule ID</th>
                <th>Reason</th>
              </tr>\n";
        
        foreach ($approvedRequests as $req) {
            echo "<tr>
                    <td>{$req['id']}</td>
                    <td>{$req['status']}</td>
                    <td>{$req['start_date']}</td>
                    <td>{$req['end_date']}</td>
                    <td>{$req['work_schedule_id']}</td>
                    <td>" . substr($req['reason'], 0, 50) . "...</td>
                  </tr>\n";
        }
        echo "</table>\n";
    }
    echo "<br>\n";
    
    // Test the date range query
    echo "<h3>2. Testing Date Range Query for $test_date:</h3>\n";
    $dateStmt = $pdo->prepare("
        SELECT * FROM post_schedule_change_requests 
        WHERE employee_id = ? 
        AND status = 'Approved' 
        AND ? BETWEEN start_date AND end_date 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $dateStmt->execute([$test_employee_id, $test_date]);
    $matchedRequest = $dateStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($matchedRequest) {
        echo "✅ <strong>MATCH FOUND!</strong> Request applies to this date.<br>\n";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>\n";
        echo "<tr><th>Field</th><th>Value</th></tr>\n";
        echo "<tr><td>Request ID</td><td>{$matchedRequest['id']}</td></tr>\n";
        echo "<tr><td>Status</td><td><strong>{$matchedRequest['status']}</strong></td></tr>\n";
        echo "<tr><td>Date Range</td><td>{$matchedRequest['start_date']} to {$matchedRequest['end_date']}</td></tr>\n";
        echo "<tr><td>Work Schedule ID</td><td>{$matchedRequest['work_schedule_id']}</td></tr>\n";
        echo "<tr><td>Reason</td><td>{$matchedRequest['reason']}</td></tr>\n";
        echo "</table>\n";
        
        // Get the actual work schedule details
        if ($matchedRequest['work_schedule_id']) {
            echo "<br><h3>3. Work Schedule Details:</h3>\n";
            $schedStmt = $pdo->prepare("SELECT * FROM work_schedules WHERE id = ?");
            $schedStmt->execute([$matchedRequest['work_schedule_id']]);
            $schedule = $schedStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($schedule) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
                echo "<tr><th>Schedule ID</th><th>Name</th><th>Time In</th><th>Time Out</th></tr>\n";
                echo "<tr>
                        <td>{$schedule['id']}</td>
                        <td>{$schedule['name']}</td>
                        <td>" . date('g:i A', strtotime($schedule['time_in'])) . "</td>
                        <td>" . date('g:i A', strtotime($schedule['time_out'])) . "</td>
                      </tr>\n";
                echo "</table>\n";
                
                echo "<br><div style='background: #f3e8ff; border: 2px solid #8b5cf6; padding: 15px; border-radius: 8px;'>\n";
                echo "<strong>📅 Calendar Display Preview:</strong><br>\n";
                echo "This date will show:<br>\n";
                echo "• <strong style='color: #8b5cf6;'>Purple card background</strong><br>\n";
                echo "• Badge: \"APPROVED REQUEST\"<br>\n";
                echo "• Schedule: {$schedule['name']}<br>\n";
                echo "• Time: " . date('g:i A', strtotime($schedule['time_in'])) . " - " . date('g:i A', strtotime($schedule['time_out'])) . "<br>\n";
                echo "</div>\n";
            } else {
                echo "❌ Work schedule not found in database.<br>\n";
            }
        }
    } else {
        echo "❌ <strong>NO MATCH</strong> - No approved request applies to this date.<br>\n";
        echo "The calendar will show the default weekly schedule or holiday status instead.<br>\n";
    }
    
    echo "<br><hr>\n";
    echo "<h3>4. Summary:</h3>\n";
    echo "The feature is working if:<br>\n";
    echo "✅ Approved requests are found in the database<br>\n";
    echo "✅ The date range query correctly identifies requests for specific dates<br>\n";
    echo "✅ Work schedule details can be retrieved<br>\n";
    echo "<br>\n";
    echo "To test on the calendar:<br>\n";
    echo "1. Login as employee ID $test_employee_id<br>\n";
    echo "2. Navigate to the Schedule view<br>\n";
    echo "3. Check date: $test_date<br>\n";
    echo "4. Look for <strong style='color: #8b5cf6;'>purple-colored</strong> schedule cards<br>\n";
    echo "5. Verify \"APPROVED REQUEST\" badge appears<br>\n";
    
} catch (PDOException $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>\n";
    echo "Please check your database connection and table structure.<br>\n";
}

echo "<hr>\n";
echo "<p><small>Test completed at " . date('Y-m-d H:i:s') . "</small></p>\n";
?>
