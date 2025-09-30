<?php
session_start();
require_once '../config/db.php';

// Debug script to check time adjustment requests
echo "<h3>Debug: Time Adjustment Requests</h3>";

// Check current session
echo "<h4>Current Session:</h4>";
echo "Employee ID: " . ($_SESSION['employee']['id'] ?? 'Not set') . "<br>";
echo "Employee Name: " . ($_SESSION['employee']['fname'] ?? 'Not set') . " " . ($_SESSION['employee']['lname'] ?? 'Not set') . "<br>";
echo "<br>";

$currentEmployeeId = $_SESSION['employee']['id'] ?? null;

if (!$currentEmployeeId) {
    echo "❌ No employee logged in!<br>";
    exit;
}

// Check both time adjustment tables
$tables = ['time_adjustment_requests', 'post_time_adjustment_requests'];

foreach ($tables as $table) {
    echo "<h4>Table: $table</h4>";
    
    try {
        // Check if table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($checkTable == 0) {
            echo "❌ Table '$table' does not exist<br><br>";
            continue;
        }
        
        // Get all columns in the table
        echo "<strong>Table Structure:</strong><br>";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}<br>";
        }
        echo "<br>";
        
        // Get all records for current employee
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$currentEmployeeId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>Recent Records for Employee ID $currentEmployeeId:</strong><br>";
        if (empty($requests)) {
            echo "❌ No records found<br>";
        } else {
            foreach ($requests as $req) {
                echo "ID: {$req['id']}, Status: " . ($req['status'] ?? 'N/A') . ", ";
                echo "Log Date: " . ($req['log_date'] ?? 'N/A') . ", ";
                echo "Requested Time In: " . ($req['requested_time_in'] ?? 'N/A') . ", ";
                echo "Requested Time Out: " . ($req['requested_time_out'] ?? 'N/A') . ", ";
                echo "Reason: " . ($req['reason'] ?? 'N/A') . "<br>";
            }
        }
        echo "<br>";
        
        // Get ALL records to see what employee IDs exist
        $allStmt = $pdo->query("SELECT id, employee_id, status, log_date FROM $table ORDER BY created_at DESC LIMIT 10");
        $allRequests = $allStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<strong>All Recent Records (any employee):</strong><br>";
        foreach ($allRequests as $req) {
            echo "ID: {$req['id']}, Employee ID: {$req['employee_id']}, Status: " . ($req['status'] ?? 'N/A') . ", ";
            echo "Log Date: " . ($req['log_date'] ?? 'N/A') . "<br>";
        }
        echo "<br>";
        
    } catch (Exception $e) {
        echo "❌ Error checking table $table: " . $e->getMessage() . "<br><br>";
    }
}

// Also check what request ID is being sent from the modal
echo "<h4>JavaScript Debug Info:</h4>";
echo "Check browser console for the 'Updating time adjustment:' log message to see what request_id and table_name are being sent.<br>";
?>

<script>
// Add this to help debug the AJAX call
console.log('=== TIME ADJUSTMENT DEBUG ===');
console.log('Current page URL:', window.location.href);
console.log('Session employee ID from PHP:', <?php echo json_encode($currentEmployeeId); ?>);

// Override the updateTimeAdjustment function temporarily for debugging
window.originalUpdateTimeAdjustment = window.updateTimeAdjustment;
window.updateTimeAdjustment = function(requestId, tableName, timeInValue, timeOutValue) {
    console.log('=== DEBUG UPDATE TIME ADJUSTMENT ===');
    console.log('Request ID:', requestId);
    console.log('Table Name:', tableName);
    console.log('Time In Value:', timeInValue);
    console.log('Time Out Value:', timeOutValue);
    console.log('Employee ID from session:', <?php echo json_encode($currentEmployeeId); ?>);
    
    // Call the original function
    if (window.originalUpdateTimeAdjustment) {
        return window.originalUpdateTimeAdjustment(requestId, tableName, timeInValue, timeOutValue);
    }
};
</script>