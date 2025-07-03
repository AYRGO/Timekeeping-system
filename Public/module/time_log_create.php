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
        'sick'         => 'SL',
        'vacation'     => 'VL',
        'paternity'    => 'Paternity',
        'maternity'    => 'Maternity',
        'solo_parent'  => 'SPL',
        'halfday'      => 'Half_VL',
        'halfday_sick' => 'Half_SL',
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

    // File upload
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

    // Insert into DB
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
<div id="messageBox" class="hidden"></div>

<body class="flex min-h-screen overflow-x-hidden">

<!-- Sidebar -->
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
                <span class="text-lg">Home</span>
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

        </nav>
    </div>
    <!-- Bottom Section: Settings and Logout -->
    <div class="border-t border-gray-300 mt-6 pt-4">
        <div class="flex flex-col space-y-1">
            <form method="POST" class="w-full">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="logout" class="flex items-center space-x-2 p-2 rounded-lg hover:bg-red-100 w-full text-left text-red-600 font-semibold">
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
        <button class="relative text-gray-600 hover:text-gray-800 focus:outline-none notification-button" onclick="toggleModal()">
            <i class="fas fa-bell text-xl"></i>
            <span class="absolute -top-1 -right-1 inline-block w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

        <!-- Notification Modal -->
        <?php include 'notification_modal.php'; ?>

        <div class="flex items-center space-x-3">
            <div class="w-px h-6 bg-gray-300 mx-2"></div>
            <span class="text-gray-700 font-medium"><?= htmlspecialchars($fname . ' ' . $lname) ?></span>
            <button class="ml-2 text-gray-600 hover:text-gray-800 focus:outline-none">
            </button>
        </div>
    </div>
</header>


<div id="dashboardView" class="mt-20">
<?php
// Fetch the number of announcements
$stmt = $pdo->query("SELECT COUNT(announcement_id) AS total_announcements FROM announcements");
$announcementCount = $stmt->fetchColumn();
?>
<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-green-600 to-green-800 rounded-2xl p-10 text-white mb-8 shadow-2xl">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-4xl font-bold mb-2">Welcome back, <?= htmlspecialchars($fname) ?> 👋</h1>
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
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php 
        include 'stats/available_leave.php';
        include 'stats/upcoming_payday.php';
        include 'stats/pending_requests.php';
        include 'stats/schedule_tracker.php';
    ?>
</div>

<div class="flex flex-col md:flex-row gap-6 items-start md:items-stretch">

    <!-- Quick Actions - Refined Style -->
    <div class="bg-white rounded-2xl shadow-lg p-6 w-full md:w-1/2 border border-gray-200">
        <!-- Header -->
        <h3 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 bg-yellow-100 p-2 rounded-full mr-3"></i>
            Quick Actions
        </h3>

        <!-- Actions Grid -->
        <div class="grid grid-cols-2 gap-4">
            <!-- Request Leave -->
            <button onclick="showSection('requestView')" class="flex flex-col items-center justify-center p-4 bg-green-50 hover:bg-green-100 rounded-xl border border-green-100 shadow-sm hover:shadow transition">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-300 to-green-500 text-white flex items-center justify-center mb-2">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <span class="text-sm font-medium text-center text-gray-700">Request Leave</span>
            </button>

            <!-- Change Schedule -->
            <button onclick="showSection('scheduleView')" class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl border border-blue-100 shadow-sm hover:shadow transition">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-300 to-blue-500 text-white flex items-center justify-center mb-2">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <span class="text-sm font-medium text-center text-gray-700">Request Change Schedule</span>
                
            </button>
            </button>

            <!-- Profile -->
            <button onclick="showSection('profileView')" class="flex flex-col items-center justify-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-xl border border-yellow-100 shadow-sm hover:shadow transition">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 text-white flex items-center justify-center mb-2">
                    <i class="fas fa-user"></i>
                </div>
                <span class="text-sm font-medium text-center text-gray-700">Profile</span>
            </button>
        </div>
    </div>
    
    <!-- Today's Attendance - Enhanced Sprout Style -->
<div class="bg-white rounded-2xl shadow-lg p-6 w-full md:w-2/3 mx-auto border border-gray-200">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-user-clock mr-3 text-green-500 bg-green-100 p-2 rounded-full"></i>
            Today's Time Log
        </h3>
        <span id="dashboardClock" class="text-sm font-mono text-gray-500 tracking-wide">--:--:-- --</span>
    </div>

    <!-- Time Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Time In -->
        <div class="flex items-center p-5 rounded-xl border border-green-200 bg-green-50 shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-green-300 to-green-500 text-white mr-4">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time In</p>
                <p class="text-xl font-bold text-gray-800">
                    <?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?>
                </p>
            </div>
        </div>

        <!-- Time Out -->
        <div class="flex items-center p-5 rounded-xl border border-yellow-200 bg-yellow-50 shadow-inner hover:shadow transition">
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-tr from-yellow-300 to-yellow-500 text-white mr-4">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div>
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="text-xl font-bold text-gray-800">
                    <?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?>
                </p>
            </div>
        </div>
    </div>

<!-- Action Button -->
<form method="POST" class="mt-6">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <?php if (!$time_in): ?>
        <button type="submit" name="time_in"
            onclick="return confirm('Are you sure you want to log Time In?')"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
            Log Time In
        </button>
    
    <?php elseif ($time_in && !$time_out): ?>
        <button type="submit" name="time_out"
            onclick="return confirm('Are you sure you want to log Time Out?')"
            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
            Log Time Out
        </button>
    <?php else: ?>
        <button type="button" disabled
            class="w-full bg-gray-300 text-white font-semibold py-3 px-4 rounded-xl cursor-not-allowed">
            Already Logged
        </button>
    <?php endif; ?>

    <!-- Request Time Adjustment as Centered Simple Text Link -->
    <!--<div class="mt-2 text-center">-->
    <!--    <a href="request_time_adjustment.php"-->
    <!--       onclick="return confirm('Are you requesting a time adjustment because you forgot to time in?')"-->
    <!--       class="text-sm text-blue-600 hover:underline">-->
    <!--        Request Time Adjustment-->
    <!--    </a>-->
    <!--</div>-->
</form>
</div>



</div>
</div>
</div>
     <!-- Request Change Schedule -->
<div id="scheduleView" class="hidden mt-32">
    <div class="flex justify-center items-center min-h-[60vh] px-4">
        <div class="max-w-xl w-full bg-white p-6 rounded-xl shadow-lg border border-green-200">

            <!-- Header -->
            <div class= "bg-green-600 p-4 rounded-lg mb-6 shadow">
                <h2 class="text-2xl font-semibold text-center text-white">
                    Request Change of Work Schedule
                </h2>
            </div>

            <!-- Form Start -->
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
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        <option value="" disabled selected>Select new work hours</option>
                        <?php 
                        $allowed = [4, 5, 6, 7, 8];
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

                <!-- Attachment Upload -->
                <div>
                    <label for="attachment_scr" class="block text-sm font-medium text-gray-700 mb-1">
                        Attachment
                    </label>
                    <input type="file" name="attachment_scr" id="attachment_scr" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold text-lg">
                    Submit Request
                </button>
            </form>
            <!-- Form End -->

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
      </div>
    </div>

    <!-- Main Profile Info -->
    <div class="md:col-span-2 space-y-10">

      <!-- Personal Info -->
      <div class="bg-white p-8 rounded-2xl shadow space-y-8">
        <div class="flex justify-between items-center">
          <h4 class="text-2xl font-semibold text-gray-800">My Profile</h4>
          <button onclick="openEditModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Edit</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-base text-gray-700">
          <div><strong>First Name:</strong> <?= htmlspecialchars($fname) ?></div>
          <div><strong>Last Name:</strong> <?= htmlspecialchars($lname) ?></div>
          <div><strong>Email Address:</strong> <?= htmlspecialchars($email) ?></div>
          <div><strong>Mobile Number:</strong> <?= htmlspecialchars($contact) ?></div>
          <div><strong>Position:</strong> <?= htmlspecialchars($position) ?></div>
          <div><strong>Company:</strong> <?= htmlspecialchars($company) ?></div>
        </div>
      </div>

    </div> <!-- End of md:col-span-2 -->
  </div> <!-- End of grid -->
</div> <!-- ✅ Properly closed profileView -->

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-11/12 md:w-1/3">
    <h4 class="text-xl font-semibold mb-4">Edit Profile</h4>
    <form id="edit-profile-form" method="POST" action="tess.php">
      <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>
      <div class="mb-4">
        <label for="contact" class="block text-sm font-medium text-gray-700">Mobile Number</label>
        <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($contact) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>
      <div class="mb-4">
        <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
        <input type="text" id="position" name="position" value="<?= htmlspecialchars($position) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>
      <div class="mb-4">
        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
        <input type="text" id="company" name="company" value="<?= htmlspecialchars($company) ?>" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
      </div>
      <div class="flex justify-end">
        <button type="button" onclick="closeEditModal()" class="mr-2 bg-gray-300 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">Save Changes</button>
      </div>
    </form>
  </div>
</div>



<!-- Attendance View -->
<div id="attendanceView" class="hidden mt-32 px-4">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-md space-y-8">
        <h2 class="text-3xl font-bold text-gray-800 text-center">Today's Attendance</h2>

        <!-- Current Time -->
        <div class="flex justify-center items-center text-gray-700 text-lg md:text-xl">
            <span class="font-semibold">Current Time:</span>
            <span id="clock" class="ml-3 font-mono tracking-widest text-gray-800">--:--:-- --</span>
        </div>

        <!-- Time Log Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Time In -->
            <div class="bg-green-100 p-6 rounded-lg text-center shadow-md hover:shadow-lg transition">
                <p class="text-sm text-gray-600">Time In</p>
                <p class="text-3xl font-bold text-green-700">
                    <?= $time_in ? date("h:i A", strtotime($time_in)) : '—'; ?>
                </p>
            </div>

            <!-- Time Out -->
            <div class="bg-yellow-100 p-6 rounded-lg text-center shadow-md hover:shadow-lg transition">
                <p class="text-sm text-gray-600">Time Out</p>
                <p class="text-3xl font-bold text-yellow-700">
                    <?= $time_out ? date("h:i A", strtotime($time_out)) : '—'; ?>
                </p>
            </div>
        </div>

        <!-- Action Button -->
        <form method="POST" class="flex justify-center">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <?php if (!$time_in): ?>
                <button type="submit" name="time_in"
                    class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition duration-200 w-full max-w-xs">
                    Log Time In
                </button>
            <?php elseif ($time_in && !$time_out): ?>
                <button type="submit" name="time_out"
                    class="bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition duration-200 w-full max-w-xs">
                    Log Time Out
                </button>
            <?php else: ?>
                <button type="button" disabled
                    class="bg-gray-400 text-white px-6 py-3 rounded-lg w-full max-w-xs">
                    Already Logged
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- News Feed View -->
<div id="newsFeedView" class="hidden px-4 mt-12 space-y-10 max-w-4xl mx-auto">
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

<!-- Request Leave -->
<div id="requestView" class="hidden mt-32">
    <div class="flex justify-center items-center min-h-[60vh] px-4">
        <div class="max-w-xl w-full bg-white p-6 rounded-xl shadow-lg border border-green-200">

            <!-- Header -->
            <div class="bg-green-600 p-4 rounded-lg mb-6 shadow">
                <h2 class="text-2xl font-semibold text-center text-white">Request Leave</h2>
            </div>

            <!-- Form Start -->
            <form id="leaveRequestForm" action="time_log_create.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- Leave Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                    <select name="leaveType" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <input type="file" name="attachment_lr" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition duration-200 font-semibold text-lg">
                    Submit Request
                </button>
            </form>

            <!-- Message Box (for JS response) -->
            <div id="messageBox" class="hidden mt-4 p-2 text-center text-white rounded"></div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Initialize flatpickr for date range
    flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d"
    });

    // Confirmation before submitting leave
    const leaveForm = document.getElementById('leaveRequestForm');
    if (leaveForm) {
        leaveForm.addEventListener('submit', function (e) {
            if (!confirm("Are you sure you want to submit this leave request?")) {
                e.preventDefault();
            }
        });
    }

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
            invalid_dates: "Invalid leave date range submitted."
        },
        schedule_change: {
            success: "Schedule change request submitted successfully!"
        }
    };

    for (const [key, messages] of Object.entries(alerts)) {
        const value = urlParams.get(key);
        if (value && messages[value]) {
            alert(messages[value]);
        }
    }

    // Remove query parameters from URL after alert
    if (urlParams.has('leave_request') || urlParams.has('schedule_change')) {
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
    ['dashboardView', 'requestView', 'scheduleView', 'attendanceView', 'profileView' , 'newsFeedView']
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
</body>
</html>
