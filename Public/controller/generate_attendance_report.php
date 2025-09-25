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
$sql = "SELECT tl.*, e.fname, e.lname, e.company, e.official_sched FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.log_date BETWEEN :start AND :end";
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

// Define schedule times (same as attendance-history.php)
$schedule_times = [
    1 => ['in' => '06:30:00', 'out' => '15:30:00'],
    2 => ['in' => '08:00:00', 'out' => '19:00:00'],
    3 => ['in' => '07:30:00', 'out' => '16:30:00'],
    4 => ['in' => '07:00:00', 'out' => '16:00:00'],
    5 => ['in' => '08:00:00', 'out' => '17:00:00'],
    6 => ['in' => '09:00:00', 'out' => '18:00:00'],
    7 => ['in' => '10:00:00', 'out' => '19:00:00'],
    8 => ['in' => '06:00:00', 'out' => '15:00:00'],
    9 => ['in' => '08:00:00', 'out' => '16:30:00'],
    10 => ['in' => '07:40:00', 'out' => '16:40:00'],
    11 => ['in' => '06:30:00', 'out' => '15:00:00'],
    12 => ['in' => '06:30:00', 'out' => '17:30:00'],
    13 => ['in' => '07:00:00', 'out' => '18:00:00'],
    14 => ['in' => '06:00:00', 'out' => '17:00:00'],
    15 => ['in' => '06:00:00', 'out' => '16:00:00'],
    16 => ['in' => '08:30:00', 'out' => '16:30:00'],
    17 => ['in' => '06:00:00', 'out' => '12:00:00'],
    18 => ['in' => '06:00:00', 'out' => '14:30:00'],
    19 => ['in' => '19:00:00', 'out' => '3:00:00'],
    20 => ['in' => '19:00:00', 'out' => '4:30:00'],
];

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
$previousDate = '';
foreach ($timeLogs as $log) {
    $empName = $log['fname'] . ' ' . $log['lname'];
    $employeeId = $log['employee_id'];
    $logDate = $log['log_date'];
    
    // Add date title and spacing between different dates for clear distinction
    if ($previousDate !== '' && $previousDate !== $logDate) {
        $row++; // Add empty row for spacing
        
        // Add date title row
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", "Date: " . date('F j, Y (l)', strtotime($logDate)));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6F3FF');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    } elseif ($previousDate === '') {
        // Add date title for first date
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", "Date: " . date('F j, Y (l)', strtotime($logDate)));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6F3FF');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }
    $previousDate = $logDate;
    
    // Determine log out date - use log_out_date if available, otherwise use log_date
    $logOutDate = !empty($log['log_out_date']) ? $log['log_out_date'] : $log['log_date'];
    
    // Get employee's schedule for this date (same logic as attendance-history.php)
    $default_schedule_id = $log['official_sched'] ?? 4;
    $schedule_id_to_use = $default_schedule_id;
    
    // Check if there's an approved schedule change for this date
    $employeeScheduleChanges = $scheduleChangeMap[$employeeId] ?? [];
    foreach ($employeeScheduleChanges as $scheduleChange) {
        if ($logDate >= $scheduleChange['start_date'] && $logDate <= $scheduleChange['end_date']) {
            $schedule_id_to_use = $scheduleChange['work_schedule_id'];
            break;
        }
    }
    
    // Get schedule times
    if (isset($schedule_times[$schedule_id_to_use])) {
        $schedule_in_24h = $schedule_times[$schedule_id_to_use]['in'];
        $schedule_out_24h = $schedule_times[$schedule_id_to_use]['out'];
        $schedule_in = date('h:i A', strtotime($schedule_in_24h));
        $schedule_out = date('h:i A', strtotime($schedule_out_24h));
    } else {
        $schedule_in_24h = '07:00:00';
        $schedule_out_24h = '16:00:00';
        $schedule_in = '07:00 AM';
        $schedule_out = '04:00 PM';
    }
    
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
    
    // Status calculation using 5 specific categories: Complete, Incomplete, Leave, Late, Undertime
    $status = '-';
    
    if ($leaveType) {
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
