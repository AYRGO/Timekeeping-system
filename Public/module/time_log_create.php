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

        // Change Schedule Request (multi-date)
        if (isset($_POST['submit_schedule_change'])) {
            $requested_schedule_id = $_POST['requested_schedule_id'] ?? null;
            $reason = trim($_POST['reason'] ?? '');
            $dates_raw = $_POST['requested_effective_date'] ?? '';

            // Validate and split dates
            $dates_array = array_map('trim', explode(',', $dates_raw));
            $valid_dates = [];

            foreach ($dates_array as $date) {
                $d = DateTime::createFromFormat('Y-m-d', $date);
                if ($d && $d->format('Y-m-d') === $date) {
                    $valid_dates[] = $date;
                }
            }

            if (empty($valid_dates)) {
                die("Invalid effective date format. Please use YYYY-MM-DD or comma-separated dates.");
            }

            $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
                (employee_id, requested_schedule_id, reason, status, requested_effective_date, created_at)
                VALUES (?, ?, ?, 'pending', ?, NOW())");

            foreach ($valid_dates as $date) {
                $stmt->execute([$employee_id, $requested_schedule_id, $reason, $date]);
            }

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
    <meta charset="UTF-8">
    <title>RSS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet"
     href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family:'Poppins',sans-serif; background:#F4F6FA; overflow-x:hidden; }
        .sidebar { background-color:rgb(24,136,43); }
        .sidebar a { color:#ffffffb3; }
        .sidebar a.active, .sidebar a:hover { color:#fff; }
        .profile-img:hover .overlay { opacity:1; }
        .overlay { transition: opacity .3s; opacity:0; }
        .fixed-box { max-height:400px; overflow-y:auto; }
        .schedule-day:hover { transform:translateY(-2px); transition:.2s ease; }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">

<aside class="sidebar w-48 bg-green-800 text-white p-6 flex flex-col">
    <div class="mb-10 text-center">
        <img src="../asset/RSS-logo-colour.png" alt="RSS Logo"
         class="w-24 mx-auto mb-2">
    </div>
    <nav class="flex-1 space-y-4">
        <a href="#" onclick="showSection('dashboardView');"
         class="flex items-center space-x-2 p-2 rounded-lg hover:bg-green-600">
            <span class="text-xl"><i class="fas fa-tachometer-alt"></i></span>
            <span class="text-lg">Dashboard</span>
        </a>
        <a href="#" onclick="showSection('requestView');"
         class="flex items-center space-x-2 p-2 rounded-lg hover:bg-green-600">
            <span class="text-xl"><i class="fas fa-file-alt"></i></span>
            <span class="text-lg">Leave</span>
        </a>
        <a href="#" onclick="showSection('scheduleView');"
         class="flex items-center space-x-2 p-2 rounded-lg hover:bg-green-600">
            <span class="text-xl"><i class="fas fa-calendar-alt"></i></span>
            <span class="text-lg">Schedule</span>
        </a>
    </nav>
    <div class="border-t border-gray-600 mt-6 pt-4">
        <div class="flex flex-col space-y-4">
            <a href="#" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-green-600">
                <span class="text-xl"><i class="fas fa-cog"></i></span>
                <span class="text-lg">Settings</span>
            </a>
            <form method="POST" class="w-full">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="logout"
                 class="flex items-center space-x-2 p-2 rounded-lg hover:bg-green-600 w-full text-left">
                    <span class="text-xl"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="text-lg">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<main class="flex-1 p-10 overflow-auto">
    <div id="dashboardView">
        <div class="flex flex-col md:flex-row justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold">
                    Welcome back, <?= htmlspecialchars($fname) ?> 👋
                </h1>
                <div class="mt-2">
                    <span class="text-4xl font-mono font-bold text-green-700" id="clock">--:--:--</span>
                </div>
                <p class="text-gray-500 text-sm mt-2">
                    Here's your time log and profile summary.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Profile -->
            <div class="bg-white p-6 rounded-lg shadow fixed-box space-y-4">
                <div class="relative w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-green-600 profile-img">
                    <?php if ($profile_picture): ?>
                        <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>"
                         class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gray-300 flex items-center justify-center text-4xl text-white">
                            👤
                        </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-black bg-opacity-50 overlay flex items-center justify-center cursor-pointer"
                     onclick="document.getElementById('fileInput').click()">
                        Change
                    </div>
                    <form action="upload_profile.php" method="POST"
                     enctype="multipart/form-data">
                        <input type="file" id="fileInput" name="profile_picture"
                         class="hidden" onchange="this.form.submit()">
                    </form>
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-semibold"><?= htmlspecialchars($fname . ' ' . $lname) ?></h2>
                    <p class="text-gray-500">
                        <?= htmlspecialchars($position) ?> @ <?= htmlspecialchars($company) ?>
                    </p>
                </div>

                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($contact) ?></p>
                </div>
            </div>

            <!-- Time Log -->
            <div class="bg-white p-6 rounded-lg shadow fixed-box space-y-4 col-span-2">
                <h2 class="text-lg font-semibold">Today's Time Log</h2>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-green-100 p-4 rounded">
                        <p class="text-gray-500">Time In</p>
                        <p class="text-2xl font-bold">
                            <?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?>
                        </p>
                    </div>
                    <div class="bg-yellow-100 p-4 rounded">
                        <p class="text-gray-500">Time Out</p>
                        <p class="text-2xl font-bold">
                            <?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?>
                        </p>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token"
                     value="<?= $_SESSION['csrf_token'] ?>">
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
                         class="w-full mt-4 bg-gray-400 text-white py-2 rounded">Already Logged</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

      <!-- Schedule Display -->
<div class="mt-6 bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Your Work Schedule</h2>
        <button onclick="showSection('scheduleView')" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
            <i class="fas fa-edit mr-1"></i> Edit Schedule
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">Day</th>
                    <th class="px-4 py-2 text-left">Time In</th>
                    <th class="px-4 py-2 text-left">Time Out</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Array to map numeric day values to day names
                $daysOfWeek = [
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday'
                ];

                // Initialize an array to hold the schedule for the week
                $weeklySchedule = array_fill(0, 7, ['time_in' => null, 'time_out' => null]);

                // Populate the weekly schedule with saved data
                if (!empty($saved_schedule)) {
                    foreach ($saved_schedule as $schedule) {
                        $dayIndex = (int)$schedule['day_of_week'];
                        if (array_key_exists($dayIndex, $weeklySchedule)) {
                            $weeklySchedule[$dayIndex] = [
                                'time_in' => $schedule['time_in'],
                                'time_out' => $schedule['time_out']
                            ];
                        }
                    }
                }

                // Display the schedule for each day of the week
                foreach ($weeklySchedule as $dayIndex => $times): ?>
                    <tr>
                        <td class="border px-4 py-2"><?= htmlspecialchars($daysOfWeek[$dayIndex]) ?></td>
                        <td class="border px-4 py-2">
                            <?= $times['time_in'] ? date("h:i A", strtotime($times['time_in'])) : 'Not Scheduled' ?>
                        </td>
                        <td class="border px-4 py-2">
                            <?= $times['time_out'] ? date("h:i A", strtotime($times['time_out'])) : 'Not Scheduled' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>


    </div>

  <!-- Request Change Schedule -->
<div id="scheduleView" class="hidden">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Request Change of Work Schedule</h2>
        <form method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="submit_schedule_change" value="1">

            <!-- Effective Dates -->
            <div class="mb-4">
                <label for="requested_effective_date" class="block text-sm font-medium mb-2">
                    Effective Dates <span class="text-gray-500 text-xs">(Format: YYYY-MM-DD, comma-separated)</span>
                </label>
                <input type="text" name="requested_effective_date" id="requested_effective_date"
                       class="w-full p-2 border rounded"
                       placeholder="e.g. 2025-07-01,2025-07-02"
                       required>
            </div>

            <!-- New Work Hours -->
            <div class="mb-4">
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
            <div class="mb-4">
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


    <!-- Request Leave -->
    <div id="requestView" class="hidden">
        <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Request Leave</h2>
            <form id="sickLeaveForm">
                <input type="hidden" name="csrf_token"
                 value="<?= $_SESSION['csrf_token'] ?>">
                <label class="block text-sm mb-1">Leave Type</label>
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
    ['dashboardView','requestView','scheduleView']
     .forEach(x=>document.getElementById(x).classList.add('hidden'));
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#requested_effective_date", {
            mode: "multiple",
            dateFormat: "Y-m-d", // <== only numbers like 2025-12-23
            altInput: false       // disables human-readable format
        });
    });
</script>
</body>
</html>
