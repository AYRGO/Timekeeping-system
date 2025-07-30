<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db.php');

// Check if user is logged in
if (!isset($_SESSION['employee']['id'])) {
    header('Location: ../login.php');
    exit;
}

$employee_id = $_SESSION['employee']['id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload failed with error code: ' . $file['error'];
        echo json_encode($response);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $response['message'] = 'Only JPEG, PNG, and GIF files are allowed.';
        echo json_encode($response);
        exit;
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        $response['message'] = 'File size must be less than 5MB.';
        echo json_encode($response);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/profile_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $employee_id . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Get current profile picture to delete old one
    $stmt = $pdo->prepare("SELECT profile_picture FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $currentPicture = $stmt->fetchColumn();
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Update database
        $stmt = $pdo->prepare("UPDATE employees SET profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$filename, $employee_id])) {
            // Delete old profile picture if it exists
            if ($currentPicture && file_exists($uploadDir . $currentPicture)) {
                unlink($uploadDir . $currentPicture);
            }
            
            $response['success'] = true;
            $response['message'] = 'Profile picture updated successfully!';
            $response['filename'] = $filename;
        } else {
            $response['message'] = 'Database update failed.';
            // Clean up uploaded file
            unlink($uploadPath);
        }
    } else {
        $response['message'] = 'Failed to move uploaded file.';
    }
} else {
    $response['message'] = 'No file uploaded.';
}

// If this is an AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Regular form submission - redirect back with message
    $_SESSION['upload_message'] = $response['message'];
    $_SESSION['upload_success'] = $response['success'];
    header('Location: ../dashboard.php');
}
exit;
?>
