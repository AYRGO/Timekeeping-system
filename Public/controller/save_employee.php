<?php
require_once '../config/db.php'; // Use your PDO connection file
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_admin_mutation('Employee creation is disabled in admin demo mode.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname     = trim($_POST['fname']);
    $lname     = trim($_POST['lname']);
    $username  = trim($_POST['username']);
    $password  = $_POST['password'];
    $email     = trim($_POST['email'] ?? '');
    $contact   = trim($_POST['contact'] ?? '');
    $position  = trim($_POST['position'] ?? '');
    $status    = $_POST['status'] ?? 'Active';

    // Validation array to collect errors
    $errors = [];

    // Validate required fields
    if (empty($fname)) $errors[] = "First name is required";
    if (empty($lname)) $errors[] = "Last name is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";

    // Username validation
    if (!empty($username)) {
        if (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long";
        }
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, underscores, and periods";
        }
    }

    // Email validation (format only, no duplicate check)
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Password strength validation
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    // Check for duplicates
    if (empty($errors)) {
        try {
            // Check for duplicate username
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already exists. Please choose a different username.";
            }

            // Check for duplicate email (only if email is provided)
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email address already exists. Please use a different email.";
                }
            }

        } catch (PDOException $e) {
            $errors[] = "Database error occurred. Please try again.";
            error_log("Database error in save_employee.php: " . $e->getMessage());
        }
    }

    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $errorString = implode("|", $errors);
        header("Location: ../module/employee_create.php?error=" . urlencode($errorString));
        exit;
    }

    // If validation passes, save the employee
    try {
        // Store password as plain text (no hashing)
        
        $stmt = $pdo->prepare("INSERT INTO employees 
            (fname, lname, username, password, email, contact, position, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $fname,
            $lname,
            $username,
            $password,
            !empty($email) ? $email : null,
            !empty($contact) ? $contact : null,
            !empty($position) ? $position : null,
            $status
        ]);

        header("Location: ../views/employee_list.php?success=1");
        exit;

    } catch (PDOException $e) {
        // Handle database insertion errors
        error_log("Database insertion error in save_employee.php: " . $e->getMessage());
        header("Location: ../module/employee_create.php?error=" . urlencode("Failed to create employee. Please try again."));
        exit;
    }
}
?>
