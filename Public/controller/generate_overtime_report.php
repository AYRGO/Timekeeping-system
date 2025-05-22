<?php
require '../../vendor/autoload.php';
require '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$month = date('n');
$year = date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$dateHeaders = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateHeaders[] = date('Y-m-d', strtotime("$year-$month-$day"));
}

// Fetch employee list
$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY lname ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch work schedule mappings
$scheduleStmt = $pdo->query("
    SELECT ews.employee_id, ews.effective_date, ws.time_out 
    FROM employee_work_schedule ews
    JOIN work_schedules ws ON ews.work_schedule_id = ws.id
");
$schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

$workMap = [];
foreach ($schedules as $sched) {
    $workMap[$sched['employee_id']][$sched['effective_date']] = $sched['time_out'];
}

// Fetch approved regular OT
$otStmt = $pdo->prepare("
    SELECT aos.employee_id, aos.ot_date, aos.extended_time_out 
    FROM approved_overtime_schedule aos 
    WHERE aos.ot_date BETWEEN :start AND :end
");
$otStmt->execute([
    'start' => "$year-$month-01",
    'end' => "$year-$month-$daysInMonth"
]);
$otRecords = $otStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved RDOT
$rdotStmt = $pdo->prepare("
    SELECT employee_id, rest_day_date, expected_time_in, expected_time_out
    FROM rest_day_overtime_requests
    WHERE status = 'Approved' AND rest_day_date BETWEEN :start AND :end
");
$rdotStmt->execute([
    'start' => "$year-$month-01",
    'end' => "$year-$month-$daysInMonth"
]);
$rdotRecords = $rdotStmt->fetchAll(PDO::FETCH_ASSOC);

// Combine OT + RDOT into one map
$otMap = [];

foreach ($otRecords as $ot) {
    $emp_id = $ot['employee_id'];
    $date = $ot['ot_date'];
    $extended_out = $ot['extended_time_out'];

    $effective_dates = array_keys($workMap[$emp_id] ?? []);
    rsort($effective_dates);

    $schedule_out = null;
    foreach ($effective_dates as $eff_date) {
        if ($eff_date <= $date) {
            $schedule_out = $workMap[$emp_id][$eff_date];
            break;
        }
    }

    if ($schedule_out) {
        $scheduled = new DateTime($schedule_out);
        $extended = new DateTime($extended_out);
        $interval = $scheduled->diff($extended);
        $hours = $interval->h + ($interval->i / 60);

        if ($hours > 0) {
            $otMap[$emp_id][$date] = [
                'hours' => $hours,
                'is_rdot' => false
            ];
        }
    }
}

foreach ($rdotRecords as $rdot) {
    $emp_id = $rdot['employee_id'];
    $date = $rdot['rest_day_date'];
    $time_in = $rdot['expected_time_in'];
    $time_out = $rdot['expected_time_out'];

    if ($time_in && $time_out) {
        $start = new DateTime($time_in);
        $end = new DateTime($time_out);
        $interval = $start->diff($end);
        $hours = $interval->h + ($interval->i / 60);

        if ($hours > 0) {
            $otMap[$emp_id][$date] = [
                'hours' => $hours,
                'is_rdot' => true
            ];
        }
    }
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Name');
$colIndex = 2;
foreach ($dateHeaders as $date) {
    $cell = Coordinate::stringFromColumnIndex($colIndex++) . '1';
    $sheet->setCellValue($cell, date('M d', strtotime($date)));
}
$sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++) . '1', 'TOTAL OT HRS');
$sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . '1', 'TOTAL RDOT HRS');

// Style headers
$highestCol = Coordinate::stringFromColumnIndex($colIndex);
$sheet->getStyle("A1:{$highestCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
$sheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

// Fill data rows
$row = 2;
foreach ($employees as $emp) {
    $sheet->setCellValue("A{$row}", "{$emp['lname']}, {$emp['fname']}");
    $totalOT = 0;
    $totalRDOT = 0;
    $col = 2;

    foreach ($dateHeaders as $date) {
        $cell = Coordinate::stringFromColumnIndex($col++) . $row;

        if (isset($otMap[$emp['id']][$date])) {
            $entry = $otMap[$emp['id']][$date];
            $hours = number_format($entry['hours'], 2);
            $label = $entry['is_rdot'] ? "RDOT: $hours" : $hours;
            $sheet->setCellValue($cell, $label);

            if ($entry['is_rdot']) {
                $totalRDOT += $entry['hours'];
            } else {
                $totalOT += $entry['hours'];
            }
        } else {
            $sheet->setCellValue($cell, '');
        }
    }

    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col++) . $row, number_format($totalOT, 2));
    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, number_format($totalRDOT, 2));
    $row++;
}

// Output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Overtime_Report_' . date('F_Y') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
