<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

try {
    $user_stmt = $pdo->prepare("SELECT fname, lname, email, contact, position, company, profile_picture FROM employees WHERE id = ?");
    $user_stmt->execute([$employee_id]);
    $user = $user_stmt->fetch();

    $fname = $user['fname'] ?? '';
    $lname = $user['lname'] ?? '';
    $email = $user['email'] ?? '';
    $contact = $user['contact'] ?? '';
    $position = $user['position'] ?? '';
    $company = $user['company'] ?? '';
    $profile_picture = $user['profile_picture'] ?? null;

    $current_date = date("Y-m-d");

    $stmt = $pdo->prepare("SELECT * FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $current_date]);
    $time_log = $stmt->fetch();

    $time_in = $time_log['time_in'] ?? null;
    $time_out = $time_log['time_out'] ?? null;

    $exception_stmt = $pdo->prepare("SELECT * FROM schedule_exceptions WHERE employee_id = ? AND start_date = ?");
    $exception_stmt->execute([$employee_id, $current_date]);
    $schedule_exception = $exception_stmt->fetch();

    if ($schedule_exception) {
        $work_start_time = $schedule_exception['start_time'];
        $work_end_time = $schedule_exception['end_time'];
    } else {
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

    $overtime_stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE employee_id = ? AND ot_date = ? AND status = 'approved' LIMIT 1");
    $overtime_stmt->execute([$employee_id, $current_date]);
    $overtime = $overtime_stmt->fetch();

    if ($overtime && $overtime['expected_time_out']) {
        $work_end_time = $overtime['expected_time_out'];
    }

    $rest_day_ot_stmt = $pdo->prepare("SELECT * FROM rest_day_overtime_requests WHERE employee_id = ? AND rest_day_date = ? AND status = 'approved' LIMIT 1");
    $rest_day_ot_stmt->execute([$employee_id, $current_date]);
    $rest_day_ot = $rest_day_ot_stmt->fetch();

    if ($rest_day_ot) {
        $work_start_time = $rest_day_ot['expected_time_in'];
        $work_end_time = $rest_day_ot['expected_time_out'];
    }

    $can_time_in = !$time_in;
    $can_time_out = $time_in && !$time_out;

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Invalid CSRF token");
        }

        $current_time = date("H:i:s");
        $current_timestamp = strtotime($current_time);
        $start_timestamp = $work_start_time ? strtotime($work_start_time) : null;
        $end_timestamp = $work_end_time ? strtotime($work_end_time) : null;

        if (isset($_POST['time_in']) && $can_time_in) {
            $is_late_in = ($start_timestamp && $current_timestamp > $start_timestamp) ? 1 : 0;
            $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out, is_late_in, is_early_out) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$employee_id, $current_date, $current_time, null, $is_late_in, 0]);
            header("Location: time_log_create.php");
            exit;
        }

        if (isset($_POST['time_out']) && $can_time_out) {
            $is_early_out = ($end_timestamp && $current_timestamp < $end_timestamp) ? 1 : 0;
            $update = $pdo->prepare("UPDATE time_logs SET time_out = ?, is_early_out = ? WHERE employee_id = ? AND log_date = ?");
            $update->execute([$current_time, $is_early_out, $employee_id, $current_date]);
            header("Location: time_log_create.php");
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Error in time_log_create.php: " . $e->getMessage());
    die("Sorry, something went wrong. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
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
      hours = hours % 12 || 12;
      clock.textContent = `${String(hours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    }
    setInterval(updateClock, 1000);
    window.onload = updateClock;
  </script>
  <style>
    body {
      background: linear-gradient(135deg, #28c197, #00bdd6);
    }
  </style>
</head>
<body class="flex justify-center items-start min-h-screen py-10 px-4 text-white">
  <main class="bg-white text-gray-900 max-w-3xl w-full rounded-2xl shadow-xl p-8 flex flex-col gap-8">
    <section class="flex items-start gap-6 bg-gray-100 p-6 rounded-xl shadow-inner">
      <div class="flex flex-col items-center w-48">
        <?php if (!empty($profile_picture)): ?>
          <img src="../uploads/profile_images/<?php echo htmlspecialchars($profile_picture); ?>" 
               alt="Profile Picture" class="w-24 h-24 rounded-full mb-2 object-cover shadow" />
        <?php else: ?>
          <div class="w-24 h-24 bg-gray-300 rounded-full mb-2 flex items-center justify-center text-4xl text-white font-bold">
            👤
          </div>
        <?php endif; ?>

        <form action="/Timekeeping-system/Public/module/upload_profile.php" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2 items-center">
          <input type="file" name="profile_picture" accept="image/*" required class="text-xs w-full">
          <button type="submit" class="bg-blue-600 text-white px-3 py-1 text-sm rounded hover:bg-blue-700">Upload</button>
        </form>

        <h3 class="text-md font-bold text-center mt-2"><?php echo htmlspecialchars($fname . ' ' . $lname); ?></h3>
      </div>

      <div class="flex-1 grid gap-2">
        <p><span class="font-medium">Position:</span> <?php echo htmlspecialchars($position); ?></p>
        <p><span class="font-medium">Company:</span> <?php echo htmlspecialchars($company); ?></p>
        <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($email); ?></p>
        <p><span class="font-medium">Contact:</span> <?php echo htmlspecialchars($contact); ?></p>
      </div>
    </section>

    <h2 id="clock" class="font-mono text-4xl font-bold text-center text-blue-600 select-none">--:--:--</h2>
    <h1 class="text-2xl font-bold text-center text-gray-900">Manual Time Log</h1>

    <form method="POST" class="flex flex-col gap-4">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

      <?php if (!$time_in && $can_time_in): ?>
        <button type="submit" name="time_in" class="bg-black text-white py-3 rounded hover:bg-gray-800">Log Time In</button>
      <?php elseif ($time_in && !$time_out): ?>
        <button type="submit" name="time_out" class="bg-black text-white py-3 rounded hover:bg-gray-800">Log Time Out</button>
      <?php else: ?>
        <button type="button" disabled class="bg-gray-400 text-gray-200 py-3 rounded">Already Logged</button>
      <?php endif; ?>
    </form>

    <div class="text-center pt-4 border-t">
      <a href="../employee/logout.php" class="text-red-600 font-semibold hover:underline">Logout</a>
    </div>
  </main>
</body>
</html>
