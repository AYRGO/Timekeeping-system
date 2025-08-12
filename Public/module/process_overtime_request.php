<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Log all received data for debugging
error_log("POST data received: " . print_r($_POST, true));
error_log("FILES data received: " . print_r($_FILES, true));

try {
    $time_log_id = $_POST['time_log_id'] ?? null;
    $overtime_hours = $_POST['overtime_hours'] ?? null;
    $reason = $_POST['reason'] ?? '';
    $reason_trimmed = trim($reason);
    $time_in = $_POST['time_in'] ?? null;
    $time_out = $_POST['time_out'] ?? null;
    $ot_type = trim($_POST['ot_type'] ?? '');

    // Debug the reason field specifically
    error_log("Reason debugging:");
    error_log("Raw reason from POST: '" . $reason . "'");
    error_log("Trimmed reason: '" . $reason_trimmed . "'");
    error_log("Reason length: " . strlen($reason_trimmed));
    error_log("Is reason empty: " . (empty($reason_trimmed) ? 'YES' : 'NO'));

    // Enhanced validation with specific error messages
    if (empty($time_log_id)) {
        echo json_encode(['success' => false, 'message' => 'Time log ID is missing']);
        exit;
    }
    
    if (empty($overtime_hours) || $overtime_hours <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid overtime hours are required']);
        exit;
    }
    
    if (empty($reason_trimmed)) {
        echo json_encode(['success' => false, 'message' => 'Reason for overtime is required. Please provide a detailed explanation. (Received: "' . $reason . '")']);
        exit;
    }
    
    if (empty($time_in)) {
        echo json_encode(['success' => false, 'message' => 'Time in is missing']);
        exit;
    }
    
    if (empty($time_out)) {
        echo json_encode(['success' => false, 'message' => 'Time out is missing']);
        exit;
    }
    
    if (empty($ot_type)) {
        echo json_encode(['success' => false, 'message' => 'Overtime type is required']);
        exit;
    }

    // Verify the time log belongs to the employee
    $stmt = $pdo->prepare("SELECT * FROM time_logs WHERE id = ? AND employee_id = ?");
    $stmt->execute([$time_log_id, $employee_id]);
    $time_log = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$time_log) {
        echo json_encode(['success' => false, 'message' => 'Invalid time log or unauthorized access']);
        exit;
    }

    // Double-check OT eligibility on server side
    require_once 'time_logs_helper.php';
    if (!isOvertimeEligible($time_in, $time_out)) {
        echo json_encode(['success' => false, 'message' => 'This time log is not eligible for overtime. Must work at least 8 hours and 30 minutes.']);
        exit;
    }

    // Check if request already exists
    $stmt = $pdo->prepare("SELECT id FROM post_ot_requests WHERE time_log_id = ?");
    $stmt->execute([$time_log_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Overtime request already exists for this time log']);
        exit;
    }

    // Handle file upload with better error handling
    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $fileTmp = $_FILES['attachment']['tmp_name'];
        $fileType = mime_content_type($fileTmp);
        $fileName = $_FILES['attachment']['name'];

        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../uploads/overtime_attachments/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $safeName = uniqid('ot_') . '.' . $ext;
            $targetPath = $uploadDir . $safeName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $attachmentPath = $safeName;
                error_log("File uploaded successfully: " . $attachmentPath);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, JPG, and PNG files are allowed.']);
            exit;
        }
    } else {
        $uploadError = $_FILES['attachment']['error'] ?? 'No file uploaded';
        echo json_encode(['success' => false, 'message' => 'Supporting document is required. Upload error: ' . $uploadError]);
        exit;
    }

    // Insert overtime request into post_ot_requests table
    $stmt = $pdo->prepare("
        INSERT INTO post_ot_requests (employee_id, time_log_id, ot_duration, reason, time_in, time_out, ot_type, attachment, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");

    $insertData = [
        $employee_id,
        $time_log_id,
        $overtime_hours,
        $reason_trimmed, // Use the trimmed version
        $time_in,
        $time_out,
        $ot_type,
        $attachmentPath
    ];

    error_log("Inserting data: " . print_r($insertData, true));

    $success = $stmt->execute($insertData);

    if ($success) {
        error_log("Overtime request inserted successfully");
        echo json_encode(['success' => true, 'message' => 'Overtime request submitted successfully!']);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Database error: " . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'Failed to submit overtime request: ' . $errorInfo[2]]);
    }

} catch (Exception $e) {
    error_log("Exception in overtime processing: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
