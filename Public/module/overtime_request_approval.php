<?php
session_start();

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['admin'])) {
    header("Location: ../admin/login.php");
    exit;
}
// Include database connection
include('../config/db.php');

// Fetch pending overtime requests
$stmt = $pdo->query("
    SELECT o.id, o.ot_date, o.expected_time_out, o.reason, o.status, 
           e.fname, e.lname
    FROM overtime_requests o
    JOIN employees e ON o.employee_id = e.id
    WHERE o.status = 'pending'
");

$overtime_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<html>
<head><title>Pending Overtime Requests</title></head>
<body>
<h1>Pending Overtime Requests</h1>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Date</th>
        <th>Expected Time Out</th>
        <th>Reason</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if (!empty($overtime_requests)): ?>
        <?php foreach ($overtime_requests as $ot): ?>
        <tr>
            <td><?= $ot['id'] ?></td>
            <td><?= htmlspecialchars($ot['fname'] . ' ' . $ot['lname']) ?></td>
            <td><?= htmlspecialchars($ot['ot_date']) ?></td>
            <td><?= htmlspecialchars($ot['expected_time_out']) ?></td>
            <td><?= htmlspecialchars($ot['reason']) ?></td>
            <td><?= htmlspecialchars($ot['status']) ?></td>
            <td>
                <a href="../controller/overtime_request_approve.php?id=<?= $ot['id'] ?>&action=approve">Approve</a> |
                <a href="../controller/overtime_request_approve.php?id=<?= $ot['id'] ?>&action=reject">Reject</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7">No pending overtime requests found.</td></tr>
    <?php endif; ?>
</table>

<p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
