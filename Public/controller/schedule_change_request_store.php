<?php
include('../config/db.php'); // adjust path as needed

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form inputs
    $employee_id = $_POST['employee_id'] ?? null;
    $requested_schedule_id = $_POST['requested_schedule_id'] ?? null;
    $requested_effective_date = $_POST['requested_effective_date'] ?? null;
    $reason = $_POST['reason'] ?? null;

    // Validate inputs
    if ($employee_id && $requested_schedule_id && $requested_effective_date && $reason) {
        // Insert the schedule change request
        $stmt = $pdo->prepare("INSERT INTO schedule_change_requests 
            (employee_id, requested_schedule_id, requested_effective_date, reason, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())");

        $stmt->execute([
            $employee_id,
            $requested_schedule_id,
            $requested_effective_date,
            $reason
        ]);

        echo "Schedule change request submitted successfully.";
        // Optionally redirect to a list view or dashboard
        // header('Location: some_page.php');
    } else {
        echo "Error: Please fill in all required fields.";
    }
} else {
    echo "Invalid request method.";
}
?>
