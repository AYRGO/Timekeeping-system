<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$month = date('n');
$year = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// =======================
// Load Employees
// =======================
$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map Employee IDs to Names
$employeeNames = [];
foreach ($employees as $emp) {
    $employeeNames[$emp['id']] = $emp['fname'] . ' ' . $emp['lname'];
}

// =======================
// Get Leave Requests
// =======================
$leaveStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE status = 'approved' AND (
    (start_date <= :end_date AND end_date >= :start_date)
)");
$leaveStmt->execute([
    'start_date' => "$year-$month-01",
    'end_date' => "$year-$month-$daysInMonth"
]);
$leaveRequests = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Get Time Logs
// =======================
$logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE log_date BETWEEN :start_date AND :end_date");
$logStmt->execute([
    'start_date' => "$year-$month-01",
    'end_date' => "$year-$month-$daysInMonth"
]);
$timeLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Map Leaves
// =======================
$leaveMap = [];
foreach ($leaveRequests as $leave) {
    $empId = $leave['employee_id'];
    $start = new DateTime($leave['start_date']);
    $end = new DateTime($leave['end_date']);
    while ($start <= $end) {
        $leaveMap[$empId][$start->format('Y-m-d')] = $leave['leave_type'];
        $start->modify('+1 day');
    }
}

// =======================
// Map Time Logs
// =======================
$logMap = [];
foreach ($timeLogs as $log) {
    if ($log['time_in'] && $log['time_out']) {
        $logMap[$log['employee_id']][$log['log_date']] = 'Present';
    }
}

// =======================
// Generate Spreadsheet
// =======================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendance Report');

// Header Row
$dateHeaders = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateHeaders[] = date('Y-m-d', strtotime("$year-$month-$day"));
}

$sheet->setCellValue('A1', 'Name');
$colIndex = 2;
foreach ($dateHeaders as $headerDate) {
    $cell = Coordinate::stringFromColumnIndex($colIndex) . '1';
    $sheet->setCellValue($cell, date('M j', strtotime($headerDate)));
    $colIndex++;
}

// Light blue header background
$highestColumnIndex = $colIndex - 1;
$headerRange = 'A1:' . Coordinate::stringFromColumnIndex($highestColumnIndex) . '1';
$sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFCCE5FF');

// Fill Rows
$row = 2;
foreach ($employees as $emp) {
    $sheet->setCellValue("A{$row}", $emp['fname'] . ' ' . $emp['lname']);
    $colIndex = 2;
    foreach ($dateHeaders as $date) {
        $value = '';
        if (!empty($leaveMap[$emp['id']][$date])) {
            $value = $leaveMap[$emp['id']][$date];
        } elseif (!empty($logMap[$emp['id']][$date])) {
            $value = 'Present';
        }
        $cell = Coordinate::stringFromColumnIndex($colIndex) . $row;
        $sheet->setCellValue($cell, $value);
        $colIndex++;
    }
    $row++;
}

// =======================
// Sheet 2: Time Logs
// =======================
$timeLogSheet = $spreadsheet->createSheet();
$timeLogSheet->setTitle('Time Logs');

// Header
$timeLogSheet->fromArray(
    ['Employee Name', 'Log Date', 'Time In', 'Time Out', 'Is Late In', 'Is Early Out'],
    NULL,
    'A1'
);

// Fill time log entries
$row = 2;
foreach ($timeLogs as $log) {
    $empName = $employeeNames[$log['employee_id']] ?? 'Unknown';
    $timeLogSheet->setCellValue("A{$row}", $empName);
    $timeLogSheet->setCellValue("B{$row}", $log['log_date']);
    $timeLogSheet->setCellValue("C{$row}", $log['time_in']);
    $timeLogSheet->setCellValue("D{$row}", $log['time_out']);
    $timeLogSheet->setCellValue("E{$row}", $log['is_late_in'] ? 'Yes' : 'No');
    $timeLogSheet->setCellValue("F{$row}", $log['is_early_out'] ? 'Yes' : 'No');
    $row++;
}

// =======================
// Output File
// =======================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Attendance_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
