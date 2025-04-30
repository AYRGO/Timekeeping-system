<?php
include('../config/db.php');

// Get the ID and action from URL
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['approve', 'reject'])) {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update the request status
    $stmt = $pdo->prepare("UPDATE schedule_exception_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($status === 'approved') {
        // Get the request details
        $query = $pdo->prepare("SELECT employee_id, exception_date, requested_time_in, requested_time_out FROM schedule_exception_requests WHERE id = ?");
        $query->execute([$id]);
        $request = $query->fetch();

        if ($request) {
            // Insert into schedule_exceptions
            $insert = $pdo->prepare("INSERT INTO schedule_exceptions (employee_id, exception_date, time_in, time_out) VALUES (?, ?, ?, ?)");
            $insert->execute([
                $request['employee_id'],
                $request['exception_date'],
                $request['requested_time_in'],
                $request['requested_time_out']
            ]);
        }
    }

    // Redirect back to approval page
    header('Location: ../module/schedule_exception_request_approval.php');
    exit;
} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}
