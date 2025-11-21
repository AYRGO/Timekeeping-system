<?php
// Test monthly schedule database and submission
require_once '../config/connection.php';

echo "<h2>Monthly Schedule Request Test</h2>";

// Check if table exists
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'month_weekly_schedule'");
    if ($checkTable->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table 'month_weekly_schedule' exists!</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $structure = $pdo->query("DESCRIBE month_weekly_schedule");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show existing records
        echo "<h3>Existing Records:</h3>";
        $records = $pdo->query("SELECT * FROM month_weekly_schedule ORDER BY created_at DESC LIMIT 10");
        $count = $records->rowCount();
        
        if ($count > 0) {
            echo "<p>Found $count record(s):</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Employee</th><th>Year</th><th>Month</th><th>Status</th><th>Created</th><th>Processed</th></tr>";
            while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['employee_id']}</td>";
                echo "<td>{$row['year']}</td>";
                echo "<td>{$row['month']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['created_at']}</td>";
                echo "<td>{$row['processed_at']}</td>";
                echo "</tr>";
                
                // Show schedule details
                echo "<tr><td colspan='7'>";
                echo "<strong>Weekly Schedule:</strong><br>";
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                foreach ($days as $day) {
                    $schedId = $row[$day . '_schedule_id'];
                    $isRest = $row[$day . '_is_rest_day'];
                    echo ucfirst($day) . ": ";
                    if ($isRest) {
                        echo "<span style='color: red;'>Rest Day</span>";
                    } elseif ($schedId) {
                        echo "Schedule ID: $schedId";
                    } else {
                        echo "<span style='color: gray;'>Not set</span>";
                    }
                    echo "<br>";
                }
                echo "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No records found.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Table 'month_weekly_schedule' does NOT exist!</p>";
        echo "<p>Please run the SQL file: create_month_weekly_schedule.sql</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test form submission simulation
echo "<h3>Form Submission Test</h3>";
echo "<p>Check browser console for POST data when submitting the form.</p>";
echo "<p><a href='../views/time_log_create.php'>Go to Schedule Request Form</a></p>";
?>

<style>
table {
    border-collapse: collapse;
    margin: 20px 0;
}
th {
    background-color: #4CAF50;
    color: white;
}
</style>
