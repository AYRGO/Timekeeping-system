<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila'); // Ensure timezone is correctly set

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Fetch employee user details
$user_stmt = $pdo->prepare("SELECT fname, lname, email, contact, position FROM employees WHERE id = ?");
$user_stmt->execute([$employee_id]);
$user = $user_stmt->fetch();

$fname = $user['fname'] ?? '';
$lname = $user['lname'] ?? '';
$email = $user['email'] ?? '';
$contact = $user['contact'] ?? '';
$position = $user['position'] ?? '';

$current_date = date("Y-m-d");

// Fetch today's time log
$stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ?");
$stmt->execute([$employee_id, $current_date]);
$time_log = $stmt->fetch();

$time_in = null;
$time_out = null;

if ($time_log) {
    $time_in = $time_log['time_in'];
    $time_out = $time_log['time_out'];
}

// Step 1: Check for schedule exception FIRST
$exception_stmt = $pdo->prepare("SELECT * FROM schedule_exceptions WHERE employee_id = ? AND start_date = ?");
$exception_stmt->execute([$employee_id, $current_date]);
$schedule_exception = $exception_stmt->fetch();

if ($schedule_exception) {
    // Use exception schedule
    $work_start_time = $schedule_exception['start_time'];
    $work_end_time = $schedule_exception['end_time'];
} else {
    // No exception, fall back to default work schedule
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
}

// Step 2: Check for approved overtime request
$overtime_stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? AND ot_date = ? AND status = 'approved' LIMIT 1");
$overtime_stmt->execute([$employee_id, $current_date]);
$overtime = $overtime_stmt->fetch();

if ($overtime && $overtime['expected_time_out']) {
    $work_end_time = $overtime['expected_time_out'];
}

// Step 3: Check for approved Rest Day Overtime
$rest_day_ot_stmt = $pdo->prepare("
    SELECT * FROM rest_day_overtime_requests 
    WHERE employee_id = ? AND rest_day_date = ? AND status = 'approved' LIMIT 1
");
$rest_day_ot_stmt->execute([$employee_id, $current_date]);
$rest_day_ot = $rest_day_ot_stmt->fetch();

if ($rest_day_ot) {
    $work_start_time = $rest_day_ot['expected_time_in'];
    $work_end_time = $rest_day_ot['expected_time_out'];
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
<html lang="en" class="scroll-smooth" >
<head>
  <meta charset="UTF-8" />
  <title>Manual Time Log</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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

    function confirmLog(form) {
      const timeInBtn = form.querySelector('button[name="time_in"]');
      const timeOutBtn = form.querySelector('button[name="time_out"]');

      if (document.activeElement === timeInBtn) {
        return confirm("Are you sure you want to log your Time In?");
      }

      if (document.activeElement === timeOutBtn) {
        return confirm("Are you sure you want to log your Time Out?");
      }

      return true;
    }
  </script>
</head>
<body class="bg-gray-50 flex justify-center items-start min-h-screen py-10 px-4">

  <main class="bg-white max-w-md w-full rounded-2xl shadow-lg p-8 flex flex-col gap-8">

    <!-- User Info -->
    <section aria-label="User details" class="bg-gray-100 p-6 rounded-xl shadow-inner">
      <h3 class="text-xl font-semibold text-gray-800 mb-2">
        <?php echo htmlspecialchars($fname . ' ' . $lname); ?>
      </h3>
      <p class="text-gray-600"><span class="font-medium">Position:</span> <?php echo htmlspecialchars($position); ?></p>
      <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($email); ?></p>
      <p class="text-gray-600"><span class="font-medium">Contact:</span> <?php echo htmlspecialchars($contact); ?></p>
    </section>

    <!-- Clock -->
    <h2 id="clock" aria-live="polite" aria-atomic="true" class="font-mono text-4xl font-bold text-blue-700 text-center select-none">
      --:--:--
    </h2>

    <h1 class="text-2xl font-bold text-gray-900 text-center">Manual Time Log</h1>

    <form method="POST" onsubmit="return confirmLog(this);" aria-label="Time log form" class="flex flex-col gap-4">
      <?php if (!$time_in): ?>
        <?php if ($can_time_in): ?>
          <button type="submit" name="time_in"
            class="w-full py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 transition focus:outline-none focus:ring-4 focus:ring-blue-300">
            Log Time In
          </button>
        <?php else: ?>
          <button type="button" disabled
            class="w-full py-3 rounded-lg bg-gray-400 text-gray-200 font-semibold cursor-not-allowed" aria-disabled="true">
            Cannot Log In - Already Logged
          </button>
        <?php endif; ?>
      <?php else: ?>
        <button type="button" disabled
          class="w-full py-3 rounded-lg bg-gray-400 text-gray-200 font-semibold cursor-not-allowed" aria-disabled="true">
          Already Logged In
        </button>
      <?php endif; ?>

      <?php if ($time_in && !$time_out): ?>
        <button type="submit" name="time_out"
          class="w-full py-3 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 transition focus:outline-none focus:ring-4 focus:ring-green-300">
          Log Time Out
        </button>
      <?php elseif ($time_out): ?>
        <button type="button" disabled
          class="w-full py-3 rounded-lg bg-gray-400 text-gray-200 font-semibold cursor-not-allowed" aria-disabled="true">
          Already Logged Out
        </button>
      <?php endif; ?>
    </form>

    <section aria-label="Requests and navigation" class="pt-4 border-t border-gray-200">
      <!-- <h2 class="text-lg font-semibold text-gray-900 mb-3">Requests &amp; Navigation</h2> -->
      <ul class="flex flex-wrap gap-4 justify-center">
        <!-- <li>
          <a href="schedule_exception_request_create.php"
            class="inline-block px-5 py-2 rounded-lg border border-blue-600 text-blue-600 font-semibold hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-blue-400">
            Schedule Change Request
          </a>
        </li> -->
        <li>
          <a href="../employee/logout.php"
            class="inline-block px-5 py-2 rounded-lg border border-red-600 text-red-600 font-semibold hover:bg-red-50 transition focus:outline-none focus:ring-2 focus:ring-red-400">
            Logout
          </a>
        </li>
      </ul>
    </section>

  </main>

</body>
</html>
