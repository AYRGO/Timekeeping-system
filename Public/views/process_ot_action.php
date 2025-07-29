<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Validate action
    if (!in_array($action, ['approve', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        try {
            $pdo->beginTransaction();

            // Get the complete overtime request data
            $stmt = $pdo->prepare("SELECT o.*, t.time_in, t.time_out FROM overtime_requests o LEFT JOIN time_logs t ON o.employee_id = t.employee_id AND o.date = t.log_date WHERE o.id = :id");
            $stmt->execute(['id' => $request_id]);
            $overtime_request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$overtime_request) {
                throw new Exception("Overtime request not found.");
            }

            // Calculate duration hours
            $duration_hours = (strtotime($overtime_request['end_time']) - strtotime($overtime_request['start_time'])) / 3600;

            if ($action === 'approve') {
                $overtime_request['status'] = 'Approved';
            } elseif ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $overtime_request['explanation'] = $explanation;
                $overtime_request['status'] = 'Rejected';
            }

            // Insert into post_overtime_requests table
            $stmt = $pdo->prepare("
                INSERT INTO post_overtime_requests 
                (employee_id, date, start_time, end_time, duration_hours, reason, status, 
                 attachment_ot, time_in, time_out, explanation, created_at, notified, deleted) 
                VALUES 
                (:employee_id, :date, :start_time, :end_time, :duration_hours, :reason, :status, 
                 :attachment_ot, :time_in, :time_out, :explanation, :created_at, :notified, :deleted)
            ");
            
            $stmt->execute([
                'employee_id' => $overtime_request['employee_id'],
                'date' => $overtime_request['date'],
                'start_time' => $overtime_request['start_time'],
                'end_time' => $overtime_request['end_time'],
                'duration_hours' => $duration_hours,
                'reason' => $overtime_request['reason'],
                'status' => $overtime_request['status'],
                'attachment_ot' => $overtime_request['attachment_ot'],
                'time_in' => $overtime_request['time_in'],
                'time_out' => $overtime_request['time_out'],
                'explanation' => $overtime_request['explanation'] ?? null,
                'created_at' => $overtime_request['created_at'],
                'notified' => $overtime_request['notified'] ?? 0,
                'deleted' => 0
            ]);

            // Delete from overtime_requests table
            $stmt = $pdo->prepare("DELETE FROM overtime_requests WHERE id = :id");
            $stmt->execute(['id' => $request_id]);

            // Create notification for the employee
            $notification_message = $action === 'approve' 
                ? "Your overtime request has been approved by admin."
                : "Your overtime request has been declined by admin.";
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (employee_id, message, type, created_at) 
                VALUES (:employee_id, :message, 'overtime_response', NOW())
            ");
            $stmt->execute([
                'employee_id' => $overtime_request['employee_id'],
                'message' => $notification_message
            ]);

            $pdo->commit();

            $action_text = $action === 'approve' ? 'approved' : 'declined';
            header("Location: ot_request.php?message=OT%20Request%20%23$request_id%20$action_text");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}

// Optional: Display error if redirected without success
if (!empty($message)) {
    echo "<p style='color: red; font-weight: bold;'>$message</p>";
    echo "<p><a href='ot_request.php'>← Back to OT Requests</a></p>";
}
?>
