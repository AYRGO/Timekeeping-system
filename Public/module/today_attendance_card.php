<?php

date_default_timezone_set('Asia/Manila');

// Make sure user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo "<script>alert('User not logged in.'); location.href='../employee/login.php';</script>";
    exit;
}

// Prepare today's time in/out

// Get today's date
$today = date('Y-m-d');

// Fetch today's time log
include_once('../config/db.php');
$stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
$stmt->execute([$employee_id, $today]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);
$time_in = $log['time_in'] ?? null;
$time_out = $log['time_out'] ?? null;

// Check for overnight shift - only if no time_in today AND there's an open log from yesterday or earlier
if (!$time_in) {
    $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
        WHERE employee_id = ? AND time_out IS NULL AND log_date < ?
        ORDER BY log_date DESC, id DESC 
        LIMIT 1");
    $overnightStmt->execute([$employee_id, $today]);
    $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($overnightLog) {
        $time_in = $overnightLog['time_in'];
        $time_out = $overnightLog['time_out']; // This will be null for open logs
        // Note: We don't update $today here to keep the display showing today's date
    }
}

// Check for approved time adjustment for today
$adjStmt = $pdo->prepare("SELECT requested_time_in, requested_time_out FROM post_time_adjustment_requests WHERE employee_id = ? AND log_date = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
$adjStmt->execute([$employee_id, $today]);
$adj = $adjStmt->fetch(PDO::FETCH_ASSOC);
if ($adj) {
    // Use adjusted time if available
    $time_in = $adj['requested_time_in'] ?? $time_in;
    $time_out = $adj['requested_time_out'] ?? $time_out;
}

$time_in = $time_in ? date('H:i:s', strtotime($time_in)) : null;
$time_out = $time_out ? date('H:i:s', strtotime($time_out)) : null;
$workingDuration = '';
if ($time_in && $time_out) {
    $start = new DateTime($time_in);
    $end = new DateTime($time_out);
    $diff = $start->diff($end);
    $hours = $diff->h + ($diff->i / 60);
    $hours -= 1; // Deduct 1 hour for lunch break
    if ($hours < 0) $hours = 0; // Prevent negative hours
    $workingDuration = number_format($hours, 2) . ' hours';
}
?>

<div class="bg-white rounded-2xl shadow-lg p-6 w-full md:w-2/3 mx-auto border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-user-clock mr-3 text-green-500 bg-green-100 p-2 rounded-full"></i>
            Today's Time Log
            <?php if ($time_in && !$time_out && isset($overnightLog)): ?>
                <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    <i class="fas fa-moon mr-1"></i>Overnight Shift
                </span>
            <?php endif; ?>
        </h3>
        <span id="dashboardClock" class="text-sm font-mono text-gray-500 tracking-wide">--:--:-- --</span>
    </div>

    <!-- Time Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
        <!-- Time In -->
        <div class="flex items-center p-5 rounded-xl border border-green-200 bg-green-50 shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-green-300 to-green-500 text-white mr-4">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="text-2xl font-extrabold text-gray-900"><?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?></p>
            </div>
        </div>

        <!-- Time Out -->
        <div class="flex items-center p-5 rounded-xl border border-yellow-200 bg-yellow-50 shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-yellow-300 to-yellow-500 text-white mr-4">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="text-2xl font-extrabold text-gray-900"><?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?></p>
            </div>
        </div>
    </div>

    <!-- Action Button -->
    <form method="POST" class="mt-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
  <?php if (!$time_in): ?>
    <button type="button" onclick="showConfirmationModal('time_in')"
        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
        Log Time In
    </button>
<?php elseif ($time_in && !$time_out): ?>
    <button type="button" onclick="showConfirmationModal('time_out')"
        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
        Log Time Out
    </button>
<?php else: ?>
    <button type="button" disabled
        class="w-full bg-gray-300 text-white font-semibold py-3 px-4 rounded-xl cursor-not-allowed">
        Already Logged
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

<div id="confirmTimeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-sm text-center border border-gray-200">
        <h2 class="text-xl font-bold text-gray-800 mb-4" id="confirmTimeTitle">Confirm Action</h2>
        <p class="text-gray-600 mb-6" id="confirmTimeMessage">Are you sure you want to proceed?</p>
        <form method="POST" id="timeLogForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" id="timeLogAction">
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

    if (actionType === 'time_in') {
        title.textContent = 'Confirm Time In';
        message.textContent = 'Are you sure you want to log your Time In for today?';
        actionInput.name = 'time_in';
    } else if (actionType === 'time_out') {
        title.textContent = 'Confirm Time Out';
        message.textContent = 'Are you sure you want to log your Time Out for today?';
        actionInput.name = 'time_out';
    }

    modal.classList.remove('hidden');
}

function hideConfirmationModal() {
    const modal = document.getElementById('confirmTimeModal');
    modal.classList.add('hidden');
}
</script>

