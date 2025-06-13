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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $pdo->prepare("SELECT fname, lname, email, contact, position, company, profile_picture FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $user = $stmt->fetch();

    $fname = $user['fname'] ?? '';
    $lname = $user['lname'] ?? '';
    $email = $user['email'] ?? '';
    $contact = $user['contact'] ?? '';
    $position = $user['position'] ?? '';
    $company = $user['company'] ?? '';
    $profile_picture = $user['profile_picture'] ?? null;

    $current_date = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $current_date]);
    $time_log = $stmt->fetch();
    $time_in = $time_log['time_in'] ?? null;
    $time_out = $time_log['time_out'] ?? null;

    $can_time_in = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['time_in']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) VALUES (?, ?, ?)");
        $stmt->execute([$employee_id, $current_date, date("H:i:s")]);
        header("Location: time_log_create.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['time_out']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $stmt = $pdo->prepare("UPDATE time_logs SET time_out = ? WHERE employee_id = ? AND log_date = ?");
        $stmt->execute([date("H:i:s"), $employee_id, $current_date]);
        header("Location: time_log_create.php");
        exit;
    }

} catch (Exception $e) {
    die("Something went wrong.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manual Time Log</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #00c6a7, #1e90ff); }
    .custom-file-input { opacity: 0; position: absolute; cursor: pointer; }
    .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.4); color: white; display: flex; justify-content: center;
      align-items: center; font-weight: bold; border-radius: 9999px; opacity: 0; transition: opacity .3s; }
    .relative:hover .overlay { opacity: 1; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<main class="bg-white text-gray-900 w-full max-w-5xl rounded-2xl shadow-lg p-8">
  <h1 class="text-2xl font-bold text-center uppercase mb-6">Manual Time Log</h1>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Profile -->
    <div class="flex flex-col items-center space-y-4">
      <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
        <div class="relative w-28 h-28">
          <?php if ($profile_picture): ?>
            <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-28 h-28 rounded-full object-cover shadow">
          <?php else: ?>
            <div class="w-28 h-28 bg-gray-300 rounded-full flex items-center justify-center text-3xl text-white">👤</div>
          <?php endif; ?>
          <div class="overlay" onclick="document.getElementById('fileInput').click()">Change</div>
          <input type="file" id="fileInput" name="profile_picture" class="custom-file-input" onchange="this.form.submit()">
        </div>
      </form>

      <h2 class="text-lg font-semibold"><?= htmlspecialchars($fname . ' ' . $lname) ?></h2>
      <div id="clock" class="text-4xl font-mono font-bold text-blue-700 tracking-wide"></div>

      <div class="bg-gray-100 text-sm text-gray-800 w-full max-w-xs p-4 rounded-md space-y-1">
        <div><strong>Email:</strong> <?= htmlspecialchars($email) ?></div>
        <div><strong>Contact:</strong> <?= htmlspecialchars($contact) ?></div>
        <div><strong>Position:</strong> <?= htmlspecialchars($position) ?></div>
        <div><strong>Company:</strong> <?= htmlspecialchars($company) ?></div>
      </div>

      <a href="../employee/logout.php" class="mt-4 bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 font-semibold">Logout</a>
    </div>

    <!-- Time Log + Leave -->
    <div class="flex flex-col space-y-4">
      <div class="bg-gray-100 text-gray-800 p-6 rounded-lg shadow-md space-y-4">
        <h2 class="text-lg font-semibold text-center">Today's Time Log</h2>
        <div class="text-center space-y-2">
          <div><strong>Time In:</strong> <?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?></div>
          <div><strong>Time Out:</strong> <?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?></div>
        </div>
        <form method="POST" class="w-full">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <?php if (!$time_in && $can_time_in): ?>
            <button type="submit" name="time_in" class="bg-green-600 text-white font-semibold py-2 w-full rounded-md hover:bg-green-700">Log Time In</button>
          <?php elseif ($time_in && !$time_out): ?>
            <button type="submit" name="time_out" class="bg-yellow-600 text-white font-semibold py-2 w-full rounded-md hover:bg-yellow-700">Log Time Out</button>
          <?php else: ?>
            <button type="button" disabled class="bg-gray-400 text-white font-semibold py-2 w-full rounded-md">Already Logged</button>
          <?php endif; ?>
        </form>
      </div>

      <!-- Leave -->
      <div class="pt-4">
        <label for="leaveType" class="block mb-1 font-semibold text-gray-800">Request Leave</label>
        <select id="leaveType" class="w-full p-2 rounded-md border border-gray-300 bg-white text-gray-800">
          <option disabled selected>Select Leave Type</option>
          <option value="sick">Sick Leave</option>
          <option value="vacation">Vacation Leave</option>
          <option value="paternity">Paternity Leave</option>
          <option value="maternity">Maternity Leave</option>
          <option value="solo_parent">Solo Parent Leave</option>
          <option value="halfday">Halfday Leave</option>
          <option value="halfday_sick">Halfday Sick Leave</option>
        </select>
        <button type="button" disabled class="mt-3 bg-blue-400 text-white px-4 py-2 rounded-md w-full cursor-not-allowed">Submit</button>
      </div>
    </div>
  </div>
</main>

<!-- Leave Modal -->
<div id="leaveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
  <form id="sickLeaveForm" class="bg-white text-gray-900 p-6 rounded-lg shadow-lg w-full max-w-md space-y-4 relative">
    <h2 class="text-xl font-bold">Select Leave Dates</h2>

    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="leaveType" id="hiddenLeaveType" value="">
    <input type="text" name="leaveDates" id="leaveDates" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Choose date range" required>
    <textarea name="reason" placeholder="Optional reason..." class="w-full p-2 border border-gray-300 rounded-md"></textarea>

    <div class="flex justify-end space-x-2 pt-4">
      <button id="closeModal" type="button" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
    </div>
  </form>
</div>

<script>
function updateClock() {
  const now = new Date();
  let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  const ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12 || 12;
  document.getElementById('clock').textContent =
    `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')} ${ampm}`;
}
setInterval(updateClock, 1000);
window.onload = updateClock;
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const lt = document.getElementById("leaveType");
  const modal = document.getElementById("leaveModal");
  const closeBtn = document.getElementById("closeModal");
  const hiddenType = document.getElementById("hiddenLeaveType");

  lt.addEventListener("change", function () {
    const val = this.value;
    if (val) {
      modal.classList.remove("hidden");
      hiddenType.value = val;
      flatpickr("#leaveDates", { mode: "range", dateFormat: "Y-m-d" });
    }
  });

  closeBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
    lt.value = "Select Leave Type";
  });
});

document.getElementById("sickLeaveForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch("submit_leave.php", {
    method: "POST",
    body: formData,
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.status === "success") {
      document.getElementById("leaveModal").classList.add("hidden");
      this.reset();
    }
  })
  .catch(err => {
    alert("An error occurred.");
    console.error(err);
  });
});
</script>
</body>
</html>
