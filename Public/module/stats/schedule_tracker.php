<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "Employee not logged in.";
    exit;
}

// Default schedule ID and time range
$default_schedule_id = 4;

// Hardcoded schedule time ranges
$schedule_times = [
    4 => ['in' => '7:00 AM',  'out' => '4:00 PM'],
    5 => ['in' => '8:00 AM',  'out' => '5:00 PM'],
    6 => ['in' => '9:00 AM',  'out' => '6:00 PM'],
    7 => ['in' => '10:00 AM', 'out' => '7:00 PM'],
    8 => ['in' => '6:00 AM',  'out' => '3:00 PM'],
];

$today = date('Y-m-d');

// Get most recent request (regardless of status)
$stmt = $pdo->prepare("
    SELECT * FROM schedule_change_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$scheduleRequest = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize default values
$schedule_id_to_use = $default_schedule_id;
$schedule_status = "default";
$status_text = "Default Schedule Active";

// Evaluate most recent request
if ($scheduleRequest) {
    $status = strtolower(trim($scheduleRequest['status']));
    $start_date = $scheduleRequest['start_date'];
    $end_date = $scheduleRequest['end_date'];

    switch ($status) {
        case 'approved':
            if ($today >= $start_date && $today <= $end_date) {
                $schedule_id_to_use = $scheduleRequest['work_schedule_id'] ?? $default_schedule_id;
                $status_text = "$start_date to $end_date";
                $schedule_status = "approved";
            } else {
                $status_text = "Latest Schedule Request Approved (Not Active Today)";
                $schedule_status = "approved";
            }
            break;

        case 'pending':
            $status_text = "Latest Schedule Change Request is Pending";
            $schedule_status = "pending";
            break;

        case 'declined':
        case 'rejected':
            $status_text = "Latest Schedule Change Request was Declined";
            $schedule_status = "declined";
            break;
    }
}

// Fetch time range based on schedule ID
$sched_time_in = $schedule_times[$schedule_id_to_use]['in'] ?? 'N/A';
$sched_time_out = $schedule_times[$schedule_id_to_use]['out'] ?? 'N/A';

if ($sched_time_in === 'N/A' || $sched_time_out === 'N/A') {
    $status_text = "Schedule Time Not Found";
    $schedule_status = "error";
}

// Color indicator
$color = match ($schedule_status) {
    'approved' => 'green',
    'pending' => 'yellow',
    'declined', 'rejected', 'error' => 'red',
    default => 'gray',
};
?>

<!-- Schedule Tracker Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Schedule Tracker</p>
            <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars($sched_time_in) ?> - <?= htmlspecialchars($sched_time_out) ?></h3>
        </div>
        <div class="w-12 h-12 rounded-full bg-<?= $color ?>-100 flex items-center justify-center">
            <i class="fas fa-clock text-<?= $color ?>-700 text-xl"></i>
        </div>
    </div>
    <div class="mt-4">
        <p class="text-sm text-gray-600"><?= htmlspecialchars($status_text) ?></p>
    </div>
</div>
