<?php
require_once('Public/config/db.php');

echo "=== WEEKLY SCHEDULE TEST ===\n\n";

// Test 1: Check if employee_default_schedules table is ready
echo "1. Checking table structure...\n";
$cols = $pdo->query("DESCRIBE employee_default_schedules")->fetchAll(PDO::FETCH_ASSOC);
$hasCorrectStructure = false;
foreach ($cols as $col) {
    if ($col['Field'] === 'day_of_week') {
        $hasCorrectStructure = true;
        break;
    }
}

if ($hasCorrectStructure) {
    echo "   ✓ Table has correct structure (day_of_week column exists)\n\n";
} else {
    echo "   ✗ Table structure is wrong!\n\n";
    exit;
}

// Test 2: Check work_schedules table
echo "2. Checking available work schedules...\n";
$schedules = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if (count($schedules) > 0) {
    echo "   ✓ Found " . count($schedules) . " work schedules:\n";
    foreach ($schedules as $sched) {
        echo "     - [{$sched['id']}] {$sched['name']} ({$sched['time_in']} - {$sched['time_out']})\n";
    }
} else {
    echo "   ✗ No work schedules found! You need to create schedules first.\n";
}

echo "\n3. Checking existing weekly schedules...\n";
$existing = $pdo->query("SELECT COUNT(*) FROM employee_default_schedules")->fetchColumn();
echo "   Total records: $existing\n";

if ($existing > 0) {
    echo "\n   Sample records:\n";
    $rows = $pdo->query("SELECT eds.*, e.fname, e.lname, ws.name as schedule_name 
                         FROM employee_default_schedules eds 
                         LEFT JOIN employees e ON e.id = eds.employee_id 
                         LEFT JOIN work_schedules ws ON ws.id = eds.work_schedule_id 
                         LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($rows as $row) {
        $dayName = $dayNames[$row['day_of_week']];
        $schedule = $row['is_rest_day'] ? 'REST DAY' : ($row['schedule_name'] ?? 'Unknown');
        echo "     - {$row['fname']} {$row['lname']}: {$dayName} = {$schedule}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNext steps:\n";
echo "1. Go to employee-edit.php?id=[employee_id]\n";
echo "2. Click on 'Weekly Schedule' tab\n";
echo "3. Select schedules for each day of the week\n";
echo "4. Click 'Update Weekly Schedule'\n";
echo "5. Go to 'Current Schedule' tab to see the calendar populated!\n";
