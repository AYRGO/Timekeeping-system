<?php
require '../../vendor/autoload.php';
require '../config/db.php';

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
$sql = "SELECT tl.*, e.fname, e.lname, e.company, e.official_sched, e.position FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.log_date BETWEEN :start AND :end";
$params = [
    'start' => $start,
    'end' => $end
];

if (!empty($search)) {
    $sql .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY e.fname, e.lname, tl.log_date ASC";

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



// Get all employees with time logs in the period
$employeesStmt = $pdo->prepare("
    SELECT DISTINCT e.id, e.fname, e.lname, e.company, e.position, e.official_sched 
    FROM employees e 
    JOIN time_logs tl ON e.id = tl.employee_id 
    WHERE tl.log_date BETWEEN :start AND :end" . 
    (!empty($search) ? " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)" : "") . "
    ORDER BY e.fname, e.lname
");

$employeeParams = ['start' => $start, 'end' => $end];
if (!empty($search)) {
    $employeeParams['search'] = "%$search%";
}

$employeesStmt->execute($employeeParams);
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

// Create attendance matrix for each employee
$attendanceData = [];
// Sort employees alphabetically by last name
usort($employees, function($a, $b) {
    return strcmp(strtolower($a['lname']), strtolower($b['lname']));
});

foreach ($employees as $employee) {
    $employeeId = $employee['id'];
    $empName = $employee['lname'] . ', ' . $employee['fname'];
    
    // Get employee's approved leaves
    $employeeLeaves = $leaveMap[$employeeId] ?? [];
    $employeeScheduleChanges = $scheduleChangeMap[$employeeId] ?? [];
    
    // Initialize employee data
    $attendanceData[$employeeId] = [
        'name' => $empName, // Now in 'Last, First' format
        'company' => $employee['company'],
        'position' => $employee['position'],
        'schedule' => getScheduleDisplay($employee['official_sched'] ?? 4),
        'days' => []
    ];
    
    // Generate all dates in the range
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $endDate = $endDate->modify('+1 day'); // Include end date
    
    while ($startDate < $endDate) {
        $logDate = $startDate->format('Y-m-d');
        $dayOfWeek = $startDate->format('N'); // 1=Monday, 7=Sunday
        
        // Get employee's schedule for this date
        $schedule_id_to_use = $employee['official_sched'] ?? 4;
        foreach ($employeeScheduleChanges as $scheduleChange) {
            if ($logDate >= $scheduleChange['start_date'] && $logDate <= $scheduleChange['end_date']) {
                $schedule_id_to_use = $scheduleChange['work_schedule_id'];
                break;
            }
        }
        
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
        $statusInfo = getAttendanceStatus($timeLog, $leaveType, $schedule_id_to_use, $dayOfWeek);
        
        $attendanceData[$employeeId]['days'][$logDate] = [
            'status' => $statusInfo['status'],
            'details' => $statusInfo['details'],
            'day_of_week' => $dayOfWeek,
            'time_in' => $timeLog['time_in'] ?? null,
            'time_out' => $timeLog['time_out'] ?? null,
            'leave_type' => $leaveType
        ];
        
        $startDate->modify('+1 day');
    }
}

// Helper function to get schedule display
function getScheduleDisplay($scheduleId) {
    global $schedule_times;
    if (isset($schedule_times[$scheduleId])) {
        $in = date('h:i A', strtotime($schedule_times[$scheduleId]['in']));
        $out = date('h:i A', strtotime($schedule_times[$scheduleId]['out']));
        return "$in-$out";
    }
    return "07:00 AM-04:00 PM";
}

// Helper function to determine attendance status with late/undertime details
function getAttendanceStatus($log, $leaveType, $scheduleId, $dayOfWeek) {
    global $schedule_times;
    
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
    
    // If no time log exists at all, check if this should be a work day
    if (!$log) {
        // If the employee has a valid schedule, this should be a work day
        // Only show OFF if they have no schedule or it's genuinely a non-work day
        if (!isset($schedule_times[$scheduleId])) {
            // No schedule defined - could be day off
            if ($dayOfWeek == 6 || $dayOfWeek == 7) { // Saturday or Sunday
                return ['status' => 'OFF', 'details' => ''];
            } else {
                return ['status' => '', 'details' => '']; // Weekday with no schedule = absent
            }
        } else {
            // Has schedule but no time log = absent (not OFF)
            return ['status' => '', 'details' => ''];
        }
    }
    
    // Check for incomplete shifts
    $isAutoIncomplete = $log && ($log['status'] === 'incomplete' || $log['time_out'] === 'INC');
    if ($isAutoIncomplete || !$log['time_out'] || $log['time_out'] === 'INC') {
        return ['status' => '', 'details' => 'Incomplete'];
    }
    
    // If both time in and out exist, check for tardiness/undertime
    if ($log['time_in'] && $log['time_out'] && $log['time_out'] !== 'INC') {
        $scheduleIn = $schedule_times[$scheduleId]['in'] ?? '07:00:00';
        $scheduleOut = $schedule_times[$scheduleId]['out'] ?? '16:00:00';
        
        $actualTimeIn = $log['time_in'];
        $actualTimeOut = $log['time_out'];
        
        // Calculate grace period (15 minutes after scheduled time in)
        $graceTimeIn = date('H:i:s', strtotime($scheduleIn . ' +15 minutes'));
        $earliestAllowedOut = date('H:i:s', strtotime($scheduleOut . ' -15 minutes'));
        
        // Calculate late minutes
        $lateMinutes = 0;
        $isLate = $actualTimeIn > $graceTimeIn;
        if ($isLate) {
            $lateSeconds = strtotime($actualTimeIn) - strtotime($graceTimeIn);
            $lateMinutes = round($lateSeconds / 60);
        }
        
        // Calculate undertime minutes
        $undertimeMinutes = 0;
        $isUndertime = false;
        if (!($log['log_out_date'] && $log['log_out_date'] !== $log['log_date'])) {
            $isUndertime = $actualTimeOut < $earliestAllowedOut;
            if ($isUndertime) {
                $undertimeSeconds = strtotime($earliestAllowedOut) - strtotime($actualTimeOut);
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
        
        if ($status === 'OFF') {
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
    ['P', 'Present (Complete - No Late/Undertime)', false],
    ['SL/VL/EL', 'Leave Types (Sick/Vacation/Emergency)', false], 
    ['OFF', 'Scheduled Day Off', true], // Keep color for OFF
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
$sheet->getColumnDimension('B')->setWidth(28); // Schedule - much wider

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