<?php
// Check schedule data in database
require_once __DIR__ . '/Public/config/db.php';

echo "<h1>Schedule Data Diagnostic</h1>";
echo "<style>table { border-collapse: collapse; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; } th { background: #4CAF50; color: white; }</style>";

// Get employee ID from URL or use default
$employee_id = isset($_GET['emp_id']) ? (int)$_GET['emp_id'] : 1;

echo "<h2>Checking data for Employee ID: $employee_id</h2>";

// 1. Check if employee exists
echo "<h3>1. Employee Data</h3>";
$emp = $pdo->prepare("SELECT id, fname, lname, official_sched FROM employees WHERE id = ?");
$emp->execute([$employee_id]);
$employee = $emp->fetch(PDO::FETCH_ASSOC);
if ($employee) {
    echo "<table><tr><th>ID</th><th>Name</th><th>Official Schedule ID</th></tr>";
    echo "<tr><td>{$employee['id']}</td><td>{$employee['fname']} {$employee['lname']}</td><td>{$employee['official_sched']}</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Employee not found!</p>";
    exit;
}

// 2. Check work_schedules table
echo "<h3>2. Available Work Schedules</h3>";
$schedules = $pdo->query("SELECT * FROM work_schedules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
if ($schedules) {
    echo "<table><tr><th>ID</th><th>Name</th><th>Time In</th><th>Time Out</th></tr>";
    foreach ($schedules as $sched) {
        echo "<tr><td>{$sched['id']}</td><td>{$sched['name']}</td><td>{$sched['time_in']}</td><td>{$sched['time_out']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No work schedules found!</p>";
}

// 3. Check employee_default_schedules (weekly default)
echo "<h3>3. Employee Default Schedules (Weekly Pattern)</h3>";
$defaults = $pdo->prepare("SELECT * FROM employee_default_schedules WHERE employee_id = ?");
$defaults->execute([$employee_id]);
$defaultSchedules = $defaults->fetchAll(PDO::FETCH_ASSOC);
if ($defaultSchedules) {
    echo "<table><tr><th>Day of Week</th><th>Work Schedule ID</th><th>Is Rest Day</th><th>Effective From</th><th>Effective Until</th></tr>";
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    foreach ($defaultSchedules as $def) {
        $dayName = $days[$def['day_of_week']] ?? 'Unknown';
        echo "<tr><td>$dayName ({$def['day_of_week']})</td><td>{$def['work_schedule_id']}</td><td>{$def['is_rest_day']}</td><td>{$def['effective_from']}</td><td>{$def['effective_until']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No weekly default schedules found for this employee!</p>";
}

// 4. Check employee_daily_schedules (admin overrides)
echo "<h3>4. Employee Daily Schedules (Admin Overrides)</h3>";
$daily = $pdo->prepare("SELECT * FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date >= CURDATE() ORDER BY schedule_date LIMIT 10");
$daily->execute([$employee_id]);
$dailySchedules = $daily->fetchAll(PDO::FETCH_ASSOC);
if ($dailySchedules) {
    echo "<table><tr><th>Date</th><th>Schedule ID</th><th>Is Rest Day</th><th>Notes</th><th>Created At</th></tr>";
    foreach ($dailySchedules as $d) {
        echo "<tr><td>{$d['schedule_date']}</td><td>{$d['actual_schedule_id']}</td><td>{$d['is_rest_day']}</td><td>{$d['notes']}</td><td>{$d['created_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No daily overrides found for this employee!</p>";
}

// 5. Check post_schedule_change_requests (approved requests)
echo "<h3>5. Approved Schedule Change Requests</h3>";
$requests = $pdo->prepare("SELECT * FROM post_schedule_change_requests WHERE employee_id = ? AND status = 'Approved' ORDER BY start_date DESC LIMIT 10");
$requests->execute([$employee_id]);
$changeRequests = $requests->fetchAll(PDO::FETCH_ASSOC);
if ($changeRequests) {
    echo "<table><tr><th>Start Date</th><th>End Date</th><th>Work Schedule ID</th><th>Status</th><th>Reason</th></tr>";
    foreach ($changeRequests as $req) {
        echo "<tr><td>{$req['start_date']}</td><td>{$req['end_date']}</td><td>{$req['work_schedule_id']}</td><td>{$req['status']}</td><td>{$req['reason']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No approved schedule change requests found!</p>";
}

// 6. Test a specific date
$test_date = date('Y-m-d');
echo "<h3>6. Testing Today's Date ($test_date)</h3>";

// Priority 1: Approved change request
$stmt1 = $pdo->prepare("SELECT * FROM post_schedule_change_requests WHERE employee_id = ? AND status = 'Approved' AND ? BETWEEN start_date AND end_date LIMIT 1");
$stmt1->execute([$employee_id, $test_date]);
$result1 = $stmt1->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Priority 1 - Approved Change Request:</strong> " . ($result1 ? "✅ Found (Schedule ID: {$result1['work_schedule_id']})" : "❌ Not found") . "</p>";

// Priority 2: Daily override
$stmt2 = $pdo->prepare("SELECT * FROM employee_daily_schedules WHERE employee_id = ? AND schedule_date = ? LIMIT 1");
$stmt2->execute([$employee_id, $test_date]);
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Priority 2 - Daily Override:</strong> " . ($result2 ? "✅ Found (Schedule ID: {$result2['actual_schedule_id']})" : "❌ Not found") . "</p>";

// Priority 3: Weekly default
$dayOfWeek = date('w', strtotime($test_date));
$stmt3 = $pdo->prepare("SELECT * FROM employee_default_schedules WHERE employee_id = ? AND day_of_week = ? AND effective_from <= ? AND (effective_until IS NULL OR effective_until >= ?) LIMIT 1");
$stmt3->execute([$employee_id, $dayOfWeek, $test_date, $test_date]);
$result3 = $stmt3->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Priority 3 - Weekly Default:</strong> " . ($result3 ? "✅ Found (Schedule ID: {$result3['work_schedule_id']}, Rest: {$result3['is_rest_day']})" : "❌ Not found") . "</p>";

// Priority 4: Holiday check
$stmt4 = $pdo->prepare("SELECT * FROM company_holidays WHERE DATE(holiday_date) = DATE(?) OR (is_recurring=1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(?, '%m-%d')) LIMIT 1");
$stmt4->execute([$test_date, $test_date]);
$result4 = $stmt4->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Priority 4 - Holiday:</strong> " . ($result4 ? "✅ Found ({$result4['holiday_name']})" : "❌ Not found") . "</p>";

// Priority 5: Weekend
$isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
echo "<p><strong>Priority 5 - Weekend:</strong> " . ($isWeekend ? "✅ Yes (Day $dayOfWeek)" : "❌ No (Day $dayOfWeek)") . "</p>";

echo "<hr>";
echo "<h2>Summary</h2>";
if (!$defaultSchedules && !$dailySchedules && !$changeRequests) {
    echo "<p style='color: red; font-size: 18px;'><strong>⚠️ ISSUE FOUND: No schedule data exists for this employee!</strong></p>";
    echo "<p><strong>Solution:</strong> You need to set up a weekly default schedule for this employee in the <code>employee_default_schedules</code> table.</p>";
    echo "<p><strong>Example SQL:</strong></p>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd;'>";
    echo "-- Set Monday-Friday schedule (day 1-5) for employee\n";
    echo "INSERT INTO employee_default_schedules (employee_id, day_of_week, work_schedule_id, is_rest_day, effective_from)\n";
    echo "VALUES \n";
    echo "  ($employee_id, 1, 1, 0, '2025-10-01'), -- Monday\n";
    echo "  ($employee_id, 2, 1, 0, '2025-10-01'), -- Tuesday\n";
    echo "  ($employee_id, 3, 1, 0, '2025-10-01'), -- Wednesday\n";
    echo "  ($employee_id, 4, 1, 0, '2025-10-01'), -- Thursday\n";
    echo "  ($employee_id, 5, 1, 0, '2025-10-01'), -- Friday\n";
    echo "  ($employee_id, 0, NULL, 1, '2025-10-01'), -- Sunday (Rest)\n";
    echo "  ($employee_id, 6, NULL, 1, '2025-10-01'); -- Saturday (Rest)\n";
    echo "</pre>";
} else {
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Employee has schedule data configured!</strong></p>";
}

echo "<hr>";
echo "<p><a href='?emp_id=" . ($employee_id + 1) . "'>Check Next Employee →</a></p>";
?>
