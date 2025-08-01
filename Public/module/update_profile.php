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

// Check if this is a password-only update
$password_only = isset($_POST['password_only']) && $_POST['password_only'] === '1';

// Sanitize and assign POST data
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$position = trim($_POST['position'] ?? '');
$company = trim($_POST['company'] ?? '');

// Password fields
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation based on update type
if ($password_only) {
    // For password-only updates, validate password fields only
    if (empty($current_password)) {
        die("Current password is required to change password.");
    }
    
    if (empty($new_password)) {
        die("New password is required.");
    }
    
    if (strlen($new_password) < 6) {
        die("New password must be at least 6 characters long.");
    }
    
    if ($new_password !== $confirm_password) {
        die("New password and confirm password do not match.");
    }
} else {
    // For profile updates, validate profile fields
    if (!$fname || !$lname || !$email || !$contact || !$position || !$company) {
        die("All required fields must be filled.");
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
}

// Password validation if any password field is provided (for mixed updates)
$password_change = false;
if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
    if (empty($current_password)) {
        die("Current password is required to change password.");
    }
    
    if (empty($new_password)) {
        die("New password is required.");
    }
    
    if (strlen($new_password) < 6) {
        die("New password must be at least 6 characters long.");
    }
    
    if ($new_password !== $confirm_password) {
        die("New password and confirm password do not match.");
    }
    
    // Verify current password
    try {
        $stmt = $pdo->prepare("SELECT password FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            die("User not found.");
        }
        
        // Debug: Check if password field exists and has value
        if (empty($user['password'])) {
            die("No password set for this account. Please contact administrator.");
        }
        
        // Try both password_verify (for hashed passwords) and direct comparison (for plain text)
        $password_valid = false;
        
        // First try password_verify for hashed passwords
        if (password_verify($current_password, $user['password'])) {
            $password_valid = true;
        } 
        // If that fails, try direct comparison for plain text passwords
        else if ($current_password === $user['password']) {
            $password_valid = true;
        }
        
        if (!$password_valid) {
            die("Current password is incorrect. Please check your password and try again.");
        }
        
        $password_change = true;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Update query using PDO
try {
    if ($password_only) {
        // Password-only update - get current profile data first
        $stmt = $pdo->prepare("SELECT fname, lname, email, contact, position, company FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_data) {
            die("User not found.");
        }
        
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update only password
        $stmt = $pdo->prepare("UPDATE employees SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $employee_id]);
        
        $message = "Password updated successfully!";
        
    } else if ($password_change) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update with password
        $stmt = $pdo->prepare("UPDATE employees SET 
            fname = ?, 
            lname = ?, 
            email = ?, 
            contact = ?, 
            position = ?, 
            company = ?,
            password = ?
            WHERE id = ?");

        $stmt->execute([$fname, $lname, $email, $contact, $position, $company, $hashed_password, $employee_id]);
        
        // Update session values
        $_SESSION['employee']['fname'] = $fname;
        $_SESSION['employee']['lname'] = $lname;
        $_SESSION['employee']['email'] = $email;
        $_SESSION['employee']['contact'] = $contact;
        $_SESSION['employee']['position'] = $position;
        $_SESSION['employee']['company'] = $company;
        
        $message = "Profile and password updated successfully!";
        
    } else {
        // Update without password
        $stmt = $pdo->prepare("UPDATE employees SET 
            fname = ?, 
            lname = ?, 
            email = ?, 
            contact = ?, 
            position = ?, 
            company = ?
            WHERE id = ?");

        $stmt->execute([$fname, $lname, $email, $contact, $position, $company, $employee_id]);
        
        // Update session values
        $_SESSION['employee']['fname'] = $fname;
        $_SESSION['employee']['lname'] = $lname;
        $_SESSION['employee']['email'] = $email;
        $_SESSION['employee']['contact'] = $contact;
        $_SESSION['employee']['position'] = $position;
        $_SESSION['employee']['company'] = $company;
        
        $message = "Profile updated successfully!";
    }

    // Redirect with success message
    header("Location: ../module/time_log_create.php?update=success&message=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

