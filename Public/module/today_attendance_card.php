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
$is_overnight_shift = false;
$original_log_date = $today;
$shift_status = null;

// Check if this is a reset request (cache busting parameter)
$is_reset_request = isset($_GET['reset']);

// First, check and mark incomplete shifts (time-ins older than 12 hours without time-out)
// Only process recent shifts (within last 2 days) to prevent old data issues
$incompleteStmt = $pdo->prepare("
    UPDATE time_logs 
    SET time_out = 'INC', status = 'incomplete' 
    WHERE employee_id = ? 
    AND time_out IS NULL 
    AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) >= 12
    AND status != 'incomplete'
    AND log_date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
");
$incompleteStmt->execute([$employee_id]);

// Also check if any previously marked incomplete shifts should be reactivated (within 12 hours)
// Only check recent shifts to prevent old data conflicts
$reactivateStmt = $pdo->prepare("
    UPDATE time_logs 
    SET time_out = NULL, status = 'active' 
    WHERE employee_id = ? 
    AND status = 'incomplete' 
    AND time_out = 'INC'
    AND TIMESTAMPDIFF(HOUR, CONCAT(log_date, ' ', time_in), NOW()) < 12
    AND log_date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
");
$reactivateStmt->execute([$employee_id]);

// Log any incomplete shifts that were just marked
if ($incompleteStmt->rowCount() > 0) {
    error_log("Marked " . $incompleteStmt->rowCount() . " incomplete shifts for employee $employee_id");
}

// Log any reactivated shifts
if ($reactivateStmt->rowCount() > 0) {
    error_log("Reactivated " . $reactivateStmt->rowCount() . " shifts within 12h window for employee $employee_id");
}

// Auto-reset display: Don't show incomplete shifts older than 12 hours (keep DB record but reset interface)
// This allows overnight shifts (like 10pm-6am) to complete normally within 12 hours

// Priority 1: Check for today's regular time log (exclude incomplete ones)
$stmt = $pdo->prepare("SELECT time_in, time_out, log_date, status FROM time_logs WHERE employee_id = ? AND log_date = ? AND status != 'incomplete' ORDER BY id DESC LIMIT 1");
$stmt->execute([$employee_id, $today]);
$todayLog = $stmt->fetch(PDO::FETCH_ASSOC);

if ($todayLog) {
    // There's a regular log for today
    $time_in = $todayLog['time_in'];
    $time_out = $todayLog['time_out'];
    $original_log_date = $todayLog['log_date'];
    $shift_status = $todayLog['status'] ?? 'active';
    error_log("Found today's log for employee $employee_id: time_in=$time_in, time_out=$time_out, status=$shift_status");
} else {
    // Priority 2: Check for overnight shift from YESTERDAY ONLY (exclude incomplete ones)
    // Only check yesterday to prevent old incomplete shifts from showing
    $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date, status FROM time_logs 
        WHERE employee_id = ? AND time_out IS NULL AND log_date = ? AND status != 'incomplete'
        ORDER BY id DESC 
        LIMIT 1");
    $overnightStmt->execute([$employee_id, $yesterday]);
    $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($overnightLog) {
        // Found an open overnight shift
        $time_in = $overnightLog['time_in'];
        $time_out = null; // Still open
        $is_overnight_shift = true;
        $original_log_date = $overnightLog['log_date'];
        $shift_status = $overnightLog['status'] ?? 'active';
    } else {
        // Priority 3: Only show incomplete shift if no active shifts exist AND not a reset request
        if (!$is_reset_request) {
            $activeShiftCheck = $pdo->prepare("SELECT COUNT(*) FROM time_logs WHERE employee_id = ? AND status = 'active' AND time_out IS NULL");
            $activeShiftCheck->execute([$employee_id]);
            $hasActiveShift = $activeShiftCheck->fetchColumn() > 0;
            
            if (!$hasActiveShift) {
                // Check if there's a RECENT incomplete shift to show (within last 2 days only)
                $incompleteStmt = $pdo->prepare("SELECT time_in, time_out, log_date, status FROM time_logs 
                    WHERE employee_id = ? AND status = 'incomplete' AND log_date >= DATE_SUB(CURDATE(), INTERVAL 2 DAY)
                    ORDER BY log_date DESC, id DESC LIMIT 1");
                $incompleteStmt->execute([$employee_id]);
                $incompleteLog = $incompleteStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($incompleteLog) {
                    // Check if 12+ hours have passed since time-in - if so, auto-reset display (keep DB record)
                    $timeInTimestamp = strtotime($incompleteLog['log_date'] . ' ' . $incompleteLog['time_in']);
                    $hoursSinceTimeIn = (time() - $timeInTimestamp) / 3600;
                    
                    if ($hoursSinceTimeIn >= 12) {
                        // Auto-reset: 12+ hours passed, start fresh interface (DB record preserved)
                        error_log("Auto-reset: 12+ hours passed since time-in for employee $employee_id, starting fresh interface");
                        // Leave all values as null for fresh start
                    } else {
                        // Show incomplete shift if within 12 hours (allows overnight shifts to complete)
                        $time_in = $incompleteLog['time_in'];
                        $time_out = $incompleteLog['time_out'];
                        $original_log_date = $incompleteLog['log_date'];
                        $shift_status = 'incomplete';
                        error_log("Showing incomplete shift within 12h window: " . number_format($hoursSinceTimeIn, 1) . " hours since time-in");
                    }
                }
            }
        }
        // If it's a reset request or no incomplete shifts, leave everything as null for fresh start
    }
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
}

// Format times for display
$display_time_in = $time_in ? date('H:i:s', strtotime($time_in)) : null;
$display_time_out = $time_out ? date('H:i:s', strtotime($time_out)) : null;

// Calculate working duration
$workingDuration = '';
if ($display_time_in && $display_time_out) {
    $start = new DateTime($time_in);
    $end = new DateTime($time_out);
    
    // Handle overnight calculation
    if ($is_overnight_shift || ($end < $start)) {
        $end->add(new DateInterval('P1D')); // Add one day for overnight shifts
    }
    
    $diff = $start->diff($end);
    $totalHours = ($diff->days * 24) + $diff->h + ($diff->i / 60);
    
    // Only deduct 1 hour for lunch break if total hours is 8 or above
    if ($totalHours >= 8) {
        $totalHours -= 1; // Deduct 1 hour for lunch break
    }
    
    if ($totalHours < 0) $totalHours = 0;
    $workingDuration = number_format($totalHours, 2) . ' hours';
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
$can_time_in = !$time_in && !$is_incomplete_shift; // Only allow time-in if no active time-in exists
$can_time_out = $time_in && !$time_out; // Allow time-out if there's a time-in but no time-out (including incomplete)
$shift_complete = $time_in && $time_out && !$is_incomplete_shift;

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
            <?php if ($is_overnight_shift): ?>
                Overnight Shift Status
            <?php elseif ($is_incomplete_shift): ?>
                Incomplete Shift - Reset Available
            <?php else: ?>
                Today's Time Log
            <?php endif; ?>
            <?php if ($is_overnight_shift): ?>
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

    <!-- Incomplete Shift Warning -->
    <?php if ($is_incomplete_shift): ?>
    <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-400 rounded">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">
                    ⚠️ Your previous shift was marked as incomplete due to missing time-out (12+ hours passed). 
                    You can now start a new shift.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overnight Shift Warning -->
    <?php if ($is_overnight_shift): ?>
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
        <div class="flex items-center p-5 rounded-xl border <?= $is_incomplete_shift ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' ?> shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr <?= $is_incomplete_shift ? 'from-red-300 to-red-500' : 'from-green-300 to-green-500' ?> text-white mr-4">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="text-2xl font-extrabold text-gray-900">
                    <?= $display_time_in ? date("h:i A", strtotime($display_time_in)) : '—'; ?>
                </p>
                <?php if ($is_overnight_shift && $display_time_in): ?>
                    <p class="text-xs text-gray-500"><?= date('M j', strtotime($original_log_date)) ?></p>
                <?php elseif ($is_incomplete_shift && $display_time_in): ?>
                    <p class="text-xs text-red-500">Incomplete Shift</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Time Out -->
        <div class="flex items-center p-5 rounded-xl border <?= $is_incomplete_shift ? 'border-red-200 bg-red-50' : 'border-yellow-200 bg-yellow-50' ?> shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr <?= $is_incomplete_shift ? 'from-red-300 to-red-500' : 'from-yellow-300 to-yellow-500' ?> text-white mr-4">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="text-2xl font-extrabold text-gray-900">
                    <?php if ($is_incomplete_shift): ?>
                        <span class="text-red-600">INC</span>
                    <?php else: ?>
                        <?= $display_time_out ? date("h:i A", strtotime($display_time_out)) : '—'; ?>
                    <?php endif; ?>
                </p>
                <?php if ($is_overnight_shift && $display_time_out): ?>
                    <p class="text-xs text-gray-500">Today</p>
                <?php elseif ($is_incomplete_shift): ?>
                    <p class="text-xs text-red-500">Auto-marked after 12hrs</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Working Duration -->
    <?php if ($workingDuration && !$is_incomplete_shift): ?>
    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Working Duration:</span>
            <span class="font-semibold text-gray-800"><?= $workingDuration ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Button -->
    <form method="POST" action="time_log_handler.php" class="mt-6">
        <?= csrf_token_field() ?>
        <input type="hidden" name="original_log_date" value="<?= $original_log_date ?>">
        <input type="hidden" name="is_overnight" value="<?= $is_overnight_shift ? '1' : '0' ?>">
        <input type="hidden" name="is_incomplete" value="<?= $is_incomplete_shift ? '1' : '0' ?>">
        
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
                <?php if ($is_incomplete_shift): ?>
                    Complete Shift (Time Out)
                <?php elseif ($is_overnight_shift): ?>
                    Complete Overnight Shift
                <?php else: ?>
                    Log Time Out
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

    if (actionType === 'time_in') {
        title.textContent = 'Confirm Time In';
        if (isIncomplete) {
            message.textContent = 'This will start a new shift and reset your incomplete shift. Are you sure?';
        } else {
            message.textContent = 'Are you sure you want to log your Time In for today?';
        }
        actionInput.name = 'time_in';
    } else if (actionType === 'time_out') {
        title.textContent = isOvernight ? 'Complete Overnight Shift' : 'Confirm Time Out';
        message.textContent = isOvernight ? 
            'Are you sure you want to complete your overnight shift?' : 
            'Are you sure you want to log your Time Out for today?';
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

