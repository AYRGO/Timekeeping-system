<?php
/**
 * POPULATE OCTOBER 2025 CACHE
 * 
 * This script populates employee_daily_schedule_cache for October 1-23, 2025
 * using each employee's default weekly schedule
 */

require 'Public/config/db.php';

echo "<h1>Populating October 2025 Cache</h1>";
echo "<p>Building cache entries for October 1-23, 2025 based on employee default schedules</p>";
echo "<hr>";

// Target date range
$startDate = '2025-10-01';
$endDate = '2025-10-23';

// Get all employees
$employeesStmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY id");
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

$totalProcessed = 0;
$totalInserted = 0;

foreach ($employees as $employee) {
    $emp_id = $employee['id'];
    $empName = $employee['fname'] . ' ' . $employee['lname'];
    
    echo "<h3>Processing: {$empName} (ID: {$emp_id})</h3>";
    
    // Get employee's default weekly schedules
    $defaultsStmt = $pdo->prepare("
        SELECT 
            day_of_week,
            work_schedule_id,
            is_rest_day,
            effective_from,
            effective_until
        FROM employee_default_schedules
        WHERE employee_id = ?
        ORDER BY day_of_week
    ");
    $defaultsStmt->execute([$emp_id]);
    $defaults = $defaultsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($defaults)) {
        echo "<p style='color: orange;'>⚠️ No default schedule found - skipping</p>";
        continue;
    }
    
    // Create a map of day_of_week => schedule
    $scheduleMap = [];
    foreach ($defaults as $def) {
        $scheduleMap[$def['day_of_week']] = $def;
    }
    
    // Loop through each date in October 1-23
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    $inserted = 0;
    
    while ($currentDate <= $endDateObj) {
        $date = $currentDate->format('Y-m-d');
        $dayOfWeek = (int)$currentDate->format('w'); // 0=Sunday, 6=Saturday
        
        // Check if cache entry already exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM employee_daily_schedule_cache 
            WHERE employee_id = ? AND schedule_date = ?
        ");
        $checkStmt->execute([$emp_id, $date]);
        
        if ($checkStmt->fetch()) {
            echo "<span style='color: gray;'>• {$date} - Already exists</span><br>";
            $currentDate->modify('+1 day');
            continue;
        }
        
        // Get the default schedule for this day of week
        if (!isset($scheduleMap[$dayOfWeek])) {
            echo "<span style='color: orange;'>• {$date} - No default for this day</span><br>";
            $currentDate->modify('+1 day');
            continue;
        }
        
        $daySchedule = $scheduleMap[$dayOfWeek];
        
        // Check if the default schedule is effective for this date
        if ($daySchedule['effective_from'] && $date < $daySchedule['effective_from']) {
            echo "<span style='color: gray;'>• {$date} - Not yet effective</span><br>";
            $currentDate->modify('+1 day');
            continue;
        }
        
        if ($daySchedule['effective_until'] && $date > $daySchedule['effective_until']) {
            echo "<span style='color: gray;'>• {$date} - No longer effective</span><br>";
            $currentDate->modify('+1 day');
            continue;
        }
        
        // Insert cache entry
        if ($daySchedule['is_rest_day']) {
            // Rest day
            $insertStmt = $pdo->prepare("
                INSERT INTO employee_daily_schedule_cache 
                (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                 schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at)
                VALUES (?, ?, NULL, 1, 0, NULL, NULL, NULL, NULL, 'weekly_default', NULL, NOW(), NOW())
            ");
            $insertStmt->execute([$emp_id, $date]);
            echo "<span style='color: blue;'>✅ {$date} - REST DAY inserted</span><br>";
        } else {
            // Work day - fetch schedule details
            $scheduleStmt = $pdo->prepare("
                SELECT id, name, time_in, time_out 
                FROM work_schedules 
                WHERE id = ?
            ");
            $scheduleStmt->execute([$daySchedule['work_schedule_id']]);
            $scheduleDetails = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($scheduleDetails) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO employee_daily_schedule_cache 
                    (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
                     schedule_name, time_in, time_out, holiday_name, source, source_id, created_at, updated_at)
                    VALUES (?, ?, ?, 0, 0, ?, ?, ?, NULL, 'weekly_default', NULL, NOW(), NOW())
                ");
                $insertStmt->execute([
                    $emp_id, 
                    $date, 
                    $scheduleDetails['id'],
                    $scheduleDetails['name'],
                    $scheduleDetails['time_in'],
                    $scheduleDetails['time_out']
                ]);
                
                $timeIn = date('g:i A', strtotime($scheduleDetails['time_in']));
                $timeOut = date('g:i A', strtotime($scheduleDetails['time_out']));
                echo "<span style='color: green;'>✅ {$date} - {$scheduleDetails['name']} ({$timeIn} - {$timeOut}) inserted</span><br>";
                $inserted++;
            } else {
                echo "<span style='color: red;'>❌ {$date} - Schedule ID {$daySchedule['work_schedule_id']} not found!</span><br>";
            }
        }
        
        $currentDate->modify('+1 day');
    }
    
    $totalInserted += $inserted;
    $totalProcessed++;
    echo "<p><strong>Inserted {$inserted} cache entries for {$empName}</strong></p>";
    echo "<hr>";
}

echo "<h2>✅ Complete!</h2>";
echo "<p>Processed {$totalProcessed} employees</p>";
echo "<p>Inserted {$totalInserted} total cache entries</p>";
echo "<p><a href='check_cache_schedules.php'>→ Verify Cache Now</a></p>";
echo "<p><a href='Public/controller/generate_payroll_report.php?start_date=2025-10-01&end_date=2025-10-15'>→ Generate Payroll Report (Oct 1-15)</a></p>";
?>
