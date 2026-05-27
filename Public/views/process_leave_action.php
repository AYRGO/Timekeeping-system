<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');
require_once __DIR__ . '/../config/demo_guard.php';
demo_block_admin_mutation('Leave approval actions are disabled in admin demo mode.');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = intval($_POST['leave_id']);
    $action = $_POST['action'];

    if (!in_array($action, ['approve', 'decline', 'cancel'])) {
        $message = "Invalid action specified.";
    } else {
        try {
            $pdo->beginTransaction();

            if ($action === 'cancel') {
                // Handle cancel action for approved leaves from post_leave_requests
                $cancel_reason = trim($_POST['cancel_reason'] ?? '');
                if (empty($cancel_reason)) {
                    throw new Exception("Reason for cancellation is required.");
                }

                // Get the leave request from post_leave_requests
                $stmt = $pdo->prepare("SELECT * FROM post_leave_requests WHERE id = :id AND status = 'approved'");
                $stmt->execute(['id' => $leave_id]);
                $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$leave_request) {
                    throw new Exception("Approved leave request not found or cannot be cancelled.");
                }

                // Check if leave hasn't started yet
                $today = new DateTime();
                $startDate = new DateTime($leave_request['start_date']);
                
                if ($startDate <= $today) {
                    throw new Exception("Cannot cancel leave request that has already started.");
                }

                $employee_id = $leave_request['employee_id'];
                $leave_type = $leave_request['leave_type'];

                // Calculate the number of days to restore
                $start_date = new DateTime($leave_request['start_date']);
                $end_date = new DateTime($leave_request['end_date']);
                $requested_days = $start_date->diff($end_date)->days + 1;
                
                // Map leave types to their corresponding credit types and determine restoration amount
                $restoration_amount = 1; // Default full day
                $credit_type = $leave_type; // Default to same type
                
                // Map halfday and special types to their base credit types
                $leave_type_lower = strtolower($leave_type);
                switch ($leave_type_lower) {
                    case 'halfday':
                        $credit_type = 'vacation';
                        $restoration_amount = 0.5; // Always exactly 0.5 for half day
                        break;
                    case 'halfday_sick':
                        $credit_type = 'sick';
                        $restoration_amount = 0.5; // Always exactly 0.5 for half day
                        break;
                    case 'lwop':
                        // LWOP doesn't affect credits
                        $restoration_amount = 0;
                        $credit_type = null;
                        break;
                    case 'bereavement':
                    case 'paternity':
                    case 'maternity':
                        $credit_type = $leave_type_lower;
                        $restoration_amount = $requested_days;
                        break;
                    default:
                        // sick, vacation, solo_parent
                        $credit_type = $leave_type_lower;
                        $restoration_amount = $requested_days;
                        break;
                }

                // Restore the corresponding leave credit if applicable
                if ($restoration_amount > 0 && $credit_type) {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = balance + ? WHERE employee_id = ? AND leave_type = ? AND year = ?");
                    $stmt->execute([
                        $restoration_amount,
                        $employee_id,
                        $credit_type,
                        date('Y')
                    ]);
                }

                // Update the status to 'cancelled' and add cancellation reason
                $stmt = $pdo->prepare("UPDATE post_leave_requests SET status = 'cancelled', explanation = ? WHERE id = ?");
                $stmt->execute([$cancel_reason, $leave_id]);

                $pdo->commit();
                $message = "Leave request #$leave_id has been cancelled and leave credits have been restored.";

            } else {
                // Original logic for approve/decline actions
                // Get the complete leave request data
                $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = :id");
                $stmt->execute(['id' => $leave_id]);
                $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$leave_request) {
                    throw new Exception("Leave request not found.");
                }

            if ($action === 'approve') {
                $employee_id = $leave_request['employee_id'];
                $leave_type = $leave_request['leave_type'];

                // Update status
                $leave_request['status'] = 'approved';

                // Map leave types to their corresponding credit types and determine deduction amount
                $deduction_amount = 1; // Default full day
                $credit_type = $leave_type; // Default to same type
                
                // Calculate the number of days requested
                $start_date = new DateTime($leave_request['start_date']);
                $end_date = new DateTime($leave_request['end_date']);
                $requested_days = $start_date->diff($end_date)->days + 1;
                
                // Map halfday and special types to their base credit types
                $leave_type_lower = strtolower($leave_type);
                switch ($leave_type_lower) {
                    case 'halfday':
                        $credit_type = 'vacation';
                        $deduction_amount = 0.5; // Always exactly 0.5 for half day
                        break;
                    case 'halfday_sick':
                        $credit_type = 'sick';
                        $deduction_amount = 0.5; // Always exactly 0.5 for half day
                        break;
                    case 'lwop':
                        // LWOP doesn't deduct from any credits
                        $deduction_amount = 0;
                        $credit_type = null;
                        break;
                    case 'bereavement':
                    case 'paternity':
                    case 'maternity':
                        // These have separate entitlements, deduct from their own credit type
                        $credit_type = $leave_type_lower;
                        $deduction_amount = $requested_days;
                        break;
                    default:
                        // sick, vacation, solo_parent
                        $credit_type = $leave_type_lower;
                        $deduction_amount = $requested_days;
                        break;
                }

                // Decrease the corresponding leave credit if applicable
                if ($deduction_amount > 0 && $credit_type) {
                    $stmt = $pdo->prepare("UPDATE leave_credits SET balance = balance - ? WHERE employee_id = ? AND leave_type = ? AND year = ?");
                    $stmt->execute([
                        $deduction_amount,
                        $employee_id,
                        $credit_type,
                        date('Y')
                    ]);
                }

            } elseif ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $leave_request['explanation'] = $explanation;
                $leave_request['status'] = 'rejected';
            }

            // Insert into post_leave_requests table
            $stmt = $pdo->prepare("
                INSERT INTO post_leave_requests 
                (employee_id, leave_type, start_date, end_date, reason, status, 
                 attachment_lr, explanation, created_at, notified) 
                VALUES 
                (:employee_id, :leave_type, :start_date, :end_date, :reason, :status, 
                 :attachment_lr, :explanation, :created_at, :notified)
            ");
            
            $stmt->execute([
                'employee_id' => $leave_request['employee_id'],
                'leave_type' => $leave_request['leave_type'],
                'start_date' => $leave_request['start_date'],
                'end_date' => $leave_request['end_date'],
                'reason' => $leave_request['reason'],
                'status' => $leave_request['status'],
                'attachment_lr' => $leave_request['attachment_lr'],
                'explanation' => $leave_request['explanation'] ?? null,
                'created_at' => $leave_request['created_at'],
                'notified' => $leave_request['notified'] ?? 0
            ]);

            // Delete from leave_requests table
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = :id");
            $stmt->execute(['id' => $leave_id]);

            $pdo->commit();

            $action_text = $action === 'approve' ? 'approved' : 'declined';
            $message = "Leave request #$leave_id has been $action_text.";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Action Result</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto mt-20 bg-white p-6 rounded shadow text-center">
        <h1 class="text-xl font-bold mb-4 text-gray-800">Action Result</h1>
        <p class="text-gray-700 mb-6"><?= htmlspecialchars($message) ?></p>
        <a href="leave_request_list.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
            ← Back to Leave Requests
        </a>
    </div>
</body>
</html>
