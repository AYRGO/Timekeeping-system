<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

// CSRF token check
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid CSRF token.");
}

// Get employee ID from session
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Sanitize and assign POST data
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$position = trim($_POST['position'] ?? '');
$company = trim($_POST['company'] ?? '');

// Basic validation (you can expand this)
if (!$fname || !$lname || !$email || !$contact || !$position || !$company) {
    die("All fields are required.");
}

// Update query using PDO
try {
    $stmt = $pdo->prepare("UPDATE employees SET 
        fname = ?, 
        lname = ?, 
        email = ?, 
        contact = ?, 
        position = ?, 
        company = ?
        WHERE id = ?");

    $stmt->execute([$fname, $lname, $email, $contact, $position, $company, $employee_id]);

    // Optionally update session values
    $_SESSION['employee']['fname'] = $fname;
    $_SESSION['employee']['lname'] = $lname;
    $_SESSION['employee']['email'] = $email;
    $_SESSION['employee']['contact'] = $contact;
    $_SESSION['employee']['position'] = $position;
    $_SESSION['employee']['company'] = $company;

    // Redirect or echo success
    header("Location: ../module/time_log_create.php?update=success");
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

