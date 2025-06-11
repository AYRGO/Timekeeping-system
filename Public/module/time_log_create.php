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
        $work_end_time = $work_schedule['end_time'] ?? null;
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
    die("Something went wrong. Try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manual Time Log</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #00c6a7, #1e90ff);
    }
    .custom-file-input {
      opacity: 0;
      position: absolute;
      cursor: pointer;
    }
    .overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.4);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      border-radius: 9999px;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .relative:hover .overlay {
      opacity: 1;
    }
  </style>
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
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-white">

  <main class="bg-white text-gray-900 max-w-4xl w-full rounded-2xl shadow-2xl p-8 space-y-8">

    <!-- Title -->
    <h1 class="text-2xl md:text-3xl font-bold tracking-widest uppercase text-center text-black">Manual Time Log</h1>

    <!-- Grid Container -->
    <div class="flex flex-col md:flex-row gap-8">
      
      <!-- Left: Profile & Clock -->
      <div class="flex-1 flex flex-col items-center space-y-4">
        <form id="uploadForm" action="upload_profile.php" method="POST" enctype="multipart/form-data">
          <div class="relative w-28 h-28">
            <?php if (!empty($profile_picture)): ?>
              <img src="../uploads/profile_images/<?php echo htmlspecialchars($profile_picture); ?>" class="w-28 h-28 rounded-full object-cover shadow" />
            <?php else: ?>
              <div class="w-28 h-28 bg-gray-300 rounded-full flex items-center justify-center text-3xl text-white">👤</div>
            <?php endif; ?>
            <div class="overlay cursor-pointer" onclick="document.getElementById('fileInput').click();">Change</div>
            <input type="file" id="fileInput" name="profile_picture" accept="image/*" class="custom-file-input" onchange="document.getElementById('uploadForm').submit();">
          </div>
        </form>

        <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($fname . ' ' . $lname); ?></h2>
        <div class="text-2xl font-mono font-bold text-blue-600" id="clock">--:--:--</div>

        <!-- Basic Info -->
        <div class="bg-gray-50 rounded-lg p-4 shadow w-full text-sm space-y-1">
          <div><span class="font-semibold text-gray-700">Email:</span> <?php echo htmlspecialchars($email); ?></div>
          <div><span class="font-semibold text-gray-700">Contact:</span> <?php echo htmlspecialchars($contact); ?></div>
          <div><span class="font-semibold text-gray-700">Company:</span> <?php echo htmlspecialchars($company); ?></div>
          <div><span class="font-semibold text-gray-700">Position:</span> <?php echo htmlspecialchars($position); ?></div>
        </div>
      </div>

      <!-- Right: Time Log + Leave -->
      <div class="flex-1 flex flex-col justify-between space-y-6">

        <!-- Time Log Box -->
        <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md space-y-4">
          <h2 class="text-lg font-semibold text-center">Today's Time Log</h2>
          <div class="text-center space-y-2">
            <div><strong>Time In:</strong> <?php echo $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?></div>
            <div><strong>Time Out:</strong> <?php echo $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?></div>
          </div>

          <!-- Time In/Out Buttons -->
          <form method="POST" class="w-full">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <?php if (!$time_in && $can_time_in): ?>
              <button type="submit" name="time_in" class="bg-green-600 text-white font-semibold py-2 w-full rounded-md hover:bg-green-700">Log Time In</button>
            <?php elseif ($time_in && !$time_out): ?>
              <button type="submit" name="time_out" class="bg-yellow-600 text-white font-semibold py-2 w-full rounded-md hover:bg-yellow-700">Log Time Out</button>
            <?php else: ?>
              <button type="button" disabled class="bg-gray-400 text-white font-semibold py-2 w-full rounded-md">Already Logged</button>
            <?php endif; ?>
          </form>
        </div>

        <!-- Leave Request -->
        <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md space-y-3">
          <h2 class="text-lg font-semibold text-center">Request Leave</h2>
          <form>
            <select class="w-full p-2 rounded-md border border-gray-300 bg-white text-gray-800">
              <option disabled selected>Select Leave Type</option>
              <option>Sick Leave</option>
              <option>Vacation Leave</option>
              <option>Maternity Leave</option>
              <option>Paternity Leave</option>
              <option>Solo Parent Leave</option>
              <option>Halfday Leave</option>
              <option>Halfday Sick Leave</option>
            </select>
            <button type="button" disabled class="mt-3 bg-blue-400 text-white px-4 py-2 rounded-md w-full cursor-not-allowed">Submit</button>
          </form>
        </div>

        <!-- Logout -->
        <div class="text-center">
          <a href="../employee/logout.php" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-800 font-semibold">Logout</a>
        </div>

      </div>
    </div>
  </main>
</body>
</html>
