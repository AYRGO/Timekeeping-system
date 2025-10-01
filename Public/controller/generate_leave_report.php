<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Get parameters from form or use defaults
$year = isset($_GET['year']) && !empty($_GET['year']) ? intval($_GET['year']) : date('Y');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate inputs
$validSorts = ['employee_name', 'updated_at'];
$validOrders = ['asc', 'desc'];
$sortColumn = in_array($sort, $validSorts) ? $sort : 'employee_name';
$sortOrder = in_array($order, $validOrders) ? $order : 'asc';

// Build query to get employees and their leave credits
$employeeQuery = "
    SELECT DISTINCT
        e.id,
        e.fname,
        e.lname,
        e.company,
        CONCAT(e.lname, ', ', e.fname) as employee_name
    FROM employees e
    LEFT JOIN leave_credits lc ON e.id = lc.employee_id AND lc.year = :year
    WHERE 1=1
";

$params = ['year' => $year];

// Add search filter
if (!empty($search)) {
    $employeeQuery .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
    $params['search'] = "%$search%";
}

// Add sorting
if ($sortColumn === 'employee_name') {
    $employeeQuery .= " ORDER BY e.lname $sortOrder, e.fname $sortOrder";
} else {
    $employeeQuery .= " ORDER BY e.lname ASC, e.fname ASC";
}

try {
    $stmt = $pdo->prepare($employeeQuery);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all leave credits for the year
    $leaveQuery = "
        SELECT employee_id, leave_type, balance 
        FROM leave_credits 
        WHERE year = :year
    ";
    $stmt = $pdo->prepare($leaveQuery);
    $stmt->execute(['year' => $year]);
    $allLeaveCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize leave credits by employee and type
    $leaveMap = [];
    foreach ($allLeaveCredits as $credit) {
        $leaveMap[$credit['employee_id']][$credit['leave_type']] = floatval($credit['balance']);
    }

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add title row
$titleText = 'LEAVE REPORT - YEAR ' . $year;
$sheet->setCellValue('A1', $titleText);

// Merge title across all columns
$sheet->mergeCells('A1:D1');

// Style title row
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7C3AED');
$sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
$sheet->getRowDimension('1')->setRowHeight(30);

// Headers (row 2)
$sheet->setCellValue('A2', 'NAME');
$sheet->setCellValue('B2', 'VL');
$sheet->setCellValue('C2', 'SL');
$sheet->setCellValue('D2', 'SPL');

// Style headers
$headerRange = 'A2:D2';
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
$sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(12);
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
$sheet->getRowDimension('2')->setRowHeight(25);

// Set column widths
$sheet->getColumnDimension('A')->setWidth(35); // Employee Name
$sheet->getColumnDimension('B')->setWidth(12); // VL
$sheet->getColumnDimension('C')->setWidth(12); // SL  
$sheet->getColumnDimension('D')->setWidth(12); // SPL

// Fill data rows (starting from row 3)
$row = 3;
foreach ($employees as $employee) {
    // Employee name (Last, First format)
    $sheet->setCellValue("A{$row}", $employee['employee_name']);
    
    // Get leave balances for this employee
    $vlBalance = isset($leaveMap[$employee['id']]['vacation']) ? $leaveMap[$employee['id']]['vacation'] : 0;
    $slBalance = isset($leaveMap[$employee['id']]['sick']) ? $leaveMap[$employee['id']]['sick'] : 0;
    $splBalance = isset($leaveMap[$employee['id']]['solo_parent']) ? $leaveMap[$employee['id']]['solo_parent'] : 0;
    
    // Set leave balances (show only if > 0, otherwise empty)
    $sheet->setCellValue("B{$row}", $vlBalance > 0 ? number_format($vlBalance, 2) : '');
    $sheet->setCellValue("C{$row}", $slBalance > 0 ? number_format($slBalance, 2) : '');
    $sheet->setCellValue("D{$row}", $splBalance > 0 ? number_format($splBalance, 2) : '');
    
    // Style employee name (bold)
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
    
    // Center align leave balance columns
    $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    
    // Add alternating row colors for better readability
    if ($row % 2 == 0) {
        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
    }
    
    // Highlight low balances
    if ($vlBalance > 0 && $vlBalance < 1) {
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('F59E0B'); // Orange for low VL
    }
    if ($slBalance > 0 && $slBalance < 1) {
        $sheet->getStyle("C{$row}")->getFont()->setBold(true)->getColor()->setRGB('F59E0B'); // Orange for low SL
    }
    if ($splBalance > 0 && $splBalance < 1) {
        $sheet->getStyle("D{$row}")->getFont()->setBold(true)->getColor()->setRGB('F59E0B'); // Orange for low SPL
    }
    
    $row++;
}

// Apply borders to all data
$lastRow = $row - 1;
$dataRange = "A2:D{$lastRow}";
$sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Add thick border around entire report
$sheet->getStyle("A1:D{$lastRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);

// Set default row height for better readability
$sheet->getDefaultRowDimension()->setRowHeight(20);

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$filename = 'Leave_Report_' . $year . '_' . date('M-d-Y') . '.xlsx';
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;