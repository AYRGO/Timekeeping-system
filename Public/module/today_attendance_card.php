<?php

date_default_timezone_set('Asia/Manila');

// Include CSRF helper
include_once('../config/csrf_helper.php');

// Make sure user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "<script>alert('User not logged in.'); location.href='../employee/login.php';</script>";
    exit;
}

// Initialize CSRF protection
init_csrf_protection();

// Get today's date
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

include_once('../config/db.php');

// Initialize variables
$time_in = null;
$time_out = null;
$log_out_date = null;
$is_overnight_shift = false;
$original_log_date = $today;
$shift_status = null;
$has_incomplete_previous_shift = false;
$incomplete_shift_date = null;
$incomplete_shift_time_in = null;

// Check if this is a reset request (cache busting parameter)
$is_reset_request = isset($_GET['reset']);

// STEP 1: Check for incomplete shift from yesterday ONLY (1 day ago)
// This prevents users from logging in today if they have an active shift from yesterday
$incompleteCheckStmt = $pdo->prepare("
    SELECT time_in, log_date 
    FROM time_logs 
    WHERE employee_id = ? 
    AND log_date = ? 
    AND time_in IS NOT NULL 
    AND time_out IS NULL 
    AND status != 'incomplete'
    LIMIT 1
");
$incompleteCheckStmt->execute([$employee_id, $yesterday]);
$incompleteFromYesterday = $incompleteCheckStmt->fetch(PDO::FETCH_ASSOC);

if ($incompleteFromYesterday) {
    $has_incomplete_previous_shift = true;
    $incomplete_shift_date = $incompleteFromYesterday['log_date'];
    $incomplete_shift_time_in = $incompleteFromYesterday['time_in'];
    error_log("Found incomplete shift from yesterday for employee $employee_id: " . $incomplete_shift_date . " " . $incomplete_shift_time_in);
}

// STEP 2: Mark shifts older than 14 hours as incomplete (system cleanup)
// DISABLED - Do not auto-mark shifts as incomplete in database
// Let the UI logic handle the display based on duration
// $oldIncompleteStmt = $pdo->prepare("
//     UPDATE time_logs 
//     SET status = 'incomplete' 
//     WHERE employee_id = ? 
//     AND time_out IS NULL 
//     AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) >= 14
//     AND status != 'incomplete'
// ");
// $oldIncompleteStmt->execute([$employee_id]);

$auto_marked_count = 0; // No auto-marking

if ($auto_marked_count > 0) {
    error_log("Marked " . $auto_marked_count . " old shifts as incomplete for employee $employee_id");
}

// STEP 3: Determine current shift logic
if ($has_incomplete_previous_shift) {
    // User has incomplete shift from yesterday - determine if it's overnight or truly incomplete
    $time_in = $incomplete_shift_time_in;
    $time_out = null;
    $original_log_date = $incomplete_shift_date;
    
    // Calculate hours elapsed
    $time_in_datetime = new DateTime($incomplete_shift_date . ' ' . $incomplete_shift_time_in);
    $now = new DateTime();
    $hours_since_time_in = ($now->getTimestamp() - $time_in_datetime->getTimestamp()) / 3600;
    
    // Determine if this should be treated as an overnight shift based on time_in
    $time_in_hour = (int)date('H', strtotime($incomplete_shift_time_in));
    $time_in_minute = (int)date('i', strtotime($incomplete_shift_time_in));
    $time_in_decimal = $time_in_hour + ($time_in_minute / 60);
    $is_likely_overnight_shift = $time_in_decimal >= 16.5; // 4:30 PM or later
    
    if ($hours_since_time_in >= 20 && !$is_likely_overnight_shift) {
        // Show incomplete card ONLY for regular shifts (not overnight) that exceed 20 hours
        // Overnight shifts (4:30 PM+) can run longer without being marked incomplete
        $is_overnight_shift = false; // Force incomplete card display (red)
        $shift_status = 'incomplete'; // UI status, not database status
        error_log("Showing incomplete UI (20+ hrs, regular shift) for employee $employee_id: " . $incomplete_shift_date . " " . $incomplete_shift_time_in);
    } else {
        // Either under 14 hours OR it's an overnight shift - show appropriate card based on start time
        $is_overnight_shift = $is_likely_overnight_shift;
        $shift_status = 'active';
        error_log("Showing " . ($is_overnight_shift ? "overnight" : "regular") . " shift from yesterday for completion: $incomplete_shift_date $incomplete_shift_time_in");
    }
} else {
    // No incomplete shift from yesterday - check today's logs normally
    
    // Priority 1: Check for today's regular time log (exclude incomplete ones)
    $stmt = $pdo->prepare("SELECT time_in, time_out, log_date, log_out_date, status FROM time_logs WHERE employee_id = ? AND log_date = ? AND status != 'incomplete' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$employee_id, $today]);
    $todayLog = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($todayLog) {
        // There's a regular log for today
        $time_in = $todayLog['time_in'];
        $time_out = $todayLog['time_out'];
        $original_log_date = $todayLog['log_date'];
        $log_out_date = $todayLog['log_out_date'] ?? null;
        $shift_status = $todayLog['status'] ?? 'active';
        error_log("Found today's log for employee $employee_id: time_in=$time_in, time_out=$time_out, log_out_date=$log_out_date, status=$shift_status");
    }
    // If no logs found, all variables remain null for fresh start
}

// Check for approved time adjustment for the relevant date
$adjStmt = $pdo->prepare("SELECT requested_time_in, requested_time_out FROM post_time_adjustment_requests 
    WHERE employee_id = ? AND log_date = ? AND status = 'approved' 
    ORDER BY id DESC LIMIT 1");
$adjStmt->execute([$employee_id, $original_log_date]);
$adj = $adjStmt->fetch(PDO::FETCH_ASSOC);

if ($adj) {
    // Use adjusted time if available
    $time_in = $adj['requested_time_in'] ?? $time_in;
    $time_out = $adj['requested_time_out'] ?? $time_out;
    // Note: log_out_date from adjustments not yet implemented in table schema
}

// Format times for display
$display_time_in = $time_in ? date('H:i:s', strtotime($time_in)) : null;
$display_time_out = $time_out ? date('H:i:s', strtotime($time_out)) : null;

// Calculate working duration
$workingDuration = '';
$is_cross_midnight = false;
$cross_midnight_indicator = '';

if ($display_time_in && $display_time_out) {
    // Create DateTime objects for calculation
    $start = new DateTime($original_log_date . ' ' . $time_in);
    
    // Use log_out_date if available, otherwise fall back to original logic
    if ($log_out_date && $log_out_date !== $original_log_date) {
        // log_out_date is different from log_date - definitely cross-midnight
        $end = new DateTime($log_out_date . ' ' . $time_out);
        $is_cross_midnight = true;
    } else {
        // Same date or no log_out_date - use original date with fallback logic
        $end = new DateTime($original_log_date . ' ' . $time_out);
        
        // Detect cross-midnight scenarios using original logic as fallback
        if ($is_overnight_shift || ($end < $start)) {
            // Confirmed overnight shift - time out is next day
            $end->add(new DateInterval('P1D')); // Add one day for overnight shifts
            $is_cross_midnight = true;
        } else {
            // Additional check: if shift duration seems too short (< 6 hours) 
            // and it's a reasonable work shift, likely cross-midnight
            $normalDiff = $start->diff($end);
            $normalHours = ($normalDiff->days * 24) + $normalDiff->h + ($normalDiff->i / 60);
            
            // If duration is less than 6 hours but the employee logged significant time,
            // AND time_in is after typical shift start (like 4:30 PM or later),
            // it's likely a night shift that crosses midnight
            $time_in_hour = (int)date('H', strtotime($time_in));
            $time_in_minute = (int)date('i', strtotime($time_in));
            $time_in_decimal = $time_in_hour + ($time_in_minute / 60);
            
            if ($normalHours < 6 && $time_in_decimal >= 16.5) {
                // Night shift starting 4:30 PM or later with short duration = likely cross-midnight
                $end->add(new DateInterval('P1D'));
                $is_cross_midnight = true;
            } elseif ($normalHours < 4 && $time_in_decimal >= 15.0) {
                // Afternoon shift starting 3:00 PM or later with very short duration = likely cross-midnight
                $end->add(new DateInterval('P1D'));
                $is_cross_midnight = true;
            }
        }
    }
    
    // Calculate final duration
    $diff = $start->diff($end);
    $totalHours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
    
    // Only deduct 1 hour for lunch break if total hours is 8 or above
    if ($totalHours >= 8) {
        $totalHours -= 1; // Deduct 1 hour for lunch break
    }
    
    if ($totalHours < 0) $totalHours = 0;
    
    // Format duration with cross-midnight indicator
    $workingDuration = number_format($totalHours, 2) . ' hours';
    if ($is_cross_midnight) {
        $cross_midnight_indicator = ' (next day)';
        $workingDuration .= $cross_midnight_indicator;
    }
}

// Check if current shift is marked as incomplete
$is_incomplete_shift = false;
if (isset($shift_status) && $shift_status === 'incomplete') {
    $is_incomplete_shift = true;
}

// Override: If we just reset, ensure we show the fresh active shift for today
if ($is_reset_request && !$is_incomplete_shift) {
    // Force refresh of today's data
    $freshCheckStmt = $pdo->prepare("SELECT time_in, time_out, log_date, status FROM time_logs WHERE employee_id = ? AND log_date = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
    $freshCheckStmt->execute([$employee_id, $today]);
    $freshLog = $freshCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freshLog) {
        $time_in = $freshLog['time_in'];
        $time_out = $freshLog['time_out'];
        $original_log_date = $freshLog['log_date'];
        $shift_status = 'active';
        $is_incomplete_shift = false;
        error_log("Reset: Found fresh log for employee $employee_id: time_in=$time_in");
    }
}

// Determine current shift status with improved logic
$can_time_in = !$time_in && !$has_incomplete_previous_shift; // Block time-in if incomplete previous shift exists
$can_time_out = $time_in && !$time_out; // Allow time-out if there's a time-in but no time-out
$shift_complete = $time_in && $time_out && !$has_incomplete_previous_shift;

// Check for manual shift completion confirmation
$shift_manually_completed = false;
$manual_completion_time = null;
$hours_until_next_shift = 0;

if ($shift_complete) {
    // Check if shift_completions table exists before querying
    $tableExistsStmt = $pdo->prepare("SHOW TABLES LIKE 'shift_completions'");
    $tableExistsStmt->execute();
    $tableExists = $tableExistsStmt->rowCount() > 0;
    
    if ($tableExists) {
        // Check if there's a manual completion record
        try {
            $completionStmt = $pdo->prepare("SELECT completed_at FROM shift_completions WHERE employee_id = ? AND log_date = ? ORDER BY completed_at DESC LIMIT 1");
            $completionStmt->execute([$employee_id, $original_log_date]);
            $completion = $completionStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                $shift_manually_completed = true;
                $manual_completion_time = $completion['completed_at'];
                
                // Calculate hours since manual completion
                $completionTime = new DateTime($manual_completion_time);
                $now = new DateTime();
                $hoursSinceCompletion = ($now->getTimestamp() - $completionTime->getTimestamp()) / 3600;
                
                if ($hoursSinceCompletion < 8) {
                    $hours_until_next_shift = 8 - $hoursSinceCompletion;
                    $can_time_in = false; // Restrict time in for 8 hours
                }
            }
        } catch (PDOException $e) {
            // Error querying table - skip manual completion check
            error_log("Error querying shift_completions table: " . $e->getMessage());
            $shift_manually_completed = false;
            $manual_completion_time = null;
        }
    } else {
        // Table doesn't exist - skip manual completion check
        error_log("shift_completions table does not exist - skipping manual completion check");
        $shift_manually_completed = false;
        $manual_completion_time = null;
    }
}

// Special case: If an overnight shift was completed today, allow new time-in for regular shift
if ($is_overnight_shift && $time_out) {
    // Overnight shift is complete, but check if user can start a new regular shift today
    $newShiftCheckStmt = $pdo->prepare("SELECT time_in FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_in IS NOT NULL");
    $newShiftCheckStmt->execute([$employee_id, $today]);
    $todayRegularShift = $newShiftCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$todayRegularShift) {
        // Check if shift_completions table exists before querying
        $tableExistsStmt = $pdo->prepare("SHOW TABLES LIKE 'shift_completions'");
        $tableExistsStmt->execute();
        $tableExists = $tableExistsStmt->rowCount() > 0;
        
        if ($tableExists) {
            // Check if overnight shift was manually completed
            try {
                $overnightCompletionStmt = $pdo->prepare("SELECT completed_at FROM shift_completions WHERE employee_id = ? AND log_date = ? ORDER BY completed_at DESC LIMIT 1");
                $overnightCompletionStmt->execute([$employee_id, $original_log_date]);
                $overnightCompletion = $overnightCompletionStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$overnightCompletion) {
                    // No manual completion yet, allow new time-in
                    $can_time_in = true;
                    $can_time_out = false;
                    $shift_complete = false;
                    $show_overnight_complete_message = true;
                } else {
                    // Check 8-hour restriction from overnight completion
                    $completionTime = new DateTime($overnightCompletion['completed_at']);
                    $now = new DateTime();
                    $hoursSinceCompletion = ($now->getTimestamp() - $completionTime->getTimestamp()) / 3600;
                    
                    if ($hoursSinceCompletion >= 8) {
                        $can_time_in = true;
                        $can_time_out = false;
                        $shift_complete = false;
                        $show_overnight_complete_message = true;
                    } else {
                        $hours_until_next_shift = 8 - $hoursSinceCompletion;
                        $can_time_in = false;
                    }
                }
            } catch (PDOException $e) {
                // Error querying table - allow new time-in without completion check
                error_log("Error querying shift_completions table: " . $e->getMessage());
                $can_time_in = true;
                $can_time_out = false;
                $shift_complete = false;
                $show_overnight_complete_message = true;
            }
        } else {
            // Table doesn't exist - allow new time-in without completion check
            error_log("shift_completions table does not exist - allowing new time-in for overnight shift");
            $can_time_in = true;
            $can_time_out = false;
            $shift_complete = false;
            $show_overnight_complete_message = true;
        }
    }
}

?>

<div class="bg-white rounded-2xl shadow-lg p-6 w-full md:w-2/3 mx-auto border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-user-clock mr-3 text-green-500 bg-green-100 p-2 rounded-full"></i>
            <?php if ($has_incomplete_previous_shift && $shift_status === 'incomplete'): ?>
                Incomplete Shift
            <?php elseif ($has_incomplete_previous_shift && $is_overnight_shift): ?>
                Complete Overnight Shift
            <?php elseif ($is_overnight_shift): ?>
                Overnight Shift Status
            <?php elseif ($is_incomplete_shift): ?>
                Incomplete Shift - Reset Available
            <?php else: ?>
                Today's Time Log
            <?php endif; ?>
            <?php if ($has_incomplete_previous_shift && $is_overnight_shift): ?>
                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    <i class="fas fa-moon mr-1"></i>Overnight
                </span>
            <?php elseif ($is_cross_midnight && $display_time_out): ?>
                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    <i class="fas fa-moon mr-1"></i>Cross-Midnight Shift
                </span>
            <?php elseif ($is_overnight_shift): ?>
                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    <i class="fas fa-moon mr-1"></i>Started: <?= date('M j', strtotime($original_log_date)) ?>
                </span>
            <?php elseif ($is_incomplete_shift): ?>
                <span class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Incomplete
                </span>
            <?php endif; ?>
        </h3>
        <span id="dashboardClock" class="text-sm font-mono text-gray-500 tracking-wide">--:--:-- --</span>
    </div>



    <?php // Removed incomplete shift warning message ?>

    <!-- Overnight Shift Warning -->
    <?php if ($is_overnight_shift && !$has_incomplete_previous_shift): ?>
    <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    You have an active overnight shift from <?= date('F j, Y', strtotime($original_log_date)) ?>. 
                    Please complete your time out to finish this shift.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overnight Shift Completion Message -->
    <?php if (isset($show_overnight_complete_message) && $show_overnight_complete_message): ?>
    <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-400 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">
                    ✅ Overnight shift completed! You can now start a new regular shift for today.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 8-Hour Restriction Message -->
    <?php if ($hours_until_next_shift > 0): ?>
    <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-400 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-clock text-amber-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-amber-700">
                    ⏰ You must wait <?= number_format($hours_until_next_shift, 1) ?> more hour(s) before starting a new shift.
                    <?php if ($manual_completion_time): ?>
                        <br><span class="text-xs">Last shift completed at: <?= date('h:i A', strtotime($manual_completion_time)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Time Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
        <!-- Time In -->
        <div class="flex items-center p-5 rounded-xl border <?php
            if ($has_incomplete_previous_shift && $shift_status === 'incomplete') {
                echo 'border-red-200 bg-red-50';
            } elseif ($has_incomplete_previous_shift && $is_overnight_shift) {
                echo 'border-blue-200 bg-blue-50';
            } elseif ($is_overnight_shift) {
                echo 'border-blue-200 bg-blue-50';
            } elseif ($is_incomplete_shift) {
                echo 'border-red-200 bg-red-50';
            } else {
                echo 'border-green-200 bg-green-50';
            }
        ?> shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr <?php
            if ($has_incomplete_previous_shift && $shift_status === 'incomplete') {
                echo 'from-red-300 to-red-500';
            } elseif ($has_incomplete_previous_shift && $is_overnight_shift) {
                echo 'from-blue-300 to-blue-500';
            } elseif ($is_overnight_shift) {
                echo 'from-blue-300 to-blue-500';
            } elseif ($is_incomplete_shift) {
                echo 'from-red-300 to-red-500';
            } else {
                echo 'from-green-300 to-green-500';
            }
        ?> text-white mr-4">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="text-2xl font-extrabold text-gray-900">
                    <?= $display_time_in ? date("h:i A", strtotime($display_time_in)) : '—'; ?>
                    <?php if ($is_cross_midnight && $display_time_in): ?>
                        <div class="text-sm font-medium text-green-700 mt-1">
                            on <?= date("M j, Y", strtotime($original_log_date)) ?>
                        </div>
                    <?php endif; ?>
                </p>
                <?php if ($has_incomplete_previous_shift && $shift_status === 'incomplete' && $display_time_in): ?>
                    <p class="text-xs text-red-500"><?= date('M j', strtotime($original_log_date)) ?> - Incomplete</p>
                <?php elseif (($has_incomplete_previous_shift && $is_overnight_shift) && $display_time_in): ?>
                    <p class="text-xs text-blue-500"><?= date('M j', strtotime($original_log_date)) ?> - Overnight</p>
                <?php elseif ($is_cross_midnight && $display_time_in): ?>
                    <p class="text-xs text-green-600 font-medium">
                        <i class="fas fa-calendar mr-1"></i>Start Date
                    </p>
                <?php elseif ($is_overnight_shift && $display_time_in): ?>
                    <p class="text-xs text-blue-500"><?= date('M j', strtotime($original_log_date)) ?> - Overnight</p>
                <?php elseif ($is_incomplete_shift && $display_time_in): ?>
                    <p class="text-xs text-red-500">Incomplete Shift</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Time Out -->
        <div class="flex items-center p-5 rounded-xl border <?php
            if ($has_incomplete_previous_shift && $shift_status === 'incomplete') {
                echo 'border-red-200 bg-red-50';
            } elseif ($has_incomplete_previous_shift && $is_overnight_shift) {
                echo 'border-blue-200 bg-blue-50';
            } elseif ($is_overnight_shift) {
                echo 'border-blue-200 bg-blue-50';
            } elseif ($is_incomplete_shift) {
                echo 'border-red-200 bg-red-50';
            } else {
                echo 'border-yellow-200 bg-yellow-50';
            }
        ?> shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr <?php
            if ($has_incomplete_previous_shift && $shift_status === 'incomplete') {
                echo 'from-red-300 to-red-500';
            } elseif ($has_incomplete_previous_shift && $is_overnight_shift) {
                echo 'from-blue-300 to-blue-500';
            } elseif ($is_overnight_shift) {
                echo 'from-blue-300 to-blue-500';
            } elseif ($is_incomplete_shift) {
                echo 'from-red-300 to-red-500';
            } else {
                echo 'from-yellow-300 to-yellow-500';
            }
        ?> text-white mr-4">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="text-2xl font-extrabold text-gray-900">
                    <?php if ($has_incomplete_previous_shift && $shift_status === 'incomplete'): ?>
                        <span class="text-red-600">REQUIRED</span>
                    <?php elseif ($has_incomplete_previous_shift && $is_overnight_shift): ?>
                        <span class="text-blue-600">REQUIRED</span>
                    <?php elseif ($is_overnight_shift): ?>
                        <span class="text-blue-600">REQUIRED</span>
                    <?php elseif ($is_incomplete_shift): ?>
                        <span class="text-red-600">INC</span>
                    <?php elseif ($is_cross_midnight && $display_time_out): ?>
                        <?= date("h:i A", strtotime($display_time_out)) ?>
                        <div class="text-sm font-medium text-blue-700 mt-1">
                            on <?= date("M j, Y", strtotime($original_log_date . ' +1 day')) ?>
                        </div>
                    <?php else: ?>
                        <?= $display_time_out ? date("h:i A", strtotime($display_time_out)) : '—'; ?>
                    <?php endif; ?>
                </p>
                <?php if ($has_incomplete_previous_shift && $shift_status === 'incomplete'): ?>
                    <p class="text-xs text-red-500">Complete previous shift</p>
                <?php elseif ($has_incomplete_previous_shift && $is_overnight_shift): ?>
                    <p class="text-xs text-blue-500">Complete overnight shift</p>
                <?php elseif ($is_cross_midnight && $display_time_out): ?>
                    <p class="text-xs text-blue-600 font-medium">
                        <i class="fas fa-calendar mr-1"></i>Next Day Time Out
                    </p>
                <?php elseif ($is_overnight_shift && $display_time_out): ?>
                    <p class="text-xs text-blue-500">Today</p>
                <?php elseif ($is_overnight_shift): ?>
                    <p class="text-xs text-blue-500">Complete shift when finished</p>
                <?php elseif ($is_incomplete_shift): ?>
                    <p class="text-xs text-red-500">Auto-marked after 14hrs</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Working Duration -->
    <?php if ($workingDuration && !$is_incomplete_shift && !$has_incomplete_previous_shift): ?>
    <div class="mb-4 p-3 <?= $is_cross_midnight ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50' ?> rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-sm <?= $is_cross_midnight ? 'text-blue-700' : 'text-gray-600' ?>">
                Working Duration:
                <?php if ($is_cross_midnight): ?>
                    <span class="text-xs text-blue-600 ml-1">
                        <i class="fas fa-moon"></i> Cross-Midnight Shift
                    </span>
                <?php endif; ?>
            </span>
            <span class="font-semibold <?= $is_cross_midnight ? 'text-blue-800' : 'text-gray-800' ?>"><?= $workingDuration ?></span>
        </div>
        <?php if ($is_cross_midnight): ?>
        <div class="mt-2 text-xs text-blue-600">
            <i class="fas fa-info-circle mr-1"></i>
            From <?= date('M j', strtotime($original_log_date)) ?> to 
            <?php if ($log_out_date): ?>
                <?= date('M j', strtotime($log_out_date)) ?>
            <?php else: ?>
                <?= date('M j', strtotime($original_log_date . ' +1 day')) ?>
            <?php endif; ?>
            (Time out occurred on the following day)
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Action Button -->
    <form method="POST" action="time_log_handler.php" class="mt-6">
        <?= csrf_token_field() ?>
        <input type="hidden" name="original_log_date" value="<?= $original_log_date ?>">
        <input type="hidden" name="is_overnight" value="<?= $is_overnight_shift ? '1' : '0' ?>">
        <input type="hidden" name="is_incomplete" value="<?= $is_incomplete_shift ? '1' : '0' ?>">
        <input type="hidden" name="has_incomplete_previous" value="<?= $has_incomplete_previous_shift ? '1' : '0' ?>">
        
        <?php if ($can_time_in): ?>
            <button type="button" onclick="showConfirmationModal('time_in')"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                <?php if ($is_incomplete_shift): ?>
                    Start New Shift (Reset)
                <?php elseif (isset($show_overnight_complete_message)): ?>
                    Start New Shift
                <?php else: ?>
                    Log Time In
                <?php endif; ?>
            </button>
        <?php elseif ($can_time_out): ?>
            <button type="button" onclick="showConfirmationModal('time_out')"
                class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                <?php if ($has_incomplete_previous_shift && $shift_status === 'incomplete'): ?>
                    Complete Incomplete Shift
                <?php elseif ($has_incomplete_previous_shift && $is_overnight_shift): ?>
                    Complete Overnight Shift
                <?php elseif ($is_incomplete_shift): ?>
                    Complete Shift (Time Out)
                <?php elseif ($is_overnight_shift): ?>
                    Complete Overnight Shift
                <?php else: ?>
                    Log Time Out
                <?php endif; ?>
            </button>
        <?php elseif ($has_incomplete_previous_shift): ?>
            <button type="button" onclick="showConfirmationModal('time_out')"
                class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                <i class="fas fa-clock mr-2"></i>
                <?php if ($shift_status === 'incomplete'): ?>
                    Complete Incomplete Shift
                <?php else: ?>
                    Complete Previous Shift
                <?php endif; ?>
            </button>
        <?php else: ?>
            <button type="button" disabled
                class="w-full bg-gray-300 text-white font-semibold py-3 px-4 rounded-xl cursor-not-allowed">
                <?php if ($hours_until_next_shift > 0): ?>
                    Next Shift Available in <?= number_format($hours_until_next_shift, 1) ?>h
                <?php elseif ($is_incomplete_shift): ?>
                    Incomplete Shift - Ready to Reset
                <?php else: ?>
                    <?= $shift_manually_completed ? 'Shift Confirmed Complete' : 'Shift Complete' ?>
                <?php endif; ?>
            </button>
        <?php endif; ?>
    </form>

    <div class="mt-4 text-center">
        <a href="test.php"
        onclick="return confirm('Are you requesting a time adjustment because you forgot to time in?')"
        class="text-sm text-blue-600 hover:underline">
            Request Time Adjustment
        </a>
    </div>
</div>

<div id="confirmTimeModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-sm text-center border border-gray-200">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="confirmTimeTitle">Confirm Action</h2>
        <p class="text-gray-600 mb-6" id="confirmTimeMessage">Are you sure you want to proceed?</p>
        <form method="POST" action="time_log_handler.php" id="timeLogForm">
            <?= csrf_token_field() ?>
            <input type="hidden" name="action" id="timeLogAction">
            <input type="hidden" name="original_log_date" value="<?= $original_log_date ?>">
            <input type="hidden" name="is_overnight" value="<?= $is_overnight_shift ? '1' : '0' ?>">
            <input type="hidden" name="has_incomplete_previous" value="<?= $has_incomplete_previous_shift ? '1' : '0' ?>">
            <div class="flex justify-center gap-4">
                <button type="button" onclick="hideConfirmationModal()"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium px-4 py-2 rounded">
                    Cancel
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2 rounded">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showConfirmationModal(actionType) {
    const modal = document.getElementById('confirmTimeModal');
    const title = document.getElementById('confirmTimeTitle');
    const message = document.getElementById('confirmTimeMessage');
    const actionInput = document.getElementById('timeLogAction');
    const isOvernight = <?= $is_overnight_shift ? 'true' : 'false' ?>;
    const isIncomplete = <?= $is_incomplete_shift ? 'true' : 'false' ?>;
    const hasIncompletePrevious = <?= $has_incomplete_previous_shift ? 'true' : 'false' ?>;

    if (actionType === 'time_in') {
        title.textContent = 'Confirm Time In';
        if (isIncomplete) {
            message.textContent = 'This will start a new shift and reset your incomplete shift. Are you sure?';
        } else {
            message.textContent = 'Are you sure you want to log your Time In for today?';
        }
        actionInput.name = 'time_in';
    } else if (actionType === 'time_out') {
        if (hasIncompletePrevious) {
            <?php if ($shift_status === 'incomplete'): ?>
            title.textContent = 'Complete Incomplete Shift';
            message.textContent = 'This shift has been running for 14+ hours. Are you sure you want to complete it from <?= date("F j, Y", strtotime($incomplete_shift_date ?? $original_log_date)) ?>?';
            <?php else: ?>
            title.textContent = 'Complete Overnight Shift';
            message.textContent = 'Are you sure you want to complete your overnight shift from <?= date("F j, Y", strtotime($incomplete_shift_date ?? $original_log_date)) ?>?';
            <?php endif; ?>
        } else {
            title.textContent = isOvernight ? 'Complete Overnight Shift' : 'Confirm Time Out';
            message.textContent = isOvernight ? 
                'Are you sure you want to complete your overnight shift?' : 
                'Are you sure you want to log your Time Out for today?';
        }
        actionInput.name = 'time_out';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideConfirmationModal() {
    const modal = document.getElementById('confirmTimeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

