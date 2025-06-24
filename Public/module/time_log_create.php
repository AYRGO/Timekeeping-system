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
    // Fetch employee details
    $stmt = $pdo->prepare("SELECT fname, lname, email, contact, position, company, profile_picture 
                           FROM employees WHERE id = ?");
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

    // Fetch today's time log
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs 
                           WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $current_date]);
    $time_log = $stmt->fetch();
    $time_in = $time_log['time_in'] ?? null;
    $time_out = $time_log['time_out'] ?? null;



    // Work schedules
    $stmt = $pdo->prepare("SELECT id, time_in, time_out, day_of_week FROM work_schedules");
    $stmt->execute();
    $work_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Employee schedules
    $stmt = $pdo->prepare("SELECT ws.id, ws.day_of_week, ws.time_in, ws.time_out 
                           FROM employee_schedules es
                           JOIN work_schedules ws ON es.work_schedule_id = ws.id
                           WHERE es.employee_id = ?");
    $stmt->execute([$employee_id]);
    $saved_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_schedule = [];
    foreach ($saved_schedule as $sched) {
        $grouped_schedule[$sched['day_of_week']] = [
            'id' => $sched['id'],
            'time_in' => $sched['time_in'],
            'time_out' => $sched['time_out']
        ];
    }

    // Handle POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            die("Invalid CSRF token.");
        }

        // Time In
        if (isset($_POST['time_in'])) {
            $stmt = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in) 
                                   VALUES (?, ?, ?)");
            $stmt->execute([$employee_id, $current_date, date("H:i:s")]);
            header("Location: time_log_create.php");
            exit;
        }

        // Time Out
        if (isset($_POST['time_out'])) {
            $stmt = $pdo->prepare("UPDATE time_logs SET time_out = ? 
                                   WHERE employee_id = ? AND log_date = ?");
            $stmt->execute([date("H:i:s"), $employee_id, $current_date]);
            header("Location: time_log_create.php");
            exit;
        }

        // Leave Request
        if (isset($_POST['leaveType'])) {
            $leaveType = $_POST['leaveType'];
            $leaveDates = $_POST['leaveDates'];
            $reason = $_POST['reason'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO leave_requests 
                                   (employee_id, leave_type, leave_dates, reason) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([$employee_id, $leaveType, $leaveDates, $reason]);
            header("Location: time_log_create.php?leave=success");
            exit;
        }

        // Schedule Change Request
        if (isset($_POST['submit_schedule_change'])) {
            $requested_schedule_id = $_POST['requested_schedule_id'] ?? null;
            $reason = trim($_POST['reason'] ?? '');
            $date_range = trim($_POST['date_range'] ?? '');

            if (empty($date_range) || strpos($date_range, ' to ') === false) {
                die("Invalid effective date format. Please use YYYY-MM-DD to YYYY-MM-DD.");
            }

            [$start_date_raw, $end_date_raw] = explode(' to ', $date_range);
            $start_date = date('Y-m-d', strtotime($start_date_raw));
            $end_date = date('Y-m-d', strtotime($end_date_raw));

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || 
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                die("Invalid date format. Use YYYY-MM-DD.");
            }

            $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
                (employee_id, requested_schedule_id, reason, status, start_date, end_date, created_at)
                VALUES (?, ?, ?, 'pending', ?, ?, NOW())");

            $stmt->execute([$employee_id, $requested_schedule_id, $reason, $start_date, $end_date]);

            header("Location: time_log_create.php?schedule_change=success");
            exit;
        }

        // Update Schedule
        if (isset($_POST['update_schedule']) || isset($_POST['request_change_schedule'])) {
            $stmt = $pdo->prepare("DELETE FROM employee_schedules WHERE employee_id = ?");
            $stmt->execute([$employee_id]);

            $new_schedule_id = $_POST['new_work_hours'] ?? null;
            $all_days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

            foreach ($all_days as $day) {
                $stmt = $pdo->prepare("INSERT INTO employee_schedules 
                    (employee_id, work_schedule_id, effective_date, day_of_week) 
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([$employee_id, $new_schedule_id, $current_date, $day]);
            }

            header("Location: time_log_create.php?schedule=updated");
            exit;
        }

        // Logout
        if (isset($_POST['logout'])) {
            session_destroy();
            header("Location: ../employee/login.php");
            exit;
        }
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>RSS Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
  body {
    font-family: 'Poppins', sans-serif;
    background: #F4F6FA;
    overflow-x: hidden;
  }

  .sidebar a {
    color: rgba(0, 0, 0, 0.7);
  }

  .sidebar a.active,
  .sidebar a:hover {
    background-color: #d1fae5; /* Light green */
    color: #065f46; /* Dark green text for contrast */
  }

  .profile-img:hover .overlay {
    opacity: 1;
  }

  .overlay {
    transition: opacity .3s;
    opacity: 0;
  }

  .fixed-box {
    max-height: 400px;
    overflow-y: auto;
  }

  .schedule-day:hover {
    transform: translateY(-2px);
    transition: .2s ease;
  }
</style>

</head>
<body class="flex min-h-screen overflow-x-hidden">

<!-- Sidebar -->
<aside class="sidebar w-60 bg-white text-gray-900 p-6 flex flex-col justify-between min-h-screen shadow-md">
    <!-- Top Section: Logo and Navigation -->
    <div>
        <!-- Logo with Divider -->
        <div class="mb-4">
            <div class="text-center mb-2">
                <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-24 mx-auto">
            </div>
            <hr class="border-gray-300 w-full mx-auto">
        </div>
        <!-- Profile Section -->
        <div class="flex items-center mt-4 mb-2">
            <img src="<?= $profile_picture ? '../uploads/profile_images/' . htmlspecialchars($profile_picture) : 'https://via.placeholder.com/40' ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border border-gray-300 mr-3">
            <div class="flex flex-col">
                <span class="font-semibold text-base"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
                <span class="text-xs text-gray-500"><?= htmlspecialchars($position) ?></span>
            </div>
        </div>

        <hr class="border-gray-300 w-full mx-auto mb-6">
        <!-- Navigation Links -->
        <nav class="flex-1 space-y-4">
            <a href="#" onclick="showSection('dashboardView');" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                <span class="text-xl"><i class="fas fa-tachometer-alt"></i></span>
                <span class="text-lg">Dashboard</span>
            </a>
            <a href="#" onclick="showSection('newsFeedView');" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                <span class="text-xl"><i class="fas fa-newspaper"></i></span>
                <span class="text-lg">News Feed</span>
            </a>
            <a href="#" onclick="showSection('scheduleView');" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                <span class="text-xl"><i class="fas fa-calendar-alt"></i></span>
                <span class="text-lg">Request Change Schedule</span>
            </a>
            <a href="#" onclick="showSection('requestView');" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                <span class="text-xl"><i class="fas fa-plane-departure"></i></span>
                <span class="text-lg">Request Leave</span>
            </a>
            <a href="#" onclick="showSection('attendanceView');" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-500">
                <span class="text-xl"><i class="fas fa-user-check"></i></span>
                <span class="text-lg">Attendance</span>
            </a>
        </nav>
    </div>
    <!-- Bottom Section: Settings and Logout -->
    <div class="border-t border-gray-300 mt-6 pt-4">
        <div class="flex flex-col space-y-4">
            <a href="#" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 text-gray-900">
                <span class="text-xl"><i class="fas fa-cog"></i></span>
                <span class="text-lg">Settings</span>
            </a>
            <form method="POST" class="w-full">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="logout" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 w-full text-left text-gray-900">
                    <span class="text-xl"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="text-lg">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>


<main class="flex-1 p-10 overflow-auto">
<!-- Fixed Header -->
<header class="fixed top-0 left-60 w-[calc(100%-15rem)] bg-white shadow z-50 flex items-center justify-between px-8 py-4">
    <h1 class="text-2xl font-semibold text-gray-800">Employee Dashboard</h1>
    <div class="flex items-center space-x-6">
        <button class="relative text-gray-600 hover:text-gray-800 focus:outline-none">
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute -top-1 -right-1 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
        </button>
        <div class="flex items-center space-x-3">
            <div class="w-px h-6 bg-gray-300 mx-2"></div>
            <span class="text-gray-700 font-medium"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
            <button class="ml-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
</header>

<div id="dashboardView" class="mt-20">

<!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-xl p-6 text-white mb-6 shadow-lg">
                <div class="flex justify-between items-center">
                    <div>
                         <h1 class="text-3xl font-bold">Welcome back, <?= htmlspecialchars($fname) ?> 👋</h1>
                        <p class="mb-4">You have 3 pending tasks to complete today.</p>
                        <button class="bg-white text-green-800 px-4 py-2 rounded-md font-medium hover:bg-opacity-90 transition">View Tasks</button>
                    </div>
                    <div class="hidden md:block">
                        <img src="https://cdn-icons-png.flaticon.com/512/1903/1903162.png" alt="HR Illustration" class="h-32">
                    </div>
                </div>
            </div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Profile or other dashboard widgets go here -->
</div>
</div>
        <!-- Request Change Schedule -->
        <div id="scheduleView" class="hidden mt-32">
            <div class="flex justify-center items-center min-h-[60vh]">
                <div class="max-w-xl w-full bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold mb-4 text-center">Request Change of Work Schedule</h2>
                    <form method="POST" id="scheduleChangeForm" class="flex flex-col items-center">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="submit_schedule_change" value="1">

                        <div class="mb-4 w-full">
                            <label for="date_range" class="block text-sm font-medium mb-2">
                                Effective Date Range <span class="text-gray-500 text-xs">(YYYY-MM-DD to YYYY-MM-DD)</span>
                            </label>
                            <input type="text" name="date_range" id="date_range"
                                class="w-full p-2 border rounded"
                                placeholder="Choose date range"
                                required>
                        </div>

                        <!-- New Work Hours -->
                        <div class="mb-4 w-full">
                            <label for="requested_schedule_id" class="block text-sm font-medium mb-2">
                                New Work Hours
                            </label>
                            <select name="requested_schedule_id" id="requested_schedule_id"
                                class="w-full p-2 border rounded" required>
                                <option value="" disabled selected>Select new work hours</option>
                                <?php 
                                $allowed = [4, 5, 6, 7];
                                foreach ($work_schedules as $ws):
                                    if (in_array($ws['id'], $allowed)):
                                ?>
                                    <option value="<?= $ws['id'] ?>">
                                        <?= date("g:i A", strtotime($ws['time_in'])) ?> to <?= date("g:i A", strtotime($ws['time_out'])) ?>
                                    </option>
                                <?php 
                                    endif;
                                endforeach;
                                ?>
                            </select>
                        </div>

                        <!-- Reason -->
                        <div class="mb-4 w-full">
                            <label for="reason" class="block text-sm font-medium mb-2">Reason for Change</label>
                            <textarea name="reason" id="reason" rows="3"
                                class="w-full p-2 border rounded"
                                placeholder="Explain your reason for the schedule change" required></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <!-- Attendance View -->
<div id="attendanceView" class="hidden mt-32">
    <div class="flex justify-center items-center min-h-[60vh]">
        <div class="w-full max-w-4xl bg-white p-6 rounded-lg shadow space-y-6 mx-auto">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 text-center">Today's Attendance</h2>

            <!-- Time Log Section -->
            <div class="space-y-4 flex flex-col items-center">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-center w-full max-w-md mx-auto">
                    <!-- Display Time In -->
                    <div class="bg-green-100 p-4 rounded">
                        <p class="text-gray-500">Time In</p>
                        <p class="text-2xl font-bold">
                            <?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?>
                        </p>
                    </div>

                    <!-- Display Time Out -->
                    <div class="bg-yellow-100 p-4 rounded">
                        <p class="text-gray-500">Time Out</p>
                        <p class="text-2xl font-bold">
                            <?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?>
                        </p>
                    </div>
                </div>

                <!-- Log Button Form -->
                <form method="POST" class="flex justify-center w-full max-w-xs">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <?php if (!$time_in): ?>
                        <button type="submit" name="time_in"
                            class="w-full mt-4 bg-green-600 text-white py-2 rounded hover:bg-green-700">
                            Log Time In
                        </button>
                    <?php elseif ($time_in && !$time_out): ?>
                        <button type="submit" name="time_out"
                            class="w-full mt-4 bg-yellow-600 text-white py-2 rounded hover:bg-yellow-700">
                            Log Time Out
                        </button>
                    <?php else: ?>
                        <button type="button" disabled
                            class="w-full mt-4 bg-gray-400 text-white py-2 rounded">
                            Already Logged
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
 <!-- Quick Actions -->
<div class="bg-white rounded-lg p-6 shadow-sm">
  <h3 class="text-lg font-semibold mb-4 flex items-center">
    <i class="fas fa-bolt text-yellow-500 mr-2"></i> Quick Actions
  </h3>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <button onclick="showSection('requestView')" class="flex flex-col items-center justify-center p-4 rounded-lg bg-green-50 hover:bg-green-100 transition">
      <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mb-2">
        <i class="fas fa-calendar-plus text-green-700"></i>
      </div>
      <span class="text-sm font-medium">Request Leave</span>
    </button>
    <button onclick="showSection('scheduleView')" class="flex flex-col items-center justify-center p-4 rounded-lg bg-blue-50 hover:bg-blue-100 transition">
      <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mb-2">
        <i class="fas fa-exchange-alt text-blue-700"></i>
      </div>
      <span class="text-sm font-medium">Change Schedule</span>
    </button>
    <button id="attendanceBtn" class="flex flex-col items-center justify-center p-4 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition">
      <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mb-2">
        <i class="fas fa-user-check text-indigo-700"></i>
      </div>
      <span class="text-sm font-medium">Mark Attendance</span>
    </button>
  </div>
</div>
                       



    <!-- Request Leave -->
    <div id="requestView" class="hidden mt-32">
        <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow flex flex-col items-center justify-center">
            <h2 class="text-xl font-semibold mb-4 text-center">Request Leave</h2>
            <form id="sickLeaveForm" class="w-full flex flex-col items-center">
                <input type="hidden" name="csrf_token"
                 value="<?= $_SESSION['csrf_token'] ?>">
                <label class="block text-sm mb-1 text-center w-full">Leave Type</label>
                <select id="leaveTypeDropdown" name="leaveType"
                 class="w-full border p-2 rounded mb-4" onchange="openLeaveModal()" required>
                    <option value="" selected disabled>Select type</option>
                    <?php
                    $types = ['sick','vacation','paternity','maternity',
                              'solo_parent','halfday','halfday_sick'];
                    foreach ($types as $t): ?>
                    <option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</main>




<!-- Leave Modal -->
<div id="leaveModal" class="hidden fixed inset-0
 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <form method="POST" class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md space-y-4"
     id="leaveRequestForm">
        <h2 class="text-xl font-bold">Select Leave Dates</h2>
        <input type="hidden" name="csrf_token"
         value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="leaveType" id="hiddenLeaveType">
        <input type="text" name="leaveDates" id="leaveDates"
         class="w-full p-2 border rounded" placeholder="Choose date range" required>
        <textarea name="reason" placeholder="Optional reason..."
         class="w-full p-2 border rounded"></textarea>
        <div class="flex justify-end space-x-2 pt-4">
            <button type="button" onclick="closeLeaveModal()"
             class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white
             rounded hover:bg-blue-700">
                Submit
            </button>
        </div>
    </form>
</div>

<script>
flatpickr("#leaveDates", { mode:"range", dateFormat:"Y-m-d" });
flatpickr("#scheduleDays", { mode:"multiple", dateFormat:"Y-m-d" });

function updateClock() {
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ampm = h>=12?'PM':'AM';
    h = h%12 || 12;
    document.getElementById('clock').textContent =
     `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${
      String(s).padStart(2,'0')} ${ampm}`;
}
setInterval(updateClock, 1000);
updateClock();

function showSection(id){
    ['dashboardView', 'requestView', 'scheduleView', 'attendanceView']
     .forEach(x => document.getElementById(x).classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

function openLeaveModal(){
    const sel = document.getElementById('leaveTypeDropdown').value;
    if (!sel) return;
    document.getElementById('hiddenLeaveType').value=sel;
    document.getElementById('leaveModal').classList.remove('hidden');
}

function closeLeaveModal(){
    document.getElementById('leaveModal').classList.add('hidden');
    document.getElementById('leaveTypeDropdown').value = '';
}

// Handle leave form via AJAX
document.getElementById("leaveRequestForm")
 .addEventListener("submit", function(e){
    e.preventDefault();
    fetch("submit_leave.php", {
        method: "POST",
        body: new FormData(this)
    }).then(res=>res.json()).then(data=>{
        alert(data.message);
        if (data.status === "success") {
            closeLeaveModal();
            this.reset();
        }
    }).catch(err=>{
        alert("Error occurred.");
        console.error(err);
    });
});

// Auto return on success
window.onload = function(){
    <?php if(isset($_GET['schedule']) && $_GET['schedule']=='updated'): ?>
        alert('Schedule updated successfully!');
        showSection('dashboardView');
    <?php elseif(isset($_GET['leave']) && $_GET['leave']=='success'): ?>
        alert('Leave request sent!');
        showSection('dashboardView');
    <?php endif; ?>
};
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
flatpickr("#date_range", {
    mode: "range",
    dateFormat: "Y-m-d"
});
</script>
</body>
</html>
