<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;

// Store latest approved schedule change in session for future use
$scheduleRequestStmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE employee_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 1");
$scheduleRequestStmt->execute([$employee_id]);
$latestScheduleRequest = $scheduleRequestStmt->fetch(PDO::FETCH_ASSOC);

if ($latestScheduleRequest) {
    $_SESSION['schedule_request'] = [
        'start_date' => $latestScheduleRequest['start_date'],
        'end_date' => $latestScheduleRequest['end_date'],
        'work_schedule_id' => $latestScheduleRequest['work_schedule_id'],
        'status' => $latestScheduleRequest['status']
    ];
}

$default_schedule_id = 4;
$schedule_times = [
    4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
    5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
    6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
    7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
    8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
    9 => ['in' => ' AM', 'out' => '08:00 PM'],
];

// Fetch all logs for July 1, 2025 onwards
$allLogsStmt = $pdo->prepare("
    SELECT 
        t.log_date, t.time_in, t.time_out, 
        r.requested_time_in, r.requested_time_out, r.status AS request_status, 
        o.start_time AS startOT, o.end_time AS endOT, o.status AS ot_status
    FROM time_logs t
    LEFT JOIN (
        SELECT r1.* 
        FROM time_adjustment_requests r1 
        INNER JOIN (
            SELECT employee_id, log_date, MAX(id) AS latest_id 
            FROM time_adjustment_requests 
            WHERE status = 'approved' 
            GROUP BY employee_id, log_date
        ) r2 ON r1.id = r2.latest_id
    ) r ON t.employee_id = r.employee_id AND t.log_date = r.log_date
    LEFT JOIN (
        SELECT o1.* 
        FROM overtime_requests o1 
        INNER JOIN (
            SELECT employee_id, date, MAX(id) AS latest_id 
            FROM overtime_requests 
            GROUP BY employee_id, date
        ) o2 ON o1.id = o2.latest_id
    ) o ON t.employee_id = o.employee_id AND t.log_date = o.date
    WHERE t.employee_id = ? AND t.log_date >= '2025-07-01'
");
$allLogsStmt->execute([$employee_id]);
$logs = $allLogsStmt->fetchAll(PDO::FETCH_ASSOC);

// Create date-indexed map
$logMap = [];
foreach ($logs as $log) {
    $logMap[$log['log_date']] = $log;
}

// Build date range
$start = new DateTime('2025-07-01');
$end = new DateTime();
$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($start, $interval, $end); // include today
?>

<div class="bg-white rounded-lg shadow p-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">My Attendance History </h3>
    </div>

    <div class="overflow-x-auto">
        <div style="max-height: 260px; overflow-y: auto;">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours Worked</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overtime</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach (array_reverse(iterator_to_array($dateRange)) as $dateObj): ?>
                        <?php
                            $logDate = $dateObj->format('Y-m-d');
                            $dayName = $dateObj->format('l');
                            $formattedDate = $dateObj->format('d M Y');

                            $log = $logMap[$logDate] ?? null;

                            // Schedule logic
                            $schedule_id_to_use = $default_schedule_id;
                            if (isset($_SESSION['schedule_request']) && strtolower($_SESSION['schedule_request']['status']) === 'approved') {
                                $req_start = $_SESSION['schedule_request']['start_date'];
                                $req_end = $_SESSION['schedule_request']['end_date'];
                                if ($logDate >= $req_start && $logDate <= $req_end) {
                                    $schedule_id_to_use = $_SESSION['schedule_request']['work_schedule_id'];
                                }
                            }

                            $schedule_in = $schedule_times[$schedule_id_to_use]['in'];
                            $schedule_out = $schedule_times[$schedule_id_to_use]['out'];

                            // Handle times
                            $isApproved = isset($log) && strtolower($log['request_status'] ?? '') === 'approved';
                            $timeIn = $isApproved ? ($log['requested_time_in'] ?? null) : ($log['time_in'] ?? null);
                            $timeOut = $isApproved ? ($log['requested_time_out'] ?? null) : ($log['time_out'] ?? null);

                            $timeInDisplay = $timeIn ? date('h:i A', strtotime($timeIn)) : '-';
                            $timeOutDisplay = $timeOut ? date('h:i A', strtotime($timeOut)) : '-';

                            $hoursWorked = '-';
                            if ($timeIn && $timeOut) {
                                $start = new DateTime($timeIn);
                                $end = new DateTime($timeOut);
                                $diff = $start->diff($end);
                                $hours = $diff->h + ($diff->i / 60);
                                $hoursWorked = number_format($hours, 2);
                            }

                            // Status
                            $status = '-';
                            $badgeClass = 'bg-gray-100 text-gray-800';

                            if ($timeIn) {
                                $inTime = strtotime("$logDate " . date('H:i:s', strtotime($timeIn)));
                                $standardIn = strtotime("$logDate " . date('H:i:s', strtotime('+15 minutes', strtotime($schedule_in))));
                                $sched_out = strtotime("$logDate " . date('H:i:s', strtotime($schedule_out)));
                                $actual_out = $timeOut ? strtotime("$logDate " . date('H:i:s', strtotime($timeOut))) : $sched_out;

                                if ($inTime <= $standardIn) {
                                    $status = 'On Time';
                                    $badgeClass = 'bg-green-100 text-green-800';
                                } else {
                                    $status = 'Late';
                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                }

                                if ($actual_out < $sched_out) {
                                    $status = 'Left Early';
                                    $badgeClass = 'bg-red-100 text-red-800';
                                }
                            }

                            // Overtime
                            $overtimeDisplay = '-';
                            if ($log && strtolower($log['ot_status'] ?? '') === 'approved') {
                                if ($log['startOT'] && $log['endOT']) {
                                    $overtimeDisplay = date('h:i A', strtotime($log['startOT'])) . ' - ' . date('h:i A', strtotime($log['endOT']));
                                }
                            } elseif ($log && strtolower($log['ot_status'] ?? '') === 'pending') {
                                $overtimeDisplay = 'Pending';
                            }
                        ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $formattedDate ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $dayName ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $timeInDisplay ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $timeOutDisplay ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $hoursWorked ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $overtimeDisplay ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $badgeClass ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
