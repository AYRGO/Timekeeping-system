<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check if form is submitted and has necessary fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Validate action
    if (!in_array($action, ['approve', 'rejected'])) {
        $message = "Invalid action specified.";
    } else {
        $new_status = $action === 'approve' ? 'Approved' : 'Rejected';

        try {
            $pdo->beginTransaction();

            // First, get the complete request data
            $stmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE id = :id");
            $stmt->execute(['id' => $request_id]);
            $request_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request_data) {
                throw new Exception("Request not found.");
            }

            // If action is decline, ensure explanation is provided
            if ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $request_data['explanation'] = $explanation;
            }

            // Update the status
            $request_data['status'] = $new_status;

            // Insert into post_schedule_change_requests table
            $stmt = $pdo->prepare("
                INSERT INTO post_schedule_change_requests 
                (employee_id, reason, status, start_date, end_date, work_schedule_id, 
                 current_work_schedule_id, attachment_scr, explanation, created_at, notified) 
                VALUES 
                (:employee_id, :reason, :status, :start_date, :end_date, :work_schedule_id, 
                 :current_work_schedule_id, :attachment_scr, :explanation, :created_at, :notified)
            ");
            
            $stmt->execute([
                'employee_id' => $request_data['employee_id'],
                'reason' => $request_data['reason'],
                'status' => $request_data['status'],
                'start_date' => $request_data['start_date'],
                'end_date' => $request_data['end_date'],
                'work_schedule_id' => $request_data['work_schedule_id'],
                'current_work_schedule_id' => $request_data['current_work_schedule_id'],
                'attachment_scr' => $request_data['attachment_scr'],
                'explanation' => $request_data['explanation'] ?? null,
                'created_at' => $request_data['created_at'],
                'notified' => $request_data['notified'] ?? 0
            ]);

            // Delete from schedule_change_requests table
            $stmt = $pdo->prepare("DELETE FROM schedule_change_requests WHERE id = :id");
            $stmt->execute(['id' => $request_id]);

            // Create notification for the employee
            $notification_message = $action === 'approve' 
                ? "Your schedule change request has been approved by admin."
                : "Your schedule change request has been declined by admin.";
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (employee_id, message, type, created_at) 
                VALUES (:employee_id, :message, 'schedule_response', NOW())
            ");
            $stmt->execute([
                'employee_id' => $request_data['employee_id'],
                'message' => $notification_message
            ]);

            $pdo->commit();

            $action_text = $action === 'approve' ? 'approved' : 'rejected';
            header("Location: schedule_request.php?message=Request%20ID%20%23$request_id%20has%20been%20$action_text");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}

// If we reach here, there was an error
header("Location: schedule_request.php?error=" . urlencode($message));
exit;
?>
