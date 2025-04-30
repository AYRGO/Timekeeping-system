<?php
include('../config/db.php');

// Get the ID and action from URL
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['approve', 'reject'])) {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update status in the database
    $stmt = $pdo->prepare("UPDATE rest_day_overtime_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Redirect back
    header('Location: ../module/rest_day_overtime_approval.php');
    exit;
} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}
