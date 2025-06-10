<?php
session_start();
include('../config/db.php');

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $upload_dir = '../uploads/profile_images/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowed_types)) {
        die("Invalid file type.");
    }

    if ($file['size'] > $max_size) {
        die("File too large. Max size is 2MB.");
    }

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $employee_id . '.' . $ext;
    $destination = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $stmt = $pdo->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$filename, $employee_id]);
        header("Location: ../time_log_create.php?upload=success");
        exit;
    } else {
        die("Failed to upload file.");
    }
} else {
    die("No file uploaded.");
}
