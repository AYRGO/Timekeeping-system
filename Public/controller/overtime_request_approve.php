<?php
include('../config/db.php');

// Get ID and action
$request_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($request_id && in_array($action, ['approve', 'reject'])) {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);

    // Redirect back to the approval page
    header('Location: overtime_request_approval.php');
    exit;
} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}