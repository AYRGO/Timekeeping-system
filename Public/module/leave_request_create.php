<?php
ob_start();
include('../config/db.php');

// Fetch all employees for the dropdown
$emp_stmt = $pdo->query("SELECT id, fname, lname FROM employees");
$employees = $emp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("
        INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->execute([$employee_id, $leave_type, $start_date, $end_date, $reason]);

    // Redirect and stop further output
    header("Location: ../views/leave_request_list.php");
    exit;
}
ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head><title>Create Leave Request</title></head>
<body>
<h1>Create Leave Request</h1>

<form method="POST">
    <label>Employee:</label><br>
    <select name="employee_id" required>
        <option value="">-- Select Employee --</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>">
                <?= htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Leave Type:</label><br>
    <select name="leave_type" required>
    <option value="">-- Select Type --</option>
    <option value="VL">Vacation Leave</option>
    <option value="SL">Sick Leave</option>
    <option value="Emergency">Emergency</option>
    <option value="Other">Other</option>
</select>
<br><br>

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
