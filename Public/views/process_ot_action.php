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
        $new_status = $action === 'approve' ? 'Approved' : 'Declined';

        // If declining, check for explanation
        if ($action === 'decline') {
            $explanation = trim($_POST['explanation'] ?? '');

            if (empty($explanation)) {
                $message = "Explanation is required for declining.";
            } else {
                // Update status and explanation for decline
                $stmt = $pdo->prepare("UPDATE overtime_requests SET status = :status, explanation = :explanation WHERE id = :id");
                $stmt->execute([
                    'status' => $new_status,
                    'explanation' => $explanation,
                    'id' => $request_id
                ]);

                header("Location: ot_request.php?message=OT%20Request%20%23$request_id%20declined");
                exit;
            }
        } else {
            // Only update status for approval
            $stmt = $pdo->prepare("UPDATE overtime_requests SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $new_status,
                'id' => $request_id
            ]);

            header("Location: ot_request.php?message=OT%20Request%20%23$request_id%20approved");
            exit;
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
