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

// Get most recent request (regardless of status)f
$stmt = $pdo->prepare("
    SELECT * FROM schedule_change_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$scheduleRequest = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize defaults
$schedule_id_to_use = $default_schedule_id;
$schedule_status = "default";
$status_text = "Regular Shift";

// Check latest schedule request
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

// Get schedule times
$sched_time_in = $schedule_times[$schedule_id_to_use]['in'] ?? 'N/A';
$sched_time_out = $schedule_times[$schedule_id_to_use]['out'] ?? 'N/A';

// Determine color
$color = match ($schedule_status) {
    'approved' => 'green',
    'pending' => 'yellow',
    'declined' => 'red',
    default => 'blue',
};
?>
<!-- ✅ Functional UI Schedule Tracker Card -->
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
