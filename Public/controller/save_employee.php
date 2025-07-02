<?php
require_once '../config/db.php'; // Use your PDO connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname     = $_POST['fname'];
    $lname     = $_POST['lname'];
    $username  = $_POST['username'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $email     = $_POST['email'] ?? null;
    $contact   = $_POST['contact'] ?? null;
    $position  = $_POST['position'] ?? null;
    $status    = $_POST['status'] ?? 'Active';

    $stmt = $pdo->prepare("INSERT INTO employees 
        (fname, lname, username, password, email, contact, position, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $fname,
        $lname,
        $username,
        $password,
        $email,
        $contact,
        $position,
        $status
    ]);

    header("Location: employee_list.php?success=1");
    exit;
}
?>
