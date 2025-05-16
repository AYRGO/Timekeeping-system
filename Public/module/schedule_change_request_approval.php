<?php
include('../config/db.php');

// Fetch pending schedule change requests
$stmt = $pdo->query("
    SELECT scr.id, scr.employee_id, scr.requested_effective_date, scr.requested_schedule_id, scr.reason, scr.status, 
           e.fname, e.lname, ws.name AS schedule_name
    FROM schedule_change_requests scr
    JOIN employees e ON scr.employee_id = e.id
    JOIN work_schedules ws ON scr.requested_schedule_id = ws.id
    WHERE scr.status = 'pending'
");


$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Change Requests</title>
</head>
<body>
    <h1>Pending Schedule Change Requests</h1>

    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Requested Schedule</th>
            <th>Effective Date</th>
            <th>Reason</th>
            <th>Actions</th>
        </tr>

        <?php if (count($requests) > 0): ?>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                    <td><?= htmlspecialchars($req['schedule_name']) ?></td>
                    <td><?= htmlspecialchars($req['requested_effective_date']) ?></td>
                    <td><?= htmlspecialchars($req['reason']) ?></td>
                    <td>
                    <a href="../controller/schedule_change_request_approve.php?id=<?= $req['id'] ?>&action=approve">Approve</a>
<a href="../controller/schedule_change_request_approve.php?id=<?= $req['id'] ?>&action=reject">Reject</a>

                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No pending requests.</td></tr>
        <?php endif; ?>
    </table>

    <p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
