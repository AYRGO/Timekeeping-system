<?php
session_start();
include('../config/db.php');

// Ensure employee is logged in
$employee_id = $_SESSION['employee']['id'] ?? null;
if (!$employee_id) {
    header("Location: ../employee/login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason]);

    header("Location: ../views/leave_request_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head><title>Create Leave Request</title></head>
<body>
<h1>Create Leave Request</h1>

<form method="POST">
    <label>Leave Type:</label><br>
    <select name="leave_type" required>
        <option value="">-- Select Type --</option>
        <option value="VL">Vacation Leave</option>
        <option value="SL">Sick Leave</option>
        <option value="Emergency">Emergency</option>
        <option value="Other">Other</option>
    </select><br><br>

    <label>Start Date:</label><br>
    <input type="date" name="start_date" required><br><br>

    <label>End Date:</label><br>
    <input type="date" name="end_date" required><br><br>

    <label>Reason:</label><br>
    <textarea name="reason" rows="4" cols="40" required></textarea><br><br>

    <button type="submit">Submit Request</button>
</form>

<p><a href="../views/leave_request_list.php">← Back to Leave List</a></p>
</body>
</html>