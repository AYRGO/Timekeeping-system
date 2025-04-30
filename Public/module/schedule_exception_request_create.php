<?php
include('../config/db.php');

// Dummy employee ID for now since no session
$employee_id = 1; // Replace with session-based ID in production

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exception_date = $_POST['exception_date'] ?? null;
    $requested_time_in = $_POST['requested_time_in'] ?? null;
    $requested_time_out = $_POST['requested_time_out'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($exception_date && $requested_time_in && $requested_time_out && $reason) {
        $stmt = $pdo->prepare("INSERT INTO schedule_exception_requests (employee_id, exception_date, requested_time_in, requested_time_out, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$employee_id, $exception_date, $requested_time_in, $requested_time_out, $reason]);
        $success = "Request submitted successfully.";
    } else {
        $error = "Please complete all fields.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Exception Request</title>
</head>
<body>
    <h2>Schedule Exception Request</h2>

    <?php if (!empty($success)): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php elseif (!empty($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Exception Date:</label><br>
        <input type="date" name="exception_date" required><br><br>

        <label>Requested Time In:</label><br>
        <input type="time" name="requested_time_in" required><br><br>

        <label>Requested Time Out:</label><br>
        <input type="time" name="requested_time_out" required><br><br>

        <label>Reason:</label><br>
        <textarea name="reason" rows="4" cols="40" required></textarea><br><br>

        <button type="submit">Submit Request</button>
    </form>
</body>
</html>
