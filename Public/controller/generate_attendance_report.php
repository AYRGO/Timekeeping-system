<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Load employees
$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map Employee IDs to Names
$employeeNames = [];
foreach ($employees as $emp) {
    $employeeNames[$emp['id']] = $emp['fname'] . ' ' . $emp['lname'];
}

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

// Fetch time logs
$logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE log_date BETWEEN :start AND :end ORDER BY log_date ASC");
$logStmt->execute(['start' => $start, 'end' => $end]);
$timeLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

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
$sheet->mergeCells('A3:D3');
$sheet->setCellValue('A3', "Time Logs Report ($start to $end)");
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(25);

// Table Header
$headers = ['Log Date', 'Employee Name', 'Time In', 'Time Out'];
$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = $sheet->getStyle('A4:D4');
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCE5FF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Fill Data
$row = 5;
foreach ($timeLogs as $log) {
    $empName = $employeeNames[$log['employee_id']] ?? 'Unknown';
    $sheet->setCellValue("A{$row}", $log['log_date']);
    $sheet->setCellValue("B{$row}", $empName);

    $sheet->setCellValue("C{$row}", !empty($log['time_in']) ? date('h:i A', strtotime($log['time_in'])) : '');
    $sheet->setCellValue("D{$row}", !empty($log['time_out']) ? date('h:i A', strtotime($log['time_out'])) : '');

    $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Auto-size Columns
foreach (range('A', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output File
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Time_Logs_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
