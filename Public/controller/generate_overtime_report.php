<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$month = date('n'); // current month (1-12)
$year = date('Y');  // current year (e.g., 2025)
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$dateHeaders = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateHeaders[] = date('Y-m-d', strtotime("$year-$month-$day"));
}

$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY lname ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved overtime (Regular)
$otStmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE status = 'approved' AND ot_date BETWEEN :start AND :end");
$otStmt->execute([
    'start' => "$year-$month-01",
    'end' => "$year-$month-$daysInMonth"
]);
$otRequests = $otStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved rest day overtime
$rdotStmt = $pdo->prepare("SELECT * FROM rest_day_overtime_requests WHERE status = 'approved' AND rest_day_date BETWEEN :start AND :end");
$rdotStmt->execute([
    'start' => "$year-$month-01",
    'end' => "$year-$month-$daysInMonth"
]);
$rdotRequests = $rdotStmt->fetchAll(PDO::FETCH_ASSOC);

// Map overtime
$otMap = [];
foreach ($otRequests as $ot) {
    $otMap[$ot['employee_id']][$ot['ot_date']] = 6; // Regular OT hours
}
foreach ($rdotRequests as $rdot) {
    $otMap[$rdot['employee_id']][$rdot['rest_day_date']] = 6; // Rest Day OT hours
}

$spreadsheet = new Spreadsheet();
/** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
$sheet = $spreadsheet->getActiveSheet();

// Header row
$sheet->setCellValue('A1', 'Name');
$colIndex = 2;
foreach ($dateHeaders as $date) {
    $cell = Coordinate::stringFromColumnIndex($colIndex) . '1';
    $sheet->setCellValue($cell, date('M d, Y', strtotime($date)));
    $colIndex++;
}

$extraHeaders = ['TOTAL REG OT', 'TOTAL RDOT', 'TOTAL RH OT', 'TOTAL RH + RDOT', 'Special Holiday'];
foreach ($extraHeaders as $header) {
    $cell = Coordinate::stringFromColumnIndex($colIndex++) . '1';
    $sheet->setCellValue($cell, $header);
}

// Style header
$highestColumn = $sheet->getHighestColumn();
$sheet->getStyle("A1:{$highestColumn}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
$sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);

// Fill employee rows
$row = 2;
foreach ($employees as $emp) {
    $sheet->setCellValue("A{$row}", "{$emp['lname']}, {$emp['fname']}");
    $totalReg = 0;
    $totalRD = 0;

    $col = 2;
    foreach ($dateHeaders as $date) {
        $value = '';
        if (!empty($otMap[$emp['id']][$date])) {
            $value = $otMap[$emp['id']][$date];
            $totalReg += $value; // For now treat all OT as regular
        }
        $cell = Coordinate::stringFromColumnIndex($col++) . $row;
        $sheet->setCellValue($cell, $value);
    }

    // Totals
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, number_format($totalReg, 2));
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, number_format($totalRD, 2));
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, ''); // RH OT
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, ''); // RH + RDOT
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, ''); // Special Holiday
    $row++;
}

// Output file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Overtime_Report_May_2025.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
