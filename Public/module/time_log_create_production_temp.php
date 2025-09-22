<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('../config/db.php');
// Temporarily commented out - upload csrf_helper.php to fix
// include('../config/csrf_helper.php');
date_default_timezone_set('Asia/Manila');

// Temporarily commented out - upload csrf_helper.php to fix
// Initialize CSRF protection
// init_csrf_protection(1800); // 30 minutes timeout

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

// REST OF YOUR FILE CONTINUES...
// (This is just showing the fix for the first 40 lines)