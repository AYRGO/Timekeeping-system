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
    19 => ['in' => '19:00:00', 'out' => '03:00:00'],
    20 => ['in' => '19:00:00', 'out' => '04:30:00'],
    21 => ['in' => '17:00:00', 'out' => '02:00:00'],
    22 => ['in' => '17:30:00', 'out' => '02:00:00'],
];

$today = date('Y-m-d');

// PRIORITY 1: Check for daily override in employee_daily_schedules (same as calendar)
$stmt = $pdo->prepare("
    SELECT actual_schedule_id, is_rest_day
    FROM employee_daily_schedules 
    WHERE employee_id = ? AND schedule_date = ? 
    LIMIT 1
");
$stmt->execute([$employee_id, $today]);
$dailyOverride = $stmt->fetch(PDO::FETCH_ASSOC);

// PRIORITY 2: Check for weekly default in employee_default_schedules
$dayOfWeek = date('w', strtotime($today)); // 0=Sunday, 1=Monday, ..., 6=Saturday
$stmt = $pdo->prepare("
    SELECT work_schedule_id, is_rest_day 
    FROM employee_default_schedules 
    WHERE employee_id = ? AND day_of_week = ? 
    AND effective_from <= ? AND (effective_until IS NULL OR effective_until >= ?) 
    LIMIT 1
");
$stmt->execute([$employee_id, $dayOfWeek, $today, $today]);
$weeklyDefault = $stmt->fetch(PDO::FETCH_ASSOC);

// Get latest schedule request from schedule_change_requests (for pending status indicator)
$stmt = $pdo->prepare("
    SELECT * FROM schedule_change_requests 
    WHERE employee_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$employee_id]);
$latestRequest = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine what schedule to use based on calendar priority (matching schedule_content.php)
if ($dailyOverride) {
    // Daily override exists (from approved schedule change request)
    if ($dailyOverride['is_rest_day']) {
        $schedule_id_to_use = null; // Rest day
        $current_real_schedule_id = null;
        $schedule_status = "rest_day";
        $status_text = "Rest Day (From Request)";
    } else {
        $current_real_schedule_id = $dailyOverride['actual_schedule_id'] ?? $default_schedule_id;
        $schedule_id_to_use = $current_real_schedule_id;
        $schedule_status = "approved";
        $status_text = "Active Schedule Override";
    }
} elseif ($weeklyDefault) {
    // Weekly default schedule
    if ($weeklyDefault['is_rest_day']) {
        $schedule_id_to_use = null;
        $current_real_schedule_id = null;
        $schedule_status = "rest_day";
        $status_text = "Rest Day (Weekly Default)";
    } else {
        $current_real_schedule_id = $weeklyDefault['work_schedule_id'] ?? $default_schedule_id;
        $schedule_id_to_use = $current_real_schedule_id;
        $schedule_status = "weekly_default";
        $status_text = "Weekly Default Schedule";
    }
} else {
    // Fallback to employee's official schedule
    $current_real_schedule_id = $default_schedule_id;
    $schedule_id_to_use = $default_schedule_id;
    $schedule_status = "baseline";
    $status_text = "Official Schedule";
}

// Check if there's a pending request (overlay indicator)
if ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') {
    $schedule_status = "pending";
    $status_text = "Schedule Change Pending";
}

// Final schedule - convert to display format (handle rest days)
if ($schedule_id_to_use === null) {
    // Rest day
    $sched_time_in = 'REST';
    $sched_time_out = 'DAY';
} else {
    $sched_time_in_24h = $schedule_times[$schedule_id_to_use]['in'] ?? '07:00:00';
    $sched_time_out_24h = $schedule_times[$schedule_id_to_use]['out'] ?? '16:00:00';
    $sched_time_in  = date('h:i A', strtotime($sched_time_in_24h));
    $sched_time_out = date('h:i A', strtotime($sched_time_out_24h));
}

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
    'has_daily_override' => $dailyOverride ? true : false,
    'has_weekly_default' => $weeklyDefault ? true : false,
    'has_pending_request' => ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') ? true : false,
];

// Function to get current real-time schedule ID (updated to check employee_daily_schedules first)
function getCurrentRealScheduleId($employee_id, $pdo) {
    // Get employee's default schedule
    $stmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $default_schedule_id = $employee['official_sched'] ?? 4;
    
    $today = date('Y-m-d');
    
    // PRIORITY 1: Check employee_daily_schedules (matches calendar logic)
    $stmt = $pdo->prepare("
        SELECT actual_schedule_id, is_rest_day 
        FROM employee_daily_schedules 
        WHERE employee_id = ? AND schedule_date = ? 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $today]);
    $dailySchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dailySchedule) {
        return $dailySchedule['is_rest_day'] ? null : ($dailySchedule['actual_schedule_id'] ?? $default_schedule_id);
    }
    
    // PRIORITY 2: Check weekly default
    $dayOfWeek = date('w', strtotime($today));
    $stmt = $pdo->prepare("
        SELECT work_schedule_id, is_rest_day 
        FROM employee_default_schedules 
        WHERE employee_id = ? AND day_of_week = ? 
        AND effective_from <= ? AND (effective_until IS NULL OR effective_until >= ?) 
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $dayOfWeek, $today, $today]);
    $weeklySchedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($weeklySchedule) {
        return $weeklySchedule['is_rest_day'] ? null : ($weeklySchedule['work_schedule_id'] ?? $default_schedule_id);
    }
    
    // PRIORITY 3: Return default
    return $default_schedule_id;
}
?>

<!-- ✅ UI: Schedule Tracker Card -->
<div class="card bg-white rounded-lg p-6 shadow-sm transition-transform duration-200 hover:scale-105 hover:shadow-lg cursor-pointer mt-8">
    <div class="flex justify-between items-center">
        <div>
            <p class="text-gray-500">Schedule Today</p>
            <?php if ($schedule_id_to_use === null): ?>
                <p class="text-2xl font-bold text-gray-900" style="font-family: 'Inter', sans-serif;">
                    REST DAY
                </p>
            <?php else: ?>
                <p class="text-2xl font-bold text-gray-900" style="font-family: 'Inter', sans-serif;">
                    <?= htmlspecialchars($sched_time_in) ?> - <?= htmlspecialchars($sched_time_out) ?>
                </p>
            <?php endif; ?>
            <!-- Enhanced debug info -->
            <!-- Debug info removed as requested -->
        </div>
        <div class="w-12 h-12 rounded-full bg-<?= $color ?>-100 flex items-center justify-center">
            <?php if ($dailyOverride): ?>
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
