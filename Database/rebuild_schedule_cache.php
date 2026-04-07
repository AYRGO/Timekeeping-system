<?php
/**
 * Rebuild Schedule Cache
 * Purpose: Generate employee_daily_schedule_cache entries from employee_default_schedules
 * Date: December 10, 2025
 */

require_once '../Public/config/db.php';

// Output formatting for web browser
if (php_sapi_name() != 'cli') {
    echo "<!DOCTYPE html><html><head><title>Rebuild Schedule Cache</title></head><body>";
    echo "<h1>Rebuild Schedule Cache</h1>";
    echo "<pre style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
}

// Configuration
$startDate = date('Y-m-d'); // Today
$endDate = date('Y-m-d', strtotime('+6 months')); // 6 months ahead

echo "Rebuilding schedule cache...\n";
echo "Date range: $startDate to $endDate\n\n";

// Get all employees with default schedules
$employeesStmt = $pdo->query("
    SELECT DISTINCT employee_id, CONCAT(e.fname, ' ', e.lname) as full_name
    FROM employee_default_schedules eds
    INNER JOIN employees e ON eds.employee_id = e.id
    WHERE e.status = 'active'
    ORDER BY e.fname, e.lname
");
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($employees) . " employees with default schedules\n\n";

$totalInserted = 0;
$employeeCount = 0;

foreach ($employees as $employee) {
    $employeeId = $employee['employee_id'];
    $fullName = $employee['full_name'];
    
    echo "Processing: $fullName (ID: $employeeId)... ";
    
    try {
        // Delete existing cache entries for this employee in the date range
        $deleteStmt = $pdo->prepare("
            DELETE FROM employee_daily_schedule_cache 
            WHERE employee_id = ? 
            AND schedule_date BETWEEN ? AND ?
        ");
        $deleteStmt->execute([$employeeId, $startDate, $endDate]);
        
        // Get employee's default weekly schedule
        $scheduleStmt = $pdo->prepare("
            SELECT 
                eds.day_of_week,
                eds.work_schedule_id,
                eds.is_rest_day,
                ws.name as schedule_name,
                ws.time_in,
                ws.time_out
            FROM employee_default_schedules eds
            LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
            WHERE eds.employee_id = ?
            ORDER BY eds.day_of_week
        ");
        $scheduleStmt->execute([$employeeId]);
        $weeklySchedule = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create associative array by day_of_week
        $scheduleByDay = [];
        foreach ($weeklySchedule as $sched) {
            $scheduleByDay[$sched['day_of_week']] = $sched;
        }
        
        // Prepare statement to check for approved schedule change requests
        $approvedRequestStmt = $pdo->prepare("
            SELECT psr.work_schedule_id, psr.is_rest_day, ws.name as schedule_name, ws.time_in, ws.time_out
            FROM post_schedule_change_requests psr
            LEFT JOIN work_schedules ws ON psr.work_schedule_id = ws.id
            WHERE psr.employee_id = ? 
              AND psr.status = 'Approved'
              AND ? BETWEEN psr.start_date AND psr.end_date
            ORDER BY psr.created_at DESC
            LIMIT 1
        ");
        
        // Generate cache entries for each day
        $insertStmt = $pdo->prepare("
            INSERT INTO employee_daily_schedule_cache 
            (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
             schedule_name, time_in, time_out, holiday_name, holiday_type, source, source_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW())
        ");
        
        $currentDate = $startDate;
        $daysInserted = 0;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = date('w', strtotime($currentDate)); // 0 (Sunday) to 6 (Saturday)
            
            // Check if it's a company holiday
            $holidayStmt = $pdo->prepare("SELECT holiday_name, holiday_type FROM company_holidays WHERE holiday_date = ?");
            $holidayStmt->execute([$currentDate]);
            $holiday = $holidayStmt->fetch(PDO::FETCH_ASSOC);
            
            $isHoliday = $holiday ? 1 : 0;
            $holidayName = $holiday ? $holiday['holiday_name'] : null;
            $holidayType = $holiday ? $holiday['holiday_type'] : null;
            
            // PRIORITY 1: Check for approved schedule change requests
            $approvedRequestStmt->execute([$employeeId, $currentDate]);
            $approvedRequest = $approvedRequestStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($approvedRequest) {
                // Use approved request schedule
                $insertStmt->execute([
                    $employeeId,
                    $currentDate,
                    $approvedRequest['work_schedule_id'],
                    $approvedRequest['is_rest_day'] ?? 0,
                    $isHoliday,
                    $approvedRequest['schedule_name'],
                    $approvedRequest['time_in'],
                    $approvedRequest['time_out'],
                    $holidayName,
                    $holidayType,
                    'approved_request'
                ]);
                $daysInserted++;
            } elseif (isset($scheduleByDay[$dayOfWeek])) {
                // PRIORITY 2: Use default weekly schedule
                $defaultSched = $scheduleByDay[$dayOfWeek];
                
                // Insert cache entry
                $insertStmt->execute([
                    $employeeId,
                    $currentDate,
                    $defaultSched['work_schedule_id'],
                    $defaultSched['is_rest_day'],
                    $isHoliday,
                    $defaultSched['schedule_name'],
                    $defaultSched['time_in'],
                    $defaultSched['time_out'],
                    $holidayName,
                    $holidayType,
                    'weekly_default'
                ]);
                
                $daysInserted++;
            }
            
            // Move to next day
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        echo "✓ $daysInserted days cached\n";
        $totalInserted += $daysInserted;
        $employeeCount++;
        
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Cache Rebuild Complete!\n";
echo "✓ Employees Processed: $employeeCount\n";
echo "✓ Total Days Cached: $totalInserted\n";
echo "✓ Date Range: $startDate to $endDate\n";
echo "========================================\n";

if (php_sapi_name() != 'cli') {
    echo "</pre>";
    echo "<p style='margin: 20px 0;'><a href='../Public/views/employee_list.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Employee List</a></p>";
    echo "</body></html>";
}

?>
