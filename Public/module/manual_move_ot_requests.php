<?php
/**
 * Manual trigger to move approved/declined overtime requests to archive
 * Can be called via AJAX from admin interfaces or manually
 */

session_start();
include('../config/db.php');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Check if request is from authenticated admin/manager
if (!isset($_SESSION['employee']) || !in_array($_SESSION['employee']['role'], ['admin', 'manager', 'HR'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

function moveCompletedOTRequests($pdo) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get all approved, declined, or rejected requests from post_ot_requests
        $selectStmt = $pdo->prepare("
            SELECT * FROM post_ot_requests 
            WHERE LOWER(status) IN ('approved', 'declined', 'rejected')
        ");
        $selectStmt->execute();
        $completedRequests = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($completedRequests)) {
            // Insert completed requests into post2_overtime_requests
            $insertStmt = $pdo->prepare("
                INSERT INTO post2_overtime_requests 
                (id, employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, attachment, reason, status, created_at, approved_at, approved_by, notified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $movedCount = 0;
            foreach ($completedRequests as $request) {
                $insertStmt->execute([
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
                $movedCount++;
            }
            
            // Delete moved requests from original table
            $deleteStmt = $pdo->prepare("
                DELETE FROM post_ot_requests 
                WHERE LOWER(status) IN ('approved', 'declined', 'rejected')
            ");
            $deleteStmt->execute();
            
            $pdo->commit();
            return $movedCount;
        }
        
        $pdo->commit();
        return 0;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error moving completed OT requests: " . $e->getMessage());
        throw $e;
    }
}

try {
    $movedCount = moveCompletedOTRequests($pdo);
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully moved {$movedCount} completed overtime requests to archive",
        'moved_count' => $movedCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>