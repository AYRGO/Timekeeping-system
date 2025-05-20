<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$month = 5; // May
$year = 2025;

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dateHeaders = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateHeaders[] = date('Y-m-d', strtotime("$year-$month-$day"));
}

$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$leaveStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE status = 'approved' AND (
    (start_date <= :end_date AND end_date >= :start_date)
)");
$leaveStmt->execute([
    'start_date' => "$year-$month-01",
    'end_date' => "$year-$month-$daysInMonth"
]);
$leaveRequests = $leaveStmt->fetchAll(PDO::FETCH_ASSOC);

$logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE log_date BETWEEN :start_date AND :end_date");
$logStmt->execute([
    'start_date' => "$year-$month-01",
    'end_date' => "$year-$month-$daysInMonth"
]);
$timeLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

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

$logMap = [];
foreach ($timeLogs as $log) {
    if ($log['time_in'] && $log['time_out']) {
        $logMap[$log['employee_id']][$log['log_date']] = 'Present';
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header: "Name" + Dates
$sheet->setCellValue('A1', 'Name');
$colIndex = 2;
foreach ($dateHeaders as $headerDate) {
    $cell = Coordinate::stringFromColumnIndex($colIndex) . '1';
    $sheet->setCellValue($cell, date('M j', strtotime($headerDate)));
    $colIndex++;
}

// Fill each row
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

// Output Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Attendance_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
