<?php
// time_log_create.php
// Handle schedule change requests - UPDATED FOR MONTHLY REQUESTS AND SCHEDULE SWITCH

include('../config/db.php');
include('../includes/session.php');
include('../includes/helpers.php');

// Check if user is logged in
$employee_id = $_SESSION['employee']['id'] ?? $_SESSION['user_id'] ?? null;
if (!$employee_id) {
    header("Location: ../login.php");
    exit;
}

// Handle Schedule Switch Request (NEW)
if (isset($_POST['submit_schedule_switch'])) {
    // Debug logging
    error_log("=== SCHEDULE SWITCH REQUEST RECEIVED ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    error_log("Session employee_id: " . $employee_id);
    
    $source_date = trim($_POST['source_date'] ?? '');
    $target_date = trim($_POST['target_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    error_log("Parsed - Employee: $employee_id, Source: $source_date, Target: $target_date");
    
    // Validation
    if (empty($source_date) || empty($target_date)) {
        error_log("ERROR: Missing dates");
        header("Location: employee_dashboard.php?schedule_switch=missing_dates#scheduleView");
        exit;
    }
    
    if ($source_date === $target_date) {
        header("Location: employee_dashboard.php?schedule_switch=same_dates#scheduleView");
        exit;
    }
    
    // Validate dates are not in the past
    $today = date('Y-m-d');
    if ($source_date < $today || $target_date < $today) {
        header("Location: employee_dashboard.php?schedule_switch=past_dates#scheduleView");
        exit;
    }
    
    if (empty($reason)) {
        header("Location: employee_dashboard.php?schedule_switch=no_reason#scheduleView");
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
            header("Location: employee_dashboard.php?schedule_switch=invalid_file#scheduleView");
            exit;
        }
        
        $new_filename = 'switch_' . $employee_id . '_' . time() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['attachment_scr']['tmp_name'], $target_path)) {
            $attachment_path = $target_path;
        } else {
            header("Location: employee_dashboard.php?schedule_switch=upload_failed#scheduleView");
            exit;
        }
    } else {
        header("Location: employee_dashboard.php?schedule_switch=no_attachment#scheduleView");
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
        error_log("SUCCESS: Inserted with ID: $insert_id");
        
        header("Location: employee_dashboard.php?schedule_switch=success#scheduleView");
        exit;
    } catch (PDOException $e) {
        error_log("DATABASE ERROR: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        header("Location: employee_dashboard.php?schedule_switch=error#scheduleView");
        exit;
    }
}

if (isset($_POST['submit_schedule_change'])) {
    // Use the same employee_id from session (already set at top of file)
    $request_type = $_POST['request_type'] ?? 'single_day';
    
    // Handle Monthly Schedule Request
    if ($request_type === 'monthly') {
        $schedule_month = trim($_POST['schedule_month'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($schedule_month)) {
            header("Location: employee_dashboard.php?schedule=no_month#scheduleView");
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
            header("Location: employee_dashboard.php?schedule=past_month#scheduleView");
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
            header("Location: employee_dashboard.php?schedule=no_schedule_selected#scheduleView");
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
                header("Location: employee_dashboard.php?schedule=invalid_file_type#scheduleView");
                exit;
            }
            
            $new_filename = 'monthly_schedule_' . $employee_id . '_' . $year . '_' . $month . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['attachment_scr']['tmp_name'], $target_path)) {
                $attachment_path = $target_path;
            } else {
                header("Location: employee_dashboard.php?schedule=upload_failed#scheduleView");
                exit;
            }
        } else {
            header("Location: employee_dashboard.php?schedule=no_attachment#scheduleView");
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
            
            header("Location: employee_dashboard.php?schedule=monthly_success#scheduleView");
            exit;
        } catch (PDOException $e) {
            error_log("Monthly schedule request error: " . $e->getMessage());
            header("Location: employee_dashboard.php?schedule=error#scheduleView");
            exit;
        }
    }
    
    // Handle Single Day Schedule Request (existing code)
    $reason = trim($_POST['reason'] ?? '');
    $work_schedule_id = !empty($_POST['work_schedule_id']) ? (int)$_POST['work_schedule_id'] : null;
    $is_rest_day = empty($work_schedule_id) ? 1 : 0;
    
    // Determine date range based on request type
    if ($request_type === 'monthly') {
        // Monthly request - entire month
        $target_month = $_POST['target_month'] ?? ''; // Format: YYYY-MM
        
        if (empty($target_month)) {
            header("Location: employee_dashboard.php?schedule=invalid_input#scheduleView");
            exit;
        }
        
        // Validate future month
        $selected_month = strtotime($target_month . '-01');
        $next_month = strtotime('first day of next month');
        
        if ($selected_month < $next_month) {
            header("Location: employee_dashboard.php?schedule=past_month#scheduleView");
            exit;
        }
        
        // Calculate start and end dates for the entire month
        $start_date = date('Y-m-01', $selected_month);
        $end_date = date('Y-m-t', $selected_month);
        
        // For monthly requests, work_schedule_id should be null (admin will set it)
        $work_schedule_id = null;
        $is_rest_day = 0; // Not a rest day request, it's a monthly schedule
        
    } else if ($request_type === 'date_range') {
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($start_date) || empty($end_date)) {
            header("Location: employee_dashboard.php?schedule=invalid_input#scheduleView");
            exit;
        }
        
    } else {
        // Single day (default)
        $start_date = $_POST['date_range'] ?? '';
        $end_date = $start_date;
        
        if (empty($start_date)) {
            header("Location: employee_dashboard.php?schedule=invalid_input#scheduleView");
            exit;
        }
    }
    
    // Validate dates
    if ($start_date < date('Y-m-d')) {
        header("Location: employee_dashboard.php?schedule=past_date#scheduleView");
        exit;
    }
    
    // Handle file upload
    $attachment_filename = null;
    if (isset($_FILES['attachment_scr']) && $_FILES['attachment_scr']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/schedule_attachments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['attachment_scr']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $attachment_filename = 'schedule_' . $employee_id . '_' . time() . '.' . $file_extension;
            move_uploaded_file($_FILES['attachment_scr']['tmp_name'], $upload_dir . $attachment_filename);
        }
    }
    
    // Insert schedule change request
    try {
        $stmt = $pdo->prepare("
            INSERT INTO schedule_change_requests 
            (employee_id, start_date, end_date, work_schedule_id, is_rest_day, reason, attachment_scr, request_type, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->execute([
            $employee_id,
            $start_date,
            $end_date,
            $work_schedule_id,
            $is_rest_day,
            $reason,
            $attachment_filename,
            $request_type
        ]);
        
        header("Location: employee_dashboard.php?schedule=success#scheduleView");
    } catch (PDOException $e) {
        error_log("Schedule request error: " . $e->getMessage());
        header("Location: employee_dashboard.php?schedule=error#scheduleView");
    }
    exit;
}

// ...existing code for other actions (if any)...
?>