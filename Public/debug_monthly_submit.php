<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Monthly Schedule Debug Test</h2>";
echo "<pre>";
echo "POST Data:\n";
print_r($_POST);
echo "\n\nFILES Data:\n";
print_r($_FILES);
echo "\n\nSESSION Data:\n";
session_start();
print_r($_SESSION);
echo "</pre>";

// Test if monthly schedule was submitted
if (isset($_POST['submit_schedule_change'])) {
    $request_type = $_POST['request_type'] ?? 'NOT SET';
    echo "<h3>Request Type: <strong>$request_type</strong></h3>";
    
    if ($request_type === 'monthly') {
        echo "<p style='color: green;'>✅ Monthly request detected!</p>";
        
        // Check all required fields
        $checks = [
            'schedule_month' => $_POST['schedule_month'] ?? null,
            'reason' => $_POST['reason'] ?? null,
            'attachment' => isset($_FILES['attachment_scr']) ? 'File uploaded' : 'NO FILE',
            'employee_id' => $_SESSION['employee']['id'] ?? 'NOT SET'
        ];
        
        echo "<h3>Field Checks:</h3><ul>";
        foreach ($checks as $field => $value) {
            $status = $value ? '✅' : '❌';
            echo "<li>$status <strong>$field</strong>: $value</li>";
        }
        echo "</ul>";
        
        // Check weekly schedules
        echo "<h3>Weekly Schedule Configuration:</h3><ul>";
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($days as $day) {
            $value = $_POST[$day . '_schedule'] ?? 'NOT SET';
            echo "<li><strong>$day</strong>: $value</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Request type is: $request_type (not monthly)</p>";
    }
} else {
    echo "<p style='color: gray;'>No form submission detected yet.</p>";
    echo "<p>Submit the form to see debug data here.</p>";
}
?>

<hr>
<h3>Test Form</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="submit_schedule_change" value="1">
    <input type="hidden" name="request_type" value="monthly">
    
    <p>
        <label>Month: <input type="month" name="schedule_month" value="2025-11"></label>
    </p>
    
    <p>
        <label>Monday: 
            <select name="monday_schedule">
                <option value="">-- Select --</option>
                <option value="rest_day">Rest Day</option>
                <option value="1">Schedule 1</option>
            </select>
        </label>
    </p>
    
    <p>
        <label>Reason: <textarea name="reason" rows="3">Test reason</textarea></label>
    </p>
    
    <p>
        <label>File: <input type="file" name="attachment_scr"></label>
    </p>
    
    <p>
        <button type="submit">Test Submit</button>
    </p>
</form>
