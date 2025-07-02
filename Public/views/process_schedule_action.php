<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Check if form is submitted and has necessary fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Validate action
    if (!in_array($action, ['approve', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        $new_status = $action === 'approve' ? 'Approved' : 'Declined';

        // If action is decline, ensure explanation is provided
        if ($action === 'decline') {
            $explanation = trim($_POST['explanation'] ?? '');

            if (empty($explanation)) {
                $message = "Explanation is required for declining.";
            } else {
                // Update status and explanation
                $stmt = $pdo->prepare("UPDATE schedule_change_requests SET status = :status, explanation = :explanation WHERE id = :id");
                $stmt->execute([
                    'status' => $new_status,
                    'explanation' => $explanation,
                    'id' => $request_id
                ]);
                header("Location: schedule_request.php?message=Request%20ID%20%23$request_id%20has%20been%20declined");
                exit;
            }
        } else {
            // If approved, just update status
            $stmt = $pdo->prepare("UPDATE schedule_change_requests SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $new_status,
                'id' => $request_id
            ]);
            header("Location: schedule_request.php?message=Request%20ID%20%23$request_id%20has%20been%20approved");
            exit;
        }
    }
} else {
    $message = "Invalid form submission.";
}
?>
