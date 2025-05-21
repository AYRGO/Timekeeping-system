<?php
include('../config/db.php');

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($request_id && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            // Fetch leave request details
            $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $leave = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($leave) {
                $leave_type = $leave['leave_type'];
                $employee_id = $leave['employee_id'];
                $start = new DateTime($leave['start_date']);
                $end = new DateTime($leave['end_date']);
                $days = $start->diff($end)->days + 1;

                $valid_leave_types = ['VL', 'SL', 'SPL', 'Half_SL', 'Half_VL'];

                if (in_array($leave_type, $valid_leave_types)) {
                    $deduction = $days;
                    if ($leave_type === 'Half_SL' || $leave_type === 'Half_VL') {
                        $deduction = 0.5 * $days;
                    }

                    // Fetch leave credit balance
                    $stmt = $pdo->prepare("SELECT balance FROM leave_credits WHERE employee_id = ? AND leave_type = ? AND year = ?");
                    $stmt->execute([$employee_id, $leave_type, date('Y')]);
                    $balance = $stmt->fetchColumn();

                    if ($balance === false || $balance < $deduction) {
                        // Not enough balance, do not approve
                        echo "<script>
                                alert('Cannot approve: Not enough leave credits.');
                                window.location.href = '../module/leave_request_approval.php';
                              </script>";
                        exit;
                    }

                    // Deduct the credits
                    $deduct = $pdo->prepare("
                        UPDATE leave_credits 
                        SET balance = balance - ? 
                        WHERE employee_id = ? AND leave_type = ? AND year = ?
                    ");
                    $deduct->execute([$deduction, $employee_id, $leave_type, date('Y')]);
                }

                // Update request status to approved
                $update = $pdo->prepare("UPDATE leave_requests SET status = 'approved' WHERE id = ?");
                $update->execute([$request_id]);
            }
        } else {
            // Reject case
            $update = $pdo->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
            $update->execute([$request_id]);
        }

        header('Location: ../module/leave_request_approval.php');
        exit;
    } else {
        echo "Error: Missing or invalid 'id' or 'action' in POST request.";
        exit;
    }
} else {
    echo "Error: Invalid request method.";
    exit;
}
