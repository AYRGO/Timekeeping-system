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

// Default schedule ID
$default_schedule_id = 4;

// Hardcoded schedule times (you can also fetch this from DB if needed)
$schedule_times = [
    3 => ['in' => '07:30 AM', 'out' => '04:30 PM'],
    4 => ['in' => '07:00 AM', 'out' => '04:00 PM'],
    5 => ['in' => '08:00 AM', 'out' => '05:00 PM'],
    6 => ['in' => '09:00 AM', 'out' => '06:00 PM'],
    7 => ['in' => '10:00 AM', 'out' => '07:00 PM'],
    8 => ['in' => '06:00 AM', 'out' => '03:00 PM'],
    9 => ['in' => '08:00 AM', 'out' => '04:30 PM'],
    10 => ['in' => '07:40 AM', 'out' => '04:40 PM'],
];

$today = date('Y-m-d');

// Get latest schedule request for today
$stmt = $pdo->prepare("
    SELECT * FROM schedule_change_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$scheduleRequest = $stmt->fetch(PDO::FETCH_ASSOC);

// Initial defaults
$schedule_id_to_use = $default_schedule_id;
$schedule_status = "default";
$status_text = "Regular Shift";

if ($scheduleRequest) {
    $status = strtolower(trim($scheduleRequest['status']));
    $start_date = $scheduleRequest['start_date'];
    $end_date = $scheduleRequest['end_date'];

    if ($status === 'approved' && $today >= $start_date && $today <= $end_date) {
        $schedule_id_to_use = $scheduleRequest['work_schedule_id'] ?? $default_schedule_id;
        $schedule_status = "approved";
        $status_text = "Adjusted Shift (" . date('M d', strtotime($start_date)) . " - " . date('M d', strtotime($end_date)) . ")";
    } elseif ($status === 'pending') {
        $schedule_status = "pending";
        $status_text = "Schedule Change Pending";
    } elseif (in_array($status, ['declined', 'rejected'])) {
        $schedule_status = "declined";
        $status_text = "Schedule Change Declined";
    }
}

// Final schedule time
$sched_time_in  = $schedule_times[$schedule_id_to_use]['in'] ?? 'N/A';
$sched_time_out = $schedule_times[$schedule_id_to_use]['out'] ?? 'N/A';

// Determine UI badge color
$color = match ($schedule_status) {
    'approved' => 'green',
    'pending'  => 'yellow',
    'declined' => 'red',
    default    => 'blue',
};

// Store schedule info in session for reuse
$_SESSION['current_schedule'] = [
    'schedule_id' => $schedule_id_to_use,
    'time_in'     => $sched_time_in,
    'time_out'    => $sched_time_out,
    'status'      => $schedule_status,
    'status_text' => $status_text,
];
?>

<!-- ✅ UI: Schedule Tracker Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Schedule Today</p>
            <p class="text-2xl font-bold text-gray-900" style="font-family: 'Inter', sans-serif;">
                <?= htmlspecialchars($sched_time_in) ?> - <?= htmlspecialchars($sched_time_out) ?>
            </p>
        </div>
        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="fas fa-calendar-day text-xl text-blue-600"></i>
        </div>
    </div>
    <div class="mt-2 flex items-center text-sm text-<?= $color ?>-600">
        <span class="bg-<?= $color ?>-100 px-2 py-1 rounded-full"><?= htmlspecialchars($status_text) ?></span>
    </div>
</div>
