<?php
include('../config/db.php');

// Fetch all pending rest day overtime requests with employee names
$stmt = $pdo->query("
    SELECT r.id, r.rest_day_date, r.expected_time_in, r.expected_time_out, r.reason, r.status,
           e.fname, e.lname
    FROM rest_day_overtime_requests r
    JOIN employees e ON r.employee_id = e.id
    WHERE r.status = 'pending'
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Rest Day OT Requests</title>
</head>
<body>
    <h1>Pending Rest Day Overtime Requests</h1>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Rest Day</th>
            <th>Expected Time In</th>
            <th>Expected Time Out</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                    <td><?= htmlspecialchars($req['rest_day_date']) ?></td>
                    <td><?= htmlspecialchars($req['expected_time_in']) ?></td>
                    <td><?= htmlspecialchars($req['expected_time_out']) ?></td>
                    <td><?= htmlspecialchars($req['reason']) ?></td>
                    <td><?= htmlspecialchars($req['status']) ?></td>
                    <td>
                        <a href="../controller/rest_day_overtime_approve.php?id=<?= $req['id'] ?>&action=approve">Approve</a> |
                        <a href="../controller/rest_day_overtime_approve.php?id=<?= $req['id'] ?>&action=reject">Reject</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No pending requests.</td></tr>
        <?php endif; ?>
    </table>

    <p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
