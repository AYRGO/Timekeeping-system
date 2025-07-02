<?php
session_start();
include('../config/db.php');

// Make sure the user is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $contact = trim($_POST['contact'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $company = trim($_POST['company'] ?? '');

    // Validate input
    if (!$email || empty($contact) || empty($position) || empty($company)) {
        $_SESSION['error'] = "All fields are required and must be valid.";
        header("Location: time_log_create.php");
        exit;
    }

    try {
        // Update employee record
        $stmt = $pdo->prepare("
            UPDATE employees 
            SET email = ?, contact = ?, position = ?, company = ?
            WHERE id = ?
        ");
        $stmt->execute([$email, $contact, $position, $company, $employee_id]);

        $_SESSION['success'] = "Profile updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    header("Location: time_log_create.php");
    exit;
} else {
    // Reject direct access without POST
    header("Location: time_log_create.php");
    exit;
}
