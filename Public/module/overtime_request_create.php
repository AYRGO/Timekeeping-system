<?php
include('../config/db.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $ot_date = $_POST['ot_date'] ?? null;
    $expected_time_out = $_POST['expected_time_out'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($employee_id && $ot_date && $expected_time_out && $reason) {
        $stmt = $pdo->prepare("
            INSERT INTO overtime_requests (employee_id, ot_date, expected_time_out, reason, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$employee_id, $ot_date, $expected_time_out, $reason]);

        echo "Overtime request submitted successfully.";
    } else {
        echo "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Overtime Request</title>
</head>
<body>
<h2>Overtime Request Form</h2>
<form method="POST">
    <label for="employee_id">Employee ID:</label>
    <input type="number" name="employee_id" required><br><br>

    <label for="ot_date">OT Date:</label>
    <input type="date" name="ot_date" required><br><br>

    <label for="expected_time_out">Expected Time Out:</label>
    <input type="time" name="expected_time_out" required><br><br>

    <label for="reason">Reason:</label>
    <textarea name="reason" required></textarea><br><br>

    <button type="submit">Submit Request</button>
</form>
</body>
</html>
