<?php
include('../config/db.php');

// Fetch employee list
$stmt = $pdo->query("SELECT id, fname, lname FROM employees ORDER BY id ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $log_date = $_POST['log_date'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    $insert = $pdo->prepare("INSERT INTO time_logs (employee_id, log_date, time_in, time_out) VALUES (?, ?, ?, ?)");
    $insert->execute([$employee_id, $log_date, $time_in, $time_out]);

    header("Location: ../views/time_log_list.php");
    exit;
}
?>

<html>
<head><title>Log Time</title></head>
<body>
<h1>Manual Time Log</h1>

<form method="POST">
    <label>Employee:</label><br>
    <select name="employee_id" required>
        <option value="">-- Select Employee --</option>
        <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>">
                <?= htmlspecialchars($emp['id'] . ' - ' . $emp['fname'] . ' ' . $emp['lname']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Date:</label><br>
    <input type="date" name="log_date" required><br><br>

    <label>Time In:</label><br>
    <input type="time" name="time_in" required><br><br>

    <label>Time Out:</label><br>
    <input type="time" name="time_out" required><br><br>

    <button type="submit">Save Log</button>
</form>

<p><a href="time_log-list.php">← View All Logs</a></p>
</body>
</html>
