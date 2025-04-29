<?php
include('../config/db.php');

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);

        header('Location: ../module/leave_request_approval.php');
        exit;
    } else {
        echo "Error: Missing or invalid 'id' or 'action' in POST request.";
        exit;
    }
} else {
    echo "Error: Invalid request method.";
    exit;
}
