<?php
$pdo = new PDO("mysql:host=localhost;dbname=rss", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== UPDATING SCHEDULES TO 7PM-3AM ===\n\n";

// First, check if Night shift schedule exists in work_schedules
$stmt = $pdo->query("SELECT id, name, time_in, time_out FROM work_schedules WHERE time_in = '19:00:00' AND time_out = '03:00:00'");
$nightShift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nightShift) {
    echo "Creating Night shift schedule (7pm-3am)...\n";
    $pdo->exec("INSERT INTO work_schedules (name, time_in, time_out) VALUES ('Night shift', '19:00:00', '03:00:00')");
    $nightShiftId = $pdo->lastInsertId();
    echo "Created Night shift with ID: $nightShiftId\n\n";
} else {
    $nightShiftId = $nightShift['id'];
    echo "Night shift already exists with ID: $nightShiftId\n";
    echo "Name: " . $nightShift['name'] . ", Time: " . $nightShift['time_in'] . " to " . $nightShift['time_out'] . "\n\n";
}

// Get employee IDs
$employees = [
    ['name' => 'Pangilinan, Roi Dane', 'id' => 84],
    ['name' => 'Pasion, Paul', 'id' => 85]
];

foreach ($employees as $emp) {
    echo "Updating schedule for " . $emp['name'] . " (ID: " . $emp['id'] . ")...\n";
    
    // Update Monday through Friday (day_of_week 1-5) to Night shift
    for ($day = 1; $day <= 5; $day++) {
        $stmt = $pdo->prepare("
            UPDATE employee_default_schedules 
            SET work_schedule_id = ?, is_rest_day = 0
            WHERE employee_id = ? AND day_of_week = ?
        ");
        $stmt->execute([$nightShiftId, $emp['id'], $day]);
        
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        echo "  Updated " . $dayNames[$day] . " to Night shift (7pm-3am)\n";
    }
    echo "\n";
}

echo "Schedule update complete!\n\n";

echo "Verifying changes:\n";
echo "------------------------------------------------\n";
$stmt = $pdo->query("
    SELECT e.lname, e.fname, edd.day_of_week, 
           ws.name as schedule_name, ws.time_in, ws.time_out
    FROM employees e 
    JOIN employee_default_schedules edd ON e.id = edd.employee_id 
    LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id 
    WHERE e.id IN (84, 85)
    ORDER BY e.lname, edd.day_of_week
");

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$currentEmp = null;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $empName = $row['lname'] . ', ' . $row['fname'];
    if ($empName !== $currentEmp) {
        if ($currentEmp !== null) echo "\n";
        echo $empName . ":\n";
        $currentEmp = $empName;
    }
    $dayName = $days[$row['day_of_week']];
    echo "  " . $dayName . ': ' . ($row['schedule_name'] ?? 'REST') . ' - ' . 
         ($row['time_in'] ?? '-') . ' to ' . ($row['time_out'] ?? '-') . "\n";
}

echo "\n=== DONE ===\n";
echo "Note: You may need to regenerate the employee_daily_schedule_cache\n";
echo "for these changes to appear in the calendar and reports.\n";
