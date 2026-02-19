<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../config/db.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    // Validate action
    if (!in_array($action, ['approve', 'decline'])) {
        $message = "Invalid action specified.";
    } else {
        try {
            $pdo->beginTransaction();

            // Get the overtime request data from post_ot_requests
            $stmt = $pdo->prepare("SELECT * FROM post_ot_requests WHERE id = :id AND status = 'Pending'");
            $stmt->execute(['id' => $request_id]);
            $overtime_request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$overtime_request) {
                throw new Exception("Overtime request not found or already processed.");
            }

            if ($action === 'approve') {
                $new_status = 'Approved';
                $explanation = null;
            } elseif ($action === 'decline') {
                $explanation = trim($_POST['explanation'] ?? '');
                if (empty($explanation)) {
                    throw new Exception("Explanation is required for declining.");
                }
                $new_status = 'Rejected';
            }

            // Update the status in post_ot_requests table
            $stmt = $pdo->prepare("
                UPDATE post_ot_requests 
                SET status = :status, 
                    approved_at = NOW(), 
                    approved_by = :approved_by,
                    reason = CASE 
                        WHEN :explanation IS NOT NULL THEN CONCAT(reason, ' [Admin Note: ', :explanation, ']')
                        ELSE reason 
                    END
                WHERE id = :id
            ");
            
            $stmt->execute([
                'status' => $new_status,
                'approved_by' => $_SESSION['employee']['id'] ?? 1, // Admin user ID
                'explanation' => $explanation,
                'id' => $request_id
            ]);

            // Get the updated request so we can archive it
            $selectStmt = $pdo->prepare("SELECT * FROM post_ot_requests WHERE id = ?");
            $selectStmt->execute([$request_id]);
            $updated_request = $selectStmt->fetch(PDO::FETCH_ASSOC);

            if ($updated_request) {
                // Archive to post2_overtime_requests (upsert — update if already exists with stale status)
                $arch = $pdo->prepare("
                    INSERT INTO post2_overtime_requests
                    (id, employee_id, time_log_id, time_in, time_out, ot_duration, ot_type, attachment, reason, status, created_at, approved_at, approved_by, notified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status), approved_at = VALUES(approved_at), approved_by = VALUES(approved_by), reason = VALUES(reason)
                ");
                $arch->execute([
                    $updated_request['id'],
                    $updated_request['employee_id'],
                    $updated_request['time_log_id'],
                    $updated_request['time_in'],
                    $updated_request['time_out'],
                    $updated_request['ot_duration'],
                    $updated_request['ot_type'],
                    $updated_request['attachment'],
                    $updated_request['reason'],
                    $updated_request['status'],
                    $updated_request['created_at'],
                    $updated_request['approved_at'],
                    $updated_request['approved_by'],
                    $updated_request['notified'] ?? 0
                ]);

                // Remove from active table
                $pdo->prepare("DELETE FROM post_ot_requests WHERE id = ?")->execute([$request_id]);
            }

            $pdo->commit();

            $action_text = $action === 'approve' ? 'approved' : 'declined';
            header("Location: ot_request.php?message=OT%20Request%20%23$request_id%20$action_text");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}

// Optional: Display error if redirected without success
if (!empty($message)) {
    echo "<p style='color: red; font-weight: bold;'>$message</p>";
    echo "<p><a href='ot_request.php'>← Back to OT Requests</a></p>";
}
?>
            $pdo->rollBack();
            $message = "Error processing request: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid form submission.";
}

// Optional: Display error if redirected without success
if (!empty($message)) {
    echo "<p style='color: red; font-weight: bold;'>$message</p>";
    echo "<p><a href='ot_request.php'>← Back to OT Requests</a></p>";
}
?>
