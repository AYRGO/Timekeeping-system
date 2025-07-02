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

       if (isset($_POST['leaveType'])) {
    $leaveType = $_POST['leaveType'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = $_POST['reason'] ?? '';

        // Check if leave already exists in that range
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests 
            WHERE employee_id = ? AND (
                (? BETWEEN start_date AND end_date) 
                OR (? BETWEEN start_date AND end_date)
                OR (start_date BETWEEN ? AND ?)
                OR (end_date BETWEEN ? AND ?)
            )");
        $stmt->execute([$employee_id, $start, $end, $start, $end, $start, $end]);

        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'You already submitted a leave within this date range.'
            ]);
            exit;
        }

        // Insert new leave
        $stmt = $pdo->prepare("INSERT INTO leave_requests 
            (employee_id, leave_type, start_date, end_date, reason) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$employee_id, $leaveType, $start, $end, $reason]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Leave request submitted successfully.'
        ]);
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
  <!-- Floating message box: should be here, right after <body> opens -->
  <div id="messageBox" class="hidden p-4 rounded-md text-white text-center fixed top-6 left-1/2 transform -translate-x-1/2 z-[9999] w-96 shadow-lg"></div>

<aside class="sidebar w-60 bg-white text-gray-900 p-6 flex flex-col justify-between min-h-screen shadow-md">
    <!-- Top Section: Logo and Navigation -->
    <div>
        <!-- Logo with Divider -->
        <div class="mb-2">
            <div class="text-center mb-2">
                <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-24 mx-auto">
            </div>
            <hr class="border-gray-300 w-full mx-auto">
        </div>
        <!-- Profile Section -->
        <div class="flex items-center mb-2">
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
</div>
         <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="card bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500">Available Leave</p>
                            <h3 class="text-2xl font-bold mt-1">12 days</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-umbrella-beach text-green-700 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 60%;"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">60% of annual leave remaining</p>
                    </div>
                </div>
                
                <div class="card bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500">Upcoming Payday</p>
                            <h3 class="text-2xl font-bold mt-1">May 15</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-blue-700 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600">5 days remaining</p>
                    </div>
                </div>
                
                <div class="card bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500">Pending Requests</p>
                            <h3 class="text-2xl font-bold mt-1">3</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-700 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-600">2 leave, 1 schedule change</p>
                    </div>
                </div>
                
                <div class="card bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500">Performance Score</p>
                            <h3 class="text-2xl font-bold mt-1">4.2/5</h3>
                        </div>
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-star text-purple-700 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star text-yellow-400"></i>
                            <i class="fas fa-star-half-alt text-yellow-400"></i>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Last reviewed: Apr 28</p>
                    </div>
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

  <!-- Profile Section -->
    <div id="profileView" class="hidden min-h-screen bg-gray-50 py-10 px-4">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Sidebar Info -->
    <div class="bg-white p-6 rounded-xl shadow space-y-4">
      <div class="flex flex-col items-center">
        <div class="relative w-24 h-24 rounded-full overflow-hidden border-4 border-green-500">
          <?php if ($profile_picture): ?>
            <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <div class="w-full h-full bg-gray-300 flex items-center justify-center text-4xl text-white">👤</div>
          <?php endif; ?>
          <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center cursor-pointer text-xs text-white opacity-0 hover:opacity-100 transition" onclick="document.getElementById('fileInput').click()">
            Change
          </div>
        </div>
        <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
          <input type="file" id="fileInput" name="profile_picture" class="hidden" onchange="this.form.submit()">
        </form>

        <h3 class="mt-4 font-semibold text-lg text-center"><?= htmlspecialchars($fname . ' ' . $lname) ?></h3>
        <span class="text-xs bg-black text-white px-2 py-1 rounded-full">Tier 2</span>
      </div>
    </div>

    <!-- Main Profile Info -->
    <div class="md:col-span-2 space-y-8">

      <!-- Personal Info -->
      <div class="bg-white p-6 rounded-xl shadow space-y-6">
        <div class="flex justify-between items-center">
          <h4 class="text-lg font-semibold text-gray-800">My Profile</h4>
          <a href="#" class="text-orange-500 font-medium">Edit</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
          <div><strong>First Name:</strong> <?= htmlspecialchars($fname) ?></div>
          <div><strong>Last Name:</strong> <?= htmlspecialchars($lname) ?></div>
          <div><strong>Email Address:</strong> <?= htmlspecialchars($email) ?></div>
          <div><strong>Mobile Number:</strong> <?= htmlspecialchars($contact) ?></div>
          <div><strong>Position:</strong> <?= htmlspecialchars($position) ?></div>
          <div><strong>Company:</strong> <?= htmlspecialchars($company) ?></div>
        </div>
      </div>

    </div>
  </div>
</div>




    


<!-- Request Leave -->
<div id="requestView" class="hidden mt-32">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow flex flex-col items-center justify-center">
        <h2 class="text-xl font-semibold mb-4 text-center">Request Leave</h2>

        <!-- Unified Form -->
        <form id="leaveRequestForm" method="POST" class="w-full flex flex-col items-center">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Leave Type -->
            <label class="block text-sm mb-1 text-center w-full">Leave Type</label>
            <select id="leaveTypeDropdown" name="leaveType" class="w-full border p-2 rounded mb-4" required>
                <option value="" selected disabled>Select type</option>
                <?php
                $types = ['sick','vacation','paternity','maternity','solo_parent','halfday','halfday_sick'];
                foreach ($types as $t): ?>
                    <option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Leave Dates -->
            <label class="block text-sm mb-1 text-center w-full">Leave Dates</label>
            <label class="block text-sm mb-1">Start Date</label>
<input type="text" name="start_date" id="start_date" class="w-full p-2 border rounded mb-2" placeholder="Start date" required>

<label class="block text-sm mb-1">End Date</label>
<input type="text" name="end_date" id="end_date" class="w-full p-2 border rounded mb-4" placeholder="End date" required>


            <!-- Reason -->
            <label class="block text-sm mb-1 text-center w-full">Reason</label>
            <textarea name="reason" placeholder="Optional reason..." class="w-full p-2 border rounded mb-4"></textarea>

            <!-- Submit -->
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Submit
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


  <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Check for session messages
        const message = "<?php echo $_SESSION['message'] ?? ''; ?>";
        const messageType = "<?php echo $_SESSION['message_type'] ?? ''; ?>";

        if (message) {
            showMessage(message, messageType);
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        }

        // Setup flatpickr on date inputs
        flatpickr("#start_date", { dateFormat: "Y-m-d" });
        flatpickr("#end_date", { dateFormat: "Y-m-d" });
        flatpickr("#date_range", { mode: "range", dateFormat: "Y-m-d" });
        flatpickr("#scheduleDays", { mode: "multiple", dateFormat: "Y-m-d" });

        // Clock updater
        function updateClock() {
            const now = new Date();
            let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('clock').textContent =
                `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')} ${ampm}`;
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Handle leave request form submission
        document.getElementById("leaveRequestForm").addEventListener("submit", function (e) {
            e.preventDefault();

            fetch("", {
                method: "POST",
                body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                showMessage(data.message, data.status);
                if (data.status === "success") {
                    this.reset();
                    closeLeaveModal();
                    showSection('dashboardView');
                }
            })
            .catch(() => {
                showMessage("An unexpected error occurred.", "error");
            });
        });
    });

    // Floating message box
    function showMessage(message, type = "success") {
        const box = document.getElementById("messageBox");
        box.textContent = message;
        box.className = "p-4 rounded-md text-white mb-4 text-center fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-96";
        box.classList.add(type === "error" ? "bg-red-500" : "bg-green-500");
        box.classList.remove("hidden");

        setTimeout(() => {
            box.classList.add("hidden");
        }, 4000);
    }

    // Navigation view switcher
    function showSection(id) {
        ['dashboardView', 'requestView', 'scheduleView', 'attendanceView', 'profileView']
            .forEach(x => document.getElementById(x).classList.add('hidden'));
        document.getElementById(id).classList.remove('hidden');
    }

    // Open Leave Modal and assign leaveType to hidden input
    function openLeaveModal() {
        const dropdown = document.getElementById("leaveTypeDropdown");
        const selectedType = dropdown.value;
        if (!selectedType) return;

        document.getElementById("hiddenLeaveType").value = selectedType;
        document.getElementById("leaveModal").classList.remove("hidden");
        document.getElementById("start_date").focus();
    }

    // Close modal
    function closeLeaveModal() {
        document.getElementById("leaveModal").classList.add("hidden");
        document.getElementById("leaveTypeDropdown").value = ''; // optional: reset dropdown
    }

    // Handle alerts on page load
    window.onload = function () {
        showSection('dashboardView');
        <?php if (isset($_GET['schedule']) && $_GET['schedule'] == 'updated'): ?>
            showMessage('Schedule updated successfully!');
        <?php elseif (isset($_GET['leave']) && $_GET['leave'] == 'success'): ?>
            showMessage('Leave request sent!');
        <?php endif; ?>
    };
  </script>
</body>

</html>
