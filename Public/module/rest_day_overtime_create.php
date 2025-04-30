<?php
include('../config/db.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $rest_day_date = $_POST['rest_day_date'] ?? null;
    $expected_time_in = $_POST['expected_time_in'] ?? null;
    $expected_time_out = $_POST['expected_time_out'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($employee_id && $rest_day_date && $expected_time_in && $expected_time_out && $reason) {
        $stmt = $pdo->prepare("
            INSERT INTO rest_day_overtime_requests 
            (employee_id, rest_day_date, expected_time_in, expected_time_out, reason, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$employee_id, $rest_day_date, $expected_time_in, $expected_time_out, $reason]);

        echo "<p style='color: green;'>Request submitted successfully!</p>";
    } else {
        echo "<p style='color: red;'>All fields are required.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Rest Day Overtime Request</title>
</head>
<body>
    <h1>Rest Day Overtime Request Form</h1>
    <form method="POST">
        <label>Employee ID:</label><br>
        <input type="number" name="employee_id" required><br><br>

        <label>Rest Day Date:</label><br>
        <input type="date" name="rest_day_date" required><br><br>

        <label>Expected Time In:</label><br>
        <input type="time" name="expected_time_in" required><br><br>

        <label>Expected Time Out:</label><br>
        <input type="time" name="expected_time_out" required><br><br>

        <label>Reason:</label><br>
        <textarea name="reason" required></textarea><br><br>

        <button type="submit">Submit Request</button>
    </form>

    <p><a href="rest_day_overtime_approval.php">← View Pending Requests</a></p>
</body>
</html>
