<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}

include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $explanation = $_POST['explanation'] ?? '';
    
    if (!$request_id || !$action) {
        header("Location: Admin_dashboard.php?error=" . urlencode("Invalid request parameters."));
        exit;
    }
    
    try {
        if ($action === 'approve') {
            // Move to post_overtime_requests table and update status
            $stmt = $pdo->prepare("
                INSERT INTO post_overtime_requests 
                (employee_id, date, start_time, end_time, reason, status, attachment_ot, duration_hours, time_in, time_out, explanation, created_at)
                SELECT 
                    o.employee_id, o.date, o.start_time, o.end_time, o.reason, 'approved', o.attachment_ot,
                    TIMESTAMPDIFF(MINUTE, o.start_time, o.end_time) / 60,
                    t.time_in, t.time_out, 'Approved by admin', o.created_at
                FROM overtime_requests o
                LEFT JOIN time_logs t ON o.employee_id = t.employee_id AND o.date = t.log_date
                WHERE o.id = ?
            ");
            $stmt->execute([$request_id]);
            
            // Delete from original table
            $stmt = $pdo->prepare("DELETE FROM overtime_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            
            $message = "Overtime request approved successfully.";
            
        } elseif ($action === 'decline') {
            if (empty($explanation)) {
                header("Location: Admin_dashboard.php?error=" . urlencode("Explanation is required for declining requests."));
                exit;
            }
            
            // Move to post_overtime_requests table with declined status
            $stmt = $pdo->prepare("
                INSERT INTO post_overtime_requests 
                (employee_id, date, start_time, end_time, reason, status, attachment_ot, duration_hours, time_in, time_out, explanation, created_at)
                SELECT 
                    o.employee_id, o.date, o.start_time, o.end_time, o.reason, 'declined', o.attachment_ot,
                    TIMESTAMPDIFF(MINUTE, o.start_time, o.end_time) / 60,
                    t.time_in, t.time_out, ?, o.created_at
                FROM overtime_requests o
                LEFT JOIN time_logs t ON o.employee_id = t.employee_id AND o.date = t.log_date
                WHERE o.id = ?
            ");
            $stmt->execute([$explanation, $request_id]);
            
            // Delete from original table
            $stmt = $pdo->prepare("DELETE FROM overtime_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            
            $message = "Overtime request declined successfully.";
        } else {
            header("Location: Admin_dashboard.php?error=" . urlencode("Invalid action."));
            exit;
        }
        
        header("Location: Admin_dashboard.php?message=" . urlencode($message));
        exit;
        
    } catch (PDOException $e) {
        header("Location: Admin_dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: Admin_dashboard.php");
    exit;
}
?>
