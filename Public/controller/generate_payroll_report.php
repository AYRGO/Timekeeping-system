<?php
/**
 * PAYROLL ATTENDANCE REPORT GENERATOR
 * 
 * IMPORTANT: This report fetches schedules from employee_daily_schedule_cache
 * This is the SAME SOURCE as the employee's personal calendar view.
 * 
 * What employees see in their calendar = What shows up in this payroll report
 * 
 * The cache includes:
 * - Weekly default schedules
 * - Approved schedule change requests
 * - Admin overrides
 * - Holidays
 * - Rest days
 */

require '../../vendor/autoload.php';
require '../config/db.php';
require_once __DIR__ . '/../config/EmployeeHolidayProfiles.php';
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_admin_mutation('Payroll report export is disabled in admin demo mode.');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Get search and sorting parameters
$search = $_GET['search'] ?? '';
$sortField = $_GET['sort'] ?? 'log_date';
$sortOrder = strtoupper($_GET['order'] ?? 'ASC');

// Validate sort order
$sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';

// Get date range from query parameters or default to current month
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

if ($startDate && $endDate) {
    $start = $startDate;
    $end = $endDate;
} else {
    $month = date('n');
    $year = date('Y');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $start = "$year-$month-01";
    $end = "$year-$month-$daysInMonth";
}

// Build SQL to get all time logs for the period
$sql = "SELECT tl.*, e.fname, e.lname, e.company, e.official_sched, e.position FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.log_date BETWEEN :start AND :end AND e.status = 'active'";
$params = [
    'start' => $start,
    'end' => $end
];

if (!empty($search)) {
    $sql .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY e.lname, e.fname, tl.log_date ASC";

$logStmt = $pdo->prepare($sql);
$logStmt->execute($params);
$timeLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved leave requests for the date range
$leaveRequestsStmt = $pdo->prepare("
    SELECT employee_id, start_date, end_date, leave_type
    FROM post_leave_requests 
    WHERE status = 'approved'
    AND end_date >= :start AND start_date <= :end
");
$leaveRequestsStmt->execute(['start' => $start, 'end' => $end]);
$approvedLeaves = $leaveRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Create leave map by employee
$leaveMap = [];
foreach ($approvedLeaves as $leave) {
    if (!isset($leaveMap[$leave['employee_id']])) {
        $leaveMap[$leave['employee_id']] = [];
    }
    $leaveMap[$leave['employee_id']][] = $leave;
}

// Fetch approved schedule changes
$scheduleChangesStmt = $pdo->prepare("
    SELECT employee_id, work_schedule_id, start_date, end_date 
    FROM post_schedule_change_requests 
    WHERE status = 'Approved' 
    AND end_date >= :start AND start_date <= :end
    ORDER BY created_at DESC
");
$scheduleChangesStmt->execute(['start' => $start, 'end' => $end]);
$scheduleChanges = $scheduleChangesStmt->fetchAll(PDO::FETCH_ASSOC);

// Create schedule change map by employee
$scheduleChangeMap = [];
foreach ($scheduleChanges as $change) {
    if (!isset($scheduleChangeMap[$change['employee_id']])) {
        $scheduleChangeMap[$change['employee_id']] = [];
    }
    $scheduleChangeMap[$change['employee_id']][] = $change;
}

// Resolve holidays directly as a safeguard for cache rows created by older refresh paths.
function resolvePayrollHoliday($pdo, $employee_id, $date) {
    static $holidayResolver = null;

    if ($holidayResolver === null) {
        $holidayResolver = new EmployeeHolidayProfiles($pdo);
    }

    try {
        return $holidayResolver->resolveHolidayForEmployeeDate((int)$employee_id, $date);
    } catch (Exception $e) {
        error_log("Payroll holiday resolve failed for employee {$employee_id} on {$date}: " . $e->getMessage());
        return [
            'is_holiday' => 0,
            'holiday_name' => null,
            'holiday_type' => null,
            'source' => null
        ];
    }
}

// Function to get employee's schedule for a specific date from PERSONAL CALENDAR CACHE
// This uses employee_daily_schedule_cache - THE EXACT SAME SOURCE as the employee's personal calendar
// This ensures payroll report shows EXACTLY what the employee sees in their calendar
function getScheduleForDate($pdo, $employee_id, $date) {
    $resolvedHoliday = resolvePayrollHoliday($pdo, $employee_id, $date);

    // Query the pre-computed cache table - THIS IS THE EMPLOYEE'S PERSONAL CALENDAR DATA
    // Check if holiday_type column exists (for backward compatibility)
    static $hasHolidayTypeColumn = null;
    if ($hasHolidayTypeColumn === null) {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM employee_daily_schedule_cache LIKE 'holiday_type'");
        $hasHolidayTypeColumn = $checkStmt->rowCount() > 0;
    }
    
    // Build query based on whether holiday_type column exists
    $selectFields = "
        schedule_date,
        employee_id,
        work_schedule_id,
        is_rest_day,
        is_holiday,
        schedule_name,
        time_in,
        time_out,
        holiday_name,
        " . ($hasHolidayTypeColumn ? "holiday_type," : "") . "
        source
    ";
    
    $stmt = $pdo->prepare("
        SELECT $selectFields
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $date]);
    $cache = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If found in cache, return formatted data
    if ($cache) {
        // If cache has valid schedule data, use it
        if ($cache['is_rest_day'] || $cache['is_holiday'] || 
            (!empty($cache['time_in']) && !empty($cache['time_out']))) {
            $isHoliday = (int)$cache['is_holiday'] === 1 || (int)($resolvedHoliday['is_holiday'] ?? 0) === 1;

            return [
                'is_rest_day' => $cache['is_rest_day'],
                'is_holiday' => $isHoliday ? 1 : 0,
                'schedule_name' => $cache['schedule_name'],
                'time_in' => $cache['time_in'],
                'time_out' => $cache['time_out'],
                'holiday_name' => $cache['holiday_name'] ?? $resolvedHoliday['holiday_name'] ?? null,
                'holiday_type' => $cache['holiday_type'] ?? $resolvedHoliday['holiday_type'] ?? null,
                'source' => $cache['source']
            ];
        }
        // If cache exists but has empty/invalid data, fall through to weekly default lookup
    }
    
    // Fallback: Check employee_default_schedules (same logic as schedule_content.php)
    $dayOfWeek = date('w', strtotime($date)); // 0=Sunday, 6=Saturday
    $weeklyStmt = $pdo->prepare("
        SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
        FROM employee_default_schedules edd
        LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
        WHERE edd.employee_id = ? 
          AND edd.day_of_week = ? 
          AND edd.effective_from <= ? 
          AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
        LIMIT 1
    ");
    $weeklyStmt->execute([$employee_id, $dayOfWeek, $date, $date]);
    $weekly = $weeklyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weekly) {
        if ($weekly['is_rest_day']) {
            return [
                'is_rest_day' => 1,
                'is_holiday' => $resolvedHoliday['is_holiday'] ?? 0,
                'schedule_name' => 'OFF',
                'time_in' => null,
                'time_out' => null,
                'holiday_name' => $resolvedHoliday['holiday_name'] ?? null,
                'holiday_type' => $resolvedHoliday['holiday_type'] ?? null,
                'source' => 'weekly_default'
            ];
        } elseif ($weekly['work_schedule_id']) {
            return [
                'is_rest_day' => 0,
                'is_holiday' => $resolvedHoliday['is_holiday'] ?? 0,
                'schedule_name' => $weekly['name'],
                'time_in' => $weekly['time_in'],
                'time_out' => $weekly['time_out'],
                'holiday_name' => $resolvedHoliday['holiday_name'] ?? null,
                'holiday_type' => $resolvedHoliday['holiday_type'] ?? null,
                'source' => 'weekly_default'
            ];
        }
    }
    
    // Final fallback: Check if weekend
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        return [
            'is_rest_day' => 1,
            'is_holiday' => $resolvedHoliday['is_holiday'] ?? 0,
            'schedule_name' => 'OFF',
            'time_in' => null,
            'time_out' => null,
            'holiday_name' => $resolvedHoliday['holiday_name'] ?? null,
            'holiday_type' => $resolvedHoliday['holiday_type'] ?? null,
            'source' => 'weekend'
        ];
    }
    
    // Absolute fallback: return default schedule
    return [
        'is_rest_day' => 0,
        'is_holiday' => $resolvedHoliday['is_holiday'] ?? 0,
        'schedule_name' => 'Day shift',
        'time_in' => '07:00:00',
        'time_out' => '16:00:00',
        'holiday_name' => $resolvedHoliday['holiday_name'] ?? null,
        'holiday_type' => $resolvedHoliday['holiday_type'] ?? null,
        'source' => 'default'
    ];
}

// Function to build weekly schedule summary in format: "M-F; 7am-4pm | Sat-Sun; OFF"
// READS FROM THE EXACT SAME SOURCE AS THE EMPLOYEE'S SCHEDULE CALENDAR
// Priority: employee_daily_schedule_cache → employee_default_schedules → work_schedules via official_sched
function getWeeklyScheduleSummary($pdo, $employee_id, $startDate) {
    $dayAbbrev = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    // Determine the month we're generating the report for
    $reportMonth = date('Y-m', strtotime($startDate));
    $firstOfMonth = $reportMonth . '-01';
    $lastOfMonth = date('Y-m-t', strtotime($firstOfMonth));
    
    // =================================================================
    // STEP 1: Fetch ALL cache entries for this employee for this month
    // This is THE SAME TABLE the Schedule Calendar reads from
    // =================================================================
    $cacheStmt = $pdo->prepare("
        SELECT schedule_date, work_schedule_id, is_rest_day, is_holiday,
               schedule_name, time_in, time_out, source
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date BETWEEN ? AND ?
        ORDER BY schedule_date
    ");
    $cacheStmt->execute([$employee_id, $firstOfMonth, $lastOfMonth]);
    $cacheEntries = $cacheStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build a per-day-of-week schedule by sampling cache entries
    // For each DOW (0-6), collect all non-holiday schedules and pick the most common one
    $dowSchedules = []; // dow => [scheduleKey => [count, schedInfo]]
    
    foreach ($cacheEntries as $entry) {
        $dow = (int)date('w', strtotime($entry['schedule_date']));
        
        // Skip holidays - they don't represent the regular schedule
        if ($entry['is_holiday']) continue;
        
        if ($entry['is_rest_day']) {
            $key = 'REST';
            $info = [
                'is_rest_day' => 1, 'is_holiday' => 0,
                'schedule_name' => 'OFF', 'time_in' => null, 'time_out' => null
            ];
        } elseif (!empty($entry['time_in']) && !empty($entry['time_out'])) {
            $key = $entry['time_in'] . '-' . $entry['time_out'];
            $info = [
                'is_rest_day' => 0, 'is_holiday' => 0,
                'schedule_name' => $entry['schedule_name'],
                'time_in' => $entry['time_in'], 'time_out' => $entry['time_out']
            ];
        } else {
            $key = 'REST';
            $info = [
                'is_rest_day' => 1, 'is_holiday' => 0,
                'schedule_name' => 'OFF', 'time_in' => null, 'time_out' => null
            ];
        }
        
        if (!isset($dowSchedules[$dow])) $dowSchedules[$dow] = [];
        if (!isset($dowSchedules[$dow][$key])) {
            $dowSchedules[$dow][$key] = ['count' => 0, 'info' => $info];
        }
        $dowSchedules[$dow][$key]['count']++;
    }
    
    // =================================================================
    // STEP 2: Build weekSchedule from cache (most common schedule per DOW)
    // =================================================================
    $weekSchedule = [];
    $cacheHasData = false;
    
    for ($dow = 0; $dow <= 6; $dow++) {
        if (!empty($dowSchedules[$dow])) {
            $cacheHasData = true;
            // Pick the most frequent schedule for this day-of-week
            $bestKey = null;
            $bestCount = 0;
            foreach ($dowSchedules[$dow] as $key => $data) {
                if ($data['count'] > $bestCount) {
                    $bestCount = $data['count'];
                    $bestKey = $key;
                }
            }
            $weekSchedule[$dow] = $dowSchedules[$dow][$bestKey]['info'];
        }
    }
    
    // =================================================================
    // STEP 3: If cache had no data, fall back to employee_default_schedules
    // (Same fallback the calendar uses when cache is empty)
    // =================================================================
    if (!$cacheHasData) {
        for ($dow = 0; $dow <= 6; $dow++) {
            $defaultStmt = $pdo->prepare("
                SELECT edd.work_schedule_id, edd.is_rest_day, ws.name, ws.time_in, ws.time_out
                FROM employee_default_schedules edd
                LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
                WHERE edd.employee_id = ? 
                  AND edd.day_of_week = ?
                  AND edd.effective_from <= ?
                  AND (edd.effective_until IS NULL OR edd.effective_until >= ?)
                ORDER BY edd.effective_from DESC
                LIMIT 1
            ");
            $defaultStmt->execute([$employee_id, $dow, $startDate, $startDate]);
            $defaultSched = $defaultStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($defaultSched && $defaultSched['is_rest_day']) {
                $weekSchedule[$dow] = [
                    'is_rest_day' => 1, 'is_holiday' => 0,
                    'schedule_name' => 'OFF', 'time_in' => null, 'time_out' => null
                ];
            } elseif ($defaultSched && $defaultSched['work_schedule_id'] && !empty($defaultSched['time_in'])) {
                $weekSchedule[$dow] = [
                    'is_rest_day' => 0, 'is_holiday' => 0,
                    'schedule_name' => $defaultSched['name'],
                    'time_in' => $defaultSched['time_in'], 'time_out' => $defaultSched['time_out']
                ];
            } else {
                // Still nothing — check official_sched from employees table
                $weekSchedule[$dow] = null; // Will be filled in Step 4
            }
        }
    }
    
    // =================================================================
    // STEP 4: Final fallback — official_sched from employees table
    // =================================================================
    $allNull = true;
    for ($dow = 0; $dow <= 6; $dow++) {
        if (isset($weekSchedule[$dow])) {
            $allNull = false;
            break;
        }
    }
    
    if ($allNull) {
        // Get official schedule from employees table
        $empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
        $empStmt->execute([$employee_id]);
        $empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($empRow && $empRow['official_sched']) {
            $wsStmt = $pdo->prepare("SELECT name, time_in, time_out FROM work_schedules WHERE id = ?");
            $wsStmt->execute([$empRow['official_sched']]);
            $ws = $wsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ws) {
                // Apply across all weekdays, rest on weekends
                for ($dow = 0; $dow <= 6; $dow++) {
                    if ($dow == 0 || $dow == 6) { // Sun/Sat
                        $weekSchedule[$dow] = [
                            'is_rest_day' => 1, 'is_holiday' => 0,
                            'schedule_name' => 'OFF', 'time_in' => null, 'time_out' => null
                        ];
                    } else {
                        $weekSchedule[$dow] = [
                            'is_rest_day' => 0, 'is_holiday' => 0,
                            'schedule_name' => $ws['name'],
                            'time_in' => $ws['time_in'], 'time_out' => $ws['time_out']
                        ];
                    }
                }
            }
        }
    }
    
    // Fill any remaining null days as OFF
    for ($dow = 0; $dow <= 6; $dow++) {
        if (!isset($weekSchedule[$dow])) {
            $weekSchedule[$dow] = [
                'is_rest_day' => 1, 'is_holiday' => 0,
                'schedule_name' => 'OFF', 'time_in' => null, 'time_out' => null
            ];
        }
    }
    
    // =================================================================
    // STEP 5: Group consecutive days with same schedule for display
    // =================================================================
    $groups = [];
    $currentGroup = null;
    $order = [1, 2, 3, 4, 5, 6, 0]; // Mon-Sun
    
    foreach ($order as $dow) {
        $sched = $weekSchedule[$dow];
        
        if ($sched['is_rest_day'] || empty($sched['time_in']) || empty($sched['time_out'])) {
            $schedKey = 'OFF';
        } else {
            $schedKey = $sched['time_in'] . '-' . $sched['time_out'];
        }
        
        if ($currentGroup === null || $currentGroup['key'] !== $schedKey) {
            if ($currentGroup !== null) $groups[] = $currentGroup;
            $currentGroup = ['key' => $schedKey, 'start_dow' => $dow, 'end_dow' => $dow, 'schedule' => $sched];
        } else {
            $currentGroup['end_dow'] = $dow;
        }
    }
    if ($currentGroup !== null) $groups[] = $currentGroup;
    
    // Format groups into display string
    $parts = [];
    foreach ($groups as $group) {
        $startDow = $group['start_dow'];
        $endDow = $group['end_dow'];
        $sched = $group['schedule'];
        
        $dayRange = ($startDow === $endDow) 
            ? $dayAbbrev[$startDow] 
            : $dayAbbrev[$startDow] . '-' . $dayAbbrev[$endDow];
        
        if ($sched['is_rest_day'] || empty($sched['time_in']) || empty($sched['time_out'])) {
            $parts[] = $dayRange . '; OFF';
        } else {
            $timeIn = date('ga', strtotime($sched['time_in']));
            $timeOut = date('ga', strtotime($sched['time_out']));
            $parts[] = $dayRange . '; ' . $timeIn . '-' . $timeOut;
        }
    }
    
    return implode(' | ', $parts);
}

// Function to check if a date is within approved leave period
function isOnApprovedLeave($checkDate, $approvedLeaves) {
    foreach ($approvedLeaves as $leave) {
        if ($checkDate >= $leave['start_date'] && $checkDate <= $leave['end_date']) {
            return $leave['leave_type'];
        }
    }
    return false;
}



// Get all employees with time logs in the period
$employeesStmt = $pdo->prepare("
    SELECT DISTINCT e.id, e.fname, e.lname, e.company, e.position, e.official_sched 
    FROM employees e 
    JOIN time_logs tl ON e.id = tl.employee_id 
    WHERE tl.log_date BETWEEN :start AND :end" . 
    (!empty($search) ? " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)" : "") . "
    ORDER BY e.lname, e.fname
");

$employeeParams = ['start' => $start, 'end' => $end];
if (!empty($search)) {
    $employeeParams['search'] = "%$search%";
}

$employeesStmt->execute($employeeParams);
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance matrix for each employee
$attendanceData = [];
// Sort employees alphabetically by last name, then first name
usort($employees, function($a, $b) {
    $lnameCompare = strcmp(strtolower($a['lname']), strtolower($b['lname']));
    if ($lnameCompare === 0) {
        return strcmp(strtolower($a['fname']), strtolower($b['fname']));
    }
    return $lnameCompare;
});

foreach ($employees as $employee) {
    $employeeId = $employee['id'];
    $empName = $employee['lname'] . ', ' . $employee['fname'];
    
    // Get employee's approved leaves
    $employeeLeaves = $leaveMap[$employeeId] ?? [];
    $employeeScheduleChanges = $scheduleChangeMap[$employeeId] ?? [];
    
    // Get weekly schedule summary in format: "M-F; 7am-4pm | Sat-Sun; OFF"
    $scheduleDisplay = getWeeklyScheduleSummary($pdo, $employeeId, $start);
    
    // Initialize employee data
    $attendanceData[$employeeId] = [
        'name' => $empName, // Now in 'Last, First' format
        'company' => $employee['company'],
        'position' => $employee['position'],
        'schedule' => $scheduleDisplay,
        'days' => []
    ];
    
    // Generate all dates in the range
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate = $endDate->modify('+1 day'); // Include end date
    
    while ($startDate < $endDate) {
        $logDate = $startDate->format('Y-m-d');
        $dayOfWeek = $startDate->format('N'); // 1=Monday, 7=Sunday
        
        // Get employee's schedule from THEIR PERSONAL CALENDAR (employee_daily_schedule_cache)
        // This is the EXACT SAME data the employee sees in their own calendar view
        $scheduleInfo = getScheduleForDate($pdo, $employeeId, $logDate);
        
        // Check if this date is on approved leave
        $leaveType = isOnApprovedLeave($logDate, $employeeLeaves);
        
        // Find actual time log for this date
        $timeLog = null;
        foreach ($timeLogs as $log) {
            if ($log['employee_id'] == $employeeId && $log['log_date'] == $logDate) {
                $timeLog = $log;
                break;
            }
        }
        
        // Determine attendance status
        $statusInfo = getAttendanceStatus($timeLog, $leaveType, $scheduleInfo, $dayOfWeek);
        
        $attendanceData[$employeeId]['days'][$logDate] = [
            'status' => $statusInfo['status'] ?? '',
            'details' => $statusInfo['details'] ?? '',
            'day_of_week' => $dayOfWeek,
            'time_in' => $timeLog['time_in'] ?? null,
            'time_out' => $timeLog['time_out'] ?? null,
            'leave_type' => $leaveType,
            'is_rest_day' => $scheduleInfo['is_rest_day'] ?? 0,
            'is_holiday' => $scheduleInfo['is_holiday'] ?? 0
        ];
        
        $startDate->modify('+1 day');
    }
}

// Helper function to determine attendance status with late/undertime details
function getAttendanceStatus($log, $leaveType, $scheduleInfo, $dayOfWeek) {
    // Safety check for scheduleInfo
    if (!$scheduleInfo) {
        $scheduleInfo = [
            'is_rest_day' => 0,
            'is_holiday' => 0,
            'time_in' => '07:00:00',
            'time_out' => '16:00:00',
            'schedule_name' => 'Default'
        ];
    }
    
    // If on leave, return leave type
    if ($leaveType) {
        // Convert leave type to short code
        switch (strtolower($leaveType)) {
            case 'sick':
                return ['status' => 'SL', 'details' => ''];
            case 'vacation':
                return ['status' => 'VL', 'details' => ''];
            case 'emergency':
                return ['status' => 'EL', 'details' => ''];
            default:
                return ['status' => strtoupper(substr($leaveType, 0, 2)), 'details' => ''];
        }
    }
    
    // Rest day takes priority over holiday and time logs for payroll display.
    // Work rendered on a rest day, including a holiday rest day, should stay OFF
    // and be filed/processed through Rest Day OT instead of being marked present.
    if (isset($scheduleInfo['is_rest_day']) && $scheduleInfo['is_rest_day'] == 1) {
        if ($log && $log['time_in']) {
            return ['status' => 'OFF', 'details' => 'Rest Day OT'];
        }
        return ['status' => 'OFF', 'details' => ''];
    }

    // If this is a holiday on a scheduled work day:
    // - HOLIDAY OFF = holiday off / no complete work log
    // - P           = worked holiday / complete time-in and time-out
    if (isset($scheduleInfo['is_holiday']) && $scheduleInfo['is_holiday'] == 1) {
        $hasCompleteHolidayLog = $log
            && !empty($log['time_in'])
            && !empty($log['time_out'])
            && $log['time_out'] !== 'INC';

        if ($hasCompleteHolidayLog) {
            return ['status' => 'P', 'details' => 'Holiday Work'];
        }

        return ['status' => 'HOLIDAY OFF', 'details' => $scheduleInfo['holiday_name'] ?? 'Holiday Off'];
    }
    
    // If no time log exists at all and it's a work day
    if (!$log) {
        return ['status' => '', 'details' => ''];
    }
    
    // Check for incomplete shifts
    $isAutoIncomplete = $log && ($log['status'] === 'incomplete' || $log['time_out'] === 'INC');
    if ($isAutoIncomplete || !$log['time_out'] || $log['time_out'] === 'INC') {
        return ['status' => '', 'details' => 'Incomplete'];
    }
    
    // If both time in and out exist, check for tardiness/undertime
    if ($log['time_in'] && $log['time_out'] && $log['time_out'] !== 'INC') {
        $scheduleIn = $scheduleInfo['time_in'] ?? '07:00:00';
        $scheduleOut = $scheduleInfo['time_out'] ?? '16:00:00';
        
        $actualTimeIn = $log['time_in'];
        $actualTimeOut = $log['time_out'];
        
        // Calculate grace period (15 minutes after scheduled time in)
        $graceTimeIn = date('H:i:s', strtotime($scheduleIn . ' +15 minutes'));
        $earliestAllowedOut = date('H:i:s', strtotime($scheduleOut . ' -15 minutes'));
        
        // Calculate late minutes (grace period only determines IF late, minutes counted from scheduled time)
        $lateMinutes = 0;
        $isLate = $actualTimeIn > $graceTimeIn;
        if ($isLate) {
            $lateSeconds = strtotime($actualTimeIn) - strtotime($scheduleIn);
            $lateMinutes = round($lateSeconds / 60);
        }
        
        // Calculate undertime minutes (grace period only determines IF undertime, minutes counted from scheduled time)
        $undertimeMinutes = 0;
        $isUndertime = false;
        if (!($log['log_out_date'] && $log['log_out_date'] !== $log['log_date'])) {
            $isUndertime = $actualTimeOut < $earliestAllowedOut;
            if ($isUndertime) {
                $undertimeSeconds = strtotime($scheduleOut) - strtotime($actualTimeOut);
                $undertimeMinutes = round($undertimeSeconds / 60);
            }
        }
        
        // Build status and details
        if (!$isLate && !$isUndertime) {
            return ['status' => 'P', 'details' => ''];
        } else {
            // Calculate total minutes (late + undertime)
            $totalMinutes = $lateMinutes + $undertimeMinutes;
            return ['status' => '', 'details' => $totalMinutes > 0 ? (string)$totalMinutes : ''];
        }
    }
    
    return ['status' => '', 'details' => ''];
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Payroll Report');

// Add Logo
$logo = new Drawing();
$logo->setName('Company Logo');
$logo->setDescription('Logo');
$logo->setPath('../asset/RSS-logo-colour.png');
$logo->setHeight(50);
$logo->setCoordinates('A1');
$logo->setOffsetX(10);
$logo->setOffsetY(5);
$logo->setWorksheet($sheet);

// Report Title with larger font and more spacing
$sheet->mergeCells('A3:S3');
// Format dates to readable format (e.g., "July 5, 2024")
$startFormatted = date('F j, Y', strtotime($start));
$endFormatted = date('F j, Y', strtotime($end));
$sheet->setCellValue('A3', "Payroll Attendance Report ($startFormatted to $endFormatted)");
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(20); // Increased from 16
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(50); // Increased from 30

// Generate weekly date headers
$startDate = new DateTime($start);
$endDate = new DateTime($end);
$dateHeaders = ['Name', 'Schedule'];
$dateColumns = [];

// Generate date range with day names
while ($startDate <= $endDate) {
    $dayAbbrev = $startDate->format('D'); // Mon, Tue, Wed, etc.
    $dateStr = $startDate->format('d-M-y'); // 07-Sep-25
    $fullDate = $startDate->format('Y-m-d');
    
    $dateHeaders[] = $dayAbbrev;
    $dateColumns[] = $fullDate;
    
    $startDate->modify('+1 day');
}

// Add final column for totals/summary if needed
$dateHeaders[] = 'Total Days';

// Set headers starting at row 4
$headerColIndex = 1; // Start with column A
foreach ($dateHeaders as $header) {
    $headerColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($headerColIndex);
    $sheet->setCellValue($headerColLetter . '4', $header);
    $headerColIndex++;
}

// Add date subheaders in row 5 with better formatting
$dateColIndex = 3; // Start after Name and Schedule columns (column C)
foreach ($dateColumns as $date) {
    $dateObj = new DateTime($date);
    $dateColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateColIndex);
    $sheet->setCellValue($dateColLetter . '5', $dateObj->format('d-M-y'));
    
    // Center align the date subheaders
    $sheet->getStyle($dateColLetter . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $dateColIndex++;
}

// Style headers - Handle multi-letter column names properly
$totalCols = count($dateHeaders);
$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);
$headerRange = 'A4:' . $lastCol . '5';

$headerStyle = $sheet->getStyle($headerRange);
$headerStyle->getFont()->setBold(true)->setSize(13); // Increased from 10 to 13
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4472C4');
$headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Merge Name and Schedule headers across both rows
$sheet->mergeCells('A4:A5');
$sheet->mergeCells('B4:B5');
$sheet->mergeCells($lastCol . '4:' . $lastCol . '5');

// Set header row heights for better spacing
$sheet->getRowDimension(1)->setRowHeight(55); // Title row
$sheet->getRowDimension(2)->setRowHeight(45); // Subtitle row
$sheet->getRowDimension(4)->setRowHeight(50); // Header row 1
$sheet->getRowDimension(5)->setRowHeight(40); // Header row 2

// Enable filtering and freeze header
$sheet->setAutoFilter('A4:' . $lastCol . '4');
$sheet->freezePane('A6');

// Fill Data
$row = 6;
$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($dateHeaders));

foreach ($attendanceData as $employeeId => $data) {
    $currentColIndex = 1; // Start with column A (1-based)
    
    // Employee Name
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColIndex) . $row, $data['name']);
    $currentColIndex++;
    
    // Schedule
    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColIndex) . $row, $data['schedule']);
    $currentColIndex++;
    
    // Attendance for each day
    $totalPresent = 0;
    foreach ($dateColumns as $date) {
        $dayData = $data['days'][$date] ?? null;
        $status = $dayData['status'] ?? '';
        $details = $dayData['details'] ?? '';
        
        // Count present days (P status)
        if ($status === 'P') {
            $totalPresent++;
        }
        
        // Prepare display text - show status with details if available
        $displayText = $status;
        if (!empty($details) && empty($status)) {
            $displayText = $details; // Show details when status is empty (late/undertime cases)
        }
        
        $currentColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColIndex);
        $sheet->setCellValue($currentColLetter . $row, $displayText);
        
        // Apply cell formatting based on status and details
        $cellStyle = $sheet->getStyle($currentColLetter . $row);
        
        if (($status === 'P' && !empty($dayData['is_holiday'])) || $status === 'HOLIDAY OFF') {
            // Scheduled work-day holiday: worked holidays and holiday off are highlighted in green
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC6EFCE');
            $cellStyle->getFont()->getColor()->setARGB('FF006100');
            $cellStyle->getFont()->setBold(true);
        } elseif ($status === 'OFF') {
            // Day off - Dark gray background (keep this color)
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF6C757D');
            $cellStyle->getFont()->getColor()->setARGB('FFFFFFFF');
            $cellStyle->getFont()->setBold(true);
        } elseif (!empty($details) && (is_numeric($details) || $details === 'Incomplete')) {
            // Minutes (late/undertime) or "Incomplete" - red font
            $cellStyle->getFont()->getColor()->setARGB('FFDC3545');
            $cellStyle->getFont()->setBold(true);
        } elseif (empty($status) && empty($details)) {
            // Empty cells (absent) - medium gray background
            $cellStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFA0A3A7');
            $cellStyle->getFont()->getColor()->setARGB('FF000000');
            $cellStyle->getFont()->setBold(true);
        } else {
            // All other statuses - no background color, just bold text
            $cellStyle->getFont()->setBold(true); 
        }
        
        // Add borders and alignment
        $cellStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $cellStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $cellStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        // Add some padding by adjusting font size - larger for better visibility
        $cellStyle->getFont()->setSize(13); // Increased from 11 to 13
        
        $currentColIndex++;
    }
    
    // Total Present Days
    $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentColIndex);
    $sheet->setCellValue($totalColLetter . $row, $totalPresent);
    $sheet->getStyle($totalColLetter . $row)->getFont()->setBold(true)->setSize(14); // Larger font
    $sheet->getStyle($totalColLetter . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle($totalColLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($totalColLetter . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    
    // Add row borders and formatting
    $rowRange = "A{$row}:{$lastCol}{$row}";
    $sheet->getStyle($rowRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Set row height for much better spacing
    $sheet->getRowDimension($row)->setRowHeight(45); // Increased to 45 for maximum spacing
    
    // Style employee name and schedule columns with larger fonts and padding
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14); // Larger font size
    $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A{$row}")->getAlignment()->setIndent(2); // More left padding
    $sheet->getStyle("B{$row}")->getFont()->setSize(12); // Larger font for schedule
    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    
    // No alternating row colors
    
    $row++;
}

// Add legend/explanation at the bottom with larger font
$row += 2;
$sheet->setCellValue("A{$row}", "LEGEND:");
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14); // Increased from 12

$row++;
$legendItems = [
    ['P', 'Present / worked scheduled holiday', false],
    ['HOLIDAY OFF', 'Holiday off on a scheduled work day', false],
    ['SL/VL/EL', 'Leave Types (Sick/Vacation/Emergency)', false], 
    ['OFF', 'Scheduled day off / rest day, including rest-day holiday or rest-day work filed as OT', true],
    ['240', 'Late/Undertime Minutes (Total)', false],
    ['(Blank)', 'Absent/Incomplete', false]
];

foreach ($legendItems as $i => $legend) {
    $legendRow = $row + $i;
    $sheet->setCellValue("A{$legendRow}", $legend[0]);
    $sheet->setCellValue("B{$legendRow}", $legend[1]);
    
    // Apply legend formatting
    if ($legend[2]) { // Only apply color for OFF status
        $sheet->getStyle("A{$legendRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF6C757D');
        $sheet->getStyle("A{$legendRow}")->getFont()->getColor()->setARGB('FFFFFFFF');
    }
    $sheet->getStyle("A{$legendRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$legendRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Set much wider column widths for spacious layout
$sheet->getColumnDimension('A')->setWidth(50); // Employee Name - much wider
$sheet->getColumnDimension('B')->setWidth(50); // Schedule - much wider for long schedule formats

// Set date columns to much wider width for better calendar view
$colIndex = 3; // Start with column C (after A=Name, B=Schedule)
foreach ($dateColumns as $date) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
    $sheet->getColumnDimension($colLetter)->setWidth(15); // Much wider date columns
    $colIndex++;
}

// Total Days column - much wider
$sheet->getColumnDimension($lastCol)->setWidth(20); // Much wider for "Total Days"

// Output File
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// Use formatted dates for filename (replace spaces and commas for valid filename)
$startFile = date('M_j_Y', strtotime($start));
$endFile = date('M_j_Y', strtotime($end));
header('Content-Disposition: attachment;filename="Payroll_Attendance_Calendar_' . $startFile . '_to_' . $endFile . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
