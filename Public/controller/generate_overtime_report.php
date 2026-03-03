<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Validate date inputs
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Get date parameters from form or use current month as default
$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'log_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    die('Invalid date format. Please use YYYY-MM-DD format.');
}

// Ensure start date is not after end date
if (strtotime($startDate) > strtotime($endDate)) {
    die('Start date cannot be after end date.');
}

// Generate date headers for the specified range
$dateHeaders = [];
$start = new DateTime($startDate);
$end = new DateTime($endDate);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($start, $interval, $end->modify('+1 day'));

foreach ($period as $dt) {
    $dateHeaders[] = $dt->format('Y-m-d');
}

// Fetch employee list with optional search filter
$employeeQuery = "SELECT id, fname, lname, company FROM employees WHERE status = 'active'";
$params = [];

if (!empty($search)) {
    $employeeQuery .= " AND (fname LIKE :search OR lname LIKE :search OR company LIKE :search)";
    $params['search'] = "%$search%";
}

// Add sorting
$validSorts = ['fname', 'lname', 'company', 'log_date'];
$validOrders = ['asc', 'desc'];
$sortColumn = in_array($sort, $validSorts) ? $sort : 'lname';
$sortOrder = in_array($order, $validOrders) ? $order : 'asc';

if ($sortColumn !== 'log_date') {
    $employeeQuery .= " ORDER BY $sortColumn $sortOrder";
} else {
    $employeeQuery .= " ORDER BY lname ASC, fname ASC";
}

$stmt = $pdo->prepare($employeeQuery);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved overtime requests from ALL OT tables to ensure complete data
// Table 1: post2_overtime_requests (archived/moved approved OT)
// Table 2: post_ot_requests (recently approved, not yet moved by cron)
// Table 3: post_overtime_requests (old approval flow)

$otRecords = [];

// --- Source 1: post2_overtime_requests (archive table) ---
$otQuery1 = "
    SELECT pot.employee_id, pot.ot_duration, pot.ot_type, tl.log_date, e.fname, e.lname
    FROM post2_overtime_requests pot
    LEFT JOIN time_logs tl ON pot.time_log_id = tl.id
    LEFT JOIN employees e ON pot.employee_id = e.id
    WHERE LOWER(pot.status) = 'approved' AND tl.log_date BETWEEN :start_date AND :end_date
";
$otParams1 = ['start_date' => $startDate, 'end_date' => $endDate];
if (!empty($search)) {
    $otQuery1 .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
    $otParams1['search'] = "%$search%";
}
$otStmt1 = $pdo->prepare($otQuery1);
$otStmt1->execute($otParams1);
$otRecords = array_merge($otRecords, $otStmt1->fetchAll(PDO::FETCH_ASSOC));

// --- Source 2: post_ot_requests (recently approved, not yet archived) ---
try {
    $otQuery2 = "
        SELECT pot.employee_id, pot.ot_duration, pot.ot_type, tl.log_date, e.fname, e.lname
        FROM post_ot_requests pot
        LEFT JOIN time_logs tl ON pot.time_log_id = tl.id
        LEFT JOIN employees e ON pot.employee_id = e.id
        WHERE LOWER(pot.status) = 'approved' AND tl.log_date BETWEEN :start_date AND :end_date
    ";
    $otParams2 = ['start_date' => $startDate, 'end_date' => $endDate];
    if (!empty($search)) {
        $otQuery2 .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
        $otParams2['search'] = "%$search%";
    }
    $otStmt2 = $pdo->prepare($otQuery2);
    $otStmt2->execute($otParams2);
    $otRecords = array_merge($otRecords, $otStmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Table may not exist, continue without it
}

// --- Source 3: post_overtime_requests (old approval flow — different schema) ---
try {
    $otQuery3 = "
        SELECT pot.employee_id, pot.duration_hours AS ot_duration, 'Regular OT' AS ot_type, pot.date AS log_date, e.fname, e.lname
        FROM post_overtime_requests pot
        LEFT JOIN employees e ON pot.employee_id = e.id
        WHERE LOWER(pot.status) = 'approved' AND pot.date BETWEEN :start_date AND :end_date
    ";
    $otParams3 = ['start_date' => $startDate, 'end_date' => $endDate];
    if (!empty($search)) {
        $otQuery3 .= " AND (e.fname LIKE :search OR e.lname LIKE :search OR e.company LIKE :search)";
        $otParams3['search'] = "%$search%";
    }
    $otStmt3 = $pdo->prepare($otQuery3);
    $otStmt3->execute($otParams3);
    $otRecords = array_merge($otRecords, $otStmt3->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Table may not exist, continue without it
}

// Process approved overtime requests
$otMap = [];

foreach ($otRecords as $ot) {
    $emp_id = $ot['employee_id'];
    $date = $ot['log_date'];
    $hours = floatval($ot['ot_duration'] ?? 0);
    $ot_type = $ot['ot_type'] ?? '';
    
    if ($hours > 0 && $date && $emp_id) {
        // If there are multiple OT entries for the same employee on the same date, sum them up
        if (isset($otMap[$emp_id][$date])) {
            $otMap[$emp_id][$date]['hours'] += $hours;
            // If any entry is RDOT, mark the whole day as RDOT
            if ($ot_type === 'Restday OT') {
                $otMap[$emp_id][$date]['is_rdot'] = true;
            }
        } else {
            $otMap[$emp_id][$date] = [
                'hours' => $hours,
                'is_rdot' => ($ot_type === 'Restday OT')
            ];
        }
    }
}

// Filter employees to only show those with overtime in the selected period
if (!empty($search) || !empty($otMap)) {
    $employeesWithOT = [];
    foreach ($employees as $emp) {
        // Include employee if they have OT records or if no search filter
        if (isset($otMap[$emp['id']]) || empty($search)) {
            $employeesWithOT[] = $emp;
        }
    }
    $employees = $employeesWithOT;
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Add title row with date range
$titleText = 'OVERTIME REPORT - ' . date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
$sheet->setCellValue('A1', $titleText);

// Merge title across all columns (we'll adjust this after determining column count)
$totalCols = count($dateHeaders) + 3; // Name + dates + 2 totals
$lastCol = Coordinate::stringFromColumnIndex($totalCols);
$sheet->mergeCells("A1:{$lastCol}1");

// Style title row
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
$sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
$sheet->getRowDimension('1')->setRowHeight(25);

// Headers (now on row 2)
$sheet->setCellValue('A2', 'Name');
$colIndex = 2;
foreach ($dateHeaders as $date) {
    $cell = Coordinate::stringFromColumnIndex($colIndex++) . '2';
    $sheet->setCellValue($cell, date('M j', strtotime($date))); // Changed format to "M j" for better spacing
}
$sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . '2', 'TOTAL OT HRS');
$sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . '2', 'TOTAL RDOT HRS');

// Style headers (row 2)
$highestCol = Coordinate::stringFromColumnIndex($colIndex);
$headerRange = "A2:{$highestCol}2";

// Header styling
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E7E6E6');
$sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
$sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
$sheet->getRowDimension('2')->setRowHeight(22);

// Set proper column widths to prevent overflow
$sheet->getColumnDimension('A')->setWidth(35); // Name column - much wider for full names

// Date columns - adequate width for "MMM J" format (July 5, July 15, etc.)
for ($i = 2; $i <= ($colIndex - 2); $i++) {
    $colLetter = Coordinate::stringFromColumnIndex($i);
    $sheet->getColumnDimension($colLetter)->setWidth(12);
}

// Total columns - wider for totals
$totalOTCol = Coordinate::stringFromColumnIndex($colIndex - 1);
$totalRDOTCol = Coordinate::stringFromColumnIndex($colIndex);
$sheet->getColumnDimension($totalOTCol)->setWidth(18);
$sheet->getColumnDimension($totalRDOTCol)->setWidth(18);

// Set row height for better readability
$sheet->getDefaultRowDimension()->setRowHeight(20);

// Fill data rows (starting from row 3)
$row = 3;
foreach ($employees as $emp) {
    // Format name as "Last Name, First Name"
    $fullName = trim($emp['lname'] . ', ' . $emp['fname']);
    $sheet->setCellValue("A{$row}", $fullName);
    
    // Style name cell - make it bold
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    
    $totalOT = 0;
    $totalRDOT = 0;
    $col = 2;

    foreach ($dateHeaders as $date) {
        $cell = Coordinate::stringFromColumnIndex($col++) . $row;

        if (isset($otMap[$emp['id']][$date])) {
            $entry = $otMap[$emp['id']][$date];
            $hours = $entry['hours'];
            
            // Display just the hours (no decimal if whole number)
            $displayHours = ($hours == floor($hours)) ? (int)$hours : number_format($hours, 2);
            $sheet->setCellValue($cell, $displayHours);

            // Apply styling for RDOT (Restday OT) or regular OT
            if ($entry['is_rdot']) {
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9D9D9'); // Dark gray background for RDOT
                $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('333333'); // Dark gray text
                $totalRDOT += $entry['hours'];
            } else {
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E6F3FF'); // Light blue background for regular OT
                $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('0066CC'); // Dark blue text
                $totalOT += $entry['hours'];
            }
            
            // Center align and add border
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        } else {
            $sheet->setCellValue($cell, '');
            // Add alternating row color for empty cells
            if ($row % 2 == 0) {
                $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F9FA');
            }
        }
    }

    // Add totals with enhanced formatting
    $totalOTCell = Coordinate::stringFromColumnIndex($col++) . $row;
    $totalRDOTCell = Coordinate::stringFromColumnIndex($col) . $row;
    
    $sheet->setCellValue($totalOTCell, $totalOT > 0 ? number_format($totalOT, 2) : '');
    $sheet->setCellValue($totalRDOTCell, $totalRDOT > 0 ? number_format($totalRDOT, 2) : '');
    
    // Style total cells with better appearance
    $sheet->getStyle($totalOTCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle($totalRDOTCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    
    // Enhanced styling for totals
    if ($totalOT > 0) {
        $sheet->getStyle($totalOTCell)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('0066CC');
        $sheet->getStyle($totalOTCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6F3FF');
    }
    if ($totalRDOT > 0) {
        $sheet->getStyle($totalRDOTCell)->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('333333');
        $sheet->getStyle($totalRDOTCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
    }
    
    // Add borders to total cells
    $sheet->getStyle($totalOTCell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
    $sheet->getStyle($totalRDOTCell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);
    
    $row++;
}

// Apply borders to all data cells (excluding title row)
$lastRow = $row - 1;
$dataRange = "A2:{$highestCol}{$lastRow}";
$sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Add a thicker border around the entire data area
$sheet->getStyle("A1:{$highestCol}{$lastRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// Create filename with actual date range
$filename = 'Overtime_Report_' . date('M-d-Y', strtotime($startDate)) . '_to_' . date('M-d-Y', strtotime($endDate)) . '.xlsx';
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
