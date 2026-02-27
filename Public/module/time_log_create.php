<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent caching to ensure JSON fix is applied immediately
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
include('../config/db.php');
include('../config/csrf_helper.php');
date_default_timezone_set('Asia/Manila');

// Initialize CSRF protection
init_csrf_protection(1800); // 30 minutes timeout

// Enhanced session security
$session_timeout = 3600; // 1 hour timeout
$csrf_timeout = 1800; // 30 minutes CSRF token timeout

// Check if session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    session_start();
    header("Location: ../employee/login.php?expired=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically (every 15 minutes)
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 900)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Enhanced employee ID validation
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id || !is_numeric($employee_id)) {
    error_log("SECURITY: Invalid employee ID in session - " . ($employee_id ?? 'NULL'));
    session_unset();
    session_destroy();
    header("Location: ../employee/login.php?invalid_session=1");
    exit;
}

// Verify employee still exists and is active
try {
    $stmt = $pdo->prepare("SELECT id, status FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        error_log("SECURITY: Employee ID $employee_id not found in database");
        session_unset();
        session_destroy();
        header("Location: ../employee/login.php?employee_not_found=1");
        exit;
    }
    
    if ($employee['status'] !== 'active') {
        error_log("SECURITY: Inactive employee $employee_id attempted access");
        session_unset();
        session_destroy();
        header("Location: ../employee/login.php?account_inactive=1");
        exit;
    }
} catch (Exception $e) {
    error_log("SECURITY: Database error during employee validation - " . $e->getMessage());
    session_unset();
    session_destroy();
    header("Location: ../employee/login.php?db_error=1");
    exit;
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

    // Check if checklist exists; if not, create a blank one
    $stmt = $pdo->prepare("SELECT * FROM employee_checklist WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $checklist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$checklist) {
        // Insert blank checklist for this employee
        $insert = $pdo->prepare("INSERT INTO employee_checklist (employee_id) VALUES (?)");
        $insert->execute([$employee_id]);

        // Re-fetch checklist after insertion
        $stmt->execute([$employee_id]);
        $checklist = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Fetch today's time log and check for overnight shifts
    $stmt = $pdo->prepare("SELECT time_in, time_out FROM time_logs 
                           WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $current_date]);
    $time_log = $stmt->fetch();
    $time_in = $time_log['time_in'] ?? null;
    $time_out = $time_log['time_out'] ?? null;

    // Check for overnight shift if no regular log for today
    if (!$time_in) {
        $overnightStmt = $pdo->prepare("SELECT time_in, time_out, log_date FROM time_logs 
            WHERE employee_id = ? AND time_out IS NULL AND log_date < ?
            ORDER BY log_date DESC, id DESC 
            LIMIT 1");
        $overnightStmt->execute([$employee_id, $current_date]);
        $overnightLog = $overnightStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($overnightLog) {
            $time_in = $overnightLog['time_in'];
            $time_out = null; // Still open
        }
    }

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
        // Validate CSRF token using centralized function
        $csrf_validation = validate_csrf_token(true);
        
        if (!$csrf_validation['valid']) {
            error_log("CSRF validation failed for employee $employee_id: " . $csrf_validation['error']);
            handle_csrf_failure($csrf_validation['error'], 'time_log_create.php');
        }
        
        error_log("CSRF TOKEN VALID - Employee ID: $employee_id");

        // Rate limiting check (simple protection against rapid-fire requests)
        $rate_limit_key = "rate_limit_" . $employee_id;
        if (!isset($_SESSION[$rate_limit_key])) {
            $_SESSION[$rate_limit_key] = ['count' => 0, 'last_reset' => time()];
        }
        
        $rate_data = $_SESSION[$rate_limit_key];
        if ((time() - $rate_data['last_reset']) > 60) { // Reset every minute
            $rate_data = ['count' => 0, 'last_reset' => time()];
        }
        
        if ($rate_data['count'] > 15) { // Increased limit to 15 requests per minute
            error_log("SECURITY: RATE LIMIT EXCEEDED - Employee ID: $employee_id, Count: " . $rate_data['count']);
            header("Location: time_log_create.php?error=rate_limit_exceeded");
            exit;
        }
        
        $rate_data['count']++;
        $_SESSION[$rate_limit_key] = $rate_data;

        // REMOVED: Time In and Time Out handling - now handled by time_log_handler.php
        // The time logging functionality has been moved to a centralized handler

        // Leave requests
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

            // ✅ Credit restrictions removed - Allow all leave requests regardless of balance

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


// Handle Schedule Swap Request
if (isset($_POST['submit_schedule_swap'])) {
    $source_date = trim($_POST['source_date'] ?? '');
    $target_date = trim($_POST['target_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    // Debug logging
    error_log("=== SCHEDULE SWAP REQUEST RECEIVED ===");
    error_log("Employee ID: $employee_id");
    error_log("Source Date: $source_date");
    error_log("Target Date: $target_date");
    error_log("Reason: $reason");
    
    // Validation
    if (empty($source_date) || empty($target_date)) {
        error_log("ERROR: Missing dates");
        header("Location: time_log_create.php?schedule_swap=missing_dates#scheduleView");
        exit;
    }
    
    if ($source_date === $target_date) {
        error_log("ERROR: Same dates");
        header("Location: time_log_create.php?schedule_swap=same_dates#scheduleView");
        exit;
    }
    
    // Validate dates are not in the past
    $today = date('Y-m-d');
    if ($source_date < $today || $target_date < $today) {
        error_log("ERROR: Past dates");
        header("Location: time_log_create.php?schedule_swap=past_dates#scheduleView");
        exit;
    }
    
    if (empty($reason)) {
        error_log("ERROR: No reason");
        header("Location: time_log_create.php?schedule_swap=no_reason#scheduleView");
        exit;
    }
    
    // Handle file upload
    $attachment_path = null;
    if (isset($_FILES['attachment_scr']) && $_FILES['attachment_scr']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/schedule_switch/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['attachment_scr']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            error_log("ERROR: Invalid file type");
            header("Location: time_log_create.php?schedule_swap=invalid_file#scheduleView");
            exit;
        }
        
        $new_filename = 'switch_' . $employee_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['attachment_scr']['tmp_name'], $target_path)) {
            $attachment_path = $target_path;
            error_log("File uploaded: $target_path");
        } else {
            error_log("ERROR: File upload failed");
            header("Location: time_log_create.php?schedule_swap=upload_failed#scheduleView");
            exit;
        }
    } else {
        $upload_error = $_FILES['attachment_scr']['error'] ?? 'No file';
        error_log("ERROR: No attachment or upload error: $upload_error");
        header("Location: time_log_create.php?schedule_swap=no_attachment#scheduleView");
        exit;
    }
    
    // Insert into database
    try {
        error_log("Attempting database insert...");
        $stmt = $pdo->prepare("
            INSERT INTO schedule_switch_requests (
                employee_id, source_date, target_date, reason, attachment_path, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $result = $stmt->execute([
            $employee_id, 
            $source_date, 
            $target_date, 
            $reason, 
            $attachment_path
        ]);
        
        $insert_id = $pdo->lastInsertId();
        error_log("SUCCESS: Inserted schedule swap request with ID: $insert_id");
        
        header("Location: time_log_create.php?schedule_swap=success#scheduleView");
        exit;
    } catch (PDOException $e) {
        error_log("DATABASE ERROR: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        header("Location: time_log_create.php?schedule_swap=error#scheduleView");
        exit;
    }
}

         /// Schedule Change Request Check
if (isset($_POST['submit_schedule_change'])) {
    $employee_id = $_SESSION['employee']['id'] ?? null;
    $request_type = $_POST['request_type'] ?? 'single_day';
    
    if (!$employee_id) {
        header("Location: time_log_create.php?schedule_change=invalid_data");
        exit;
    }
    
    // Handle Monthly Schedule Request
    if ($request_type === 'monthly') {
        $schedule_month = trim($_POST['schedule_month'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($schedule_month)) {
            header("Location: time_log_create.php?schedule_change=no_month");
            exit;
        }
        
        // Parse year and month
        list($year, $month) = explode('-', $schedule_month);
        $year = (int)$year;
        $month = (int)$month;
        
        // Validate month is not in the past
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('n');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            header("Location: time_log_create.php?schedule_change=past_month");
            exit;
        }
        
        // Collect weekly schedule data
        $weekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $scheduleData = [];
        $hasAtLeastOneSchedule = false;
        
        foreach ($weekDays as $day) {
            $scheduleValue = $_POST[$day . '_schedule'] ?? '';
            
            if (!empty($scheduleValue)) {
                $hasAtLeastOneSchedule = true;
                if ($scheduleValue === 'rest_day') {
                    $scheduleData[$day] = [
                        'schedule_id' => null,
                        'is_rest_day' => 1
                    ];
                } else {
                    $scheduleData[$day] = [
                        'schedule_id' => (int)$scheduleValue,
                        'is_rest_day' => 0
                    ];
                }
            } else {
                $scheduleData[$day] = [
                    'schedule_id' => null,
                    'is_rest_day' => 0
                ];
            }
        }
        
        if (!$hasAtLeastOneSchedule) {
            header("Location: time_log_create.php?schedule_change=no_schedule_selected");
            exit;
        }
        
        // Handle file upload
        $attachment_path = null;
        if (isset($_FILES['attachment_scr']) && $_FILES['attachment_scr']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/schedule_requests/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['attachment_scr']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                header("Location: time_log_create.php?schedule_change=invalid_file_type");
                exit;
            }
            
            $new_filename = 'monthly_schedule_' . $employee_id . '_' . $year . '_' . $month . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment_scr']['tmp_name'], $target_path)) {
                $attachment_path = $target_path;
            } else {
                header("Location: time_log_create.php?schedule_change=upload_failed");
                exit;
            }
        } else {
            header("Location: time_log_create.php?schedule_change=no_attachment");
            exit;
        }
        
        // Insert into database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO month_weekly_schedule (
                    employee_id, year, month,
                    sunday_schedule_id, sunday_is_rest_day,
                    monday_schedule_id, monday_is_rest_day,
                    tuesday_schedule_id, tuesday_is_rest_day,
                    wednesday_schedule_id, wednesday_is_rest_day,
                    thursday_schedule_id, thursday_is_rest_day,
                    friday_schedule_id, friday_is_rest_day,
                    saturday_schedule_id, saturday_is_rest_day,
                    reason, attachment_path, status, created_at
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, 'pending', NOW()
                )
            ");
            
            $stmt->execute([
                $employee_id, $year, $month,
                $scheduleData['sunday']['schedule_id'], $scheduleData['sunday']['is_rest_day'],
                $scheduleData['monday']['schedule_id'], $scheduleData['monday']['is_rest_day'],
                $scheduleData['tuesday']['schedule_id'], $scheduleData['tuesday']['is_rest_day'],
                $scheduleData['wednesday']['schedule_id'], $scheduleData['wednesday']['is_rest_day'],
                $scheduleData['thursday']['schedule_id'], $scheduleData['thursday']['is_rest_day'],
                $scheduleData['friday']['schedule_id'], $scheduleData['friday']['is_rest_day'],
                $scheduleData['saturday']['schedule_id'], $scheduleData['saturday']['is_rest_day'],
                $reason, $attachment_path
            ]);
            
            header("Location: time_log_create.php?schedule_change=monthly_success");
            exit;
        } catch (PDOException $e) {
            error_log("Monthly schedule request error: " . $e->getMessage());
            header("Location: time_log_create.php?schedule_change=error");
            exit;
        }
    }
    
    // Handle Single Day Schedule Request (existing code)
    $work_schedule_id = $_POST['work_schedule_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    $date_range = trim($_POST['date_range'] ?? '');

    // Determine if this is a rest day request (empty work_schedule_id)
    $is_rest_day = empty($work_schedule_id);

    if (!$employee_id) {
        header("Location: time_log_create.php?schedule_change=invalid_data");
        exit;
    }

    // If NOT a rest day, validate work_schedule_id exists
    if (!$is_rest_day && !is_numeric($work_schedule_id)) {
        header("Location: time_log_create.php?schedule_change=invalid_data");
        exit;
    }

    // If NOT a rest day, validate schedule exists in database
    if (!$is_rest_day) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM work_schedules WHERE id = ?");
        $stmt->execute([$work_schedule_id]);
        if ($stmt->fetchColumn() == 0) {
            header("Location: time_log_create.php?schedule_change=invalid_schedule_id");
            exit;
        }
    }

    // Get current ACTUAL schedule ID from calendar (not just official schedule)
    // This ensures we capture the real schedule at the time of request
    $today = date('Y-m-d');
    $current_real_schedule_id = null;
    
    // PRIORITY 1: Check employee_daily_schedule_cache (actual calendar schedule)
    $cacheStmt = $pdo->prepare("
        SELECT work_schedule_id 
        FROM employee_daily_schedule_cache 
        WHERE employee_id = ? AND schedule_date = ?
        LIMIT 1
    ");
    $cacheStmt->execute([$employee_id, $today]);
    $cacheSchedule = $cacheStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cacheSchedule && $cacheSchedule['work_schedule_id']) {
        $current_real_schedule_id = $cacheSchedule['work_schedule_id'];
    }
    
    // PRIORITY 2: Check employee_default_schedules (weekly default)
    if (!$current_real_schedule_id) {
        $dayOfWeek = date('w', strtotime($today));
        $defaultStmt = $pdo->prepare("
            SELECT work_schedule_id 
            FROM employee_default_schedules 
            WHERE employee_id = ? AND day_of_week = ?
            LIMIT 1
        ");
        $defaultStmt->execute([$employee_id, $dayOfWeek]);
        $defaultSchedule = $defaultStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($defaultSchedule && $defaultSchedule['work_schedule_id']) {
            $current_real_schedule_id = $defaultSchedule['work_schedule_id'];
        }
    }
    
    // PRIORITY 3: Fallback to official schedule
    if (!$current_real_schedule_id) {
        $empStmt = $pdo->prepare("SELECT official_sched FROM employees WHERE id = ?");
        $empStmt->execute([$employee_id]);
        $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
        $current_real_schedule_id = $employee['official_sched'] ?? 4;
    }

    // Determine start and end date
    // Flatpickr sends dates in Y-m-d format by default
    if (empty($date_range)) {
        header("Location: time_log_create.php?schedule_change=no_date");
        exit;
    }
    
    if (strpos($date_range, ' to ') !== false) {
        [$start_date_raw, $end_date_raw] = explode(' to ', $date_range);
    } else {
        $start_date_raw = $end_date_raw = $date_range;
    }
    
    // Trim any whitespace
    $start_date_raw = trim($start_date_raw);
    $end_date_raw = trim($end_date_raw);
    
    // Validate dates are in Y-m-d format already (from flatpickr)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date_raw)) {
        $start_date = $start_date_raw;
    } else {
        // Fallback to strtotime if not in expected format
        $timestamp = strtotime($start_date_raw);
        $start_date = ($timestamp !== false) ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date_raw)) {
        $end_date = $end_date_raw;
    } else {
        // Fallback to strtotime if not in expected format
        $timestamp = strtotime($end_date_raw);
        $end_date = ($timestamp !== false) ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

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

    // Save request with current_work_schedule_id
    // If rest day, work_schedule_id will be NULL
    $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
        (employee_id, work_schedule_id, current_work_schedule_id, reason, status, start_date, end_date, created_at, attachment_scr, is_rest_day)
        VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW(), ?, ?)");
    
    $stmt->execute([
        $employee_id,
        $is_rest_day ? null : $work_schedule_id, // NULL if rest day
        $current_real_schedule_id, // Current schedule being used
        $reason,
        $start_date,
        $end_date,
        $attachmentPath,
        $is_rest_day ? 1 : 0 // Flag for rest day
    ]);

    // Redirect with appropriate message
    if ($is_rest_day) {
        header("Location: time_log_create.php?rest_day=success");
    } else {
        header("Location: time_log_create.php?schedule_change=success");
    }
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

        // Handle Overtime Request
        if (isset($_POST['action']) && $_POST['action'] === 'overtime_request') {
            $ot_type = $_POST['ot_type'] ?? '';
            $ot_date = $_POST['ot_date'] ?? '';
            $time_in = $_POST['time_in'] ?? '';
            $time_out = $_POST['time_out'] ?? '';
            $reason = trim($_POST['reason'] ?? '');

            // Validate inputs
            if (empty($ot_type) || empty($ot_date) || empty($time_in) || empty($time_out) || empty($reason)) {
                header("Location: time_log_create.php?overtime=invalid_input");
                exit;
            }

            // Validate time order
            $timeInDate = new DateTime($ot_date . ' ' . $time_in);
            $timeOutDate = new DateTime($ot_date . ' ' . $time_out);
            
            if ($timeOutDate <= $timeInDate) {
                $timeOutDate->add(new DateInterval('P1D')); // Add 1 day for next day overtime
            }

            // Calculate OT duration (subtract 8 hours)
            $diffSeconds = $timeOutDate->getTimestamp() - $timeInDate->getTimestamp();
            $totalHours = $diffSeconds / 3600;
            $otDuration = max(0, $totalHours - 8);

            if ($otDuration <= 0) {
                header("Location: time_log_create.php?overtime=no_overtime");
                exit;
            }

            // Find corresponding time log
            $stmt = $pdo->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND log_date = ? AND time_in = ? AND time_out = ?");
            $stmt->execute([$employee_id, $ot_date, $time_in, $time_out]);
            $timeLog = $stmt->fetch();

            $time_log_id = $timeLog['id'] ?? null;

            // Insert overtime request
            $stmt = $pdo->prepare("
                INSERT INTO new_ot_requests (employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, reason, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $success = $stmt->execute([
                $employee_id,
                $time_log_id,
                $time_in,
                $time_out,
                $otDuration,
                $ot_type,
                $reason
            ]);

            if ($success) {
                header("Location: time_log_create.php?overtime=success");
                exit;
            } else {
                header("Location: time_log_create.php?overtime=error");
                exit;
            }
        }

        // Handle Schedule Request
        if (isset($_POST['action']) && $_POST['action'] === 'schedule_request') {
            $schedule_date = $_POST['schedule_date'] ?? '';
            $request_type = $_POST['request_type'] ?? '';
            $new_schedule_id = $_POST['new_schedule_id'] ?? null;
            $reason = trim($_POST['reason'] ?? '');

            // Validate inputs
            if (empty($schedule_date) || empty($request_type) || empty($reason)) {
                header("Location: time_log_create.php?schedule=invalid_input#scheduleView");
                exit;
            }

            // Validate date is in the future
            if ($schedule_date < date('Y-m-d')) {
                header("Location: time_log_create.php?schedule=past_date#scheduleView");
                exit;
            }

            // Insert schedule change request
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO schedule_change_requests (employee_id, request_date, request_type, new_schedule_id, reason, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");

                $success = $stmt->execute([
                    $employee_id,
                    $schedule_date,
                    $request_type,
                    $new_schedule_id ?: null,
                    $reason
                ]);

                if ($success) {
                    header("Location: time_log_create.php?schedule=success#scheduleView");
                    exit;
                } else {
                    header("Location: time_log_create.php?schedule=error#scheduleView");
                    exit;
                }
            } catch (PDOException $e) {
                // If the table doesn't exist, handle gracefully
                error_log("Schedule request error: " . $e->getMessage());
                header("Location: time_log_create.php?schedule=error#scheduleView");
                exit;
            }
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
    
    // Calculate total hours worked (including lunch) and include days span
    $interval = $timeIn->diff($timeOut);
    $totalHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    
    // Only subtract 1 hour for lunch break if total hours is 8 or above
    $actualWorkedHours = $totalHours >= 8 ? max(0, $totalHours - 1) : $totalHours;
    
    // For regular OT, require 8.5 hours (8hrs 30mins) - consistent with helper function
    if ($actualWorkedHours >= 8.5) {
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
  /* Enhanced Sidebar Styles */
.nav-item {
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.nav-item.active {
    background: linear-gradient(135deg, rgb(34, 197, 94) 0%, rgb(59, 130, 246) 100%);
    color: white;
    transform: translateX(4px);
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
}

.nav-item.active .fas {
    color: white;
}

.submenu-item {
    margin-left: 1rem;
    border-left: 2px solid transparent;
    transition: all 0.2s ease;
}

.submenu-item:hover {
    border-left-color: rgb(34, 197, 94);
    transform: translateX(2px);
}

/* Smooth animations for submenu */
#leaveSubmenu {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 0;
    opacity: 0;
}

#leaveSubmenu:not(.hidden) {
    max-height: 200px;
    opacity: 1;
}

/* Icon hover effects */
.nav-item i {
    transition: all 0.2s ease;
}

.nav-item:hover i {
    transform: scale(1.1);
}

/* User card hover effect */
.user-info-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transition: all 0.3s ease;
}

.user-info-card:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    transform: translateY(-2px);
}

/* Scrollbar styling for sidebar */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #cbd5e1, #94a3b8);
    border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #94a3b8, #64748b);
}

/* Enhanced gradients */
.gradient-border {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid transparent;
    background-clip: padding-box;
}

.gradient-border::before {
    content: '';
    position: absolute;
    inset: 0;
    padding: 1px;
    background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
    border-radius: inherit;
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
}

/* Sidebar Active Navigation Style */
.nav-link {
    position: relative;
    transition: all 0.2s ease-in-out;
}


.nav-link.active-nav {
    background-color: #d1fae5 !important;
    color: #059669 !important;
    font-weight: 600;
    border-left: 4px solid #059669;
    padding-left: calc(0.75rem - 4px); /* Adjust padding to account for border */
    margin-left: 0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.nav-link:hover {
    background-color: #f0fdf4;
    transform: translateX(2px);
}

.nav-link.active-nav:hover {
    background-color: #d1fae5;
}

.nav-link:focus {
    outline: none;
    box-shadow: none;
}

.nav-link i {
    transition: transform 0.2s ease-in-out;
}

.nav-link:hover i {
    transform: scale(1.1);
}

/* Ensure nav container doesn't clip borders */
nav {
    overflow-x: visible !important;
}
</style>

<script>
// Essential functions needed by sidebar and other inline onclick handlers
// These must be defined before the HTML that uses them

function showSection(sectionId) {
  // Hide all sections
  document.querySelectorAll('[id$="View"]').forEach(el => el.classList.add('hidden'));

  // Show the selected section
  const targetSection = document.getElementById(sectionId);
  if (targetSection) {
    targetSection.classList.remove('hidden');
  }
  
  // Update active states in sidebar
  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active-nav', 'bg-green-100', 'text-green-700', 'border-l-4', 'border-green-600');
    link.classList.add('text-gray-700');
  });
  
  // Add active state to clicked link
  const activeLink = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
  if (activeLink) {
    activeLink.classList.add('active-nav', 'bg-green-100', 'text-green-700', 'border-l-4', 'border-green-600');
    activeLink.classList.remove('text-gray-700');
  }
  
  // If it's a leave submenu item, also highlight the parent
  if (sectionId === 'requestView' || sectionId === 'leaveCreditsView') {
    const leaveParent = document.querySelector('.nav-link[data-section="leaveMenu"]');
    if (leaveParent) {
      leaveParent.classList.add('active-nav', 'bg-green-100', 'text-green-700', 'border-l-4', 'border-green-600');
      leaveParent.classList.remove('text-gray-700');
    }
    // Make sure submenu is open
    const submenu = document.getElementById('leaveSubmenu');
    if (submenu) {
      submenu.classList.remove('hidden');
    }
  }
}

function toggleLeaveMenu() {
    const submenu = document.getElementById('leaveSubmenu');
    const icon = document.getElementById('leaveMenuIcon');

    if (submenu) submenu.classList.toggle('hidden');
    if (icon) icon.classList.toggle('rotate-180');
}

function toggleMobileMenu() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar) sidebar.classList.toggle("hidden");
}

// Set initial active state on page load
document.addEventListener('DOMContentLoaded', function() {
  // Check URL for view parameter
  const urlParams = new URLSearchParams(window.location.search);
  const viewParam = urlParams.get('view');
  
  // Show the requested view or default to dashboard
  if (viewParam === 'schedule') {
    showSection('scheduleView');
  } else {
    showSection('dashboardView');
  }
});
</script>

</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden relative">

        <?php include 'sidebar.php'; ?>

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

<?php
// Determine initial view based on URL parameter to prevent flash
$initialView = isset($_GET['view']) && $_GET['view'] === 'schedule' ? 'schedule' : 'dashboard';
?>

<main class="flex-1 pt-20 px-8 overflow-auto">
<div id="dashboardView" class="<?= $initialView === 'dashboard' ? 'mt-20' : 'hidden' ?>">
<?php
// Fetch the number of announcements
$stmt = $pdo->query("SELECT COUNT(announcement_id) AS total_announcements FROM announcements");
$announcementCount = $stmt->fetchColumn();
?>
<?php include 'welcome_banner.php'; ?>

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

<?php include 'schedule_change_form.php'; ?>


<?php include 'profile_section.php'; ?>

<!-- News Feed View -->
<div id="newsFeedView" class="hidden px-4 mt-12 space-y-10 max-w-6xl mx-auto">
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

<!-- Schedule Management View -->
<div id="scheduleView" class="<?= $initialView === 'schedule' ? 'mt-20' : 'hidden' ?>">
    <?php include 'schedule_content.php'; ?>
</div>

<?php include 'leave_request_form.php'; ?>

<?php include 'new_overtime.php'; ?>

<?php
// PHP: Load leave credits for current user
$leaveCredits = [];
if (isset($_SESSION['employee']['id'])) {
    $empId = $_SESSION['employee']['id'];
    
    // Fetch all leave credit types for the current year
    $stmt = $pdo->prepare("SELECT leave_type, balance FROM leave_credits WHERE employee_id = ? AND year = ?");
    $stmt->execute([$empId, date('Y')]);
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to key-value pairs for easier JavaScript access
    foreach ($credits as $credit) {
        $leaveCredits[$credit['leave_type']] = floatval($credit['balance']);
    }
    
    // Debug: Log what we found
    error_log("Leave Credits for Employee $empId: " . print_r($leaveCredits, true));
    
    // Ensure all expected leave types have entries (default to 0 if missing)
    $expectedTypes = ['sick', 'vacation', 'paternity', 'maternity', 'solo_parent', 'bereavement'];
    foreach ($expectedTypes as $type) {
        if (!isset($leaveCredits[$type])) {
            $leaveCredits[$type] = 0;
        }
    }

    // Make sure to pass leaveCredits from the backend
echo "<script>
  const leaveCredits = " . json_encode($leaveCredits) . ";
  console.log('Leave Credits loaded:', leaveCredits);
</script>";
} else {
    echo "<script>
  const leaveCredits = {};
  console.log('No employee session found');
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
        minDate: (() => {
            // Allow backtrack for 5 days
            const today = new Date();
            today.setDate(today.getDate() - 5);
            return today;
        })(),
        // Remove any disabling/blocking of days before today
        onChange: function(selectedDates) {
            checkLeaveCredits(); // Check credits when dates change
        }
    });

    // Leave credit checking functionality
    const leaveTypeSelect = document.getElementById("leaveType");
    const leaveBalanceDisplay = document.getElementById("leaveBalanceDisplay");
    const submitBtn = document.getElementById("leaveSubmitBtn");
    const dateRangeInput = document.getElementById("date_range");

    function checkLeaveCredits() {
        const selectedType = leaveTypeSelect.value;
        const dateRange = dateRangeInput.value;
        
        if (!selectedType) return;

        // Map halfday types to their base credit types
        let creditType = selectedType;
        let requiresCredits = false;
        
        if (selectedType === 'halfday') {
            creditType = 'vacation';
            requiresCredits = true;
        } else if (selectedType === 'halfday_sick') {
            creditType = 'sick';
            requiresCredits = true;
        } else if (selectedType === 'sick' || selectedType === 'vacation') {
            creditType = selectedType;
            requiresCredits = true;
        }

        // Get available balance for the correct credit type
        const balance = leaveCredits[creditType] ?? 0;
        
        // Debug logging
        console.log('Selected Type:', selectedType);
        console.log('Credit Type:', creditType);
        console.log('Requires Credits:', requiresCredits);
        console.log('Available Leave Credits:', leaveCredits);
        console.log('Balance for', creditType, ':', balance);

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

        // Adjust for half days
        if (selectedType === "halfday" || selectedType === "halfday_sick") {
            requestedDays = requestedDays * 0.5;
        }

        console.log('Requested Days:', requestedDays);

        // Show balance display
        leaveBalanceDisplay.classList.remove("hidden");
        
        // Get the inner container and elements
        const container = leaveBalanceDisplay.querySelector('div');
        const icon = container.querySelector('i');
        const textSpan = container.querySelector('span');

        // Handle different leave types
        if (!requiresCredits) {
            // Leave types that don't require credits (LWOP, Paternity, Maternity, etc.)
            container.className = "bg-blue-50 border border-blue-200 rounded-xl p-4";
            icon.className = "fas fa-info-circle text-blue-600 mr-3";
            textSpan.className = "text-blue-700 font-medium";
            textSpan.textContent = `ℹ️ This leave type doesn't require leave credits.`;
            enableSubmitButton();
        } else {
            // Leave types that require credits (VL, SL, Half_VL, Half_SL)
            const displayType = creditType === 'sick' ? 'Sick Leave (SL)' : 'Vacation Leave (VL)';
            
            if (balance >= requestedDays) {
                // Sufficient credits
                container.className = "bg-green-50 border border-green-200 rounded-xl p-4";
                icon.className = "fas fa-check-circle text-green-600 mr-3";
                textSpan.className = "text-green-700 font-medium";
                textSpan.textContent = `✅ ${displayType}: ${balance} day(s) available. Requesting ${requestedDays} day(s). After approval: ${(balance - requestedDays).toFixed(1)} day(s) remaining.`;
                enableSubmitButton();
            } else {
                // Insufficient credits - block submission
                container.className = "bg-red-50 border border-red-300 rounded-xl p-4";
                icon.className = "fas fa-times-circle text-red-600 mr-3";
                textSpan.className = "text-red-800 font-medium";
                textSpan.textContent = `❌ ${displayType}: ${balance} day(s) available. Requesting ${requestedDays} day(s). Insufficient credits - cannot submit request.`;
                disableSubmitButton();
            }
        }
    }

    function disableSubmitButton() {
        submitBtn.disabled = true;
        submitBtn.classList.remove("bg-green-600", "hover:bg-green-700");
        submitBtn.classList.add("opacity-50", "cursor-not-allowed", "bg-gray-400");
        const btnText = submitBtn.querySelector('span');
        if (btnText) {
            btnText.textContent = "Insufficient Credits";
        } else {
            submitBtn.textContent = "Insufficient Credits";
        }
    }

    function enableSubmitButton() {
        submitBtn.disabled = false;
        submitBtn.classList.remove("opacity-50", "cursor-not-allowed", "bg-gray-400");
        submitBtn.classList.add("bg-green-600", "hover:bg-green-700");
        const btnText = submitBtn.querySelector('span');
        if (btnText) {
            btnText.textContent = "Submit Leave Request";
        } else {
            submitBtn.textContent = "Submit Request";
        }
    }

    // Event listeners
    leaveTypeSelect.addEventListener("change", function() {
        checkLeaveCredits();
    });

    // Confirmation before submitting leave
    const leaveForm = document.getElementById('leaveRequestForm');
    if (leaveForm) {
        leaveForm.addEventListener('submit', function (e) {
            // No credit checks - always allow submission
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
        },
        rest_day: {
            success: "Day off request submitted successfully!"
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


<script>
    // Hamburger menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');

        if (hamburgerBtn && sidebar) {
            hamburgerBtn.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }
    });
</script>

<script>
// Auto-Accrual Background Processor (runs on employee dashboard too)
document.addEventListener('DOMContentLoaded', function() {
    // Function to check and process accruals
    function processAutoAccrual() {
        fetch('../module/process_auto_accrual.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.processed > 0) {
                console.log('✅ Auto-accrual processed:', data.processed, 'employees');
            }
        })
        .catch(error => {
            console.error('Auto-accrual check error:', error);
        });
    }
    
    // Run accrual check on page load
    processAutoAccrual();
    
    // Check periodically (every hour)
    setInterval(processAutoAccrual, 3600000);
});
</script>

</main>
</body>
</html>
