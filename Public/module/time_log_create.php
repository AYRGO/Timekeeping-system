<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila'); // Ensure timezone is correctly set

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

$current_date = date("Y-m-d");

// Fetch today's time log (so we know if they already timed in or out)
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ?");
$stmt->execute([$employee_id, $current_date]);
$time_log = $stmt->fetch();

$time_in = null;
$time_out = null;

if ($time_log) {
    $time_in = $time_log['time_in'];
    $time_out = $time_log['time_out'];
}

// Get employee's current applicable work schedule (latest effective date)
$schedule_stmt = $pdo->prepare("
    SELECT ws.time_in AS start_time, ws.time_out AS end_time 
    FROM employee_work_schedule ews
    JOIN work_schedules ws ON ews.work_schedule_id = ws.id
    WHERE ews.employee_id = ? 
    ORDER BY ews.effective_date DESC 
    LIMIT 1
");
$schedule_stmt->execute([$employee_id]);
$work_schedule = $schedule_stmt->fetch();

$work_start_time = $work_schedule['start_time'] ?? null;
$work_end_time   = $work_schedule['end_time'] ?? null;

// Step 1: Check for schedule exception
$exception_stmt = $pdo->prepare("SELECT * FROM schedule_exceptions WHERE employee_id = ? AND exception_date = ?");
$exception_stmt->execute([$employee_id, $current_date]);
$schedule_exception = $exception_stmt->fetch();

if ($schedule_exception) {
    $work_start_time = $schedule_exception['start_time'];
    $work_end_time = $schedule_exception['end_time'];
}

// Step 2: Check for approved overtime request
$overtime_stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? AND ot_date = ? AND status = 'approved' LIMIT 1");
$overtime_stmt->execute([$employee_id, $current_date]);
$overtime = $overtime_stmt->fetch();

if ($overtime && $work_end_time) {
    // Extend end time by overtime hours
    $work_end_time = date("H:i", strtotime($work_end_time) + ($overtime['hours'] * 3600));
}

// Determine if employee can log time in or out
$can_time_in = !$time_in;
$can_time_out = $time_in && !$time_out;

// Handle Time Log Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_time = date("H:i:s");
    $current_timestamp = strtotime($current_time);
    $start_timestamp = $work_start_time ? strtotime($work_start_time) : null;
    $end_timestamp = $work_end_time ? strtotime($work_end_time) : null;

    // Handle Time-In
    if (isset($_POST['time_in']) && $can_time_in) {
        $is_late_in = ($start_timestamp && $current_timestamp > $start_timestamp) ? 1 : 0;

        $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out, is_late_in, is_early_out) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$employee_id, $current_date, $current_time, null, $is_late_in, 0]);

        header("Location: time_log_create.php");
        exit;
    }

    // Handle Time-Out
    if (isset($_POST['time_out']) && $can_time_out) {
        $is_early_out = ($end_timestamp && $current_timestamp < $end_timestamp) ? 1 : 0;

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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function updateClock() {
      const clock = document.getElementById("clock");
      const now = new Date();
      let hours = now.getHours();
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      const ampm = hours >= 12 ? 'PM' : 'AM';

      hours = hours % 12;
      hours = hours ? hours : 12;
      const formattedHours = String(hours).padStart(2, '0');

      clock.textContent = `${formattedHours}:${minutes}:${seconds} ${ampm}`;
    }

    setInterval(updateClock, 1000);
    window.onload = updateClock;
  </script>
</head>
<body class="bg-[#A3F7B5] min-h-screen flex flex-col items-center justify-start px-4 py-8 sm:py-12">

  <div class="w-full max-w-3xl space-y-10">

    <!-- Manual Time Log Box -->
    <div class="bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8 space-y-6">
      <div id="clock" class="text-center text-3xl sm:text-4xl font-bold text-[#2F9C95]"></div>
      <h1 class="text-2xl sm:text-3xl font-bold text-center text-[#2F9C95]">Manual Time Log</h1>

      <form method="POST" class="space-y-5">
        <?php if (!$time_in): ?>
          <?php if ($can_time_in): ?>
            <button 
              type="submit" 
              name="time_in" 
              class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-4 rounded-xl transition duration-200 text-lg sm:text-xl"
            >
              Log Time In
            </button>
          <?php else: ?>
            <button 
              type="button" 
              disabled 
              class="w-full bg-gray-300 text-gray-600 font-medium py-4 rounded-xl cursor-not-allowed text-base sm:text-lg"
            >
              Cannot Log In - Outside Scheduled Hours
            </button>
          <?php endif; ?>
        <?php else: ?>
          <button 
            type="button" 
            disabled 
            class="w-full bg-gray-300 text-gray-600 font-medium py-4 rounded-xl cursor-not-allowed text-base sm:text-lg"
          >
            Already Logged In
          </button>
        <?php endif; ?>

        <?php if ($time_in && !$time_out): ?>
          <button 
            type="submit" 
            name="time_out" 
            class="w-full bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-4 rounded-xl transition duration-200 text-base sm:text-lg"
          >
            Log Time Out
          </button>
        <?php elseif ($time_out): ?>
          <button 
            type="button" 
            disabled 
            class="w-full bg-gray-300 text-gray-600 font-medium py-4 rounded-xl cursor-not-allowed text-base sm:text-lg"
          >
            Already Logged Out
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- Action Links Box -->
    <div class="bg-white shadow-xl rounded-2xl border-t-8 border-[#40C9A2] p-6 sm:p-8">
      <h2 class="text-2xl sm:text-3xl font-bold text-[#2F9C95] text-center mb-6">Requests & Navigation</h2>

      <div class="space-y-4 text-base sm:text-lg">
        <a href="schedule_change_request_create.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Schedule Change Request
        </a>
        <a href="schedule_exception_request_create.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Schedule Exception Request
        </a>
        <a href="rest_day_overtime_create.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Rest Day Overtime Request
        </a>
        <a href="overtime_request_create.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Overtime Request
        </a>
        <a href="leave_request_create.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Leave Request
        </a>
        <a href="../employee/logout.php" class="block w-full text-center bg-[#40C9A2] hover:bg-[#2F9C95] text-white font-semibold py-3 rounded-xl transition duration-200">
          Logout
        </a>
      </div>
    </div>

  </div>

</body>
</html>
