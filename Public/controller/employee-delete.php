<?php
session_start();

include('../config/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Check CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die('Invalid CSRF token.');
    }

    // Validate ID
    if (!$id || !is_numeric($id)) {
        die('Invalid employee ID.');
    }

    // Delete employee
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Redirect back to employee list with success message (optional)
    header("Location: ../module/employee_list.php?msg=deleted");
    exit;
} else {
    // Reject non-POST requests
    header("HTTP/1.1 405 Method Not Allowed");
    exit;
}
?>
