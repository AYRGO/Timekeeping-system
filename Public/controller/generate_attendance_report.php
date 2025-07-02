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

// Build SQL with optional search and sorting
$sql = "SELECT tl.*, e.fname, e.lname, e.company FROM time_logs tl JOIN employees e ON tl.employee_id = e.id WHERE tl.log_date BETWEEN :start AND :end";
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
$sheet->mergeCells('A3:E3');
$sheet->setCellValue('A3', "Time Logs Report ($start to $end)");
$sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(3)->setRowHeight(25);

// Table Header
$headers = ['Log Date', 'Employee Name', 'Company', 'Time In', 'Time Out'];
$sheet->fromArray($headers, NULL, 'A4');

// Style header
$headerStyle = $sheet->getStyle('A4:E4');
$headerStyle->getFont()->setBold(true)->setSize(12);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCE5FF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Enable filtering and freeze header
$sheet->setAutoFilter('A4:E4');
$sheet->freezePane('A5');

// Fill Data
$row = 5;
foreach ($timeLogs as $log) {
    $empName = $log['fname'] . ' ' . $log['lname'];
    $sheet->setCellValue("A{$row}", $log['log_date']);
    $sheet->setCellValue("B{$row}", $empName);
    $sheet->setCellValue("C{$row}", $log['company']);
    $sheet->setCellValue("D{$row}", !empty($log['time_in']) ? date('h:i A', strtotime($log['time_in'])) : '');
    $sheet->setCellValue("E{$row}", !empty($log['time_out']) ? date('h:i A', strtotime($log['time_out'])) : '');
    $sheet->getStyle("A{$row}:E{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $row++;
}

// Auto-size Columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output File
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Time_Logs_Report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
