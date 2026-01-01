<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Get date range from query parameters or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'employee_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate inputs
$validSorts = ['employee_name', 'updated_at'];
$validOrders = ['asc', 'desc'];
$sortColumn = in_array($sort, $validSorts) ? $sort : 'employee_name';
$sortOrder = in_array($order, $validOrders) ? $order : 'asc';

// Get year for leave credits from the start date
$year = date('Y', strtotime($startDate));

// Build query to get employees 
$employeeQuery = "
    SELECT DISTINCT
        e.id,
        e.fname,
        e.lname,
        e.company,
        CONCAT(e.lname, ', ', e.fname) as employee_name
    FROM employees e
    WHERE 1=1
";

$params = [];

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

    // Get approved leave requests for the date range - ONLY INCLUDE SPECIFIC LEAVE TYPES
    $leaveRequestsQuery = "
        SELECT 
            employee_id, 
            start_date, 
            end_date, 
            leave_type,
            status
        FROM post_leave_requests 
        WHERE status = 'approved'
        AND leave_type IN ('sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'halfday', 'halfday_sick', 'lwop', 'bereavement')
        AND end_date >= :start_date 
        AND start_date <= :end_date
        ORDER BY employee_id, start_date
    ";
    $stmt = $pdo->prepare($leaveRequestsQuery);
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $approvedLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create leave map by employee and date
    $leavesByEmployeeAndDate = [];
    foreach ($approvedLeaves as $leave) {
        $employeeId = $leave['employee_id'];
        $start = new DateTime($leave['start_date']);
        $end = new DateTime($leave['end_date']);
        
        // Create entry for each date in the leave period
        while ($start <= $end) {
            $dateKey = $start->format('Y-m-d');
            if ($dateKey >= $startDate && $dateKey <= $endDate) {
                $leavesByEmployeeAndDate[$employeeId][$dateKey] = strtoupper($leave['leave_type']);
            }
            $start->modify('+1 day');
        }
    }

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Generate date headers
$dateHeaders = ['NAME', 'VL', 'SL', 'SPL'];
$dateColumns = [];

$currentDate = new DateTime($startDate);
$endDateObj = new DateTime($endDate);

while ($currentDate <= $endDateObj) {
    $dayAbbrev = $currentDate->format('D'); // Mon, Tue, Wed, etc.
    $dateStr = $currentDate->format('d-M'); // 07-Sep
    $fullDate = $currentDate->format('Y-m-d');
    
    $dateHeaders[] = $dayAbbrev;
    $dateColumns[] = $fullDate;
    
    $currentDate->modify('+1 day');
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add title row
$startFormatted = date('F j, Y', strtotime($startDate));
$endFormatted = date('F j, Y', strtotime($endDate));
$titleText = "Leave Tracker ($startFormatted to $endFormatted)";
$sheet->setCellValue('A1', $titleText);

// Calculate total columns and merge title
$totalCols = count($dateHeaders);
$lastCol = Coordinate::stringFromColumnIndex($totalCols);
$sheet->mergeCells("A1:{$lastCol}1");

// Style title row
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('7C3AED');
$sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
$sheet->getRowDimension('1')->setRowHeight(30);

// Set headers starting at row 2
$headerColIndex = 1;
foreach ($dateHeaders as $header) {
    $headerColLetter = Coordinate::stringFromColumnIndex($headerColIndex);
    $sheet->setCellValue($headerColLetter . '2', $header);
    $headerColIndex++;
}

// Add date subheaders in row 3 (only for date columns)
$dateColIndex = 5; // Start after NAME, VL, SL, SPL columns
foreach ($dateColumns as $date) {
    $dateObj = new DateTime($date);
    $dateColLetter = Coordinate::stringFromColumnIndex($dateColIndex);
    $sheet->setCellValue($dateColLetter . '3', $dateObj->format('d-M-y'));
    
    // Center align the date subheaders
    $sheet->getStyle($dateColLetter . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $dateColIndex++;
}

// Style headers
$headerRange = "A2:{$lastCol}3";
$headerStyle = $sheet->getStyle($headerRange);
$headerStyle->getFont()->setBold(true)->setSize(11);
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
$headerStyle->getFont()->getColor()->setRGB('FFFFFF');
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Merge header cells that don't have date subheaders
$sheet->mergeCells('A2:A3'); // NAME
$sheet->mergeCells('B2:B3'); // VL
$sheet->mergeCells('C2:C3'); // SL  
$sheet->mergeCells('D2:D3'); // SPL

// Set column widths
$sheet->getColumnDimension('A')->setWidth(35); // Employee Name
$sheet->getColumnDimension('B')->setWidth(12); // VL
$sheet->getColumnDimension('C')->setWidth(12); // SL
$sheet->getColumnDimension('D')->setWidth(12); // SPL

// Set date columns width
$colIndex = 5; // Start with column E
foreach ($dateColumns as $date) {
    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
    $sheet->getColumnDimension($colLetter)->setWidth(8);
    $colIndex++;
}

// Fill data rows (starting from row 4)
$row = 4;
foreach ($employees as $employee) {
    $currentColIndex = 1;
    
    // Employee name (Last, First format)
    $sheet->setCellValue("A{$row}", $employee['employee_name']);
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
    $currentColIndex++;
    
    // Get leave balances for this employee (these are the total available credits)
    $vlBalance = isset($leaveMap[$employee['id']]['vacation']) ? $leaveMap[$employee['id']]['vacation'] : 15; // Default total credits
    $slBalance = isset($leaveMap[$employee['id']]['sick']) ? $leaveMap[$employee['id']]['sick'] : 15; // Default total credits
    $splBalance = isset($leaveMap[$employee['id']]['solo_parent']) ? $leaveMap[$employee['id']]['solo_parent'] : 0;
    
    // Calculate range for date columns (E to last column)
    $firstDateCol = 'E';
    $lastDateCol = Coordinate::stringFromColumnIndex($totalCols);
    
    // VL Balance Formula: Total VL Credits - (COUNTIF for VL * 1) - (COUNTIF for HDVL * 0.5) - (COUNTIF for HALFDAY * 0.5)
    $vlFormula = "={$vlBalance}-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"VL\")*1)-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"HDVL\")*0.5)-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"HALFDAY\")*0.5)";
    $sheet->setCellValue("B{$row}", $vlFormula);
    
    // SL Balance Formula: Total SL Credits - (COUNTIF for SL * 1) - (COUNTIF for HDSL * 0.5)
    $slFormula = "={$slBalance}-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"SL\")*1)-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"HDSL\")*0.5)";
    $sheet->setCellValue("C{$row}", $slFormula);
    
    // SPL Balance Formula: Total SPL Credits - (COUNTIF for SPL * 1) - (COUNTIF for HDSPL * 0.5)
    $splFormula = "={$splBalance}-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"SPL\")*1)-(COUNTIF({$firstDateCol}{$row}:{$lastDateCol}{$row},\"HDSPL\")*0.5)";
    $sheet->setCellValue("D{$row}", $splFormula);
    
    // Style the VL, SL, SPL columns with different colors (matching your image)
    $sheet->getStyle("B{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE'); // Light green for VL
    $sheet->getStyle("C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC'); // Light yellowish for SL
    $sheet->getStyle("D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCE4D6'); // Light pink/salmon for SPL
    
    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
    $sheet->getStyle("C{$row}")->getFont()->setBold(true);
    $sheet->getStyle("D{$row}")->getFont()->setBold(true);
    
    $currentColIndex += 3;
    
    // Date columns (populate with approved leaves or leave empty for user input)
    foreach ($dateColumns as $date) {
        $colLetter = Coordinate::stringFromColumnIndex($currentColIndex);
        
        // Check if there's an approved leave for this employee on this date
        $leaveValue = '';
        if (isset($leavesByEmployeeAndDate[$employee['id']][$date])) {
            $leaveType = $leavesByEmployeeAndDate[$employee['id']][$date];
            
            // Convert leave types to display format - MATCH DROPDOWN OPTIONS EXACTLY
            switch (strtolower($leaveType)) {
                case 'vacation':
                    $leaveValue = 'VL';
                    break;
                case 'sick':
                    $leaveValue = 'SL';
                    break;
                case 'paternity':
                    $leaveValue = 'PL';
                    break;
                case 'maternity':
                    $leaveValue = 'ML';
                    break;
                case 'solo_parent':
                    $leaveValue = 'SPL';
                    break;
                case 'halfday':
                    $leaveValue = 'Half_VL'; // Half Day Vacation
                    break;
                case 'halfday_sick':
                    $leaveValue = 'Half_SL'; // Half Day Sick
                    break;
                case 'lwop':
                    $leaveValue = 'LWOP';
                    break;
                case 'bereavement':
                    $leaveValue = 'BL';
                    break;
                default:
                    // Skip any leave types not in the approved list
                    continue 2;
            }
        }
        
        $sheet->setCellValue($colLetter . $row, $leaveValue);
        
        // Light gray background for date cells (whether they have data or are editable)
        $sheet->getStyle($colLetter . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
        $sheet->getStyle($colLetter . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // If there's a leave entry, make it bold and colored
        if (!empty($leaveValue)) {
            $sheet->getStyle($colLetter . $row)->getFont()->setBold(true);
            
            // Color code the leave types - MATCH NEW LEAVE TYPES
            switch ($leaveValue) {
                case 'VL':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('006100'); // Dark green
                    break;
                case 'SL':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('7030A0'); // Purple
                    break;
                case 'PL': // Paternity
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('0070C0'); // Blue
                    break;
                case 'ML': // Maternity
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('FF69B4'); // Hot pink
                    break;
                case 'SPL':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('C55A11'); // Orange
                    break;
                case 'Half_VL':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('006100'); // Dark green (same as VL)
                    break;
                case 'Half_SL':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('7030A0'); // Purple (same as SL)
                    break;
                case 'LWOP':
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('808080'); // Gray
                    break;
                case 'BL': // Bereavement
                    $sheet->getStyle($colLetter . $row)->getFont()->getColor()->setRGB('000000'); // Black
                    break;
            }
        }
        
        $currentColIndex++;
    }
    
    // Center align leave balance columns
    $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    
    $row++;
}

// Apply borders to all data
$lastRow = $row - 1;
$dataRange = "A2:{$lastCol}{$lastRow}";
$sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Add thick border around entire report
$sheet->getStyle("A1:{$lastCol}{$lastRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);

// Set default row height for better readability
$sheet->getDefaultRowDimension()->setRowHeight(25);

// Add legend/instructions at the bottom
$legendRow = $lastRow + 2;
$sheet->setCellValue("A{$legendRow}", "INSTRUCTIONS:");
$sheet->getStyle("A{$legendRow}")->getFont()->setBold(true)->setSize(12);

$legendRow++;
$instructions = [
    ['VL', 'Vacation Leave (1 full day) - Green text', false],
    ['SL', 'Sick Leave (1 full day) - Purple text', false], 
    ['PL', 'Paternity Leave (1 full day) - Blue text', false],
    ['ML', 'Maternity Leave (1 full day) - Pink text', false],
    ['SPL', 'Solo Parent Leave (1 full day) - Orange text', false],
    ['Half_VL', 'Half Day Vacation (0.5 day) - Green text', false],
    ['Half_SL', 'Half Day Sick (0.5 day) - Purple text', false],
    ['LWOP', 'Leave Without Pay (1 full day) - Gray text', false],
    ['BL', 'Bereavement Leave (1 full day) - Black text', false],
    ['Auto-populated from approved requests', '', false]
];

foreach ($instructions as $i => $instruction) {
    $currentLegendRow = $legendRow + $i;
    $sheet->setCellValue("A{$currentLegendRow}", $instruction[0]);
    $sheet->setCellValue("B{$currentLegendRow}", $instruction[1]);
    
    $sheet->getStyle("A{$currentLegendRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle("A{$currentLegendRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$startFile = date('M_j_Y', strtotime($startDate));
$endFile = date('M_j_Y', strtotime($endDate));
$filename = 'Leave_Tracker_' . $startFile . '_to_' . $endFile . '.xlsx';
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;