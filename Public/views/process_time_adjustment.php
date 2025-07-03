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

    // Fetch current request to verify existence
    $stmt = $pdo->prepare("SELECT * FROM time_adjustment_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        die("Request not found.");
    }

    if ($action === 'approve') {
        $update = $pdo->prepare("UPDATE time_adjustment_requests SET status = 'approved' WHERE id = ?");
        $update->execute([$request_id]);

        $_SESSION['success'] = "Request approved successfully.";

    } elseif ($action === 'decline') {
        $explanation = trim($_POST['explanation'] ?? '');

        if (empty($explanation)) {
            $_SESSION['error'] = "Explanation is required for declining a request.";
            header("Location: time_adjustment_list.php");
            exit;
        }

        // Optionally, store explanation in a new column (if available)
        $update = $pdo->prepare("UPDATE time_adjustment_requests SET status = 'declined', reason = CONCAT(reason, ' | Decline Reason: ', ?) WHERE id = ?");
        $update->execute([$explanation, $request_id]);

        $_SESSION['success'] = "Request declined with explanation.";
    } else {
        $_SESSION['error'] = "Unknown action.";
    }
} else {
    $_SESSION['error'] = "Invalid method.";
}

// Redirect back to the list
header("Location: time_adjustment_list.php");
exit;
