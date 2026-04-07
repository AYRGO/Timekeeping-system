<?php
/**
 * Rebuild Schedule Cache for April 2026
 * Includes past dates to populate holiday information
 */

require_once 'Public/config/db.php';

if (!isset($pdo)) {
    die("Database connection not available.");
}

echo "<pre>";
echo "========================================\n";
echo "Rebuilding April 2026 Schedule Cache...\n";
echo "========================================\n\n";

// Set date range for April 2026
$startDate = '2026-04-01';
$endDate = '2026-04-30';

echo "Date range: $startDate to $endDate\n";
echo "This will update holiday information for all employees.\n\n";

// Delete existing cache entries for April 2026
$deleteStmt = $pdo->prepare("DELETE FROM employee_daily_schedule_cache WHERE schedule_date BETWEEN ? AND ?");
$deleteStmt->execute([$startDate, $endDate]);
echo "✓ Cleared existing cache for April 2026\n\n";

// Get all active employees
$employeesStmt = $pdo->query("
    SELECT DISTINCT employee_id, CONCAT(e.fname, ' ', e.lname) as full_name
    FROM employee_default_schedules eds
    INNER JOIN employees e ON eds.employee_id = e.id
    WHERE e.status = 'active'
    ORDER BY e.fname, e.lname
");
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

$totalInserted = 0;
$employeeCount = 0;

foreach ($employees as $employee) {
    $employeeId = $employee['employee_id'];
    $fullName = $employee['full_name'];
    
    echo "Processing: $fullName (ID: $employeeId)... ";
    
    try {
        // Get employee's weekly schedule
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
        
        // Prepare approved request query
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
        
        // Prepare insert statement with holiday_type
        $insertStmt = $pdo->prepare("
            INSERT INTO employee_daily_schedule_cache 
            (employee_id, schedule_date, work_schedule_id, is_rest_day, is_holiday, 
             schedule_name, time_in, time_out, holiday_name, holiday_type, source, source_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW())
        ");
        
        $currentDate = $startDate;
        $daysInserted = 0;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = date('w', strtotime($currentDate));
            
            // Check if it's a company holiday
            $holidayStmt = $pdo->prepare("SELECT holiday_name, holiday_type FROM company_holidays WHERE holiday_date = ?");
            $holidayStmt->execute([$currentDate]);
            $holiday = $holidayStmt->fetch(PDO::FETCH_ASSOC);
            
            $isHoliday = $holiday ? 1 : 0;
            $holidayName = $holiday ? $holiday['holiday_name'] : null;
            $holidayType = $holiday ? $holiday['holiday_type'] : null;
            
            // Check for approved schedule change request
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
                // Use default weekly schedule
                $defaultSched = $scheduleByDay[$dayOfWeek];
                
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
echo "✓ Cache Rebuild Complete!\n";
echo "✓ Employees Processed: $employeeCount\n";
echo "✓ Total Days Cached: $totalInserted\n";
echo "✓ Date Range: $startDate to $endDate\n";
echo "========================================\n";
echo "\nHolidays included for April 2026:\n";
echo "  - Apr 2: Maundy Thursday\n";
echo "  - Apr 3: Good Friday\n";
echo "  - Apr 4: Easter Saturday\n";
echo "  - Apr 5: Easter Sunday\n";
echo "  - Apr 6: Easter Monday\n";
echo "  - Apr 9: Araw ng Kagitingan\n";
echo "  - Apr 25: Anzac Day\n";
echo "\n";
echo "</pre>";

?>
