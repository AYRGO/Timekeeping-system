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

// Get current month's time logs
$month = date('n');
$year = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$logStmt = $pdo->prepare("SELECT * FROM time_logs WHERE log_date BETWEEN :start_date AND :end_date");
$logStmt->execute([
    'start_date' => "$year-$month-01",
    'end_date' => "$year-$month-$daysInMonth"
]);
$timeLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Time Logs');

// =======================
// Add Logo
// =======================
$logo = new Drawing();
$logo->setName('Company Logo');
$logo->setDescription('Logo');
$logo->setPath('../asset/RSS-logo-colour.png'); // Path to your logo image
$logo->setHeight(50);
$logo->setCoordinates('A1');
$logo->setOffsetX(10);
$logo->setOffsetY(5);
$logo->setWorksheet($sheet);

// =======================
// Title
// =======================
$sheet->mergeCells('A3:F3');
$sheet->setCellValue('A3', 'Monthly Time Logs Report');
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(25);

// =======================
// Table Header
// =======================
$headers = ['Log Date', 'Employee Name', 'Time In', 'Time Out', 'Is Late In', 'Is Early Out'];
$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = $sheet->getStyle('A4:F4');
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCE5FF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// =======================
// Fill Data
// =======================
$row = 5;
foreach ($timeLogs as $log) {
    $empName = $employeeNames[$log['employee_id']] ?? 'Unknown';
    $sheet->setCellValue("A{$row}", $log['log_date']);
    $sheet->setCellValue("B{$row}", $empName);
    $sheet->setCellValue("C{$row}", $log['time_in']);
    $sheet->setCellValue("D{$row}", $log['time_out']);
    $sheet->setCellValue("E{$row}", $log['is_late_in'] ? 'Yes' : 'No');
    $sheet->setCellValue("F{$row}", $log['is_early_out'] ? 'Yes' : 'No');

    // Add borders to each row
    $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// =======================
// Auto-size Columns
// =======================
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// =======================
// Output File
// =======================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Time_Logs_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
