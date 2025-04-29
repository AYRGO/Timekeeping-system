<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Fetch leave requests with employee names
$stmt = $pdo->query("
    SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status,
           e.fname, e.lname
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    ORDER BY lr.start_date DESC
");
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<html>
<head><title>Leave Requests</title></head>
<body>
<h1>Leave Requests</h1>

<a href="../module/leave_request_create.php">Add Leave Request</a>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Leave Type</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Reason</th>
        <th>Status</th>
    </tr>

    <?php foreach ($leave_requests as $lr): ?>
    <tr>
        <td><?= $lr['id'] ?></td>
        <td><?= htmlspecialchars($lr['fname'] . ' ' . $lr['lname']) ?></td>
        <td><?= htmlspecialchars($lr['leave_type']) ?></td>
        <td><?= htmlspecialchars($lr['start_date']) ?></td>
        <td><?= htmlspecialchars($lr['end_date']) ?></td>
        <td><?= htmlspecialchars($lr['reason']) ?></td>
        <td><?= htmlspecialchars($lr['status']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
 