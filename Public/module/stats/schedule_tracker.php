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

// Fetch official_sched from employee table
$stmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Fallback to 4 if not set
$default_schedule_id = $employee['official_sched'] ?? 4;

// Hardcoded schedule times
$schedule_times = [
    1 => ['in' => '06:30:00', 'out' => '15:30:00'],
    2 => ['in' => '08:00:00', 'out' => '19:00:00'],
    3 => ['in' => '07:30:00', 'out' => '16:30:00'],
    4 => ['in' => '07:00:00', 'out' => '16:00:00'],
    5 => ['in' => '08:00:00', 'out' => '17:00:00'],
    6 => ['in' => '09:00:00', 'out' => '18:00:00'],
    7 => ['in' => '10:00:00', 'out' => '19:00:00'],
    8 => ['in' => '06:00:00', 'out' => '15:00:00'],
    9 => ['in' => '08:00:00', 'out' => '16:30:00'],
    10 => ['in' => '07:40:00', 'out' => '16:40:00'],
    11 => ['in' => '06:30:00', 'out' => '15:00:00'],
    12 => ['in' => '06:30:00', 'out' => '17:30:00'],
    13 => ['in' => '07:00:00', 'out' => '18:00:00'],
    14 => ['in' => '06:00:00', 'out' => '17:00:00'],
    15 => ['in' => '06:00:00', 'out' => '16:00:00'],
    16 => ['in' => '08:30:00', 'out' => '16:30:00'],
    17 => ['in' => '06:00:00', 'out' => '12:00:00'],
    18 => ['in' => '06:00:00', 'out' => '14:30:00'],
    19 => ['in' => '19:00:00', 'out' => '3:00:00'],     
];

$today = date('Y-m-d');

// Get current active approved schedule from post_schedule_change_requests
$stmt = $pdo->prepare("
    SELECT work_schedule_id, status, start_date, end_date 
    FROM post_schedule_change_requests 
    WHERE employee_id = ? AND status = 'Approved' 
    AND ? BETWEEN start_date AND end_date 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id, $today]);
$currentActiveSchedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Get latest schedule request from schedule_change_requests (for pending status)
$stmt = $pdo->prepare("
    SELECT * FROM schedule_change_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$latestRequest = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine what schedule to use based on current situation
if ($currentActiveSchedule) {
    // There's an active approved schedule - use it
    $current_real_schedule_id = $currentActiveSchedule['work_schedule_id'] ?? $default_schedule_id;
    $schedule_id_to_use = $current_real_schedule_id;
    
    // Check if latest request is pending (while current approved is still active)
    if ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') {
        $schedule_status = "pending";
        $status_text = "New Schedule Pending";
    } else {
        $schedule_status = "approved";
        $status_text = "Changed Schedule (Active: " . date('M d', strtotime($currentActiveSchedule['start_date'])) . " - " . date('M d', strtotime($currentActiveSchedule['end_date'])) . ")";
    }
} else {
    // No currently active approved schedule
    $current_real_schedule_id = $default_schedule_id;
    $schedule_id_to_use = $default_schedule_id;
    
    // Check latest request status
    if ($latestRequest) {
        $status = strtolower(trim($latestRequest['status']));
        
        if ($status === 'pending') {
            $schedule_status = "pending";
            $status_text = "Schedule Change Pending ";
        } elseif (in_array($status, ['declined', 'rejected'])) {
            $schedule_status = "declined";
            $status_text = "Request Declined";
        } else {
            $schedule_status = "baseline";
            $status_text = "Official Schedule (ID: {$default_schedule_id})";
        }
    } else {
        $schedule_status = "baseline";
        $status_text = "Official Schedule";
    }
}

// Final schedule - convert to display format
$sched_time_in_24h = $schedule_times[$schedule_id_to_use]['in'] ?? '07:00:00';
$sched_time_out_24h = $schedule_times[$schedule_id_to_use]['out'] ?? '16:00:00';
$sched_time_in  = date('h:i A', strtotime($sched_time_in_24h));
$sched_time_out = date('h:i A', strtotime($sched_time_out_24h));

// Determine UI badge color
$color = match ($schedule_status) {
    'approved' => 'green',
    'pending'  => 'yellow',
    'declined' => 'red',
    default    => 'blue',
};

// Store in session (including current real schedule ID)
$_SESSION['current_schedule'] = [
    'schedule_id' => $schedule_id_to_use,
    'current_real_schedule_id' => $current_real_schedule_id,
    'baseline_schedule_id' => $default_schedule_id,
    'time_in'     => $sched_time_in,
    'time_out'    => $sched_time_out,
    'status'      => $schedule_status,
    'status_text' => $status_text,
    'has_active_approved' => $currentActiveSchedule ? true : false,
    'has_pending_request' => ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') ? true : false,
];

// Function to get current real-time schedule ID (updated to use post_schedule_change_requests)
function getCurrentRealScheduleId($employee_id, $pdo) {
    // Get employee's default schedule
    $stmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $default_schedule_id = $employee['official_sched'] ?? 4;
    
    $today = date('Y-m-d');
    
    // Check for active approved schedule changes from post_schedule_change_requests
    $stmt = $pdo->prepare("
        SELECT work_schedule_id, status, start_date, end_date 
        FROM post_schedule_change_requests 
        WHERE employee_id = ? AND status = 'Approved' 
        AND ? BETWEEN start_date AND end_date 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $today]);
    $activeRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $activeRequest ? ($activeRequest['work_schedule_id'] ?? $default_schedule_id) : $default_schedule_id;
}
?>

<!-- ✅ UI: Schedule Tracker Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Schedule Today</p>
            <p class="text-2xl font-bold text-gray-900" style="font-family: 'Inter', sans-serif;">
                <?= htmlspecialchars($sched_time_in) ?> - <?= htmlspecialchars($sched_time_out) ?>
            </p>
            <!-- Enhanced debug info -->
            <!-- Debug info removed as requested -->
        </div>
        <div class="w-12 h-12 rounded-full bg-<?= $color ?>-100 flex items-center justify-center">
            <?php if ($currentActiveSchedule): ?>
                <i class="fas fa-exchange-alt text-xl text-<?= $color ?>-600"></i>
            <?php else: ?>
                <i class="fas fa-calendar-day text-xl text-<?= $color ?>-600"></i>
            <?php endif; ?>
        </div>
    </div>
    <div class="mt-2 flex items-center text-sm text-<?= $color ?>-600">
        <span class="bg-<?= $color ?>-100 px-1 py-1 rounded-full">
            <?php 
            // Remove 'Active:' from status_text if present
            $clean_status_text = str_replace('Active:', '', $status_text);
            echo htmlspecialchars(trim($clean_status_text));
            ?>
        </span>
    </div>
</div>
