<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$employee_role = $_SESSION['employee']['role'] ?? 'employee';

if (isset($_POST['switch_to_admin']) && $employee_role === 'internal') {
    $_SESSION['view_mode'] = 'admin';
    header("Location: ../views/admin_homepage.php");
    exit;
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

    // Check if checklist exists; if not, create a blank one
$stmt = $pdo->prepare("SELECT * FROM employee_checklist WHERE employee_id = ?");
$stmt->execute([$employee_id]);
$checklist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$checklist) {
        // Insert blank checklist for this employee
        $insert = $pdo->prepare("INSERT INTO employee_checklist (employee_id) VALUES (?)");
        $insert->execute([$employee_id]);

        // Re-fetch checklist after insertion
        $check_stmt->execute([$employee_id]);
        $checklist = $check_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Now you have both $user and $checklist available
} catch (PDOException $e) {
    // Handle errors gracefully
    echo "Database error: " . $e->getMessage();
    exit;
}




    
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leaveType'], $_POST['date_range'])) {
    require '../config/db.php';

    $employee_id = $_SESSION['employee']['id'] ?? null;

    if (!$employee_id) {
        header("Location: time_log_create.php?leave_request=unauthorized");
        exit;
    }

    $leaveTypeInput = trim($_POST['leaveType']);
    $reason = trim($_POST['reason'] ?? '');
    $dateRange = trim($_POST['date_range']);

    $leaveTypeMap = [
        'sick'         => 'sick',
        'vacation'     => 'vacation',
        'paternity'    => 'paternity',
        'maternity'    => 'Maternity',
        'solo_parent'  => 'solo_parent',
        'halfday'      => 'halfday',
        'halfday_sick' => 'halfday_sick',
        'lwop'         => 'LWOP',
        'bereavement'  => 'bereavement'
    ];

    if (!isset($leaveTypeMap[$leaveTypeInput])) {
        header("Location: time_log_create.php?leave_request=invalid_type");
        exit;
    }

    $leaveType = $leaveTypeMap[$leaveTypeInput];

    // Parse date range
    $dates = explode(' to ', $dateRange);
    $start = isset($dates[0]) ? trim($dates[0]) : null;
    $end = isset($dates[1]) ? trim($dates[1]) : $start;

    if (!$start || !$end || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        header("Location: time_log_create.php?leave_request=invalid_dates");
        exit;
    }

    // ✅ Check leave credits BEFORE inserting
    if ($leaveType !== 'LWOP') {
        $stmt = $pdo->prepare("
            SELECT balance FROM leave_credits 
            WHERE employee_id = :employee_id AND leave_type = :leave_type AND year = :year
        ");
        $stmt->execute([
            'employee_id' => $employee_id,
            'leave_type'  => $leaveType,
            'year'        => date('Y')
        ]);
        $creditRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $daysRequested = $startDate->diff($endDate)->days + 1; // Inclusive

        if (!$creditRow) {
            header("Location: time_log_create.php?leave_request=no_credit_record");
            exit;
        }

        if ($creditRow['balance'] < $daysRequested) {
            header("Location: time_log_create.php?leave_request=insufficient_credits");
            exit;
        }
    }

    // ✅ Handle File Upload
    $attachmentPath = null;
    if (isset($_FILES['attachment_lr']) && $_FILES['attachment_lr']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $fileTmp = $_FILES['attachment_lr']['tmp_name'];
        $fileType = mime_content_type($fileTmp);
        $fileName = $_FILES['attachment_lr']['name'];

        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../uploads/leave_attachments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeName = uniqid('lr_') . '.' . $ext;
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $attachmentPath = $safeName;
            }
        }
    }

    // ✅ Insert leave request
    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status, created_at, attachment_lr)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?)
    ");

    $success = $stmt->execute([
        $employee_id,
        $leaveType,
        $start,
        $end,
        $reason,
        $attachmentPath
    ]);

    if ($success) {
        header("Location: time_log_create.php?leave_request=success");
        exit;
    } else {
        die("DB Error: " . implode(" | ", $stmt->errorInfo()));
    }
}



         /// Schedule Change Request Check
if (isset($_POST['submit_schedule_change'])) {
    $employee_id = $_SESSION['employee']['id'] ?? null;
    $work_schedule_id = $_POST['work_schedule_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    $date_range = trim($_POST['date_range'] ?? '');

    if (!$employee_id || !$work_schedule_id || !is_numeric($work_schedule_id)) {
        header("Location: time_log_create.php?schedule_change=invalid_data");
        exit;
    }

    // Optional: Validate work_schedule_id exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM work_schedules WHERE id = ?");
    $stmt->execute([$work_schedule_id]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: time_log_create.php?schedule_change=invalid_schedule_id");
        exit;
    }

    // Determine start and end date
    if (strpos($date_range, ' to ') !== false) {
        [$start_date_raw, $end_date_raw] = explode(' to ', $date_range);
    } else {
        $start_date_raw = $end_date_raw = $date_range;
    }

    $start_date = date('Y-m-d', strtotime($start_date_raw));
    $end_date = date('Y-m-d', strtotime($end_date_raw));

    // Handle attachment (optional)
    $attachmentPath = null;
    if (isset($_FILES['attachment_scr']) && $_FILES['attachment_scr']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $fileTmp = $_FILES['attachment_scr']['tmp_name'];
        $fileType = mime_content_type($fileTmp);
        $fileName = $_FILES['attachment_scr']['name'];

        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../uploads/schedule_attachments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeName = uniqid('scr_') . '.' . $ext;
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $attachmentPath = $safeName;
            }
        }
    }

    // Save request
    $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
        (employee_id, work_schedule_id, reason, status, start_date, end_date, created_at, attachment_scr)
        VALUES (?, ?, ?, 'pending', ?, ?, NOW(), ?)");
    
    $stmt->execute([
        $employee_id,
        $work_schedule_id,
        $reason,
        $start_date,
        $end_date,
        $attachmentPath
    ]);

    header("Location: time_log_create.php?schedule_change=success");
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['type'] ?? '') === 'comment') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $announcement_id = intval($_POST['announcement_id'] ?? 0);
    $comment_content = trim($_POST['comment'] ?? '');
    $employee_id = $_SESSION['employee']['id'] ?? null;

    // Validate CSRF token
    if ($csrf_token !== ($_SESSION['csrf_token'] ?? '')) {
        die("Invalid CSRF token.");
    }

    // Validate logged-in user
    if (!$employee_id) {
        die("User not authenticated.");
    }

    // Validate announcement exists
    $stmt = $pdo->prepare("SELECT 1 FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$announcement_id]);
    if (!$stmt->fetchColumn()) {
        die("Announcement not found.");
    }

    // Prevent inserting blank comments
    if (!empty($comment_content)) {
        $insert = $pdo->prepare("
            INSERT INTO comments (announcement_id, employee_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $insert->execute([$announcement_id, $employee_id, $comment_content]);

        // Redirect to avoid form resubmission on refresh
        header("Location: time_log_create.php#newsFeedView");
        exit;
    }
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


$today = date('Y-m-d');
$overtimeEligible = false;

// Get today’s log
$todayLogStmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs WHERE employee_id = :id AND log_date = :today");
$todayLogStmt->execute(['id' => $employee_id, 'today' => $today]);
$todayLog = $todayLogStmt->fetch();

if ($todayLog && $todayLog['time_in'] && $todayLog['time_out']) {
    $timeIn = new DateTime($todayLog['time_in']);
    $timeOut = new DateTime($todayLog['time_out']);
    $diffInSeconds = $timeOut->getTimestamp() - $timeIn->getTimestamp();

    if ($diffInSeconds >= (7 * 3600 + 58 * 60)) { // 7hrs 58mins = 28680 secs
        $overtimeEligible = true;
    }
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
    background-color:rgb(15, 255, 131); /* Light green */
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
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden relative">

        <!-- Sidebar -->
       <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col fixed md:relative z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out fixed h-full z-40">


            <!-- Logo -->
            <div class="p-6 flex justify-center">
                <img src="../asset/RSS-logo-colour.png" alt="RSS Logo" class="w-32">
            </div>

            <hr class="border-t border-gray-300 w-full mb-4">

            <!-- Navigation -->
            <nav class="flex-1 px-4 space-y-2 overflow-y-auto">
                <a href="#" onclick="showSection('dashboardView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-tachometer-alt text-lg"></i>
                    <span class="text-base">Home</span>
                </a>
                <a href="#" onclick="showSection('newsFeedView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-newspaper text-lg"></i>
                    <span class="text-base">News Feed</span>
                </a>
                <a href="#" onclick="showSection('scheduleView');" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-green-100 text-gray-700">
                    <i class="fas fa-calendar-alt text-lg"></i>
                    <span class="text-base">Request Change Schedule</span>
                </a>
              <div class="space-y-1">
    <button onclick="toggleLeaveMenu()" class="flex items-center justify-between w-full p-2 rounded-lg hover:bg-green-100 text-gray-700">
        <span class="flex items-center space-x-3">
            <i class="fas fa-plane-departure text-lg"></i>
            <span class="text-base">Leave</span>
        </span>
        <i class="fas fa-chevron-down text-sm transition-transform" id="leaveMenuIcon"></i>
    </button>
    <div id="leaveSubmenu" class="pl-10 hidden space-y-1">
        <a href="#" onclick="showSection('requestView');" class="block p-2 rounded hover:bg-green-100 text-gray-700">
            Request Leave
        </a>
        <a href="#" onclick="showSection('leaveCreditsView');" class="block p-2 rounded hover:bg-green-100 text-gray-700">
            Leave Credits
        </a>
    </div>
</div>

            </nav>

           <hr class="border-t border-gray-300 w-full mt-4 mb-1">


            <!-- User Info -->
            <div class="px-4 py-3 flex items-center">
                <?php if ($profile_picture): ?>
                    <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover border border-gray-300 mr-3">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-green-600 flex items-center justify-center text-white font-bold text-xl border border-gray-300 mr-3">
                        <?= strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="flex flex-col">
                    <span class="font-semibold text-sm text-gray-800"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
                    <span class="text-xs text-gray-500"><?= htmlspecialchars($position) ?></span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Fixed Header -->
            <header class="fixed top-0 left-0 md:left-64 w-full md:w-[calc(100%-16rem)] bg-white shadow z-50 flex items-center justify-between px-4 md:px-8 py-4">
    <div class="flex items-center space-x-4">
        <!-- Hamburger button -->
    <!-- Hamburger only on mobile -->
   <button id="hamburgerBtn" class="md:hidden text-gray-600 mr-2">
  <i class="fas fa-bars text-xl"></i>
</button>


        <h1 class="text-2xl font-semibold text-gray-800">Employee Dashboard</h1>
    </div>

 <div class="flex items-center space-x-6">
      <button class="relative text-gray-600 hover:text-gray-800 focus:outline-none notification-button" onclick="toggleModal()">
        <i class="fas fa-bell text-xl"></i>
        <span class="absolute -top-1 -right-1 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
      </button>
                    
       

        <!-- Notification Modal -->
        <?php include 'notification_modal.php'; ?>

        <div class="flex items-center space-x-3 relative">
            <div class="w-px h-6 bg-gray-300 mx-2"></div>
            <span class="text-gray-700 font-medium"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
            <!-- Dropdown Button -->
            <button onclick="toggleUserDropdown()" class="ml-2 text-gray-600 hover:text-gray-800 focus:outline-none">
                <i class="fas fa-chevron-down"></i>
            </button>
<!-- Dropdown Menu -->
<div id="userDropdown" class="absolute right-0 top-12 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden">
  
  <!-- Profile Button -->
  <button onclick="showSection('profileView'); closeUserDropdown();" 
          class="flex items-center w-full px-4 py-3 text-gray-700 hover:bg-yellow-50 transition duration-150 ease-in-out border-b border-gray-100">
    <i class="fas fa-user mr-3 text-yellow-500 w-4 text-center"></i> 
    <span>Profile</span>
  </button>

  <!-- Switch to Admin Form (Conditional) -->
  <?php if (isset($employee_role) && $employee_role === 'internal'): ?>
    <form method="POST" class="w-full">
      <button type="submit" name="switch_to_admin" 
              class="flex items-center w-full px-4 py-3 text-blue-600 hover:bg-blue-50 transition duration-150 ease-in-out border-b border-gray-100">
        <i class="fas fa-sync-alt mr-3 text-blue-500 w-4 text-center"></i> 
        <span>Switch to Admin</span>
      </button>
    </form>
  <?php endif; ?>

  <!-- Logout Form -->
  <form method="POST" class="w-full">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button type="submit" name="logout" 
            class="flex items-center w-full px-4 py-3 text-red-600 hover:bg-red-50 transition duration-150 ease-in-out rounded-b-lg">
      <i class="fas fa-sign-out-alt mr-3 text-red-500 w-4 text-center"></i> 
      <span>Logout</span>
    </button>
  </form>
</div>

        </div>
    </div>
</header>
<script>
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
    // Close dropdown when clicking outside
    if (!dropdown.classList.contains('hidden')) {
        document.addEventListener('click', closeDropdownOnClickOutside);
    }
}
function closeUserDropdown() {
    document.getElementById('userDropdown').classList.add('hidden');
    document.removeEventListener('click', closeDropdownOnClickOutside);
}
function closeDropdownOnClickOutside(e) {
    const dropdown = document.getElementById('userDropdown');
    const btn = event.target.closest('button[onclick^="toggleUserDropdown"]');
    if (!dropdown.contains(e.target) && !btn) {
        closeUserDropdown();
    }
}
</script>

<main class="flex-1 pt-20 px-8 overflow-auto">
<div id="dashboardView" class="mt-20">
<?php
// Fetch the number of announcements
$stmt = $pdo->query("SELECT COUNT(announcement_id) AS total_announcements FROM announcements");
$announcementCount = $stmt->fetchColumn();
?>
<!-- Welcome Banner -->
<div class="rounded-2xl p-10 text-white mb-8 shadow-2xl -mt-10"
     style="background: linear-gradient(135deg, rgb(16, 185, 72) 0%, rgb(5, 101, 211) 100%);">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-5xl font-bold mb-2">Welcome back, <?= htmlspecialchars($fname) ?> 👋</h1>
            <p class="mb-6 text-lg">
                You have <?= (int)$announcementCount ?> announcement<?= $announcementCount == 1 ? '' : 's' ?>.
            </p>
            <button
                class="bg-white text-green-800 px-6 py-3 rounded-lg font-semibold hover:bg-opacity-90 transition text-base"
                onclick="showSection('newsFeedView')"
            >
                View News Feed
            </button>
        </div>
        <div class="hidden md:block">
            <img src="https://cdn-icons-png.flaticon.com/512/1903/1903162.png" alt="HR Illustration" class="h-40">
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6 mt-[-2.5rem] relative z-10">
    <?php 
        include 'stats/available_leave.php';
        include 'stats/upcoming_payday.php';
        include 'stats/pending_requests.php';
        include 'stats/schedule_tracker.php';
    ?>
</div>

    <div class="flex flex-col md:flex-row gap-6 items-start md:items-stretch">
        <?php include 'today_attendance_card.php'; ?>
        <?php include 'recent_activity_card.php'; ?>
    </div>
    <?php include 'attendance-history.php'; ?>
</div>

     <!-- Request Change Schedule -->
<div id="scheduleView" class="hidden mt-12">
  <div class="flex justify-center items-start min-h-[60vh] px-4">
    <div class="w-full max-w-xl">

      <!-- Floating Green Header -->
      <div class="bg-green-600 p-4 rounded-t-xl shadow-lg text-center">
        <h2 class="text-2xl font-semibold text-white">
          Request Change of Work Schedule
        </h2>
      </div>

      <!-- Card Pulled Up Under Header -->
      <div class="bg-white p-6 rounded-b-xl shadow-lg border border-green-200 -mt-1">
        <form method="POST" enctype="multipart/form-data" id="scheduleChangeForm" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="submit_schedule_change" value="1">

          <!-- Date Range -->
          <div>
            <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">
              Effective Date Range
            </label>
            <input type="text" name="date_range" id="date_range"
              class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
              placeholder="Choose date range" required>
          </div>

          <!-- New Work Hours -->
          <div>
            <label for="work_schedule_id" class="block text-sm font-medium text-gray-700 mb-1">
              New Work Hours
            </label>
            <select name="work_schedule_id" id="work_schedule_id"
              class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
              required>
              <option value="" disabled selected>Select new work hours</option>
              <?php 
              $allowed = [3, 4, 5, 6, 7, 8, 9, 10];
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
          <div>
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
              Reason
            </label>
            <textarea name="reason" id="reason" rows="3"
              class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
              placeholder="Explain your reason for the schedule change" required></textarea>
          </div>

<!-- Attachment -->
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1">Attachment <span class="text-red-500">*</span></label>
  <input 
    type="file" 
    name="attachment_scr" 
    accept=".pdf,.jpg,.jpeg,.png"
    required
    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
</div>

          <!-- Submit Button -->
          <button type="submit"
            class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold text-lg">
            Submit Request
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
<!-- Profile Section -->
<div id="profileView" class="hidden min-h-screen bg-gray-50 py-10 px-4">
  <div class="max-w-6xl mx-auto">

    <!-- Combined Profile Card -->
    <div class="bg-white p-8 rounded-2xl shadow space-y-8 mb-8">
      <!-- Header with Edit Button -->
      <div class="flex justify-between items-center">
        <h4 class="text-2xl font-semibold text-gray-800">My Profile</h4>
        <button onclick="openEditModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Edit</button>
      </div>

      <!-- Profile Picture and Info Section -->
      <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-8 items-start">
        
        <!-- Profile Picture Section -->
        <div class="flex flex-col items-center space-y-4">
          <div class="relative w-48 h-48 rounded-full overflow-hidden border-4 border-green-500 flex items-center justify-center">
            <?php if ($profile_picture): ?>
              <img src="../uploads/profile_images/<?= htmlspecialchars($profile_picture) ?>" class="w-full h-full object-cover">
            <?php else: ?>
              <div class="w-full h-full bg-gray-300 flex items-center justify-center text-6xl text-white">👤</div>
            <?php endif; ?>
            <div 
              class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-sm text-white opacity-0 hover:opacity-100 transition cursor-pointer"
              onclick="document.getElementById('fileInput').click()"
            >
              Change Photo
            </div>
          </div>
          <form action="upload_profile.php" method="POST" enctype="multipart/form-data">
            <input type="file" id="fileInput" name="profile_picture" class="hidden" onchange="this.form.submit()">
          </form>
          <div class="text-center">
            <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($fname . ' ' . $lname) ?></h3>
            <p class="text-gray-600"><?= htmlspecialchars($position) ?></p>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($company) ?></p>
          </div>
        </div>

        <!-- Profile Information Grid -->
        <div class="space-y-6">
          <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Personal Information</h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-base text-gray-700">
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">First Name</span>
              <span class="text-gray-800"><?= htmlspecialchars($fname) ?></span>
            </div>
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">Last Name</span>
              <span class="text-gray-800"><?= htmlspecialchars($lname) ?></span>
            </div>
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">Email Address</span>
              <span class="text-gray-800"><?= htmlspecialchars($email) ?></span>
            </div>
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">Mobile Number</span>
              <span class="text-gray-800"><?= htmlspecialchars($contact) ?></span>
            </div>
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">Position</span>
              <span class="text-gray-800"><?= htmlspecialchars($position) ?></span>
            </div>
            <div class="flex flex-col space-y-1">
              <span class="text-sm font-medium text-gray-500">Company</span>
              <span class="text-gray-800"><?= htmlspecialchars($company) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

<!-- Checklist Card -->
<div class="bg-white p-8 rounded-2xl shadow space-y-6">
  <h4 class="text-2xl font-semibold text-gray-800">201 Checklist</h4>

  <!-- RSS Documents -->
  <div class="mt-6">
    <h5 class="text-lg font-semibold text-gray-700 mb-2">RSS Documents</h5>
    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
      <?php
        $rssDocs = [
          'letter_offer' => 'Signed Letter of Offer',
          'employment_contract' => 'Signed Employment Contract'
        ];
        foreach ($rssDocs as $field => $label):
      ?>
      <li><?= $label ?>:
        <?php if (!empty($checklist[$field])): ?>
          <?php
            $files = is_array($checklist[$field]) ? $checklist[$field] : explode(',', $checklist[$field]);
            foreach ($files as $file):
              $file = trim($file);
              if ($file):
          ?>
            <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="text-blue-600 underline mr-2">View</a>
          <?php
              endif;
            endforeach;
          ?>
        <?php else: ?>
          <span class="text-red-600 font-medium">Not Uploaded</span>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Pre-Employment Requirements -->
  <div class="mt-6">
    <h5 class="text-lg font-semibold text-gray-700 mb-2">Pre-Employment Requirements</h5>
    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
      <?php
        $requirements = [
          'medical' => 'Medical',
          'nbi_clearance' => 'NBI Clearance',
          'diploma_tor' => 'Diploma/TOR',
          'psa' => 'PSA',
          'sss' => 'SSS',
          'tin' => 'TIN',
          'philhealth' => 'PhilHealth',
          'pagibig' => 'Pag-IBIG',
          'coe' => 'COE (Recent Employer)',
          'valid_id' => 'Valid ID',
          'valid_id_2' => 'Valid ID 2',
          'solo_parent_id' => 'Solo Parent ID',
        ];
        foreach ($requirements as $field => $label):
      ?>
      <li><?= $label ?>:
        <?php if (!empty($checklist[$field])): ?>
          <?php
            $files = is_array($checklist[$field]) ? $checklist[$field] : explode(',', $checklist[$field]);
            foreach ($files as $file):
              $file = trim($file);
              if ($file):
          ?>
            <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="text-blue-600 underline mr-2">View</a>
          <?php
              endif;
            endforeach;
          ?>
        <?php else: ?>
          <span class="text-red-600 font-medium">Not Uploaded</span>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

<!-- Employment Adjustment -->
<div class="mt-6">
  <h5 class="text-lg font-semibold text-gray-700 mb-2">Employment Adjustment Form</h5>
  <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
    <li>Adjustment Files:
      <?php if (!empty($checklist['employment_adjustment_form'])): ?>
        <?php
          $files = is_array($checklist['employment_adjustment_form']) 
            ? $checklist['employment_adjustment_form'] 
            : explode(',', $checklist['employment_adjustment_form']);
          foreach ($files as $file):
            $file = trim($file);
            if ($file):
        ?>
          <a href="../uploads/checklist/<?= htmlspecialchars($file) ?>" target="_blank" class="text-blue-600 underline mr-2">View</a>
        <?php
            endif;
          endforeach;
        ?>
      <?php else: ?>
        <span class="text-red-600 font-medium">Not Uploaded</span>
      <?php endif; ?>
    </li>
  </ul>
</div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-11/12 md:w-1/3">
    <h4 class="text-xl font-semibold mb-4">Edit Profile</h4>
    
    <form id="edit-profile-form" method="POST" action="update_profile.php">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <!-- First Name -->
      <div class="mb-4">
        <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
        <input type="text" id="fname" name="fname" value="<?= htmlspecialchars($fname) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Last Name -->
      <div class="mb-4">
        <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
        <input type="text" id="lname" name="lname" value="<?= htmlspecialchars($lname) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Email -->
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Mobile Number -->
      <div class="mb-4">
        <label for="contact" class="block text-sm font-medium text-gray-700">Mobile Number</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($contact) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Position -->
      <div class="mb-4">
        <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
        <input type="text" id="position" name="position" value="<?= htmlspecialchars($position) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Company -->
      <div class="mb-4">
        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
        <input type="text" id="company" name="company" value="<?= htmlspecialchars($company) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>

      <!-- Buttons -->
      <div class="flex justify-end">
        <button type="button" onclick="closeEditModal()" class="mr-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-11/12 md:w-1/3">
    <h4 class="text-xl font-semibold mb-4 text-gray-800">Confirm Update</h4>
    <p class="text-gray-600 mb-6">Are you sure you want to save the changes to your profile?</p>
    <div class="flex justify-end">
      <button onclick="closeConfirmModal()" class="mr-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
      <button onclick="submitProfileForm()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Yes, Save</button>
    </div>
  </div>
</div>

<!-- News Feed View -->
<div id="newsFeedView" class="hidden px-4 mt-12 space-y-10 max-w-7xl mx-auto">
  <?php include 'news_feed_content.php'; ?>
</div>

<!-- Comments Modal -->
<div id="commentsModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center px-4">
    <div class="bg-white rounded-lg shadow-lg max-w-xl w-full max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center border-b px-4 py-3">
            <h3 class="text-lg font-semibold text-gray-800">Comments</h3>
            <button onclick="closeCommentsModal()" class="text-gray-400 hover:text-red-600 text-xl">&times;</button>
        </div>

        <!-- Comments List -->
        <div id="modalCommentsContent" class="flex-1 overflow-y-auto px-4 py-3 space-y-4">
            <!-- Comments will be loaded here -->
        </div>

        <!-- Comment Input -->
        <form method="POST" action="#newsFeedView" class="border-t p-4">
            <input type="hidden" name="type" value="comment">
            <input type="hidden" name="announcement_id" id="modalAnnouncementId">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="flex items-start space-x-3">
                <!-- No default avatar -->
                <textarea name="comment" rows="1" required placeholder="Write a comment..."
                    class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm resize-none focus:ring-2 focus:ring-green-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end mt-2">
                <button type="submit"
                    class="bg-green-600 text-white px-4 py-1 rounded-full text-sm hover:bg-green-700 transition">
                    Post
                </button>
            </div>
        </form>
    </div>
</div>

<div id="leaveCreditsView" class="hidden">
    <?php include 'leave_credits.php'; ?>
</div>

<!-- Request Leave -->
<div id="requestView" class="hidden mt-12">
  <div class="flex justify-center items-start min-h-[60vh] px-4">
    <div class="w-full max-w-xl">

      <!-- Floating Green Header Outside the Card -->
      <div class="bg-green-600 p-4 rounded-t-xl shadow-lg text-center">
        <h2 class="text-2xl font-semibold text-white">Request Leave</h2>
      </div>

      <!-- Card Slightly Pulled Up -->
      <div class="bg-white p-6 rounded-b-xl shadow-lg border border-green-200 -mt-1">
        <form id="leaveRequestForm" action="time_log_create.php" method="POST" enctype="multipart/form-data" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

          <!-- Leave Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
            <select name="leaveType" id="leaveType" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
              <option value="" disabled selected>Select type</option>
              <?php
              $types = [
                'sick'          => 'Sick Leave (SL)',
                'vacation'      => 'Vacation Leave (VL)',
                'paternity'     => 'Paternity Leave',
                'maternity'     => 'Maternity Leave',
                'solo_parent'   => 'Solo Parent Leave (SPL)',
                'halfday'       => 'Half Day Vacation (Half_VL)',
                'halfday_sick'  => 'Half Day Sick (Half_SL)',
                'lwop'          => 'Leave Without Pay (LWOP)',
                'bereavement'   => 'Bereavement Leave',
              ];
              foreach ($types as $val => $label):
              ?>
                <option value="<?= $val ?>"><?= $label ?></option>
              <?php endforeach; ?>
            </select>

            <!-- Leave Credit Display -->
            <div id="leaveBalance" class="text-sm mt-2 text-gray-600 hidden"></div>
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Dates</label>
            <input type="text" name="date_range" id="date_range" placeholder="Choose date range"
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" required>
          </div>

          <!-- Reason -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
            <textarea name="reason" rows="3" placeholder="Enter reason..."
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"></textarea>
          </div>

          <!-- Attachment -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Attachment <span class="text-red-500">*</span></label>
            <input 
              type="file" 
              name="attachment_lr" 
              accept=".pdf,.jpg,.jpeg,.png"
              required
              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
          </div>
            
          <!-- Leave Balance Message -->
<div id="leaveBalanceDisplay" class="hidden mt-2 text-sm text-red-600 font-medium text-center"></div>

          <!-- Submit -->
<button type="submit" id="submitBtn"
  class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold text-lg">
  Submit Request
</button>
        </form>

        <!-- Message Box -->
        <div id="messageBox" class="hidden mt-4 p-2 text-center text-white rounded"></div>
      </div>
    </div>
  </div>
</div>

<?php
// PHP: Load leave credits for current user
$leaveCredits = [];
if (isset($_SESSION['employee']['id'])) {
    $empId = $_SESSION['employee']['id'];
    $stmt = $pdo->prepare("SELECT leave_type, balance FROM leave_credits WHERE employee_id = ? AND year = ?");
    $stmt->execute([$empId, date('Y')]);
    $leaveCredits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Make sure to pass leaveCredits from the backend
echo "<script>
  const leaveCredits = " . json_encode($leaveCredits ?? []) . ";
</script>";
}
?>


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Initialize flatpickr for date range
    flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates) {
            checkLeaveCredits(); // Check credits when dates change
        }
    });

    // Leave credit checking functionality
    const leaveTypeSelect = document.getElementById("leaveType");
    const leaveBalanceDisplay = document.getElementById("leaveBalanceDisplay");
    const submitBtn = document.getElementById("submitBtn");
    const dateRangeInput = document.getElementById("date_range");

    function checkLeaveCredits() {
        const selectedType = leaveTypeSelect.value;
        const dateRange = dateRangeInput.value;
        
        if (!selectedType) return;

        // Reset state
        leaveBalanceDisplay.classList.remove("hidden", "text-green-600", "text-red-600", "text-blue-600");
        enableSubmitButton();

        // Get available balance
        const balance = leaveCredits[selectedType] ?? 0;

        // Calculate requested days
        let requestedDays = 1;
        if (dateRange && dateRange.includes(' to ')) {
            const dates = dateRange.split(' to ');
            if (dates.length === 2) {
                const startDate = new Date(dates[0]);
                const endDate = new Date(dates[1]);
                const timeDiff = endDate.getTime() - startDate.getTime();
                requestedDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            }
        }

        // Handle different leave types
        if (selectedType === "lwop") {
            leaveBalanceDisplay.classList.add("text-blue-600");
            leaveBalanceDisplay.textContent = "ℹ️ Leave Without Pay doesn't require credits.";
        } else if (selectedType === "halfday" || selectedType === "halfday_sick") {
            requestedDays = requestedDays * 0.5; // Half days
            if (balance < requestedDays) {
                leaveBalanceDisplay.classList.add("text-red-600");
                leaveBalanceDisplay.textContent = `❌ Not enough credits! You need ${requestedDays} day(s) but only have ${balance} day(s).`;
                disableSubmitButton();
            } else {
                leaveBalanceDisplay.classList.add("text-green-600");
                leaveBalanceDisplay.textContent = `✅ You have ${balance} day(s) available. Requesting ${requestedDays} day(s).`;
            }
        } else {
            if (balance < requestedDays) {
                leaveBalanceDisplay.classList.add("text-red-600");
                leaveBalanceDisplay.textContent = `❌ Not enough credits! You need ${requestedDays} day(s) but only have ${balance} day(s).`;
                disableSubmitButton();
            } else {
                leaveBalanceDisplay.classList.add("text-green-600");
                leaveBalanceDisplay.textContent = `✅ You have ${balance} day(s) available. Requesting ${requestedDays} day(s).`;
            }
        }

        leaveBalanceDisplay.classList.remove("hidden");
    }

    function disableSubmitButton() {
        submitBtn.disabled = true;
        submitBtn.classList.remove("bg-green-600", "hover:bg-green-700");
        submitBtn.classList.add("opacity-50", "cursor-not-allowed", "bg-gray-400");
        submitBtn.textContent = "Insufficient Leave Credits";
    }

    function enableSubmitButton() {
        submitBtn.disabled = false;
        submitBtn.classList.remove("opacity-50", "cursor-not-allowed", "bg-gray-400");
        submitBtn.classList.add("bg-green-600", "hover:bg-green-700");
        submitBtn.textContent = "Submit Request";
    }

    // Event listeners
    leaveTypeSelect.addEventListener("change", function() {
        checkLeaveCredits();
    });

    // Confirmation before submitting leave
    const leaveForm = document.getElementById('leaveRequestForm');
    if (leaveForm) {
        leaveForm.addEventListener('submit', function (e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                alert("Cannot submit: Insufficient leave credits!");
                return false;
            }
            if (!confirm("Are you sure you want to submit this leave request?")) {
                e.preventDefault();
            }
        });
    }

    // ... keep all your existing code below this ...
    // Confirmation before submitting schedule change (if present)
    const scheduleForm = document.getElementById('scheduleChangeForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function (e) {
            if (!confirm("Are you sure you want to request a schedule change?")) {
                e.preventDefault();
            }
        });
    }

    // Show alerts based on query parameters
    const urlParams = new URLSearchParams(window.location.search);
    const alerts = {
        leave_request: {
            success: "Leave request submitted successfully!",
            invalid_dates: "Invalid leave date range submitted.",
            insufficient_credits: "❌ Insufficient leave credits for this request!",
            no_credit_record: "❌ No leave credit record found for this leave type!"
        },
        schedule_change: {
            success: "Schedule change request submitted successfully!"
        },
        overtime: {
            success: "Overtime request submitted successfully!",
            invalid_time_order: "End time must be after start time.",
            invalid_input: "Missing or invalid data. Please try again."
        }
    };

    for (const [key, messages] of Object.entries(alerts)) {
        const value = urlParams.get(key);
        if (value && messages[value]) {
            alert(messages[value]);
        }
    }

    // Remove query parameters from URL after alert
    if (['leave_request', 'schedule_change', 'overtime'].some(key => urlParams.has(key))) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Clock updater for all clock elements
    function updateAllClocks() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')} ${ampm}`;
        const clockIds = ['dashboardClock', 'clock'];
        clockIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = timeStr;
        });
    }
    updateAllClocks();
    setInterval(updateAllClocks, 1000);
});
// Section toggle
function showSection(id) {
    ['dashboardView', 'requestView', 'scheduleView', 'attendanceView', 'profileView' , 'newsFeedView', 'overtimeRequestView']
        .forEach(x => document.getElementById(x)?.classList.add('hidden'));
    document.getElementById(id)?.classList.remove('hidden');
}



function openCommentsModal(announcementId) {
    // Set hidden input field with announcement ID
    document.getElementById('modalAnnouncementId').value = announcementId;

    // Show the modal
    document.getElementById('commentsModal').classList.remove('hidden');

    // Clear previous content and show a loader
    const commentsContainer = document.getElementById('modalCommentsContent');
    commentsContainer.innerHTML = '<p class="text-gray-500 text-sm">Loading comments...</p>';

    // Fetch comments via AJAX
    fetch('fetch_comments.php?announcement_id=' + announcementId)
        .then(response => response.text())
        .then(data => {
            commentsContainer.innerHTML = data;
        })
        .catch(error => {
            commentsContainer.innerHTML = '<p class="text-red-500 text-sm">Failed to load comments.</p>';
            console.error('Error loading comments:', error);
        });
}

function closeCommentsModal() {
    document.getElementById('commentsModal').classList.add('hidden');
}
function openEditModal() {
    document.getElementById('edit-profile-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-profile-modal').classList.add('hidden');
}

    setTimeout(() => {
        const alerts = document.querySelectorAll('.mb-4.p-4');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68636c761c010c190e038444/1iv25vcme';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->

<script>
function showSection(sectionId) {
  // Hide all sections
  document.querySelectorAll('[id$="View"]').forEach(el => el.classList.add('hidden'));

  // Show the selected section
  document.getElementById(sectionId).classList.remove('hidden');
}
</script>

<script>
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');

    hamburgerBtn.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
</script>

<script>
  function toggleMobileMenu() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("hidden");
  }
</script>
<script>
function toggleLeaveMenu() {
    const submenu = document.getElementById('leaveSubmenu');
    const icon = document.getElementById('leaveMenuIcon');

    submenu.classList.toggle('hidden');
    icon.classList.toggle('rotate-180'); // Optional: rotate arrow icon
}
</script>
<script>
  const leaveTypeSelect = document.getElementById("leaveType");
  const leaveBalanceDisplay = document.getElementById("leaveBalanceDisplay");
  const submitBtn = document.getElementById("submitBtn");

  leaveTypeSelect.addEventListener("change", function () {
    const selectedType = this.value;
    const balance = leaveCredits[selectedType] ?? 0;

    // Reset state
    leaveBalanceDisplay.classList.remove("hidden", "text-green-600", "text-red-600");
    submitBtn.disabled = false;
    submitBtn.classList.remove("opacity-50", "cursor-not-allowed");

    if (selectedType === "lwop") {
      leaveBalanceDisplay.classList.add("text-green-600");
      leaveBalanceDisplay.textContent = "Leave Without Pay doesn't require credits.";
    } else if (balance < 1) {
      leaveBalanceDisplay.classList.add("text-red-600");
      leaveBalanceDisplay.textContent = "❌ Not enough leave credits for this leave type.";
      submitBtn.disabled = true;
      submitBtn.classList.add("opacity-50", "cursor-not-allowed");
    } else {
      leaveBalanceDisplay.classList.add("text-green-600");
      leaveBalanceDisplay.textContent = `✅ You have ${balance} day(s) available.`;
    }

    leaveBalanceDisplay.classList.remove("hidden");
  });
</script>

</body>
</html>
