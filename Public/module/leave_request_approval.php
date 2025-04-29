<?php
include('../config/db.php');

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID and action from the form data
    $request_id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    // Ensure both 'id' and 'action' are provided
    if ($request_id && in_array($action, ['approve', 'reject'])) {
        // Prepare the status based on the action
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        // Update the leave request status in the database
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);

        // Redirect back to the employee list page after the update
        header('Location: ../views/employee_list.php');
        exit;  // Make sure script stops after redirect
    } else {
        echo "Error: Missing 'id' or 'action' in form submission.";
        exit;
    }
}

// Fetch pending leave requests with employee details (fname, lname)
$stmt = $pdo->query("
    SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status,
           e.fname, e.lname
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.status = 'pending'
");

$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Check if leave requests are fetched correctly
if (empty($leave_requests)) {
    echo "No pending leave requests found.";
    exit;
}
?>

<html>
<head><title>Pending Leave Requests</title></head>
<body>
<h1>Pending Leave Requests</h1>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Employee</th>
        <th>Leave Type</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Reason</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if (!empty($leave_requests)): ?>
        <?php foreach ($leave_requests as $lr): ?>
        <tr>
            <td><?= $lr['id'] ?></td>
            <td><?= htmlspecialchars($lr['fname'] . ' ' . $lr['lname']) ?></td>
            <td><?= htmlspecialchars($lr['leave_type']) ?></td>
            <td><?= htmlspecialchars($lr['start_date']) ?></td>
            <td><?= htmlspecialchars($lr['end_date']) ?></td>
            <td><?= htmlspecialchars($lr['reason']) ?></td>
            <td><?= htmlspecialchars($lr['status']) ?></td>
            <td>
            <form action="../controller/leave_request_approve.php" method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $lr['id'] ?>">
    <input type="hidden" name="action" value="approve">
    <button type="submit">Approve</button>
</form>

<form action="../controller/leave_request_approve.php" method="POST" style="display:inline;">
    <input type="hidden" name="id" value="<?= $lr['id'] ?>">
    <input type="hidden" name="action" value="reject">
    <button type="submit">Reject</button>
</form>

            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No pending leave requests found.</td>
        </tr>
    <?php endif; ?>
</table>

<p><a href="../views/employee_list.php">← Back to Employee List</a></p>
</body>
</html>
