<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../config/db.php');

// Fetch all pending requests
$stmt = $pdo->query("
    SELECT ser.id, ser.employee_id, e.fname, e.lname, ser.exception_date, ser.requested_time_in, ser.requested_time_out, ser.reason, ser.status
    FROM schedule_exception_requests ser
    JOIN employees e ON ser.employee_id = e.id
    ORDER BY ser.created_at DESC
");

$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule Exception Request Approval</title>
</head>
<body>
    <h2>Schedule Exception Request Approvals</h2>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Employee</th>
            <th>Exception Date</th>
            <th>Requested Time In</th>
            <th>Requested Time Out</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php if ($requests): ?>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= $req['id'] ?></td>
                    <td><?= htmlspecialchars($req['fname'] . ' ' . $req['lname']) ?></td>
                    <td><?= $req['exception_date'] ?></td>
                    <td><?= $req['requested_time_in'] ?></td>
                    <td><?= $req['requested_time_out'] ?></td>
                    <td><?= htmlspecialchars($req['reason']) ?></td>
                    <td><?= $req['status'] ?></td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <a href="../controller/schedule_exception_request_approve.php?id=<?= $req['id'] ?>&action=approve">✅ Approve</a> |
                            <a href="../controller/schedule_exception_request_approve.php?id=<?= $req['id'] ?>&action=reject">❌ Reject</a>
                        <?php else: ?>
                            <em><?= ucfirst($req['status']) ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">No schedule exception requests found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
