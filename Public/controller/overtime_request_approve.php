<?php
include('../config/db.php');

// Get ID and action from URL
$request_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($request_id && in_array($action, ['approve', 'reject'])) {
    // Fetch the schedule request details
    $stmt = $pdo->prepare("SELECT * FROM schedule_change_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo "Error: Request not found.";
        exit;
    }

    // Update the request status
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE schedule_change_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);

    // If approved, insert new row into employee_work_schedule
    if ($status === 'approved') {
        $employee_id = $request['employee_id'];
        $schedule_id = $request['requested_schedule_id'];
        $effective_date = $request['requested_effective_date'];

        // End current schedule if active
        $stmt = $pdo->prepare("UPDATE employee_work_schedule 
                               SET end_date = DATE_SUB(?, INTERVAL 1 DAY) 
                               WHERE employee_id = ? AND (end_date IS NULL OR end_date >= ?)");
        $stmt->execute([$effective_date, $employee_id, $effective_date]);

        // Insert new schedule
        $stmt = $pdo->prepare("INSERT INTO employee_work_schedule (employee_id, schedule_id, start_date) VALUES (?, ?, ?)");
        $stmt->execute([$employee_id, $schedule_id, $effective_date]);
    }

    // Redirect back to approval list or confirmation
    header("Location: schedule_change_request_approval.php");
    exit;

} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}
