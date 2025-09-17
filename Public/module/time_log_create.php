<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // Now you have both $user and $checklist available
} catch (PDOException $e) {
    // Handle errors gracefully
    echo "Database error: " . $e->getMessage();
    exit;
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
try {
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
        // The time logging functionality has been moved to a
