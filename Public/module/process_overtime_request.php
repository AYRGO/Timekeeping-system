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
    
    // Validate overtime hours numerically; then for RDOT compute server-side for source of truth
    $overtime_hours_num = is_null($overtime_hours) ? null : floatval($overtime_hours);
    if ($ot_type === 'Restday OT') {
        // Compute RDOT hours on server (actual worked minus 1h lunch)
        require_once 'time_logs_helper.php';
        $computed_rdot_hours = calculateOvertimeHours($time_in, $time_out, 'Restday OT');
        $overtime_hours_num = round(max(0, (float)$computed_rdot_hours), 2);
        error_log("Computed RDOT hours (server): {$overtime_hours_num} for time_in={$time_in} time_out={$time_out}");
    } else {
        // Non-RDOT: ensure provided hours is a positive number
        if (is_null($overtime_hours_num) || $overtime_hours_num <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid overtime hours are required']);
            exit;
        }
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
    if (!isOvertimeEligible($time_in, $time_out, $ot_type)) {
        $errorMessage = 'This time log is not eligible for overtime.';
        if ($ot_type === 'Restday OT') {
            $errorMessage .= ' Please ensure valid time in/out and at least some hours worked.';
        } else {
            $errorMessage .= ' Must work at least 8 hours and 30 minutes.';
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
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
        $overtime_hours_num,
        $reason_trimmed, // Use the trimmed version
        $time_in,
        $time_out,
        $ot_type,
        $attachmentPath
    ];

    error_log("Inserting data: " . print_r($insertData, true));

    $success = $stmt->execute($insertData);

    if ($success) {
        $requestId = $pdo->lastInsertId();
        error_log("Overtime request inserted successfully with ID: $requestId");
        
        // Get employee details for WhatsApp notification
        $empStmt = $pdo->prepare("
            SELECT CONCAT(fname, ' ', lname) as employee_name, company 
            FROM employees 
            WHERE id = ?
        ");
        $empStmt->execute([$employee_id]);
        $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
        
        // Send WhatsApp notification if employee is from Bugardi
        if ($employee && strtolower($employee['company']) === 'bugardi') {
            try {
                require_once '../Bugardi/quick-approval-system/whatsapp-notifications/CallMeBotWhatsApp.php';
                
                $whatsapp = new CallMeBotWhatsApp();
                
                // Prepare OT data for notification
                $otData = [
                    'employee_name' => $employee['employee_name'],
                    'date' => date('Y-m-d', strtotime($time_in)),
                    'start_time' => date('H:i:s', strtotime($time_in)),
                    'end_time' => date('H:i:s', strtotime($time_out)),
                    'duration_hours' => $overtime_hours_num,
                    'reason' => $reason_trimmed,
                    'position' => 'Staff' // You can modify this if position is available
                ];
                
                $whatsappResult = $whatsapp->sendOTNotification($requestId, $otData);
                
                if ($whatsappResult) {
                    error_log("✅ WhatsApp notification sent successfully for OT request #$requestId - " . $employee['employee_name']);
                } else {
                    error_log("❌ WhatsApp notification failed for OT request #$requestId - " . $employee['employee_name']);
                }
                
            } catch (Exception $e) {
                error_log("WhatsApp notification error for OT request #$requestId: " . $e->getMessage());
            }
        }
        
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
