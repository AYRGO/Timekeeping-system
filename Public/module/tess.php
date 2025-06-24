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

        // Schedule Change Request using date range (Flatpickr)
        if (isset($_POST['submit_schedule_change'])) {
            $requested_schedule_id = $_POST['requested_schedule_id'] ?? null;
            $reason = trim($_POST['reason'] ?? '');
            $date_range = trim($_POST['date_range'] ?? '');

            // Format must be: YYYY-MM-DD to YYYY-MM-DD
            if (empty($date_range) || strpos($date_range, ' to ') === false) {
                die("Invalid effective date format. Please use YYYY-MM-DD to YYYY-MM-DD.");
            }

            [$start_date_raw, $end_date_raw] = explode(' to ', $date_range);
            $start_date = date('Y-m-d', strtotime($start_date_raw));
            $end_date = date('Y-m-d', strtotime($end_date_raw));

            // Final date format check
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || 
                !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                die("Invalid date format. Use YYYY-MM-DD.");
            }

            // Insert the request
            $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
                (employee_id, requested_schedule_id, reason, status, start_date, end_date, created_at)
                VALUES (?, ?, ?, 'pending', ?, ?, NOW())");

            $stmt->execute([$employee_id, $requested_schedule_id, $reason, $start_date, $end_date]);

            header("Location: time_log_create.php?schedule_change=success");
            exit;
        }

        // Update Schedule (assign same work hours to all days)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS - Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #60ad5e;
            --primary-dark: #005005;
            --secondary: #ff8f00;
            --accent: #00c853;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(46, 125, 50, 0.1);
        }
        
        .sidebar-item.active {
            background-color: rgba(46, 125, 50, 0.2);
            border-left: 4px solid var(--primary);
        }
        
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e0e0e0;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background-color: var(--primary);
            transition: width 0.5s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Attendance Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .attendance-success {
            display: none;
            text-align: center;
            padding: 1rem;
        }
        
        .attendance-success i {
            font-size: 3rem;
            color: #2e7d32;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="flex min-h-screen overflow-x-hidden">
    <!-- Sidebar Navigation -->
    <div class="sidebar w-64 bg-white shadow-md flex flex-col">
     <!-- Minimalist Logo Version -->
<div class="py-5 flex items-center justify-center border-b border-gray-100">
    <img src="../asset/RSS-logo-colour.png" alt="RSS Logo"
         class="h-14 w-auto transition-all hover:scale-130">
</div>

        <!-- User Profile -->
<div class="p-4 flex items-center border-b relative">
    <div class="w-12 h-12 rounded-full overflow-hidden bg-green-100 flex items-center justify-center mr-3 relative">
        <?php if ($profile_picture): ?>
            <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-full h-full object-cover">
        <?php else: ?>
            <div class="w-full h-full bg-gray-300 flex items-center justify-center text-2xl text-white">👤</div>
        <?php endif; ?>

        <!-- Overlay for change button, only visible on hover -->
        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center cursor-pointer text-xs text-white opacity-0 hover:opacity-100 transition"
             onclick="document.getElementById('fileInput').click()">
            Change
        </div>

        <!-- File input form -->
        <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
            <input type="file" id="fileInput" name="profile_picture" class="hidden" onchange="this.form.submit()">
        </form>
    </div>

    <div>
        <p class="font-semibold text-base"><?= htmlspecialchars($fname . ' ' . $lname) ?></p>
        <p class="text-sm text-gray-500">Role: <?= htmlspecialchars($position) ?></p>
    </div>
</div>

        <!-- Main Navigation -->
        <nav class="flex-1 overflow-y-auto">
            <ul class="py-2">
                <li>
                    <a href="#" class="sidebar-item active flex items-center p-3 text-gray-700">
                        <i class="fas fa-tachometer-alt w-6 text-center mr-2 text-green-700"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-calendar-alt w-6 text-center mr-2"></i>
                        <span>Leave Management</span>
                        <span class="notification-badge bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center ml-auto">3</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-exchange-alt w-6 text-center mr-2"></i>
                        <span>Schedule Changes</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-user-check w-6 text-center mr-2"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-file-alt w-6 text-center mr-2"></i>
                        <span>Documents</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-chart-line w-6 text-center mr-2"></i>
                        <span>Performance</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-money-bill-wave w-6 text-center mr-2"></i>
                        <span>Payroll</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="sidebar-item flex items-center p-3 text-gray-700">
                        <i class="fas fa-cog w-6 text-center mr-2"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Footer -->
        <div class="p-4 border-t text-center text-sm text-gray-500">
            <p>RSS v2.1.0</p>
            <p>© 2023 Recruitment Sprout System</p>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="flex-1 overflow-y-auto">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm p-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Employee Dashboard</h2>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="p-2 rounded-full hover:bg-gray-100">
                        <i class="fas fa-bell text-gray-600"></i>
                        <span class="notification-badge bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">5</span>
                    </button>
                </div>
                <div class="relative">
                    <button class="p-2 rounded-full hover:bg-gray-100">
                        <i class="fas fa-envelope text-gray-600"></i>
                        <span class="notification-badge bg-blue-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">2</span>
                    </button>
                </div>
                <div class="border-l pl-4">
                    <button class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-2">
                            <i class="fas fa-user text-green-700"></i>
                        </div>
                        <span class="font-medium"><?= htmlspecialchars($fname) ?></span>
                        <i class="fas fa-chevron-down ml-2 text-gray-500"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <main class="p-6">
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
            
            <!-- Main Content Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
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
      <button class="flex flex-col items-center justify-center p-4 rounded-lg bg-purple-50 hover:bg-purple-100 transition">
        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mb-2">
          <i class="fas fa-file-upload text-purple-700"></i>
        </div>
        <span class="text-sm font-medium">Submit Document</span>
      </button>
    </div>
  </div>

  <!-- Request Leave -->
  <div id="requestView" class="hidden">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold mb-4">Request Leave</h2>
      <form id="sickLeaveForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <label class="block text-sm mb-1">Leave Type</label>
        <select id="leaveTypeDropdown" name="leaveType" class="w-full border p-2 rounded mb-4" onchange="openLeaveModal()" required>
          <option value="" selected disabled>Select type</option>
          <?php
          $types = ['sick','vacation','paternity','maternity','solo_parent','halfday','halfday_sick'];
          foreach ($types as $t): ?>
            <option value="<?= $t ?>"><?= ucfirst(str_replace('_',' ',$t)) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

  <!-- Request Change Schedule -->
  <div id="scheduleView" class="hidden">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold mb-4">Request Change of Work Schedule</h2>
      <form method="POST" id="scheduleChangeForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="submit_schedule_change" value="1">

        <div class="mb-4">
          <label for="date_range" class="block text-sm font-medium mb-2">Effective Date Range <span class="text-gray-500 text-xs">(YYYY-MM-DD to YYYY-MM-DD)</span></label>
          <input type="text" name="date_range" id="date_range" class="w-full p-2 border rounded" placeholder="Choose date range" required>
        </div>

        <div class="mb-4">
          <label for="requested_schedule_id" class="block text-sm font-medium mb-2">New Work Hours</label>
          <select name="requested_schedule_id" id="requested_schedule_id" class="w-full p-2 border rounded" required>
            <option value="" disabled selected>Select new work hours</option>
            <?php 
            $allowed = [4, 5, 6, 7];
            foreach ($work_schedules as $ws):
              if (in_array($ws['id'], $allowed)):
            ?>
              <option value="<?= $ws['id'] ?>">
                <?= date("g:i A", strtotime($ws['time_in'])) ?> to <?= date("g:i A", strtotime($ws['time_out'])) ?>
              </option>
            <?php endif; endforeach; ?>
          </select>
        </div>

        <div class="mb-4">
          <label for="reason" class="block text-sm font-medium mb-2">Reason for Change</label>
          <textarea name="reason" id="reason" rows="3" class="w-full p-2 border rounded" placeholder="Explain your reason" required></textarea>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
          Submit Request
        </button>
      </form>
    </div>
  </div>

</main>

<!-- Leave Modal -->
<div id="leaveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
  <form method="POST" class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md space-y-4" id="leaveRequestForm">
    <h2 class="text-xl font-bold">Select Leave Dates</h2>
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="leaveType" id="hiddenLeaveType">
    <input type="text" name="leaveDates" id="leaveDates" class="w-full p-2 border rounded" placeholder="Choose date range" required>
    <textarea name="reason" placeholder="Optional reason..." class="w-full p-2 border rounded"></textarea>
    <div class="flex justify-end space-x-2 pt-4">
      <button type="button" onclick="closeLeaveModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit</button>
    </div>
  </form>
</div>
                    
                   

<!-- Scripts -->
<script>
flatpickr("#date_range", { mode: "range", dateFormat: "Y-m-d" });
flatpickr("#leaveDates", { mode: "range", dateFormat: "Y-m-d" });

function showSection(id) {
  ['dashboardView','requestView','scheduleView'].forEach(v => {
    const el = document.getElementById(v);
    if (el) el.classList.add('hidden');
  });
  document.getElementById(id).classList.remove('hidden');
}

function openLeaveModal() {
  const val = document.getElementById('leaveTypeDropdown').value;
  if (!val) return;
  document.getElementById('hiddenLeaveType').value = val;
  document.getElementById('leaveModal').classList.remove('hidden');
}

function closeLeaveModal() {
  document.getElementById('leaveModal').classList.add('hidden');
  document.getElementById('leaveTypeDropdown').value = '';
}

document.getElementById("leaveRequestForm").addEventListener("submit", function(e){
  e.preventDefault();
  fetch("submit_leave.php", {
    method: "POST",
    body: new FormData(this)
  }).then(res => res.json()).then(data => {
    alert(data.message);
    if (data.status === "success") {
      closeLeaveModal();
      this.reset();
    }
  }).catch(err => {
    alert("Error occurred.");
    console.error(err);
  });
});

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

</body>
</html>
</body>
</html>






 