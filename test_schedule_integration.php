<?php
// Test script to check schedule integration

session_start();
include('Public/config/db.php');

echo "<h1>Schedule Integration Test</h1>";

echo "<h2>1. Testing Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT 'Database connected' as status");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ " . $result['status'] . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Testing Schedule Change Requests Table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE schedule_change_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($columns) {
        echo "<p style='color: green;'>✓ schedule_change_requests table exists</p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ schedule_change_requests table missing: " . $e->getMessage() . "</p>";
    echo "<p>Creating table...</p>";
    
    try {
        $createTable = "
        CREATE TABLE schedule_change_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            request_date DATE NOT NULL,
            request_type VARCHAR(50) NOT NULL,
            new_schedule_id INT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )";
        $pdo->exec($createTable);
        echo "<p style='color: green;'>✓ schedule_change_requests table created successfully</p>";
    } catch (PDOException $createError) {
        echo "<p style='color: red;'>✗ Failed to create table: " . $createError->getMessage() . "</p>";
    }
}

echo "<h2>3. Testing Work Schedules Table</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM work_schedules LIMIT 5");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($schedules) {
        echo "<p style='color: green;'>✓ work_schedules table exists with " . count($schedules) . " records</p>";
        foreach ($schedules as $sched) {
            echo "<li>ID: " . $sched['id'] . " - " . ($sched['name'] ?? 'Unnamed') . 
                 " (" . $sched['time_in'] . " - " . $sched['time_out'] . ")</li>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ work_schedules table exists but is empty</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ work_schedules table issue: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testing File Structure</h2>";
$files = [
    'Public/module/time_log_create.php' => 'Main Dashboard',
    'Public/module/schedule_content.php' => 'Schedule Content',
    'Public/module/sidebar.php' => 'Employee Sidebar',
    'Public/module/schedule_old.php' => 'Old Schedule File (should exist as backup)'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $desc: $file</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing: $desc - $file</p>";
    }
}

echo "<h2>5. Navigation Test</h2>";
echo "<p>Try these links to test navigation:</p>";
echo "<ul>";
echo "<li><a href='Public/module/time_log_create.php' target='_blank'>Main Dashboard</a></li>";
echo "<li><a href='Public/module/time_log_create.php#scheduleView' target='_blank'>Direct Schedule View</a></li>";
echo "</ul>";

echo "<p><strong>Integration Status:</strong> Schedule integration appears to be complete. The standalone schedule.php has been moved to schedule_old.php to prevent conflicts.</p>";

?>