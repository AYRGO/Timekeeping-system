<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

// Build SQL with optional search and sorting - include log_out_date and status
$sql = "SELECT tl.*, e.fname, e.lname, e.company, e.official_sched FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.log_date BETWEEN :start AND :end AND e.status = 'active'";
$params = [
    'start' => $start,
    'end' => $end
];

if (!empty($search)) {
    $sql .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY $sortField $sortOrder";

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

// Fetch employee daily schedule cache for all employees in date range (if table exists)
// This is the employee's ACTUAL calendar data - matching schedule_content.php
$scheduleCacheMap = [];
try {
    $cacheStmt = $pdo->prepare("
        SELECT edsc.employee_id, edsc.schedule_date, edsc.work_schedule_id, edsc.is_rest_day,
               edsc.time_in, edsc.time_out, edsc.schedule_name
        FROM employee_daily_schedule_cache edsc
        WHERE edsc.schedule_date BETWEEN :start AND :end
    ");
    $cacheStmt->execute(['start' => $start, 'end' => $end]);
    $scheduleCacheRows = $cacheStmt->fetchAll(PDO::FETCH_ASSOC);

    // Create schedule cache map by employee and date
    foreach ($scheduleCacheRows as $cache) {
        $key = $cache['employee_id'] . '_' . $cache['schedule_date'];
        $scheduleCacheMap[$key] = $cache;
    }
} catch (PDOException $e) {
    // Table doesn't exist, continue without cache
    $scheduleCacheMap = [];
}

// Fetch employee default schedules (weekly patterns)
$defaultScheduleMap = [];
try {
    $defaultSchedStmt = $pdo->prepare("
        SELECT eds.employee_id, eds.day_of_week, eds.work_schedule_id, eds.is_rest_day,
               eds.effective_from, eds.effective_until,
               ws.time_in, ws.time_out, ws.name as schedule_name
        FROM employee_default_schedules eds
        LEFT JOIN work_schedules ws ON eds.work_schedule_id = ws.id
    ");
    $defaultSchedStmt->execute();
    $defaultSchedules = $defaultSchedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Create default schedule map by employee and day of week
    foreach ($defaultSchedules as $sched) {
        $key = $sched['employee_id'] . '_' . $sched['day_of_week'];
        if (!isset($defaultScheduleMap[$key])) {
            $defaultScheduleMap[$key] = [];
        }
        $defaultScheduleMap[$key][] = $sched;
    }
} catch (PDOException $e) {
    // Table doesn't exist, continue without default schedules
    $defaultScheduleMap = [];
}

// Function to get employee schedule for a specific date
function getEmployeeSchedule($employeeId, $logDate, $scheduleCacheMap, $defaultScheduleMap) {
    // Default values
    $result = [
        'time_in' => '07:00:00',
        'time_out' => '16:00:00',
        'is_rest_day' => false,
        'schedule_name' => 'Default'
    ];
    
    // PRIORITY 1: Check daily_schedule_cache
    $cacheKey = $employeeId . '_' . $logDate;
    if (isset($scheduleCacheMap[$cacheKey])) {
        $cache = $scheduleCacheMap[$cacheKey];
        $result['is_rest_day'] = ($cache['is_rest_day'] == 1);
        if ($cache['work_schedule_id'] && $cache['time_in'] && $cache['time_out']) {
            $result['time_in'] = $cache['time_in'];
            $result['time_out'] = $cache['time_out'];
            $result['schedule_name'] = $cache['schedule_name'] ?? 'Cached';
        }
        return $result;
    }
    
    // PRIORITY 2: Check employee_default_schedules (weekly pattern)
    $dayOfWeek = date('w', strtotime($logDate));
    $defaultKey = $employeeId . '_' . $dayOfWeek;
    
    if (isset($defaultScheduleMap[$defaultKey])) {
        foreach ($defaultScheduleMap[$defaultKey] as $sched) {
            // Check if the schedule is effective for this date
            if ($logDate >= $sched['effective_from'] && 
                ($sched['effective_until'] === null || $logDate <= $sched['effective_until'])) {
                $result['is_rest_day'] = ($sched['is_rest_day'] == 1);
                if ($sched['work_schedule_id'] && $sched['time_in'] && $sched['time_out']) {
                    $result['time_in'] = $sched['time_in'];
                    $result['time_out'] = $sched['time_out'];
                    $result['schedule_name'] = $sched['schedule_name'] ?? 'Weekly Default';
                }
                return $result;
            }
        }
    }
    
    // PRIORITY 3: Weekend fallback
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $result['is_rest_day'] = true;
    }
    
    return $result;
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

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Time Logs');

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

// Report Title
$sheet->mergeCells('A3:G3');
$sheet->setCellValue('A3', "Time Logs Report ($start to $end)");
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(25);

// Table Header - Add Schedule and Status columns
$headers = ['Log Date', 'Employee Name', 'Company', 'Schedule', 'Time In', 'Time Out (Log Out Date)', 'Status'];
$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = $sheet->getStyle('A4:G4');
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCE5FF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Enable filtering and freeze header
$sheet->setAutoFilter('A4:G4');
$sheet->freezePane('A5');

// Fill Data
$row = 5;
foreach ($timeLogs as $log) {
    $empName = $log['fname'] . ' ' . $log['lname'];
    $employeeId = $log['employee_id'];
    $logDate = $log['log_date'];
    
    // Determine log out date - use log_out_date if available, otherwise use log_date
    $logOutDate = !empty($log['log_out_date']) ? $log['log_out_date'] : $log['log_date'];
    
    // Get employee's schedule for this date using the proper priority system
    $schedule = getEmployeeSchedule($employeeId, $logDate, $scheduleCacheMap, $defaultScheduleMap);
    
    $schedule_in_24h = $schedule['time_in'];
    $schedule_out_24h = $schedule['time_out'];
    $isRestDay = $schedule['is_rest_day'];
    $schedule_in = date('h:i A', strtotime($schedule_in_24h));
    $schedule_out = date('h:i A', strtotime($schedule_out_24h));
    
    // Get employee's approved leaves
    $employeeLeaves = $leaveMap[$employeeId] ?? [];
    
    // Check if this date is on approved leave first (same logic as attendance-history.php)
    $leaveType = isOnApprovedLeave($logDate, $employeeLeaves);
    
    // Get time values
    $timeIn = $log['time_in'] ?? null;
    $timeOut = $log['time_out'] ?? null;
    
    // Check for cross-midnight shift
    $isCrossMidnight = !empty($log['log_out_date']) && $log['log_out_date'] !== $log['log_date'];
    
    // Check if this is an auto-marked incomplete shift
    $isAutoIncomplete = $log && ($log['status'] === 'incomplete' || $log['time_out'] === 'INC');
    
    // Check for night shift
    $isNightShift = $timeIn && strtotime($timeIn) > strtotime('18:00:00');
    
    // Status calculation using 5 specific categories: Complete, Incomplete, Leave, Late, Undertime, Rest Day
    $status = '-';
    
    if ($isRestDay && !$timeIn && !$timeOut) {
        // Rest day with no work
        $status = 'Rest Day';
    } elseif ($leaveType) {
        // Employee is on approved leave
        $status = 'Leave';
    } elseif ($isAutoIncomplete || !$timeOut || $timeOut === 'INC') {
        // Missing time out or auto-marked incomplete
        $status = 'Incomplete';
    } elseif ($timeIn && $timeOut && $timeOut !== 'INC') {
        // Both time in and time out are present
        $actualTimeIn = date('H:i:s', strtotime($timeIn));
        $actualTimeOut = date('H:i:s', strtotime($timeOut));
        
        // Calculate grace period (15 minutes after scheduled time in)
        $scheduledTimeIn = $schedule_in_24h;
        $graceTimeIn = date('H:i:s', strtotime($scheduledTimeIn . ' +15 minutes'));
        $scheduledTimeOut = $schedule_out_24h;
        
        // Check if late (arrived after grace period)
        $isLate = $actualTimeIn > $graceTimeIn;
        
        // Check if undertime (left early - more than 15 minutes before scheduled out)
        $earliestAllowedOut = date('H:i:s', strtotime($scheduledTimeOut . ' -15 minutes'));
        $isUndertime = false;
        
        if ($isCrossMidnight) {
            // For cross-midnight shifts, we need to handle the time comparison differently
            $isUndertime = false; // For cross-midnight, consider complete unless obviously early
        } else {
            // Regular shift - check if left significantly early
            $isUndertime = $actualTimeOut < $earliestAllowedOut;
        }
        
        // Determine final status - prioritize Late over Undertime
        if ($isLate) {
            $status = 'Late';
        } elseif ($isUndertime) {
            $status = 'Undertime';
        } else {
            $status = 'Complete';
        }
    } else {
        // No time in record and not on leave
        $status = 'Incomplete';
    }
    
    // Format Time In (without log date)
    $timeInDisplay = '';
    if (!empty($log['time_in'])) {
        $timeInDisplay = date('h:i A', strtotime($log['time_in']));
    }
    
    // Format Time Out with Log Out Date (only if log_out_date exists and is different from log_date)
    $timeOutDisplay = '';
    if (!empty($log['time_out']) && $log['time_out'] !== 'INC') {
        if (!empty($log['log_out_date']) && $log['log_out_date'] !== $log['log_date']) {
            $timeOutDisplay = date('h:i A', strtotime($log['time_out'])) . ' (' . $log['log_out_date'] . ')';
        } else {
            $timeOutDisplay = date('h:i A', strtotime($log['time_out']));
        }
    } elseif ($isAutoIncomplete) {
        $timeOutDisplay = 'INC';
    }
    
    $sheet->setCellValue("A{$row}", $log['log_date']);
    $sheet->setCellValue("B{$row}", $empName);
    $sheet->setCellValue("C{$row}", $log['company']);
    $sheet->setCellValue("D{$row}", $schedule_in . ' - ' . $schedule_out);
    $sheet->setCellValue("E{$row}", $timeInDisplay);
    $sheet->setCellValue("F{$row}", $timeOutDisplay);
    $sheet->setCellValue("G{$row}", $status);
    $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Auto-size Columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output File
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Time_Logs_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
