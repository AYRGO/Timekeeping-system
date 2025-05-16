<?php
include('../config/db.php');

// Get the ID and action from URL
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['approve', 'reject'])) {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update the schedule_change_requests status
    $stmt = $pdo->prepare("UPDATE schedule_change_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // If approved, update employee_work_schedule
    if ($status === 'approved') {
        // Get requested data
        $query = $pdo->prepare("SELECT employee_id, requested_schedule_id, requested_effective_date FROM schedule_change_requests WHERE id = ?");
        $query->execute([$id]);
        $request = $query->fetch();

        if ($request) {
            // Update the employee's schedule
            $update = $pdo->prepare("UPDATE employee_work_schedule SET work_schedule_id = ?, effective_date = ? WHERE employee_id = ?");
            $update->execute([
                $request['requested_schedule_id'],
                $request['requested_effective_date'],
                $request['employee_id']
            ]);
        }
    }

    // Redirect back to the approval page
    header('Location: ../module/schedule_change_request_approval.php');
    exit;
} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}
