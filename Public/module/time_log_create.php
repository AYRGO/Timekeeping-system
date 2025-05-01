<?php
session_start();
include('../config/db.php');

$employee_id = $_SESSION['employee']['id'];
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

$current_time = date("H:i");
$current_date = date("Y-m-d");

// Fetch today's time log
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = CURDATE() LIMIT 1");
$stmt->execute([$employee_id]);
$time_log = $stmt->fetch();

$time_in = $time_log['time_in'] ?? null;
$time_out = $time_log['time_out'] ?? null;

// Step 1: Check for schedule exception
$exception_stmt = $pdo->prepare("SELECT * FROM schedule_exceptions WHERE employee_id = ? AND exception_date = ?");
$exception_stmt->execute([$employee_id, $current_date]);
$schedule_exception = $exception_stmt->fetch();

if ($schedule_exception) {
    $work_start_time = $schedule_exception['start_time'];
    $work_end_time = $schedule_exception['end_time'];
} else {
    // Step 2: Fallback to regular schedule
    $schedule_stmt = $pdo->prepare("SELECT * FROM employee_work_schedule WHERE employee_id = ? LIMIT 1");
    $schedule_stmt->execute([$employee_id]);
    $work_schedule = $schedule_stmt->fetch();
    $work_start_time = $work_schedule['start_time'] ?? null;
    $work_end_time   = $work_schedule['end_time'] ?? null;

    // Step 3: Check for approved overtime request
    $overtime_stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? AND ot_date = ? AND status = 'approved' LIMIT 1");
    $overtime_stmt->execute([$employee_id, $current_date]);
    $overtime = $overtime_stmt->fetch();

    if ($overtime && isset($work_end_time)) {
        // Extend end time by overtime hours
        $end_plus = date("H:i", strtotime($work_end_time) + ($overtime['hours'] * 3600));
        $work_end_time = $end_plus;
    }
}

$can_time_in = !$time_in;
$can_time_out = $time_in && !$time_out;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['time_in']) && $can_time_in) {
        $is_late_in = ($work_start_time && $current_time > $work_start_time) ? 1 : 0;
        $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out, is_late_in) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$employee_id, $current_date, $current_time, null, $is_late_in]);

        header("Location: time_log_create.php");
        exit;
    }

    if (isset($_POST['time_out']) && $can_time_out) {
        $is_early_out = ($work_end_time && $current_time < $work_end_time) ? 1 : 0;
        $update = $pdo->prepare("UPDATE time_logs SET time_out = ?, is_early_out = ? WHERE employee_id = ? AND log_date = ?");
        $update->execute([$current_time, $is_early_out, $employee_id, $current_date]);

        header("Location: time_log_create.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manual Time Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function updateClock() {
            const clock = document.getElementById("clock");
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clock.textContent = `${hours}:${minutes}:${seconds}`;
        }

        setInterval(updateClock, 1000);
        window.onload = updateClock;
    </script>
</head>
<body class="bg-[#A3F7B5] min-h-screen overflow-y-auto flex flex-col items-center justify-start px-4 py-6">
    <div class="w-full max-w-sm sm:max-w-md md:max-w-lg lg:max-w-xl xl:max-w-2xl space-y-4 sm:space-y-6">

        <!-- Manual Time Log Box -->
        <div class="bg-white shadow-lg rounded-2xl p-4 sm:p-6 w-full border-t-8 border-[#40C9A2]">
            <div class="text-center text-xl sm:text-2xl font-semibold text-[#2F9C95] mb-4 sm:mb-6" id="clock"></div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#2F9C95] mb-4 sm:mb-6 text-center">Manual Time Log</h1>

            <form method="POST" class="space-y-5 text-lg">
                <?php if (!$time_in): ?>
                    <?php if ($can_time_in): ?>
                        <button 
                            type="submit" 
                            name="time_in" 
                            class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200 text-lg"
                        >
                            Log Time In
                        </button>
                    <?php else: ?>
                        <button 
                            type="button" 
                            disabled 
                            class="w-full bg-gray-300 text-gray-600 font-semibold py-3 px-6 rounded-xl cursor-not-allowed text-lg"
                        >
                            Cannot Log In - Outside Scheduled Hours
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button 
                        type="button" 
                        disabled 
                        class="w-full bg-gray-300 text-gray-600 font-semibold py-3 px-6 rounded-xl cursor-not-allowed text-lg"
                    >
                        Already Logged In
                    </button>
                <?php endif; ?>

                <?php if ($time_in && !$time_out): ?>
                    <button 
                        type="submit" 
                        name="time_out" 
                        class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200 text-lg"
                    >
                        Log Time Out
                    </button>
                <?php elseif ($time_out): ?>
                    <button 
                        type="button" 
                        disabled 
                        class="w-full bg-gray-300 text-gray-600 font-semibold py-3 px-6 rounded-xl cursor-not-allowed text-lg"
                    >
                        Already Logged Out
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Action Links Box -->
        <div class="bg-white shadow-lg rounded-2xl p-4 sm:p-6 w-full border-t-8 border-[#40C9A2]">
            <h2 class="text-xl sm:text-2xl font-bold text-[#2F9C95] mb-4 text-center">Requests & Navigation</h2>
            <div class="space-y-3 sm:space-y-4 text-base sm:text-lg">
                
                <a href="schedule-change-request.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Schedule Change Request
                </a>
                <a href="schedule-exception-request.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Schedule Exception Request
                </a>
                <a href="rest-day-overtime.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Rest Day Overtime Request
                </a>
                <a href="overtime-request.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Overtime Request
                </a>
                <a href="leave-request.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Leave Request
                </a>

                <a href="logout.php" class="block text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 px-6 rounded-xl transition duration-200">
                    Logout
                </a>
            </div>
        </div>

    </div>

</body>
</html>
