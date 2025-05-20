<?php
include('../config/db.php');

$request_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($request_id && in_array($action, ['approve', 'reject'])) {
    // ✅ Fetch the correct overtime request
    $stmt = $pdo->prepare("SELECT * FROM overtime_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo "Error: Request not found.";
        exit;
    }

    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE overtime_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);

    // ✅ If approved, insert one-day OT extension
    if ($status === 'approved') {
        $employee_id = $request['employee_id'];
        $ot_date = $request['ot_date'];
        $extended_time_out = $request['expected_time_out'];
        $reason = $request['reason'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO approved_overtime_schedule (employee_id, ot_date, extended_time_out, reason)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$employee_id, $ot_date, $extended_time_out, $reason]);
    }

    header("Location: ../module/overtime_request_approval.php");
    exit;
} else {
    echo "Error: Missing or invalid 'id' or 'action' parameters.";
}
