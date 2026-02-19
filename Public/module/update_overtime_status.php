<?php
session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Check if user is authorized (admin/manager)
if (!isset($_SESSION['employee']) || !in_array($_SESSION['employee']['role'], ['admin', 'manager', 'HR'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $request_id = $_POST['request_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $approved_by = $_SESSION['employee']['id'];
    
    // Validate inputs
    if (!$request_id || !in_array($new_status, ['approved', 'declined'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update the overtime request status
    $updateStmt = $pdo->prepare("
        UPDATE post_ot_requests 
        SET status = ?, approved_by = ?, approved_at = NOW() 
        WHERE id = ?
    ");
    
    $updateResult = $updateStmt->execute([$new_status, $approved_by, $request_id]);
    
    if (!$updateResult) {
        throw new Exception('Failed to update overtime request status');
    }
    
    // Get the updated request data
    $selectStmt = $pdo->prepare("SELECT * FROM post_ot_requests WHERE id = ?");
    $selectStmt->execute([$request_id]);
    $request = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Overtime request not found');
    }
    
    // Move to archive table since status is now approved/declined (upsert if already exists with stale status)
    $insertStmt = $pdo->prepare("
        INSERT INTO post2_overtime_requests 
        (id, employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, attachment, reason, status, created_at, approved_at, approved_by, notified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), approved_at = VALUES(approved_at), approved_by = VALUES(approved_by), reason = VALUES(reason)
    ");
    
    $insertResult = $insertStmt->execute([
        $request['id'],
        $request['employee_id'],
        $request['time_log_id'],
        $request['time_in'],
        $request['time_out'],
        $request['ot_duration'],
        $request['ot_type'],
        $request['attachment'],
        $request['reason'],
        $request['status'],
        $request['created_at'],
        $request['approved_at'],
        $request['approved_by'],
        $request['notified']
    ]);
    
    if (!$insertResult) {
        throw new Exception('Failed to archive overtime request');
    }
    
    // Delete from original table
    $deleteStmt = $pdo->prepare("DELETE FROM post_ot_requests WHERE id = ?");
    $deleteResult = $deleteStmt->execute([$request_id]);
    
    if (!$deleteResult) {
        throw new Exception('Failed to remove overtime request from active table');
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Send WhatsApp notification to employee if applicable
    try {
        if ($request['employee_id']) {
            // Get employee details
            $empStmt = $pdo->prepare("
                SELECT CONCAT(fname, ' ', lname) as employee_name, company, phone 
                FROM employees 
                WHERE id = ?
            ");
            $empStmt->execute([$request['employee_id']]);
            $employee = $empStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($employee && strtolower($employee['company']) === 'bugardi') {
                require_once '../Bugardi/quick-approval-system/whatsapp-notifications/CallMeBotWhatsApp.php';
                
                $whatsapp = new CallMeBotWhatsApp();
                $statusMessage = $new_status === 'approved' ? 'APPROVED' : 'DECLINED';
                
                $otData = [
                    'employee_name' => $employee['employee_name'],
                    'status' => $statusMessage,
                    'date' => date('Y-m-d', strtotime($request['time_in'])),
                    'duration_hours' => $request['ot_duration'],
                    'reason' => $request['reason']
                ];
                
                $whatsappResult = $whatsapp->sendOTStatusNotification($request_id, $otData);
                
                if ($whatsappResult) {
                    error_log("✅ WhatsApp status notification sent for OT request #$request_id - Status: $statusMessage");
                } else {
                    error_log("❌ WhatsApp status notification failed for OT request #$request_id");
                }
            }
        }
    } catch (Exception $e) {
        error_log("WhatsApp notification error: " . $e->getMessage());
        // Don't fail the main operation for notification errors
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Overtime request {$new_status} and archived successfully",
        'status' => $new_status
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error updating overtime status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>