<?php
include('../config/db.php');

// Fetch all time logs joined with employee names
$stmt = $pdo->query("
    SELECT t.id, t.log_date, t.time_in, t.time_out, e.fname, e.lname 
    FROM time_logs t
    JOIN employees e ON t.employee_id = e.id
    ORDER BY t.log_date DESC, t.time_in ASC
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<html>
<head><title>Time Logs</title></head>
<body>
<h1>Time Logs</h1>

<a href="../module/time_log_create.php">Add Time Log</a>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Date</th>
        <th>Time In</th>
        <th>Time Out</th>
    </tr>

    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= $log['id'] ?></td>
        <td><?= htmlspecialchars($log['fname'] . ' ' . $log['lname']) ?></td>
        <td><?= htmlspecialchars($log['log_date']) ?></td>
        <td><?= htmlspecialchars($log['time_in']) ?></td>
        <td><?= htmlspecialchars($log['time_out']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
