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

// PRIORITY 0: Check post_schedule_change_requests for approved day off requests
$stmt = $pdo->prepare("
    SELECT is_rest_day, start_date, end_date, work_schedule_id
    FROM post_schedule_change_requests
    WHERE employee_id = ? 
      AND status = 'Approved'
      AND is_rest_day = 1
      AND ? BETWEEN start_date AND end_date
    LIMIT 1
");
$stmt->execute([$employee_id, $today]);
$approvedDayOff = $stmt->fetch(PDO::FETCH_ASSOC);

// If today is within an approved day off period, treat it as OFF
if ($approvedDayOff) {
    $schedule_id_to_use = null;
    $current_real_schedule_id = null;
    $schedule_status = "off";
    $status_text = "Day Off (Approved)";
    $sched_time_in = 'OFF';
    $sched_time_out = '';
    $color = 'gray';
    
    // Store in session and skip further checks
    $_SESSION['current_schedule'] = [
        'schedule_id' => $schedule_id_to_use,
        'current_real_schedule_id' => $current_real_schedule_id,
        'baseline_schedule_id' => $default_schedule_id,
        'time_in'     => $sched_time_in,
        'time_out'    => $sched_time_out,
        'status'      => $schedule_status,
        'status_text' => $status_text,
        'has_daily_override' => false,
        'has_weekly_default' => false,
        'has_pending_request' => false,
    ];
} else {
    // Continue with normal priority checks
    
    // PRIORITY 1: Check employee_daily_schedule_cache
    $stmt = $pdo->prepare("
        SELECT 
            work_schedule_id,
            is_rest_day,
            is_holiday,
            schedule_name,
            time_in,
            time_out,
            source
        FROM employee_daily_schedule_cache
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $stmt->execute([$employee_id, $today]);
    $cacheData = $stmt->fetch(PDO::FETCH_ASSOC);

// FALLBACK: Check for weekly default in employee_default_schedules
$dayOfWeek = date('w', strtotime($today)); // 0=Sunday, 1=Monday, ..., 6=Saturday
$stmt = $pdo->prepare("
    SELECT edd.work_schedule_id, edd.is_rest_day, ws.time_in, ws.time_out, ws.name
    FROM employee_default_schedules edd
    LEFT JOIN work_schedules ws ON edd.work_schedule_id = ws.id
    WHERE edd.employee_id = ? AND edd.day_of_week = ? 
    AND edd.effective_from <= ? AND (edd.effective_until IS NULL OR edd.effective_until >= ?) 
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

// Determine what schedule to use based on cache or fallback
if ($cacheData) {
    // Data found in cache
    if ($cacheData['is_rest_day']) {
        $schedule_id_to_use = null;
        $current_real_schedule_id = null;
        $schedule_status = "off";
        $status_text = "Off";
        $sched_time_in = 'OFF';
        $sched_time_out = '';
    } elseif ($cacheData['is_holiday']) {
        $schedule_id_to_use = null;
        $current_real_schedule_id = null;
        $schedule_status = "holiday";
        $status_text = "Holiday";
        $sched_time_in = 'HOLIDAY';
        $sched_time_out = '';
    } else {
        $current_real_schedule_id = $cacheData['work_schedule_id'];
        $schedule_id_to_use = $current_real_schedule_id;
        
        // Determine status based on source
        if ($cacheData['source'] === 'approved_request') {
            $schedule_status = "approved";
            $status_text = "Schedule Changed";
        } elseif ($cacheData['source'] === 'admin_override') {
            $schedule_status = "approved";
            $status_text = "Admin Override";
        } else {
            $schedule_status = "weekly_default";
            $status_text = "Active Schedule";
        }
        
        // Use times from cache
        $sched_time_in = date('h:i A', strtotime($cacheData['time_in']));
        $sched_time_out = date('h:i A', strtotime($cacheData['time_out']));
    }
} elseif ($weeklyDefault) {
    // Fallback to weekly default
    if ($weeklyDefault['is_rest_day']) {
        $schedule_id_to_use = null;
        $current_real_schedule_id = null;
        $schedule_status = "off";
        $status_text = "Off";
        $sched_time_in = 'OFF';
        $sched_time_out = '';
    } else {
        $current_real_schedule_id = $weeklyDefault['work_schedule_id'] ?? $default_schedule_id;
        $schedule_id_to_use = $current_real_schedule_id;
        $schedule_status = "weekly_default";
        $status_text = "Active Schedule";
        
        // Use times from weekly default
        if ($weeklyDefault['time_in'] && $weeklyDefault['time_out']) {
            $sched_time_in = date('h:i A', strtotime($weeklyDefault['time_in']));
            $sched_time_out = date('h:i A', strtotime($weeklyDefault['time_out']));
        } else {
            $sched_time_in_24h = $schedule_times[$schedule_id_to_use]['in'] ?? '07:00:00';
            $sched_time_out_24h = $schedule_times[$schedule_id_to_use]['out'] ?? '16:00:00';
            $sched_time_in = date('h:i A', strtotime($sched_time_in_24h));
            $sched_time_out = date('h:i A', strtotime($sched_time_out_24h));
        }
    }
} else {
    // Check if weekend
    if ($dayOfWeek == 0 || $dayOfWeek == 6) {
        $schedule_id_to_use = null;
        $current_real_schedule_id = null;
        $schedule_status = "off";
        $status_text = "Off";
        $sched_time_in = 'OFF';
        $sched_time_out = '';
    } else {
        // Final fallback to employee's official schedule
        $current_real_schedule_id = $default_schedule_id;
        $schedule_id_to_use = $default_schedule_id;
        $schedule_status = "baseline";
        $status_text = "Official Schedule";
        
        $sched_time_in_24h = $schedule_times[$schedule_id_to_use]['in'] ?? '07:00:00';
        $sched_time_out_24h = $schedule_times[$schedule_id_to_use]['out'] ?? '16:00:00';
        $sched_time_in = date('h:i A', strtotime($sched_time_in_24h));
        $sched_time_out = date('h:i A', strtotime($sched_time_out_24h));
    }
}

    // Check if there's a pending request (overlay indicator)
    if ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') {
        $schedule_status = "pending";
        $status_text = "Schedule Change Pending";
    }

    // Determine UI badge color
    $color = match ($schedule_status) {
        'approved' => 'green',
        'pending'  => 'yellow',
        'declined' => 'red',
        'off'      => 'gray',
        'holiday'  => 'amber',
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
        'has_daily_override' => ($cacheData && $cacheData['source'] === 'admin_override') ? true : false,
        'has_weekly_default' => $weeklyDefault ? true : false,
        'has_pending_request' => ($latestRequest && strtolower(trim($latestRequest['status'])) === 'pending') ? true : false,
    ];
} // Close the else block from approved day off check

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
            <?php if ($schedule_status === 'off'): ?>
                <p class="text-2xl font-bold text-gray-600" style="font-family: 'Inter', sans-serif;">
                    OFF
                </p>
            <?php elseif ($schedule_status === 'holiday'): ?>
                <p class="text-2xl font-bold text-amber-600" style="font-family: 'Inter', sans-serif;">
                    HOLIDAY
                </p>
            <?php elseif ($schedule_id_to_use === null): ?>
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
            <?php if ($schedule_status === 'off'): ?>
                <i class="fas fa-bed text-xl text-<?= $color ?>-600"></i>
            <?php elseif ($schedule_status === 'holiday'): ?>
                <i class="fas fa-star text-xl text-<?= $color ?>-600"></i>
            <?php elseif ($schedule_status === 'approved'): ?>
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
