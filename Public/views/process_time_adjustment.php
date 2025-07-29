<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('../config/db.php');

// Validate required fields
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$request_id || !$action) {
        die("Invalid request.");
    }

    try {
        $pdo->beginTransaction();

        // Fetch current request to verify existence
        $stmt = $pdo->prepare("SELECT * FROM time_adjustment_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception("Request not found.");
        }

        if ($action === 'approve') {
            $request['status'] = 'approved';
        } elseif ($action === 'decline') {
            $explanation = trim($_POST['explanation'] ?? '');

            if (empty($explanation)) {
                throw new Exception("Explanation is required for declining a request.");
            }

            $request['status'] = 'declined';
            // Store explanation by appending to reason or in a separate field if available
            $request['reason'] = $request['reason'] . ' | Decline Reason: ' . $explanation;
        } else {
            throw new Exception("Unknown action.");
        }

        // Insert into post_time_adjustment_requests table
        $stmt = $pdo->prepare("
            INSERT INTO post_time_adjustment_requests 
            (employee_id, log_date, current_time_in, current_time_out, requested_time_in, requested_time_out, 
             reason, status, submitted_at, attachment, created_at, notified, deleted) 
            VALUES 
            (:employee_id, :log_date, :current_time_in, :current_time_out, :requested_time_in, :requested_time_out, 
             :reason, :status, :submitted_at, :attachment, :created_at, :notified, :deleted)
        ");
        
        $stmt->execute([
            'employee_id' => $request['employee_id'],
            'log_date' => $request['log_date'],
            'current_time_in' => $request['current_time_in'],
            'current_time_out' => $request['current_time_out'],
            'requested_time_in' => $request['requested_time_in'],
            'requested_time_out' => $request['requested_time_out'],
            'reason' => $request['reason'],
            'status' => $request['status'],
            'submitted_at' => $request['submitted_at'],
            'attachment' => $request['attachment'],
            'created_at' => date('Y-m-d H:i:s'),
            'notified' => 0,
            'deleted' => 0
        ]);

        // Delete from time_adjustment_requests table
        $stmt = $pdo->prepare("DELETE FROM time_adjustment_requests WHERE id = ?");
        $stmt->execute([$request_id]);

        $pdo->commit();

        $action_text = $action === 'approve' ? 'approved' : 'declined';
        $_SESSION['success'] = "Request $action_text successfully.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid method.";
}

// Redirect back to the list
header("Location: time_adjustment_list.php");
exit;
