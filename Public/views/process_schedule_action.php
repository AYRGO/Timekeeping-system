<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check if form is submitted and has necessary fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Allow approve, rejected, and decline actions
    if (!in_array($action, ['approve', 'rejected', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        // Set status based on action
        if ($action === 'approve') {
            $new_status = 'Approved';
        } elseif ($action === 'decline') {
            $new_status = 'Declined';
        } else {
            $new_status = 'Rejected';
        }

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

            $pdo->commit();

            // Set action_text for message
            if ($action === 'approve') {
                $action_text = 'approved';
            } elseif ($action === 'decline') {
                $action_text = 'declined';
            } else {
                $action_text = 'rejected';
            }
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
