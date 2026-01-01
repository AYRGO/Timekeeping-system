<?php
// Connect to PRODUCTION Hostinger database
$pdo = new PDO(
    "mysql:host=localhost;dbname=u816220874_calendartype",
    "u816220874_calendartype",
    "Gr33n$$wRf"
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== CHECKING PRODUCTION (HOSTINGER) DATABASE ===\n\n";

// Check employee_default_schedules
echo "1. Employee Default Schedules (weekly pattern):\n";
echo "------------------------------------------------\n";
$stmt = $pdo->query("
    SELECT e.id, e.lname, e.fname, edd.day_of_week, 
           ws.name as schedule_name, ws.time_in, ws.time_out,
           edd.effective_from, edd.effective_until
    FROM employees e 
    JOIN employee_default_schedules edd ON e.id = edd.employee_id 
    LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id 
    WHERE e.lname IN ('Pangilinan', 'Pasion')
    AND (e.fname = 'Roi Dane' OR e.fname = 'Paul')
    ORDER BY e.lname, edd.day_of_week
");

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$currentEmp = null;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $empName = $row['lname'] . ', ' . $row['fname'] . ' (ID:' . $row['id'] . ')';
    if ($empName !== $currentEmp) {
        if ($currentEmp !== null) echo "\n";
        echo $empName . ":\n";
        $currentEmp = $empName;
    }
    $dayName = $days[$row['day_of_week']];
    echo "  " . $dayName . ': ' . ($row['schedule_name'] ?? 'REST') . ' - ' . 
         ($row['time_in'] ?? '-') . ' to ' . ($row['time_out'] ?? '-');
    echo ' [Effective: ' . $row['effective_from'] . ' to ' . ($row['effective_until'] ?? 'ongoing') . "]\n";
}

echo "\n2. Employee Daily Schedule Cache (for January 2026):\n";
echo "------------------------------------------------\n";
$stmt = $pdo->query("
    SELECT e.lname, e.fname, cache.schedule_date,
           cache.schedule_name, cache.time_in, cache.time_out,
           cache.source, cache.is_rest_day
    FROM employee_daily_schedule_cache cache
    JOIN employees e ON cache.employee_id = e.id
    WHERE e.lname IN ('Pangilinan', 'Pasion')
    AND (e.fname = 'Roi Dane' OR e.fname = 'Paul')
    AND cache.schedule_date BETWEEN '2026-01-01' AND '2026-01-10'
    ORDER BY e.lname, cache.schedule_date
");

$currentEmp = null;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $empName = $row['lname'] . ', ' . $row['fname'];
    if ($empName !== $currentEmp) {
        if ($currentEmp !== null) echo "\n";
        echo $empName . ":\n";
        $currentEmp = $empName;
    }
    $dayName = date('D', strtotime($row['schedule_date']));
    echo "  " . $row['schedule_date'] . ' (' . $dayName . '): ';
    if ($row['is_rest_day']) {
        echo "REST DAY";
    } else {
        echo ($row['schedule_name'] ?? 'N/A') . ' - ' . 
             ($row['time_in'] ?? '-') . ' to ' . ($row['time_out'] ?? '-');
    }
    echo ' [Source: ' . $row['source'] . "]\n";
}

echo "\n3. Approved Schedule Change Requests (affecting Jan 2026):\n";
echo "------------------------------------------------\n";
$stmt = $pdo->query("
    SELECT e.lname, e.fname, pscr.start_date, pscr.end_date,
           ws.name as new_schedule, ws.time_in, ws.time_out,
           pscr.status, pscr.created_at
    FROM post_schedule_change_requests pscr
    JOIN employees e ON pscr.employee_id = e.id
    LEFT JOIN work_schedules ws ON pscr.work_schedule_id = ws.id
    WHERE e.lname IN ('Pangilinan', 'Pasion')
    AND (e.fname = 'Roi Dane' OR e.fname = 'Paul')
    AND pscr.status = 'Approved'
    AND pscr.end_date >= '2026-01-01'
    ORDER BY e.lname, pscr.start_date
");

$found = false;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $found = true;
    echo $row['lname'] . ', ' . $row['fname'] . "\n";
    echo "  Period: " . $row['start_date'] . ' to ' . $row['end_date'] . "\n";
    echo "  New Schedule: " . ($row['new_schedule'] ?? 'N/A') . ' - ' . 
         ($row['time_in'] ?? '-') . ' to ' . ($row['time_out'] ?? '-') . "\n";
    echo "  Status: " . $row['status'] . " (Created: " . $row['created_at'] . ")\n\n";
}

if (!$found) {
    echo "No approved schedule change requests found.\n";
}

echo "\n=== ANALYSIS ===\n";
echo "This shows what's ACTUALLY stored in your Hostinger production database.\n";
echo "The payroll report generates based on this data.\n";
